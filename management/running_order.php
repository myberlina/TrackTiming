<?php
  include_once 'database.php';

  if (isset($_GET['id']))
    $row_id = $_GET['id'];
  else
    $row_id = 0;

  if(count($_POST)>0) {
    if(($row_id > 0) && ('Change Event' == $_POST['submit'])) {
      if ($post_qry = $db->prepare("UPDATE current_event set current_event=:num WHERE rowid=1")) {
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["Event"]), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Event Set Successfully";
        else
          $message = "<font color=\"#c00000\"> Event Set failed for &nbsp; ".$_POST["EvtNum-$row_id"].", \"".$_POST["EvtName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
      }
      else
        $message = "<font color=\"#c00000\"> Event Set failed for &nbsp; ".$_POST["EvtNum-$row_id"].", \"".$_POST["EvtName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }

    if('Create' == $_POST['submit']) {
      if ($post_qry = $db->prepare("INSERT INTO event_info(num, name) VALUES(:num, :name)")) {
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["EvtNum-$row_id"]), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', $db->escapeString($_POST["EvtName-$row_id"]), SQLITE3_TEXT);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Modified Successfully";
        else
          $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EvtNum-$row_id"].", \"".$_POST["EvtName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
      }
      else
        $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EvtNum-$row_id"].", \"".$_POST["EvtName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }
  }


  $current = $db->query('select current_event, current_run from current_event, current_run;');
  if ($row = $current->fetchArray()) {
    $cur_evt = $row["current_event"];
    $cur_run = $row["current_run"];
  }

  if ($events = $db->query('SELECT num, name, COUNT() as entrants FROM event_info
  				LEFT JOIN entrant_info ON event = num
  				GROUP BY num ORDER BY num DESC; ')) {
    $event_select = "<option value=\"\">Please Select Date</option>";
    while($row = $events->fetchArray()) {
      $ev=$row['num']; $nm=$row['name'] . " - " . $row['entrants'] . " Entrants";
      if ($ev == $cur_evt)
        $event_select = "$event_select <option value=\"$ev\" selected>$nm</option>";
      else
        $event_select = "$event_select <option value=\"$ev\">$nm</option>";
    }
  }
  else
    $message = $message . "<BR><font color=\"#c00000\"> Database read failed\n<BR>" . $db->lastErrorMsg();

  $order = $db->query("SELECT next_car.rowid, next_car.car_num, car_name FROM next_car
	  		LEFT JOIN entrant_info ON event=$cur_evt AND entrant_info.car_num = next_car.car_num ORDER BY ord");

?>

<html>
  <head>
    <title>Events</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<br>
  <br>
  <form name="frmRunOrd" method="post" action="">
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
    <div align="center" style="padding-bottom:5px;">
   Current Event <select name="Event" style="width: 240px">
     <?php echo $event_select;?>
   </select>
  </div>

  <table align=center border="2" cellpadding="4">
   <tr class="listheader">
      <td width=50>Num</td>
      <td>Driver</td>
   </tr>
   <?php

   $i=0;
   while(($row = $order->fetchArray())||($i==0)) {
    if(!is_array($row)) break;
    if($i%2==0)
     $classname="class=\"evenRow\"";
    else
     $classname="class=\"oddRow\"";
    echo "<tr $classname>";
    $safe_num=htmlspecialchars($row['car_num']);
    $safe_name=htmlspecialchars($row['car_name']);
    $row_id=$row['rowid'];
    echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"EvtNum-$row_id\" required min=\"1\" value=\"$safe_num\"";
    echo " class=\"input_number\" oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_num')\" ></td>\n";
    echo "<td><input type=\"text\" placeholder=\"Event Name\" name=\"EvtName-$row_id\" class=\"txtField\" required value=\"$safe_name\"";
    #echo " oninput=\"document.getElementById('submit-$row_id').disabled=false\" ></td>\n";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_name')\" ></td>\n";
    echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Update\" formaction=\"?id=$row_id\" class=\"button\" disabled> </td>\n";
    echo "<td><a href=\"entrants.php?evt=$safe_num\">Entrants</a>\n";
    echo "</td></tr>\n";
    $i++;
   }
   ?>
  </table>
  </form>
 </body>
</html>
