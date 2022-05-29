<?php
  if (isset($_GET['id']))
    $row_id = $_GET['id'];
  else
    $row_id = 0;

  if(count($_POST)>0) {
    include_once 'database.php';
    if(($row_id > 0) && ('Update' == $_POST['submit'])) {
      if ($post_qry = $db->prepare("UPDATE event_info set num=:num, name=:name WHERE rowid=:row")) {
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["EvtNum-$row_id"]), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', $db->escapeString($_POST["EvtName-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':row', 0 + $row_id, SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Modified Successfully";
        else
          $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EvtNum-$row_id"].", \"".$_POST["EvtName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
	$post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EvtNum-$row_id"].", \"".$_POST["EvtName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }

    if('Create' == $_POST['submit']) {
      if ($post_qry = $db->prepare("INSERT INTO event_info(num, name) VALUES(:num, :name)")) {
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["EvtNum-$row_id"]), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', $db->escapeString($_POST["EvtName-$row_id"]), SQLITE3_TEXT);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Modified Successfully";
        else
          $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EvtNum-$row_id"].", \"".$_POST["EvtName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
	$post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EvtNum-$row_id"].", \"".$_POST["EvtName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
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
  $events = $db->query('SELECT rowid, num, name FROM event_info ORDER BY num DESC');

?>

<html>
  <head>
    <title>Events</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<script type="text/javascript">function showTiming(str){document.location = 'events.php?evt='+str;}</script>
<br>
  <br>
  <form name="frmEvent" method="post" action="">
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
  <table align=center border="2" cellpadding="4">
   <tr class="listheader">
      <td width=50>Num</td>
      <td>Title</td>
   </tr>
   <?php

   $i=0;
   while(($row = $events->fetchArray())||($i==0)) {
    if($i==0) {
     if(is_array($row))
      $min_evt=1 + htmlspecialchars($row['num']);
     else
      $min_evt=1;
     $classname="class=\"oddRow\"";
     echo "<tr $classname>";
     $safe_num=""; $safe_name=""; $row_id="new";
     echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"EvtNum-$row_id\" required min=\"$min_evt\" value=\"$min_evt\"";
     echo " class=\"input_number\" ></td>\n";
     echo "<td><input type=\"text\" placeholder=\"Event Name\" name=\"EvtName-$row_id\" class=\"txtField\" value=\"$safe_name\"";
     echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_name')\" ></td>\n";
     echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Create\" formaction=\"?id=$row_id\" class=\"button\" disabled> </td>\n";
     echo "</td></tr>\n";
    }
    if(!is_array($row)) break;
    if($i%2==0)
     $classname="class=\"evenRow\"";
    else
     $classname="class=\"oddRow\"";
    if ($cur_evt == $row["num"])
     $classname="$classname-hilight";
    echo "<tr $classname>";
    $safe_num=htmlspecialchars($row['num']);
    $safe_name=htmlspecialchars($row['name']);
    $row_id=$row['rowid'];
    echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"EvtNum-$row_id\" required min=\"1\" value=\"$safe_num\"";
    echo " class=\"input_number\" oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_num')\" ></td>\n";
    echo "<td><input type=\"text\" placeholder=\"Event Name\" name=\"EvtName-$row_id\" class=\"txtField\" required value=\"$safe_name\"";
    #echo " oninput=\"document.getElementById('submit-$row_id').disabled=false\" ></td>\n";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_name')\" ></td>\n";
    echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Update\" formaction=\"?id=$row_id\" class=\"button\" disabled> </td>\n";
    echo "<td><a href=\"entrants.php?evt=$safe_num\">Entrants</a>\n";
    if ($cur_evt == $row["num"])
      echo " &nbsp; *";
    echo "</td></tr>\n";
    $i++;
   }
   ?>
  </table>
  </form>
 </body>
</html>
