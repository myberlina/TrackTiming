<?php
  include_once 'database.php';

  if (isset($_GET['evt']))
    $evt = 0 + $_GET['evt'];
  else
    $evt = 0;

  if (isset($_GET['id']))
    $row_id = 0 + $_GET['id'];
  else
    $row_id = 0;


  if(($evt>0)&&(count($_POST)>0)) {
    if(('Update' == $_POST['submit'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("UPDATE entrant_info set car_num=:num, car_name=:name, car_info=:info WHERE rowid=:row AND event=:event")){
        $post_qry->bindValue(':event', 0 + $db->escapeString($evt), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["EntNum-$row_id"]), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', $db->escapeString($_POST["EntName-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':info', $db->escapeString($_POST["EntInfo-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':row', 0 + $row_id, SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Modified Successfully";
        else
          $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
      }
      else
        $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }

    if('Create' == $_POST['submit']) {
      if ($post_qry = $db->prepare("INSERT INTO entrant_info(event, car_num, car_name, car_info) VALUES(:event, :num, :name, :info)")){
        $post_qry->bindValue(':event', 0 + $db->escapeString($evt), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["EntNum-$row_id"]), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', $db->escapeString($_POST["EntName-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':info', $db->escapeString($_POST["EntInfo-$row_id"]), SQLITE3_TEXT);
        if ($update_result = $post_qry->execute()) 
          $message = "<font color=\"#00a000\"> Record Created Successfully";
        else
          $message = "<font color=\"#c00000\"> Record Create failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
      }
      else
        $message = "<font color=\"#c00000\"> Record Create failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }
    if(('Yes!' == $_POST['really-delete'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("DELETE FROM entrant_info WHERE event=:event AND car_num=:num AND car_name=:name AND car_info=:info AND rowid=:row")){
        $post_qry->bindValue(':event', 0 + $db->escapeString($evt), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["EntNum-$row_id"]), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', $db->escapeString($_POST["EntName-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':info', $db->escapeString($_POST["EntInfo-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':row', 0 + $row_id, SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Deleted Successfully for ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>";
        else
          $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
      }
      else
        $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }
  }

  if ($events = $db->query('SELECT DISTINCT num, name FROM event_info ORDER BY num DESC')) {
    $event_select = "<option value=\"\">Please Select Date</option>";
    while($row = $events->fetchArray()) {
      $ev=$row['num']; $nm=$row['name'];
      if ($evt == 0) $evt=$ev;
      if ($ev == $evt)
        $event_select = "$event_select <option value=\"$ev\" selected>$nm</option>";
      else
        $event_select = "$event_select <option value=\"$ev\">$nm</option>";
    }
  }
  else
    $message = $message . "<BR><font color=\"#c00000\"> Database read failed\n<BR>" . $db->lastErrorMsg();


  $current = $db->query('select current_event, current_run from current_event, current_run;');
  if ($row = $current->fetchArray()) {
    $cur_evt = $row["current_event"];
    $cur_run = $row["current_run"];
  }
  if ($ent_qry = $db->prepare('SELECT rowid, car_num, car_name, car_info FROM entrant_info WHERE event = :event ORDER BY car_num')) {
    $ent_qry->bindValue(':event', 0 + $evt, SQLITE3_INTEGER);
    $entrants = $ent_qry->execute();
  }
  else
    $message = $message . "<BR><font color=\"#c00000\"> Database read failed\n<BR>" . $db->lastErrorMsg();

?>

<html>
  <head>
    <title>Entrants</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<script type="text/javascript">function showEntrants(str){document.location = 'entrants.php?evt='+str;}</script>
<div align="center" style="padding-bottom:5px;">
 Entrants for <select name="EventList" style="width: 240px" onchange="showEntrants(this.value)">
   <?php echo $event_select;?>
 </select>
</div/
<br>
  <br>
  <form name="frmEntrant" method="post" action="">
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
  <table align=center border="2" cellpadding="4">
   <tr class="listheader">
      <td width=50>Num</td>
      <td>Title</td>
      <td>Info/Model</td>
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
    $safe_name=htmlspecialchars($row['car_name']);
    $safe_info=htmlspecialchars($row['car_info']);
    $row_id=$row['rowid'];
    echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"EntNum-$row_id\" required min=\"1\" value=\"$safe_num\"";
    echo " class=\"input_number\" oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_num')\" ></td>\n";
    echo "<td><input type=\"text\" placeholder=\"Entrant Name\" name=\"EntName-$row_id\" class=\"txtField\" required value=\"$safe_name\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_name')\" ></td>\n";
    echo "<td><input type=\"text\" placeholder=\"Entrant Info\" name=\"EntInfo-$row_id\" class=\"txtField\" value=\"$safe_info\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_info')\" ></td>\n";
    echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Update\" formaction=\"?evt=$evt&id=$row_id\" class=\"button\" disabled> </td>\n";
    echo "<td> <input id=\"delete-$row_id\" type=\"button\" name=\"delete-$row_id\" value=\"Delete\" onclick=\"document.getElementById('really-delete-$row_id').disabled=false\" class=\"button\"> </td>\n";
    echo "<td> <input id=\"really-delete-$row_id\" type=\"submit\" name=\"really-delete\" value=\"Yes!\" formnovalidate formaction=\"?evt=$evt&id=$row_id\" class=\"button\" disabled> </td>\n";
    echo "</tr>\n";
    $i++;
   }
   if($i==0)
    $min_evt=1;
   else
    $min_evt=1 + $safe_num;
   if($i%2==0)
    $classname="class=\"evenRow\"";
   else
    $classname="class=\"oddRow\"";
   echo "<tr $classname>";
   $safe_num=""; $safe_name=""; $safe_info=""; $row_id=0;
   echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"EntNum-$row_id\" required min=\"$min_evt\" value=\"$min_evt\"";
   echo " class=\"input_number\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Entrant Name\" name=\"EntName-$row_id\" class=\"txtField\" value=\"$safe_name\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_name')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Entrant Info\" name=\"EntInfo-$row_id\" class=\"txtField\" value=\"$safe_info\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_info')\" ></td>\n";
   echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Create\" formaction=\"?evt=$evt&id=$row_id\" class=\"button\" disabled> </td>\n";
   echo "</td></tr>\n";
   ?>
  </table>
  <br>
  <div align="center">
   <input type="file" name="Upload_CSV" oninput="document.getElementById('submit-file').disabled=false">
   <input id="submit-file" type="submit" name="submit" value="Upload" disabled formenctype="multipart/form-data" formaction="entrants_upload.php?evt=<?php echo $evt;?>" >
  </div>
  </form>
 </body>
</html>
