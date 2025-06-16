<?php

  include_once 'database.php';

  $event_name = array();
  $scores = array();
  $prev_results_file = '/etc/timing/TriSeriesScores.php';
  if (file_exists($prev_results_file))
    include_once $prev_results_file;

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

  $this_event_name = "";
  while($row = $events->fetchArray()) {
    $ev=$row['event']; $nm=$row['name'];
    if ($evt == 0) $evt=$ev;
    if ($ev == $evt)
      $this_event_name = $nm;
  }

  $class_qry = $db->query('SELECT  class, count(class) AS class_count FROM entrant_info WHERE event = ' . $db->escapeString($evt) . ' group by class' );
  while ($row = $class_qry->fetchArray()) {
    $points = $row["class_count"];
    if ($points > 7)  $points=7;
    elseif ($points < 3) $points=$points+1;
    $top_points[$row["class"]] = $points;
    # echo $row["class"];
    # echo ":$points    ";
  }

  // Set PHP headers for CSV output.
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=' . $this_event_name . '_Results.csv');

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
    if (! isset($place_sp[$row["special"]])) $place_sp[$row["special"]] = 1;
    if (isset($row["run"]))
        $place_special[$row["car_num"]][$row["special"]] = $place_sp[$row["special"]]++;
    else
	$place_special[$row["car_num"]][$row["special"]] = "";
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

   echo $quote . $this_event_name . "${quote}\n\n";
   echo "${quote}Num${quote},${quote}Driver${quote},${quote}Club${quote},${quote}Car${quote},${quote}Info${quote},${quote}Special${quote}";
   echo ",${quote}Class Pos${quote},${quote}Outright${quote},${quote}Points${quote},${quote}Best${quote},${quote}NR${quote}";

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
	 $points = $top_points[$row["class"]];
       }
       echo "\n";
       echo htmlspecialchars($row["car_num"]) . ",";
       echo $quote . htmlspecialchars($row["car_name"]) . $quote . ",";
       echo $quote . htmlspecialchars($row["car_entrant"]) . $quote . ",";
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
       if ($points > 0) {
         if (isset( $club_tot[$row["car_entrant"]]))
           $club_tot[$row["car_entrant"]] = $club_tot[$row["car_entrant"]] + $points;
         else
           $club_tot[$row["car_entrant"]] = $points;
         echo "$points,"; 
	 $points--;
       }
       else
	 echo ",";
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
   echo "\n\n";

   echo ",,,${quote}Club Name${quote},${quote}Points${quote}\n";
   arsort($club_tot, SORT_NUMERIC);
   foreach ($club_tot as $club => $tot) {
     echo ",,,${quote}$club${quote},$tot\n";
   }

   echo "\n\n";
   foreach ($club_tot as $club => $tot) {
     $all_points[$club] = $tot;
   }
   foreach ($event_name as $rnd => $rnd_name) {
     if (array_key_exists($rnd,$scores))
       foreach ($scores[$rnd] as $club => $points) {
         if (isset($all_points[$club]))
           $all_points[$club] += $points;
         else
           $all_points[$club] = $points;
       }
   }
   echo ",,,${quote}Club Name${quote},";
   foreach ($event_name as $rnd => $rnd_name)
     echo "${quote}$rnd_name${quote},";
   echo "${quote}WSCC${quote},${quote}Total${quote}\n";
   arsort($all_points, SORT_NUMERIC);
   foreach ($all_points as $club => $tot) {
     echo ",,,${quote}$club${quote},";
     foreach ($event_name as $rnd => $rnd_name)
       if (isset($scores[$rnd]) && isset($scores[$rnd][$club]))
         echo $scores[$rnd][$club] . ",";
       else
         echo ",";
     $round_total=""; $series_total="";
     if (isset($club_tot[$club]))  $round_total=$club_tot[$club];
     if (isset($all_points[$club]))  $series_total=$all_points[$club];
     echo "$round_total,$series_total\n";
   }

?>
