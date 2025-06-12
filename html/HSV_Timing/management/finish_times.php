<?php
  if (isset($_GET['evt']))
    $evt = intval($_GET['evt']);
  else
    $evt = 0;

  if (isset($_GET['run']))
    $run = intval($_GET['run']);
  else
    $run = 0;

  if (count($_POST)>0) {
    include_once 'database.php';
    if (isset($_POST['tgt_evt'])) $evt = $_POST['tgt_evt'];
    if (isset($_POST['tgt_run'])) $run = $_POST['tgt_run'];
    if (isset($_POST['tgt_row'])) $row_id = $_POST['tgt_row'];
    else $row_id = 0;
    if ((isset($_POST['submit']))&&('Fix' == $_POST['submit'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("UPDATE finish_time set car_num=:num WHERE rowid=:row AND event=:event AND run=:run")){
        $post_qry->bindValue(':event', intval($db->escapeString($evt)), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', intval($db->escapeString($_POST["CarNum-$row_id"])), SQLITE3_INTEGER);
        $post_qry->bindValue(':run', intval($db->escapeString($run)), SQLITE3_INTEGER);
        $post_qry->bindValue(':row', intval($row_id), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "";
        else
          $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>". $db->lastErrorMsg();
        $post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>". $db->lastErrorMsg();
    }

    if((isset($_POST['really-delete']))&&('Yes' == $_POST['really-delete'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("DELETE FROM finish_time WHERE event=:event AND run=:run AND car_num=:num AND rowid=:row")){
        $post_qry->bindValue(':event', intval($db->escapeString($evt)), SQLITE3_INTEGER);
        $post_qry->bindValue(':run', intval($db->escapeString($run)), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', intval($db->escapeString($_POST["CarNum-$row_id"])), SQLITE3_INTEGER);
        $post_qry->bindValue(':row', intval($row_id), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "";
        else
          $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>". $db->lastErrorMsg();
        $post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["CarNum-$row_id"].", \"".$evt.":".$run."\"\n<BR>". $db->lastErrorMsg();
    }
  }
  else {
    include_once 'database_ro.php';
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

  if (isset($_GET['ET'])) {
    $time_title='E/T';
    $time_colour='Start';
    $time_query=
	 'SELECT finish_time.rowid, finish_time.car_num, finish_time.time_ms, finish_time.time_ms - start_time.time_ms delta
	  FROM finish_time
	  LEFT JOIN start_time ON start_time.event = finish_time.event AND start_time.run = finish_time.run AND start_time.car_num = finish_time.car_num AND finish_time.time_ms > start_time.time_ms
	  WHERE finish_time.event = :event AND finish_time.run = :run
	  ORDER BY finish_time.rowid desc, delta';
  }
  else {
    $time_title='F/T';
    $time_colour='Green';
    $time_query=
	 'SELECT finish_time.rowid, finish_time.car_num, finish_time.time_ms, finish_time.time_ms - green_time.time_ms delta
	  FROM finish_time
	  LEFT JOIN green_time ON green_time.event = finish_time.event AND green_time.run = finish_time.run AND green_time.car_num = finish_time.car_num AND finish_time.time_ms > green_time.time_ms
	  WHERE finish_time.event = :event AND finish_time.run = :run
	  ORDER BY finish_time.rowid desc, delta';
  }

  if ($ent_qry = $db->prepare($time_query)) {
    $ent_qry->bindValue(':event', intval($evt), SQLITE3_INTEGER);
    $ent_qry->bindValue(':run', intval($run), SQLITE3_INTEGER);
    $entrants = $ent_qry->execute();
  }
  else
    $message = $message . "<BR><font color=\"#c00000\"> Database read failed\n<BR>" . $db->lastErrorMsg();

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Finish Times <?php echo htmlspecialchars($evt).":".htmlspecialchars($run);?></title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
  <form name="frmTiming" method="post" action="">
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
  <table align=center border="0" cellpadding="1">
   <input type="hidden" id="tgt_row" name="tgt_row" value="">
   <input type="hidden" id="tgt_evt" name="tgt_evt" value="<?php echo htmlspecialchars($evt);?>">
   <input type="hidden" id="tgt_run" name="tgt_run" value="<?php echo htmlspecialchars($run);?>">
   <tr>
      <td colspan=2>Finish</td>
      <td colspan=1>Run <?php echo $run;?></td>
      <td colspan=3 align="right"><a href=""> Refresh </a></td>
   </tr>
   <tr class="listheader">
      <td width=50>Car</td>
      <td><?php echo $time_title;?></td>
      <td>Time</td>
      <td colspan=3>Operation</td>
   </tr>
   <?php

   $i=0; $prev_row_id = 0;
   while(isset($entrants) && $row = $entrants->fetchArray()) {
    $row_id=$row['rowid'];
    if ($row_id == $prev_row_id) $i--;
    if($i%2==0)
     $classname="class=\"evenRow\"";
    else
     $classname="class=\"oddRow\"";
    echo "<tr $classname>";
    $safe_num=htmlspecialchars($row['car_num']);
    $safe_time=htmlspecialchars($row['time_ms']/1000);
    $safe_delta=round(htmlspecialchars($row['delta']/10))/100;
    if ($row_id != $prev_row_id) {
      echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"CarNum-$row_id\" id=\"CarNum-$row_id\" required value=\"$safe_num\"";
      echo " class=\"input_number\" oninput=\"block_refresh=1;document.getElementById('submit-$row_id').disabled=(this.value == '$safe_num')\" ></td>\n";
    }
    else {
      echo "<td>&nbsp;$safe_num</td>";
    }
    $delta_ondblclick="ondblclick=\"tb=document.getElementById('CarNum-$row_id');tb.value = -tb.value;block_refresh=1;document.getElementById('submit-$row_id').disabled=(tb.value == '$safe_num')\"";
    if ( ($safe_delta <= 0) && ($safe_delta > -2.0) )
      echo "<td style=\"background: red\" $delta_ondblclick>$safe_delta</td>";
    else
      echo "<td $delta_ondblclick>$safe_delta</td>";
    echo "<td>$safe_time</td>";
    if ($row_id != $prev_row_id) {
      echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Fix\" onclick=\"document.getElementById('tgt_row').value='$row_id'\" class=\"button\" disabled> </td>\n";
      echo "<td> <input id=\"delete-$row_id\" type=\"button\" name=\"delete-$row_id\" value=\"Del\" onclick=\"block_refresh=1;document.getElementById('really-delete-$row_id').disabled=false\" class=\"button\"> </td>\n";
      echo "<td> <input id=\"really-delete-$row_id\" type=\"submit\" name=\"really-delete\" value=\"Yes\" formnovalidate onclick=\"document.getElementById('tgt_row').value='$row_id'\" class=\"button\" disabled> </td>\n";
    }
    else {
      echo "<td colspan=3 style=\"background: pink\"> Dup $time_colour Time</td>\n";
    }
    echo "</tr>\n";
    $prev_row_id = $row_id;
    $i++;
   }
   $ent_qry->close();
   ?>
  </table>
  <br>
  </form>
 </body>
 <script type="text/javascript">
  block_refresh = 0;
<?php
   if(count($_POST)>0) /* Dont do a reload if this was a post */
    echo "   function refesh_page() { if (block_refresh == 0) document.location=document.location; };";
   else /* Prefer reload if not a post as browser will preserve your location in the page */
    echo "   function refesh_page() { if (block_refresh == 0) location.reload(true); };";
?>
  refesh_timeout=setTimeout(refesh_page, 70000);
  var ws = new WebSocket('ws://'+location.host+'/ws/status/finish/');
  ws.onopen = function() { /* got the websocket, switch to ws based refresh */
    clearTimeout(refesh_timeout);
    ws.onclose = function()     { refesh_page(); }; /* Idle time out in 60s */
  }
  ws.onmessage = function(event){ refesh_page(); };
 </script>
</html>
