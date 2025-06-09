<?php
  // Results_Info:  Drag style results, ordered on Reaction Time
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
  $best_qry = $db->query('SELECT MAX(run) AS max_runs FROM results WHERE event = ' . $db->escapeString($evt) );
  if ($row = $best_qry->fetchArray()) {
    $max_runs = $row["max_runs"];
  }

  $place=1;
  $best_qry = $db->query('SELECT * FROM rt_order WHERE event = ' . $db->escapeString($evt) );
  while($row = $best_qry->fetchArray()) {
    if ($place == 1)
      $purple_rt = $row["best_rt"] / 1000;
    $best_rt[$row["car_num"]] = $row["best_rt"] / 1000;
    $place_rt[$row["car_num"]] = $place++;
  }

  $place=1;
  $best_qry = $db->query('SELECT * FROM et_order WHERE event = ' . $db->escapeString($evt) );
  while($row = $best_qry->fetchArray()) {
    if ($place == 1)
      $purple_et = $row["best_et"] / 1000;
    $best_et[$row["car_num"]] = $row["best_et"] / 1000;
    $place_et[$row["car_num"]] = $place++;
  }

  $place=1;
  $best_qry = $db->query('SELECT * FROM ft_order WHERE event = ' . $db->escapeString($evt) );
  while($row = $best_qry->fetchArray()) {
    if ($place == 1)
      $purple_ft = $row["best_ft"] / 1000;
    $best_ft[$row["car_num"]] = $row["best_ft"] / 1000;
    $place_ft[$row["car_num"]] = $place++;
  }

  $best_qry = $db->query('SELECT * FROM rt_order
                         LEFT JOIN entrant_info ON rt_order.car_num = entrant_info.car_num and rt_order.event = entrant_info.event
                         WHERE rt_order.event = ' . $db->escapeString($evt) .
                        ' AND special != ""
                          ORDER BY rt_order.red, rt_order.best_rt, rt_order.run, rt_order.car_num');
  while($row = $best_qry->fetchArray()) {
    if (! isset($place_sp[$row["special"]])) $place_sp[$row["special"]] = 1;
    $place_special[$row["car_num"]][$row["special"]] = $place_sp[$row["special"]]++;
  }

  $res_qry = $db->prepare('
      SELECT results.event, results.run, results.car_num, car_name, car_info, rt_ms/1000.0 as rt, et_ms/1000.0 as et, ft_ms/1000.0 as ft
      FROM results, rt_order
      LEFT JOIN entrant_info ON results.car_num = entrant_info.car_num and results.event = entrant_info.event
      WHERE results.event = :event AND results.car_num > 0
        AND results.event = rt_order.event AND results.car_num = rt_order.car_num
      ORDER BY results.event, rt_order.red, rt_order.best_rt, results.car_num, results.run');
  $res_qry->bindValue(':event', $evt, SQLITE3_INTEGER);

  $results = $res_qry->execute();

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Results</title>
    <link rel="stylesheet" href="/HSV_Timing/style.css"/>
<?php
  $icon_file="icons.inc";
  if (file_exists($icon_file))
    readfile($icon_file);
?>
    <meta http-equiv="refresh" content="20"/>
    <!-- Timing_Event_Num='<?php echo $evt;?>' -->
  </head>
<body>
<script type="text/javascript">function showTiming(str){document.location = '?evt='+str;}</script>
<div align="center" style="padding-bottom:5px;">
 Times for <select name="EventList" style="width: 240px" onchange="showTiming(this.value)">
   <?php echo $event_select;?>
 </select>
</div>
<br/>
  <table align="center" border="2" cellpadding="4">
   <tr class="listheader">
      <td>Driver</td>
      <td>Info</td>
      <td>Run 1</td>
   <?php
   $i=1;
   while(++$i <= $max_runs) {
       echo "<td>Run $i</td>";
   }
   $i=0;
   $prev_car = "";
   $tab_run = 1;

   while($row = $results->fetchArray()) {
     if($i%2==0)
       $classname="evenRow";
     else
       $classname="oddRow";
     if ($row["car_num"] != $prev_car ) {
       echo "</tr>";
       echo "<tr class=\"$classname\">";
       echo "<td><div style=\"float:left\">";
       $achievement="";
       if ($best_et[$row["car_num"]] == $purple_et)
           $achievement="&nbsp; ET";
       if ($best_ft[$row["car_num"]] == $purple_ft)
           $achievement="&nbsp; FT";
       if ($achievement != "")
           $achievement="</div><div style=\"float:right\"><strong style=\"color: purple; text-align: right;\">" . $achievement . "</strong>";
       echo htmlspecialchars($row["car_name"]) . $achievement . "</div><br/>&nbsp; &nbsp; Place:" . $place_rt[$row["car_num"]];
       if (isset($place_special[$row["car_num"]])) {
         foreach ($place_special[$row["car_num"]] as $type => $place) {
          echo " &nbsp; &nbsp; <strong>$type:&nbsp;$place</strong>";
         }
       }

       echo "</td><td>" . htmlspecialchars($row["car_num"]) . "<br/>" . htmlspecialchars($row["car_info"]) . "</td>\n";
       $prev_car = $row["car_num"];
       $tab_run = 1;
       $i++;
     }
     elseif ($row["run"] == $prev_run ) continue;
     while ($row["run"] > $tab_run++)
         echo "<td></td>";
     if ($row["rt"] < 0)
         echo "<td style=\"color: red\"><sup><font size='2'/>";
     else
         echo "<td><sup><font size='2'/>";
     if ($best_rt[$row["car_num"]] == $row["rt"])
         if ($purple_rt == $row["rt"])
             printf("<strong style=\"color: purple\">%4.3f</strong> ", $row["rt"]);
         else
             printf("<strong>%4.3f</strong> ", $row["rt"]);
     else
         printf("%4.3f ", $row["rt"]);
     if ($best_et[$row["car_num"]] == $row["et"])
         if ($purple_et == $row["et"])
             printf("<strong style=\"color: purple\">%3.2f</strong></sup><br/><font size='3'/>", $row["et"]);
         else
             printf("<strong>%3.2f</strong></sup><br/><font size='3'/>", $row["et"]);
     else
         printf("%3.2f</sup><br/><font size='3'/>", $row["et"]);
     if ($best_ft[$row["car_num"]] == $row["ft"])
         if ($purple_ft == $row["ft"])
             printf("<strong style=\"color: purple\">%5.2f</strong>", $row["ft"]);
         else
             printf("<strong>%5.2f</strong>", $row["ft"]);
     else
         printf("%5.2f", $row["ft"]);
     echo "</td>";
     $prev_run = $row["run"];
   }
   $res_qry->close();
   ?>
   </tr>
  </table>
 </body>
</html>
