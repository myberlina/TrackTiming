<?php
  // Results_Info:  Hillclimb style with split, no reaction time, Green light => Button
  include_once 'database.php';

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
      SELECT results.event, results.run, results.car_num, car_name, car_info, entrant_info.class, car_car, class_info, record, ft_ms/1000.0 as ft, rt_ms/1000.0 as rt
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
      SELECT entrant_info.event, results.run, entrant_info.car_num, car_name, car_info, entrant_info.class, car_car, class_info, record, ft_ms/1000.0 as ft, rt_ms/1000.0 as rt
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
  <table align="center" border="1" cellpadding="1">
   <tr class="listheader">
      <td>Num</td>
      <td>Driver</td>
      <td>Car</td>
      <td>Place</td>
      <td>Run 1</td>
   <?php
   $i=1;
   while(++$i <= $max_runs) {
       echo "<td>Run $i</td>";
   }
   $i=0;
   $prev_car = "";
   $tab_run = 1;
   $prev_class = "lksfjlasjfiwfl";
   $class_place=1;
   $span_cols = 4 + $max_runs;

   while($row = $results->fetchArray()) {
     if($i%2==0)
       $classname="evenRow";
     else
       $classname="oddRow";
     if ($row["car_num"] != $prev_car ) {
       if ($row["class"] != $prev_class) {
         if ($row["class"] != "" ) {
           echo "</tr>";
           echo "<tr class=\"newClass\">";
           echo "<td colspan=$span_cols>";
           echo "<div style=\"float:left\">" . htmlspecialchars($row["class"]) . "</div>\n";
	   echo "<div class=\"classInfo\" style=\"float:right\">";
	   if (isset($row["record"]) && '' != $row["record"])
	     echo " &nbsp; Record: " . htmlspecialchars($row["record"]) . " &nbsp; ";
	   if (isset($row["class_info"]))
	     echo htmlspecialchars($row["class_info"]);
	   echo "</div></td>\n";
         }
         $prev_class=$row["class"];
         $class_place=1;
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
       echo "</td><td>" . htmlspecialchars($row["car_car"]) . "</td>\n";
       if (!isset($row["run"])) continue;
       if (isset($place_ft[$row["car_num"]])) {
         echo "<td><div style=\"float:left\">" . $class_place . "</div><div style=\"float:right\"><font size='2'/>(" . $place_ft[$row["car_num"]] . ")</td>\n";
         $class_place++;
       }
       else {
         echo "<td></td>\n";
       }
       $prev_car = $row["car_num"];
       $tab_run = 1;
       $i++;
     }
     elseif ($row["run"] == $prev_run ) continue;
     if (!isset($row["run"])) continue;
     while ($row["run"] > $tab_run++)
         echo "<td></td>";
     echo "<td><font size='3' style=\"float:right\"/>";
     if (isset($row["rt"])) {
       #echo "<sub>";
       echo "<font size='2'>";
       if ($best_rt[$row["car_num"]] == $row["rt"])
         if ($purple_rt == $row["rt"])
             printf("<strong style=\"color: purple\">$split_fmt</strong> ", $row["rt"]);
         else
             printf("<strong>$split_fmt</strong> ", $row["rt"]);
       else
         printf("$split_fmt ", $row["rt"]);
       echo "</font><br>";
       #echo "</sub>";
     }
     if ($best_ft[$row["car_num"]] == $row["ft"])
         if ($purple_ft == $row["ft"])
             printf("<strong style=\"color: purple\">%3.2f</strong>", $row["ft"]);
         else
             printf("<strong>%3.2f</strong>", $row["ft"]);
     else
         printf("%3.2f", $row["ft"]);
     echo "</td>";
     $prev_run = $row["run"];
   }
   $res_qry->close();
   ?>
   </tr>
  </table>
 </body>
</html>
