<?php
  include_once 'database.php';

  if (isset($_GET['evt']))
    $evt = 0 + $_GET['evt'];
  else
    $evt = 0;

  if (isset($_GET['run']))
    $run = 0 + $_GET['run'];
  else
    $run = 0;

  if (isset($_GET['id']))
    $row_id = 0 + $_GET['id'];
  else
    $row_id = 0;

  if((count($_POST)>0)) {
    var_dump($_POST);
    if (isset($_POST['tgt_evt'])) $evt = $_POST['tgt_evt'];
    if (isset($_POST['tgt_run'])) $run = $_POST['tgt_run'];
    if (isset($_POST['tgt_row'])) $row_id = $_POST['tgt_row'];
    if(('Fix' == $_POST['submit'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("UPDATE start_time set car_num=:num WHERE rowid=:row AND event=:event AND run=:run")){
        $post_qry->bindValue(':event', 0 + $db->escapeString($evt), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["CarNum-$row_id"]), SQLITE3_INTEGER);
        $post_qry->bindValue(':run', 0 + $db->escapeString($run), SQLITE3_INTEGER);
        $post_qry->bindValue(':row', 0 + $row_id, SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Modified Successfully";
        else
          $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>". $db->lastErrorMsg();
      }
      else
        $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>". $db->lastErrorMsg();
    }

    if(('Yes' == $_POST['really-delete'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("DELETE FROM start_time WHERE event=:event AND run=:run AND car_num=:num AND rowid=:row")){
        $post_qry->bindValue(':event', 0 + $db->escapeString($evt), SQLITE3_INTEGER);
        $post_qry->bindValue(':run', 0 + $db->escapeString($run), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["CarNum-$row_id"]), SQLITE3_INTEGER);
        $post_qry->bindValue(':row', 0 + $row_id, SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Deleted Successfully for ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>";
        else
          $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>". $db->lastErrorMsg();
      }
      else
        $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>". $db->lastErrorMsg();
    }
  }

  $current = $db->query('select current_event, current_run from current_event, current_run;');
  if ($row = $current->fetchArray()) {
    $cur_evt = $row["current_event"];
    $cur_run = $row["current_run"];
  }

  if (($evt == 0) || ($run == 0)) {
    $evt = $cur_evt;
    $run = $cur_run;
  }

  if ($ent_qry = $db->prepare(
	 'SELECT start_time.rowid, start_time.car_num, start_time.time_ms, start_time.time_ms - green_time.time_ms delta
	  FROM start_time
	  LEFT JOIN green_time ON green_time.event = start_time.event AND green_time.run = start_time.run AND green_time.car_num = start_time.car_num AND start_time.time_ms > green_time.time_ms
	  WHERE start_time.event = :event AND start_time.run = :run
	  ORDER BY start_time.rowid desc'
    )) {
    $ent_qry->bindValue(':event', 0 + $evt, SQLITE3_INTEGER);
    $ent_qry->bindValue(':run', 0 + $run, SQLITE3_INTEGER);
    $entrants = $ent_qry->execute();
  }
  else
    $message = $message . "<BR><font color=\"#c00000\"> Database read failed\n<BR>" . $db->lastErrorMsg();

?>

<html>
  <head>
    <title>Start Times <?php echo htmlspecialchars($evt).":".htmlspecialchars($run);?></title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<br>
  <br>
  <form name="frmEntrant" method="post" action="">
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
  <table align=center border="0" cellpadding="1">
   <input type="hidden" id="tgt_row" name="tgt_row" value="">
   <input type="hidden" id="tgt_evt" name="tgt_evt" value="<?php echo htmlspecialchars($evt);?>">
   <input type="hidden" id="tgt_run" name="tgt_run" value="<?php echo htmlspecialchars($run);?>">
   <tr class="listheader">
      <td width=50>Car</td>
      <td>R/T</td>
      <td>Time</td>
      <td colspan=3>Operation</td>
   </tr>
   <?php

   $i=0;
   while(isset($entrants) && $row = $entrants->fetchArray()) {
    if($i%2==0)
     $classname="class=\"evenRow\"";
    else
     $classname="class=\"oddRow\"";
    echo "<tr $classname>";
    $safe_num=htmlspecialchars($row['car_num']);
    $safe_time=htmlspecialchars($row['time_ms']/1000);
    $safe_delta=htmlspecialchars($row['delta']/1000);
    $row_id=$row['rowid'];
    echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"CarNum-$row_id\" required value=\"$safe_num\"";
    echo " class=\"input_number\" oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_num')\" ></td>\n";
    echo "<td>$safe_delta</td>";
    echo "<td>$safe_time</td>";
    echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Fix\" onclick=\"document.getElementById('tgt_row').value='$row_id'\" class=\"button\" disabled> </td>\n";
    echo "<td> <input id=\"delete-$row_id\" type=\"button\" name=\"delete-$row_id\" value=\"Del\" onclick=\"document.getElementById('really-delete-$row_id').disabled=false\" class=\"button\"> </td>\n";
    echo "<td> <input id=\"really-delete-$row_id\" type=\"submit\" name=\"really-delete\" value=\"Yes\" formnovalidate onclick=\"document.getElementById('tgt_row').value='$row_id'\" class=\"button\" disabled> </td>\n";
    echo "</tr>\n";
    $i++;
   }
   ?>
  </table>
  <br>
  </form>
 </body>
</html>
