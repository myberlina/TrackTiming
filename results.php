<?php

  include_once 'database.php';

  $events = $db->query('SELECT DISTINCT event, name FROM results, event_info WHERE event = num ORDER BY event DESC');

  if (isset($_GET['evt'])) {
    $evt = $_GET['evt'];
  }
  else $evt = 0;

  $event_select = "<option value=\"\">Please Select Date</option>";
  while($row = $events->fetchArray()) {
    $ev=$row['event']; $nm=$row['name'];
    if ($evt == 0) $evt=$ev;
    if ($ev == $evt)
      $event_select = "$event_select <option value=\"$ev\" selected>$nm</option>";
    else
      $event_select = "$event_select <option value=\"$ev\">$nm</option>";
  }

  $res_qry = $db->prepare('
      SELECT results.event, run, results.car_num, car_name, rt_ms/1000.0 as rt, et_ms/1000.0 as et, ft_ms/1000.0 as ft
      FROM results
      LEFT JOIN entrant_info ON results.car_num = entrant_info.car_num and results.event = entrant_info.event
      WHERE results.event = :event AND results.car_num > 0
      ORDER BY results.event, results.car_num, run');

  $res_qry->bindValue(':event', $evt, SQLITE3_INTEGER);

  $results = $res_qry->execute();

?>

<html>
  <head>
    <title>Results</title>
    <link rel="stylesheet" href="style.css">
    <meta http-equiv="refresh" content="20" >
  </head>
<body>
<script type="text/javascript">function showTiming(str){document.location = 'results.php?evt='+str;}</script>
<div align="center" style="padding-bottom:5px;">
 Times for <select name="EventList" style="width: 240px" onchange="showTiming(this.value)">
   <?php echo $event_select;?>
 </select>
</div/
<br>
  <table align=center border="2" cellpadding="4">
   <tr class="listheader">
      <td>Car</td>
      <td>Run</td>
      <td>R/T</td>
      <td>E/T</td>
      <td>F/T</td>
    </tr>
   <?php
   $i=0;
   $prev_car = "";

   while($row = $results->fetchArray()) {
    if($i%2==0)
     $classname="evenRow";
    else
     $classname="oddRow";
   ?>
   <?php
   if ($row["car_num"] != $prev_car ) {
   ?>
     <tr border="0" class="new_owner">
     <th colspan="5" align="left"> <?php echo htmlspecialchars($row["car_num"]) . 
     " &nbsp; &nbsp; " . htmlspecialchars($row["car_name"]); ?> </th>
     </tr>
   <?php
     $prev_car = htmlspecialchars($row["car_num"]);
   }
   elseif ($row["run"] == $prev_run ) continue;
   ?>
   <tr class="<?php if(isset($classname)) echo $classname;?>">
     <td>
       <?php echo htmlspecialchars($row["car_num"]); ?>
     </td>
     <td><?php echo htmlspecialchars($row["run"]); ?></td>
     <td><?php echo htmlspecialchars($row["rt"]); ?></td>
     <td><?php echo htmlspecialchars($row["et"]); ?></td>
     <td><?php echo htmlspecialchars($row["ft"]); ?></td>
   </tr>
   <?php
   $prev_run = $row["run"];
   $i++;
   }
   ?>
  </table>
 </body>
</html>





