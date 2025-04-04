<?php

  include_once 'database.php';

  $events = $db->query('SELECT DISTINCT event, name FROM results, event_info WHERE event = num ORDER BY event DESC');

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
  $best_qry = $db->query('SELECT MAX(run) AS max_runs FROM results WHERE event = ' . $db->escapeString($evt) );
  if ($row = $best_qry->fetchArray()) {
    $max_runs = $row["max_runs"];
  }

  $place=1;
  $best_qry = $db->query('SELECT * FROM et_order WHERE event = ' . $db->escapeString($evt) );
  while($row = $best_qry->fetchArray()) {
    if ($place == 1)
      $purple_et = $row["best_et"] / 1000;
    $best_et[$row["car_num"]] = $row["best_et"] / 1000;
    $place_et[$row["car_num"]] = $place++;
  }

  if (false)
      $best_qry = $db->query('SELECT * FROM et_order
                         LEFT JOIN entrant_info ON et_order.car_num = entrant_info.car_num and et_order.event = entrant_info.event
                         WHERE et_order.event = ' . $db->escapeString($evt) .
                        ' AND special != ""
                          ORDER BY et_order.red, et_order.best_et, et_order.run, et_order.car_num');
  else
      $best_qry = $db->query('SELECT entrant_info.car_num, special, et_order.run FROM entrant_info
                         LEFT JOIN et_order ON et_order.car_num = entrant_info.car_num and et_order.event = entrant_info.event
                         WHERE entrant_info.event = ' . $db->escapeString($evt) .
                        ' AND special != ""
                          ORDER BY et_order.red NULLS LAST, et_order.best_et, et_order.run, et_order.car_num');
  while($row = $best_qry->fetchArray()) {
    if (! isset($place_sp[$row["special"]])) $place_sp[$row["special"]] = 1;
    if (isset($row["run"]))
        $place_special[$row["car_num"]][$row["special"]] = $place_sp[$row["special"]]++;
    else
	$place_special[$row["car_num"]][$row["special"]] = "";
  }

  if (false) {
    $res_qry = $db->prepare('
      SELECT results.event, results.run, results.car_num, car_name, car_entrant, car_info, class, car_car, et_ms/1000.0 as et
      FROM results, et_order
      LEFT JOIN entrant_info ON results.car_num = entrant_info.car_num and results.event = entrant_info.event
      WHERE results.event = :event AND results.car_num > 0
        AND results.event = et_order.event AND results.car_num = et_order.car_num
      ORDER BY results.event, class, et_order.red, et_order.best_et, results.car_num, results.run');
  }
  else {
    $res_qry = $db->prepare('
      SELECT entrant_info.event, results.run, entrant_info.car_num, car_name, car_entrant, car_info, entrant_info.class, car_car, class_info, record, et_ms/1000.0 as et
      FROM entrant_info
      LEFT JOIN class_info ON entrant_info.class = class_info.class
      LEFT JOIN results ON results.car_num = entrant_info.car_num AND results.event = entrant_info.event
      LEFT JOIN et_order ON results.event = et_order.event AND results.car_num = et_order.car_num
      WHERE entrant_info.event = :event AND entrant_info.car_num > 0
      ORDER BY entrant_info.event, entrant_info.class, et_order.red ASC NULLS LAST, et_order.best_et, results.car_num, results.run');

  }
  $res_qry->bindValue(':event', $evt, SQLITE3_INTEGER);

  $results = $res_qry->execute();

   echo "\"" . $event_name . "\"\n\n";
   echo "\"Num\", \"Entrant\", \"Driver\", \"Car\", \"Info\", \"Special\"";
   echo ", \"Class Pos\", \"Outright\", \"Best\"";
   $i=0;
   while(++$i <= $max_runs) {
       echo ", \"Run $i\"";
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
         echo "\n\n\"" . htmlspecialchars($row["class"]) . "\"";
	 if (($row["class_info"] != "") || (isset($row["record"]) && '' != $row["record"]) ) {
	   echo ",,,,,,\"";
	   if (isset($row["record"]) && '' != $row["record"])
	     echo "Record: " . htmlspecialchars($row["record"]) . " ";
	   echo htmlspecialchars($row["class_info"]) . "\"";
	 }
         $prev_class=$row["class"];
         $class_place=1;
       }
       echo "\n";
       echo htmlspecialchars($row["car_num"]) . ", ";
       echo "\"" . htmlspecialchars($row["car_entrant"]) . "\", ";
       echo "\"" . htmlspecialchars($row["car_name"]) . "\", ";
       echo "\"" . htmlspecialchars($row["car_car"]) . "\", ";
       echo "\"" . htmlspecialchars($row["car_info"]) . "\", ";
       $achievement="";
       if (isset($place_et[$row["car_num"]]) && ($place_et[$row["car_num"]] == 1)) {
	 $achievement="FTD  ";
       }
       if (isset($place_special[$row["car_num"]])) {
         foreach ($place_special[$row["car_num"]] as $type => $place) {
           $achievement=$achievement. "  $type:$place";
         }
       }
       echo "\"" . $achievement . "\", ";
       if (!isset($row["run"])) continue;
       echo $class_place . ", ";
       $class_place++;
       echo $place_et[$row["car_num"]] . ", ";
       printf("%3.2f", $best_et[$row["car_num"]]);

       $prev_car = $row["car_num"];
       $tab_run = 1;
       $i++;
     }
     elseif ($row["run"] == $prev_run ) continue;
     while ($row["run"] > $tab_run++)
         echo ", ";
     printf(", %3.2f", $row["et"]);
     $prev_run = $row["run"];
   }
   $res_qry->close();
   echo "\n";

?>
