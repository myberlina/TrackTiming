<?php
  // Results_Info:  Hillclimb style with split, and club for TriSeries
  include_once 'database.php';

  $event_name = array();
  $scores = array();
  $prev_results_file = '/etc/timing/TriSeriesScores.php';
  if (file_exists($prev_results_file))
    include_once $prev_results_file;

  if(isset($argc) && ($argc>1))
    parse_str(implode('&',array_slice($argv, 1)), $_GET);

  if (isset($_GET['runners_only'])) {
    $runners_only = true;
    $runners_only_checked = "checked";
    $runners_url = '&runners_only';
    $opp_runners_url = '';
  }
  else {
    $runners_only = false;
    $runners_only_checked = "";
    $runners_url = '';
    $opp_runners_url = '&runners_only';
  }

  if (isset($config) && isset($config['results']) && isset($config['results']['split_line']) && $config['results']['split_line']) {
    $split_br="<br>";
    $split_align="right";
  }
  else {
    $split_br="&nbsp;";
    $split_align="left";
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

  $event_select = "<option value=\"\">Please Select Date</option>";
  while($row = $events->fetchArray()) {
    $ev=$row['event']; $nm=$row['name'];
    if ($evt == 0) $evt=$ev;
    if ($ev == $evt)
      $event_select = "$event_select <option value=\"$ev\" selected>$nm</option>";
    else
      $event_select = "$event_select <option value=\"$ev\">$nm</option>";
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

  $place=1;
  $best_qry = $db->query('SELECT * FROM rt_order WHERE event = ' . $db->escapeString($evt) );
  while($row = $best_qry->fetchArray()) {
    if ($place == 1)
      $purple_rt = $row["best_rt"] / 1000;
    $best_rt[$row["car_num"]] = $row["best_rt"] / 1000;
    $place_rt[$row["car_num"]] = $place++;
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
      SELECT results.event, results.run, results.car_num, car_name, car_info, entrant_info.class, car_car, car_entrant, class_info, record, ft_ms/1000.0 as ft, rt_ms/1000.0 as rt
      FROM hc_results as results, hc_order
      LEFT JOIN entrant_info ON results.car_num = entrant_info.car_num and results.event = entrant_info.event
      LEFT JOIN class_info ON entrant_info.class = class_info.class
      WHERE results.event = :event AND results.car_num > 0
        AND results.event = hc_order.event AND results.car_num = hc_order.car_num
      ORDER BY results.event, entrant_info.class, hc_order.best_ft, results.car_num, results.run');
    $res_qry->bindValue(':event', $evt, SQLITE3_INTEGER);
  }
  else {
    $res_qry = $db->prepare('
      SELECT entrant_info.event, results.run, entrant_info.car_num, car_name, car_info, entrant_info.class, car_car, car_entrant, class_info, record, ft_ms/1000.0 as ft, rt_ms/1000.0 as rt
      FROM entrant_info
      LEFT JOIN class_info ON entrant_info.class = class_info.class
      LEFT JOIN hc_results AS results ON results.car_num = entrant_info.car_num AND results.event = entrant_info.event
      LEFT JOIN hc_order ON results.event = hc_order.event AND results.car_num = hc_order.car_num
      WHERE entrant_info.event = :event AND entrant_info.car_num > 0
      ORDER BY entrant_info.event, entrant_info.class, hc_order.best_ft NULLS LAST, results.car_num, results.run');
    $res_qry->bindValue(':event', $evt, SQLITE3_INTEGER);
  }

  $results = $res_qry->execute();

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Results</title>
    <link rel="stylesheet" href="/HSV_Timing/style.css"/>
<?php
  $icon_file=dirname(__FILE__) . "/icons.inc";
  if (file_exists($icon_file))
    readfile($icon_file);
?>
    <meta http-equiv="refresh" content="20"/>
    <!-- Timing_Event_Num='<?php echo $evt;?>' -->
  </head>
<body>
<script type="text/javascript">function showTiming(str){document.location = '?evt='+str;}</script>
<div align="center" style="padding-bottom:5px;">
 Times for <select name="EventList" style="width: 240px" onchange="showTiming(this.value + '<?php echo $runners_url;?>')">
   <?php echo $event_select;?>
 </select>
<div style="float:right">
  <?php echo "<input type=\"checkbox\" id=\"runners_only\" name=\"runners_only\" $runners_only_checked onchange=\"showTiming('$evt$opp_runners_url')\">";?>
  <label for="runners_only"> Show Runners Only </label>
</div>
</div>
<br/>
 <table align="center" border="0" cellpadding="0">
 <tr><td>
  <table border="1" cellpadding="1">
   <tbody>
   <tr class="listheader">
      <td>Num</td>
      <td>Driver</td>
      <td>Club</td>
      <td>Car</td>
      <td>Place</td>
      <td>Points</td>
      <td align="right">Run 1</td>
   <?php
   $i=1;
   while(++$i <= $max_runs) {
       echo "<td align=\"right\">Run $i</td>";
   }
   $i=0;
   $prev_car = "";
   $tab_run = 1;
   $prev_class = "lksfjlasjfiwfl";
   $class_place=1;
   $span_cols = 6 + $max_runs;

   while($row = $results->fetchArray()) {
     if ($row["car_num"] != $prev_car ) {
       if($i%2==0)
         $classname="evenRow";
       else
         $classname="oddRow";
       $i++;
       if ($row["class"] != $prev_class) {
         if ($row["class"] != "" ) {
           echo "</tr>";
           echo "<tr class=\"newClass\">";
           echo "<td colspan=$span_cols>";
           echo "<div style=\"float:left\">" . htmlspecialchars($row["class"]) . "</div>\n";
	   echo "<div class=\"classInfo\" style=\"float:right\">";
           if (isset($row["record"]) && ('' != $row["record"]) && (intval($row["record"]) > 0))
             if (isset($best_ft[$row["car_num"]]) && ($best_ft[$row["car_num"]] < $row["record"]))
               echo " &nbsp; Old record: " . htmlspecialchars($row["record"]) . " &nbsp; ";
             else
	       echo " &nbsp; Record: " . htmlspecialchars($row["record"]) . " &nbsp; ";
	   if (isset($row["class_info"]))
	     echo htmlspecialchars($row["class_info"]);
	   echo "</div></td>\n";
         }
         $prev_class=$row["class"];
         $class_place=1;
         $points = $top_points[$row["class"]];
       }
       echo "</tr>";
       echo "<tr class=\"$classname\">";
       echo "<td style=\"text-align: right;\">" . htmlspecialchars($row["car_num"]) . "&nbsp;</td>\n";
       echo "<td><div style=\"float:left\">";
       $achievement="";
       if (isset($place_ft[$row["car_num"]]) && ($place_ft[$row["car_num"]] == 1)) {
	 $achievement=" &nbsp; &nbsp; FTD";
       }
       #if (isset($place_rt[$row["car_num"]]) && ($place_rt[$row["car_num"]] <= 5))
       #    $achievement=$achievement. "&nbsp; SP:".$place_rt[$row["car_num"]];
       if (isset($place_rt[$row["car_num"]]) && ($place_rt[$row["car_num"]] == 1))
           $achievement=$achievement. "&nbsp; SP";
       if (isset($place_special[$row["car_num"]])) {
         foreach ($place_special[$row["car_num"]] as $type => $place) {
           $achievement=$achievement. " &nbsp; &nbsp; <strong>$type:&nbsp;$place</strong>";
         }
       }
       if ($achievement != "")
           $achievement="</div><div style=\"float:right\"><strong style=\"color: purple; text-align: right;\">" . $achievement . "</strong>";
       echo htmlspecialchars($row["car_name"]) . $achievement . "</div>";

       #echo "</td><td>" . htmlspecialchars($row["car_num"]) . "<br/>" . htmlspecialchars($row["car_info"]) . "</td>\n";
       echo "</td><td>" . htmlspecialchars($row["car_entrant"]) . "</td>\n";
       echo "</td><td>" . htmlspecialchars($row["car_car"]) . "</td>\n";
       if (!isset($row["run"])) continue;
       if (isset($place_ft[$row["car_num"]])) {
         echo "<td><div style=\"float:left\">" . $class_place . "</div><div style=\"float:right\"><font size='2'/>(" . $place_ft[$row["car_num"]] . ")</td>\n";
         $class_place++;
         if ($points > 0) {
	   if (isset( $club_tot[$row["car_entrant"]]))
	     $club_tot[$row["car_entrant"]] = $club_tot[$row["car_entrant"]] + $points;
	   else
	     $club_tot[$row["car_entrant"]] = $points;
           if (isset($row["car_entrant"]) && ($row["car_entrant"] != ""))
             echo "<td style=\"text-align: center; font-weight: bold\">$points</td>\n";
           else
             echo "<td></td>\n";
           $points--;
         }
         else {
           echo "<td></td>\n";
         }
       }
       else {
         echo "<td></td>\n";
         echo "<td></td>\n";
       }
       $prev_car = $row["car_num"];
       $tab_run = 1;
     }
     elseif ($row["run"] == $prev_run ) continue;
     if (!isset($row["run"])) continue;
     while ($row["run"] > $tab_run++)
         echo "<td></td>";
     echo "<td>";
     if (isset($row["rt"])) {
       echo "<font size='2' style=\"float:$split_align\">";
       if ($best_rt[$row["car_num"]] == $row["rt"])
         if ($purple_rt == $row["rt"])
             printf("<strong style=\"color: purple\">$split_fmt</strong> ", $row["rt"]);
         else
             printf("<strong>$split_fmt</strong> ", $row["rt"]);
       else
         printf("$split_fmt ", $row["rt"]);
       echo "</font>$split_br";
     }
     $rec="float:right;";
     if (isset($row["record"]) && (intval($row["record"]) > 0) && ($row["ft"] < $row["record"]))
       $rec='text-decoration: underline;' . $rec;
       #$rec='color: darkblue;';
       #$rec='background-color: lightyellow;';
       #$rec='text-shadow: 0 0 8px gold;';
     if ($best_ft[$row["car_num"]] == $row["ft"]) {
         if ($purple_ft == $row["ft"])
             $rec="color:purple;" . $rec;
         $rec="font-weight:bold;" . $rec;
     }
     if ("" != $rec) $rec=' style="' . $rec . '"';
     printf("<font size='3'$rec>$final_fmt</font>", $row["ft"]);
     echo "</td>";
     $prev_run = $row["run"];
   }
   $res_qry->close();
   ?>
   </tr>
   </tbody>
  </table>
 </td></tr>
 <tr><td>
  <table border="1" cellpadding="1">
   <tbody>
   <tr class="listheader">
      <td>Club Name</td>
      <td align="right">Points</td>
   </tr>
   <?php
      arsort($club_tot, SORT_NUMERIC);
      foreach ($club_tot as $club => $tot) {
        if ($club != "")
          echo "<tr><td>$club</td><td align=\"right\">$tot</td></tr>";
      }
   ?>
   </tbody>
  </table>
 </td></tr>
 <tr><td>
  <table border="1" cellpadding="1">
   <?php
     foreach ($club_tot as $club => $tot) {
       if ($club != "")
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
   ?>
   <tbody>
   <tr class="listheader">
      <td>Club Name</td>
      <?php
        foreach ($event_name as $rnd => $rnd_name)
          echo "<td align=\"right\">$rnd_name</td>";
      ?>
      <td>WSCC</td>
      <td>Total</td>
   </tr>
   <?php
      arsort($all_points, SORT_NUMERIC);
      foreach ($all_points as $club => $tot) {
        echo "<tr><td>$club</td>";
        foreach ($event_name as $rnd => $rnd_name)
          if (isset($scores[$rnd]) && isset($scores[$rnd][$club]))
            echo "<td align=\"right\">" . $scores[$rnd][$club] . "</td>";
	  else
            echo "<td align=\"right\"></td>";
	$round_total=""; $series_total="";
        if (isset($club_tot[$club]))  $round_total=$club_tot[$club];
        if (isset($all_points[$club]))  $series_total=$all_points[$club];
        echo "<td align=\"right\">$round_total</td><td align=\"right\">$series_total</td></tr>";
      }
   ?>
   </tbody>
  </table>
 </td></tr>
 </table>
 </body>
</html>
