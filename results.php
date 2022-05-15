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
      SELECT event , run , car_num , rt_ms/1000.0 as rt, et_ms/1000.0 as et, ft_ms/1000.0 as ft
      FROM results
      WHERE event = :event
      ORDER BY event, car_num, run');

  $res_qry->bindValue(':event', $evt, SQLITE3_INTEGER);

  $results = $res_qry->execute();

?>

<html>
  <head>
    <title>Results</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<script type="text/javascript">function showTiming(str){document.location = 'results.php?evt='+str;}</script>
<div align="center" style="padding-bottom:5px;">
 Times for <select name="WebTiming" style="width: 240px" onchange="showTiming(this.value)">
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
     <th colspan="4" align="left"> <?php echo htmlspecialchars($row["car_num"]); ?> </th>
     </tr>
   <?php
     $prev_car = htmlspecialchars($row["car_num"]);
   }
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
   $i++;
   }
   ?>
  </table>
 </body>
</html>





