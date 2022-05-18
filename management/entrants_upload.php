<?php
  include_once 'database.php';

  if (isset($_GET['evt']))
    $evt = 0 + $_GET['evt'];
  else
    $evt = 0;

  echo "<br>_POST<BR>";
  var_dump($_POST);
  echo "<br>_FILES<BR>";
  var_dump($_FILES);

  if (($evt<1)||(count($_POST)<1)||('Upload'!=$_POST['submit']))
    die;

  if(($evt>0)&&(count($_POST)>0)) {
    if(('Update' == $_POST['submit'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("UPDATE entrant_info set car_num=:num, car_name=:name, car_info=:info WHERE rowid=:row")){
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
    <title>Entrants File Upload</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<form name="frmEntrantUpload" method="post" enctype="multipart/form-data" action="">
  <div align="center" style="padding-bottom:5px;">
   Entrants for <select name="EventList" style="width: 240px">
     <?php echo $event_select;?>
   </select>
  </div>
  <br>
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>

  </table>
  <br>
  <div align="center">
   First Row is Headings <select name="head_lines">
        <option value="0" selected>No</option>
        <option value="1">One Row</option>
        <option value="2">Two Rows</option>
        <option value="3">Three Rows</option>
   </select> &nbsp; &nbsp; &nbsp; &nbsp;
   <input id="submit-csv" type="submit" name="submit" value="Submit" >
  </div>
  <?php
  if (is_array($_FILES) && is_array($_FILES["Upload_CSV"]) 
      && (0 == $_FILES["Upload_CSV"]["error"])){
    echo "<BR>Processing file '" . $_FILES["Upload_CSV"]["name"]. "'<br>";
    $col_type_sel='
	<option value="ignore">Ignore</option>
	<option value="number">Number</option>
	<option value="name1">Name1</option>
	<option value="name2">Name2</option>
	<option value="name3">Name3</option>
	<option value="info1">Info1</option>
	<option value="info2">Info2</option>
    ';
    if ($handle = fopen($_FILES["Upload_CSV"]["tmp_name"], "r")) {
      echo "<table>";
      if ($row = fgetcsv($handle, 0, "\t")) {
	echo "<tr>";
	$i=0;
	foreach($row as $cell) {
	  echo "<td><select name=\"col-$i\"> $col_type_sel </select></td>";
	  $i=$i+1;
        }
	echo "</tr>";
      }
      fseek($handle, 0, SEEK_SET);
      $j=0;
      while ($row = fgetcsv($handle, 0, "\t")) {
	echo "<tr>";
	$i=0;
	foreach($row as $cell) {
          echo "<td>$cell</td>";
	  echo "<input type=\"hidden\" name=\"val_$j-$i\" value=\"$cell\">";
	  $i=$i+1;
        }
	echo "</tr>";
	$j=$j+1;
      }
      echo "</table>";
    }
  }

  ?>
  </form>
 </body>
</html>
