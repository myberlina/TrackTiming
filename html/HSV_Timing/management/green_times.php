<?php

  if (isset($_GET['evt']))
    $evt = intval($_GET['evt']);
  else
    $evt = 0;

  if (isset($_GET['run']))
    $run = intval($_GET['run']);
  else
    $run = 0;

  if (isset($_GET['title']))
    $title = htmlspecialchars($_GET['title']);
  else
    $title = "Green";

  if (count($_POST)>0) {
    include_once 'database.php';
    if (isset($_POST['tgt_evt'])) $evt = $_POST['tgt_evt'];
    if (isset($_POST['tgt_run'])) $run = $_POST['tgt_run'];
    if (isset($_POST['tgt_row'])) $row_id = $_POST['tgt_row'];
    else $row_id = 0;
    if ((isset($_POST['submit']))&&('Fix' == $_POST['submit'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("UPDATE green_time set car_num=:num WHERE rowid=:row AND event=:event AND run=:run")){
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
      if ($post_qry = $db->prepare("DELETE FROM green_time WHERE event=:event AND run=:run AND car_num=:num AND rowid=:row")){
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

  if ($ent_qry = $db->prepare('SELECT rowid, car_num, time_ms FROM green_time WHERE event = :event AND run = :run ORDER BY rowid desc')) {
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
    <title><?php echo "$title Times ".htmlspecialchars($evt).":".htmlspecialchars($run);?></title>
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
      <td colspan=1><?php echo $title;?></td>
      <td colspan=1>Run <?php echo $run;?></td>
      <td colspan=3 align="right"><a href=""> Refresh </a></td>
   </tr>
   <tr class="listheader">
      <td width=50>Car</td>
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
    $row_id=$row['rowid'];
    $colour='';
    if (isset($seen_car_num[$safe_num]))
      $colour=' style="background: pink"';
    echo "<td><input $colour type=\"number\" placeholder=\"Num\" size=\"4\" name=\"CarNum-$row_id\" required value=\"$safe_num\"";
    echo " class=\"input_number\" oninput=\"block_refresh=1;document.getElementById('submit-$row_id').disabled=(this.value == '$safe_num')\" ></td>\n";
    echo "<td>$safe_time</td>";
    echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Fix\" onclick=\"document.getElementById('tgt_row').value='$row_id'\" class=\"button\" disabled> </td>\n";
    echo "<td> <input id=\"delete-$row_id\" type=\"button\" name=\"delete-$row_id\" value=\"Del\" onclick=\"block_refresh=1;document.getElementById('really-delete-$row_id').disabled=false\" class=\"button\"> </td>\n";
    echo "<td> <input id=\"really-delete-$row_id\" type=\"submit\" name=\"really-delete\" value=\"Yes\" formnovalidate onclick=\"document.getElementById('tgt_row').value='$row_id'\" class=\"button\" disabled> </td>\n";
    echo "</tr>\n";
    $seen_car_num[$safe_num] = 1;
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
  var ws = new WebSocket('ws://'+location.host+'/ws/status/green/');
  ws.onopen = function() { /* got the websocket, switch to ws based refresh */
    clearTimeout(refesh_timeout);
    ws.onclose = function()     { refesh_page(); }; /* Idle time out in 60s */
  }
  ws.onmessage = function(event){ refesh_page(); };
 </script>
</html>
