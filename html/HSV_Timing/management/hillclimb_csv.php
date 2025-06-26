<?php

  include_once 'database.php';

  if(isset($argc) && ($argc>1))
    parse_str(implode('&',array_slice($argv, 1)), $_GET);

  if (isset($_GET['runners_only']))
    $runners_only = true;
  else
    $runners_only = false;

  if (isset($_GET['split']))
    $with_split = true;
  else
    $with_split = false;

  if (isset($_GET['quote']))
    $quote = '"';
  else
    $quote = '';

  if (!(false === $config) && isset($config['results']) && isset($config['results']['csv_quotes']) && $config['results']['csv_quotes']) {
    $quote = '"';
  }

  $events = $db->query('SELECT DISTINCT event, name FROM hc_results, event_info WHERE event = num ORDER BY event DESC');

  if (isset($_GET['evt'])) {
    $evt = $_GET['evt'];
  }
  else {
    $current = $db->query('select current_event from current_event;');
    if ($row = $current->fetchArray()) {
      $evt = $row["current_event"];
    }
  }

  $event_name = "";
  while($row = $events->fetchArray()) {
    $ev=$row['event']; $nm=$row['name'];
    if ($evt == 0) $evt=$ev;
    if ($ev == $evt)
      $event_name = $nm;
  }

  // Set PHP headers for CSV output.
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=' . $event_name . '_Results.csv');

  $max_runs=5;
  $best_qry = $db->query('SELECT MAX(run) AS max_runs FROM hc_results WHERE event = ' . $db->escapeString($evt) );
  if ($row = $best_qry->fetchArray()) {
    $max_runs = $row["max_runs"];
  }

  $place=1;
  $best_qry = $db->query('SELECT * FROM hc_order WHERE event = ' . $db->escapeString($evt) );
  while($row = $best_qry->fetchArray()) {
    if ($place == 1)
      $purple_ft = $row["best_ft"] / 1000;
    $best_ft[$row["car_num"]] = $row["best_ft"] / 1000;
    $place_ft[$row["car_num"]] = $place++;
  }

  $split_fmt="%4.3f";
  $split_fmt="%3.2f";
  $final_fmt="%4.3f";
  $final_fmt="%3.2f";

  if ($runners_only)
      $best_qry = $db->query('SELECT * FROM hc_order
                         LEFT JOIN entrant_info ON hc_order.car_num = entrant_info.car_num and hc_order.event = entrant_info.event
                         WHERE hc_order.event = ' . $db->escapeString($evt) .
                        ' AND special != ""
                          ORDER BY hc_order.best_ft, hc_order.run, hc_order.car_num');
  else
      $best_qry = $db->query('SELECT entrant_info.car_num, special, hc_order.run FROM entrant_info
                         LEFT JOIN hc_order ON hc_order.car_num = entrant_info.car_num and hc_order.event = entrant_info.event
                         WHERE entrant_info.event = ' . $db->escapeString($evt) .
                        ' AND special != ""
                          ORDER BY hc_order.best_ft NULLS LAST, hc_order.run, hc_order.car_num');
  while($row = $best_qry->fetchArray()) {
    $specials = explode(",", $row["special"], 10);
    foreach ($specials as $special)  {
      if (! isset($place_sp[$special])) $place_sp[$special] = 1;
      if (isset($row["run"]))
          $place_special[$row["car_num"]][$special] = $place_sp[$special]++;
      else
          $place_special[$row["car_num"]][$special] = "";
    }
  }

  if ($runners_only) {
    $res_qry = $db->prepare('
      SELECT results.event, results.run, results.car_num, car_name, car_entrant, car_info, class, car_car, ft_ms/1000.0 as ft, rt_ms/1000.0 as rt
      FROM hc_results as results, hc_order
      LEFT JOIN entrant_info ON results.car_num = entrant_info.car_num and results.event = entrant_info.event
      WHERE results.event = :event AND results.car_num > 0
        AND results.event = hc_order.event AND results.car_num = hc_order.car_num
      ORDER BY results.event, class, hc_order.best_ft, results.car_num, results.run');
  }
  else {
    $res_qry = $db->prepare('
      SELECT entrant_info.event, results.run, entrant_info.car_num, car_name, car_entrant, car_info, entrant_info.class, car_car, class_info, record, ft_ms/1000.0 as ft, rt_ms/1000.0 as rt
      FROM entrant_info
      LEFT JOIN class_info ON entrant_info.class = class_info.class
      LEFT JOIN hc_results as results ON results.car_num = entrant_info.car_num AND results.event = entrant_info.event
      LEFT JOIN hc_order ON results.event = hc_order.event AND results.car_num = hc_order.car_num
      WHERE entrant_info.event = :event AND entrant_info.car_num > 0
      ORDER BY entrant_info.event, entrant_info.class, hc_order.best_ft NULLS LAST, results.car_num, results.run');

  }
  $res_qry->bindValue(':event', $evt, SQLITE3_INTEGER);

  $results = $res_qry->execute();

   echo $quote . $event_name . "${quote}\n\n";
   echo "${quote}Num${quote},${quote}Entrant${quote},${quote}Driver${quote},${quote}Car${quote},${quote}Info${quote},${quote}Special${quote}";
   echo ",${quote}Class Pos${quote},${quote}Outright${quote},${quote}Best${quote},${quote}NR${quote}";
   $i=0;
   while(++$i <= $max_runs) {
     if ($with_split)
       echo ",${quote}Split${quote},${quote}Run $i${quote}";
     else
       echo ",${quote}Run $i${quote}";
   }
   $i=0;
   $prev_car = "";
   $tab_run = 1;
   $prev_class = "lksfjlasjfiwfl";
   $class_place=1;
   $span_cols = 4 + $max_runs;

   while($row = $results->fetchArray()) {
     if ($row["car_num"] != $prev_car ) {
       if ($row["class"] != $prev_class ) {
         echo "\n\n" . $quote . htmlspecialchars($row["class"]) . $quote;
	 if (($row["class_info"] != "") || (isset($row["record"]) && '' != $row["record"]) ) {
	   echo ",,,,,," . $quote;
	   if (isset($row["record"]) && '' != $row["record"])
	     echo "Record: " . htmlspecialchars($row["record"]) . " ";
	   echo htmlspecialchars($row["class_info"]) . $quote;
	 }
         $prev_class=$row["class"];
         $class_place=1;
       }
       echo "\n";
       echo htmlspecialchars($row["car_num"]) . ",";
       echo $quote . htmlspecialchars($row["car_entrant"]) . $quote . ",";
       echo $quote . htmlspecialchars($row["car_name"]) . $quote . ",";
       echo $quote . htmlspecialchars($row["car_car"]) . $quote . ",";
       echo $quote . htmlspecialchars($row["car_info"]) . $quote . ",";
       $achievement="";
       if (isset($place_ft[$row["car_num"]]) && ($place_ft[$row["car_num"]] == 1)) {
	 $achievement="FTD  ";
       }
       if (isset($place_special[$row["car_num"]])) {
         foreach ($place_special[$row["car_num"]] as $type => $place) {
           $achievement=$achievement. "  $type:$place";
         }
       }
       echo $quote . $achievement . $quote . ",";
       if (!isset($row["run"])) continue;
       echo $class_place . ",";
       echo $place_ft[$row["car_num"]] . ",";
       printf("$final_fmt",$best_ft[$row["car_num"]]);
       if (($class_place==1) && isset($row["record"]) && (0 < intval($row["record"])) && ($best_ft[$row["car_num"]] < $row["record"]))
	 echo ",*";
       else
	 echo ",";
       $class_place++;

       $prev_car = $row["car_num"];
       $tab_run = 1;
       $i++;
     }
     elseif ($row["run"] == $prev_run ) continue;
     while ($row["run"] > $tab_run++)
       if ($with_split)
         echo ",,";
       else
         echo ",";
     if ($with_split) {
       if (isset($row["rt"]))
         printf(",$split_fmt",$row["rt"]);
       else
         echo ",";
     }
     printf(",$final_fmt",$row["ft"]);
     $prev_run = $row["run"];
   }
   $res_qry->close();
   echo "\n";

?>
