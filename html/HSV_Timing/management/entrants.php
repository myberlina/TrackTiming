<?php

  if (isset($_GET['evt']) && is_numeric($_GET['evt']))
    $evt = intval($_GET['evt']);
  else
    $evt = 0;

  if (isset($_GET['id']) && is_numeric($_GET['id']))
    $row_id = intval($_GET['id']);
  else
    $row_id = 0;

  $orderby = "Num";
  $orderbyq = "car_num";
  if (isset($_POST['orderby'])&&("" != $_POST['orderby'])) {
    //var_dump($_POST['orderby']);
    switch ($_POST['orderby']) {
      case "Num" :	$orderby = $_POST['orderby']; $orderbyq = "car_num" ; break;
      case "Title" :	$orderby = $_POST['orderby']; $orderbyq = "car_name, car_num" ; break;
      case "Info" :	$orderby = $_POST['orderby']; $orderbyq = "car_info, car_num" ; break;
      case "Special" :	$orderby = $_POST['orderby']; $orderbyq = "special, car_num" ; break;
      case "Car" :	$orderby = $_POST['orderby']; $orderbyq = "car_car, car_num" ; break;
      case "Class" :	$orderby = $_POST['orderby']; $orderbyq = "class, car_num" ; break;
      case "Entrant" :	$orderby = $_POST['orderby']; $orderbyq = "car_entrant, car_num" ; break;
      case "Order" :	$orderby = $_POST['orderby']; $orderbyq = "run_order, car_num" ; break;
    }
  }

  if(($evt>0)&&(count($_POST)>0)) {
    include_once 'database.php';
    if(isset($_POST['submit'])&&('Update' == $_POST['submit'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("UPDATE entrant_info set car_num=:num, car_name=:name, car_info=:info, special=:special, class=:class, car_car=:car, car_entrant=:entrant, run_order=:run_order WHERE rowid=:row AND event=:event")){
        $post_qry->bindValue(':event', intval(htmlspecialchars_decode($evt)), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', intval(htmlspecialchars_decode($_POST["EntNum-$row_id"])), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', htmlspecialchars_decode($_POST["EntName-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':info', htmlspecialchars_decode($_POST["EntInfo-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':special', htmlspecialchars_decode($_POST["EntSpecial-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':class', htmlspecialchars_decode($_POST["EntClass-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':car', htmlspecialchars_decode($_POST["EntCar-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':entrant', htmlspecialchars_decode($_POST["EntEntrant-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':run_order', htmlspecialchars_decode($_POST["EntOrder-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':row', intval($row_id), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Modified Successfully";
        else
          $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
        $post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Record Modify failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }

    if(isset($_POST['submit']) && ('Create' == $_POST['submit'])) {
      if ($post_qry = $db->prepare("INSERT INTO entrant_info(event, car_num, car_name, car_info, special, class, car_car, car_entrant, run_order) VALUES(:event, :num, :name, :info, :special, :class, :car, :entrant, :run_order)")){
        $post_qry->bindValue(':event', intval(htmlspecialchars_decode($evt)), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', intval(htmlspecialchars_decode($_POST["EntNum-$row_id"])), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', htmlspecialchars_decode($_POST["EntName-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':info', htmlspecialchars_decode($_POST["EntInfo-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':special', htmlspecialchars_decode($_POST["EntSpecial-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':class', htmlspecialchars_decode($_POST["EntClass-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':car', htmlspecialchars_decode($_POST["EntCar-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':entrant', htmlspecialchars_decode($_POST["EntEntrant-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':run_order', htmlspecialchars_decode($_POST["EntOrder-$row_id"]), SQLITE3_TEXT);
        if ($update_result = $post_qry->execute()) 
          $message = "<font color=\"#00a000\"> Record Created Successfully";
        else
          $message = "<font color=\"#c00000\"> Record Create failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
        $post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Record Create failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }
    if(isset($_POST['really-delete'])&&('Yes!' == $_POST['really-delete'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("DELETE FROM entrant_info WHERE event=:event AND car_num=:num AND car_name=:name AND car_info=:info AND special=:special AND rowid=:row")){
        $post_qry->bindValue(':event', intval(htmlspecialchars_decode($evt)), SQLITE3_INTEGER);
        $post_qry->bindValue(':num', intval(htmlspecialchars_decode($_POST["EntNum-$row_id"])), SQLITE3_INTEGER);
        $post_qry->bindValue(':name', htmlspecialchars_decode($_POST["EntName-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':info', htmlspecialchars_decode($_POST["EntInfo-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':special', htmlspecialchars_decode($_POST["EntSpecial-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':row', intval($row_id), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Deleted Successfully for ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>";
        else
          $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
        $post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }
    if(isset($_POST['really-delete-ALL'])&&('Yes' == $_POST['really-delete-ALL'])&&
             isset($_POST['update_list'])&&("" != $_POST['update_list'])) {
      //var_dump($_POST);
      $update_list = explode (";submit-", $_POST['update_list']); 
      //var_dump($update_list);
      if (sizeof($update_list) > 1) {
        if ($post_qry = $db->prepare("UPDATE entrant_info set car_num=:num, car_name=:name, car_info=:info, special=:special, class=:class, car_car=:car, car_entrant=:entrant, run_order=:run_order WHERE rowid=:row AND event=:event")){
	  $good=0;
	  $bad_message="";
          foreach($update_list as $row_id) {
	    if ($row_id < 1) continue;
            $post_qry->bindValue(':event', intval(htmlspecialchars_decode($evt)), SQLITE3_INTEGER);
            $post_qry->bindValue(':num', intval(htmlspecialchars_decode($_POST["EntNum-$row_id"])), SQLITE3_INTEGER);
            $post_qry->bindValue(':name', htmlspecialchars_decode($_POST["EntName-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':info', htmlspecialchars_decode($_POST["EntInfo-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':special', htmlspecialchars_decode($_POST["EntSpecial-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':class', htmlspecialchars_decode($_POST["EntClass-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':car', htmlspecialchars_decode($_POST["EntCar-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':entrant', htmlspecialchars_decode($_POST["EntEntrant-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':run_order', htmlspecialchars_decode($_POST["EntOrder-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':row', intval($row_id), SQLITE3_INTEGER);
            if ($update_result = $post_qry->execute())
              $good++;
            else
              $bad_message = $bad_message." ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
          }
	  if ($good > 0)
	    $message = "<font color=\"#00a000\"> $good records modified successfully";
	  if ($bad_message != "")
	    $message = $message."<BR><font color=\"#c00000\"> Record Modify failed for $bad_message";
          $post_qry->close();
        }
        else
          $message = "<font color=\"#c00000\"> Record Modify failed for all\n<BR>". $db->lastErrorMsg();
      }
    }
    if(isset($_POST['really-delete-ALL'])&&('Yes!' == $_POST['really-delete-ALL'])) {
      if ($post_qry = $db->prepare("DELETE FROM entrant_info WHERE event=:event")){
        $post_qry->bindValue(':event', intval(htmlspecialchars_decode($evt)), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> All Entrants Deleted Successfully for Event ".$evt.", \"".$_POST["EventList"]."\"\n<BR>";
        else
          $message = "<font color=\"#c00000\"> All Entrants Delete failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
        $post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> All Entrants Delete failed for &nbsp; ".$_POST["EntNum-$row_id"].", \"".$_POST["EntName-$row_id"]."\"\n<BR>". $db->lastErrorMsg();
    }
  }
  else {
    include_once 'database_ro.php';
  }

  
  #if ($events = $db->query('SELECT DISTINCT num, name FROM event_info ORDER BY num DESC')) {
  if ($events = $db->query('SELECT DISTINCT num, name, COUNT(event) as entrants
		  		FROM event_info LEFT JOIN entrant_info ON event = num
                                GROUP BY num ORDER BY num DESC; ')) {
    $event_select = "<option value=\"\">Please Select Event</option>";
    while($row = $events->fetchArray()) {
      #$ev=$row['num']; $nm=$row['name'];
      $ev=$row['num']; $nm=$row['name'] . " - " . $row['entrants'] . " Entrants";
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

  if ($ent_qry = $db->prepare("SELECT rowid, car_num, car_name, car_info, special, class, car_car, car_entrant, run_order FROM entrant_info WHERE event = :event ORDER BY $orderbyq")) {
    $ent_qry->bindValue(':event', intval($evt), SQLITE3_INTEGER);
    $entrants = $ent_qry->execute();
  }
  else
    $message = $message . "<BR><font color=\"#c00000\"> Database read failed\n<BR>" . $db->lastErrorMsg();

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Entrant Management</title>
    <link rel="stylesheet" href="style.css">
<?php
  $icon_file=dirname(__FILE__) . "/icons.inc";
  if (file_exists($icon_file))
    readfile($icon_file);
?>
  </head>
<body>
<center>
<h2>Entrant Management</h2>
  <form name="frmEntrant" id="frmEntrant" method="post" action="">
    <input type="hidden" name="update_list" value="" id="update_list">
<?php echo '<input type="hidden" name="orderby" value="' . $orderby . '" id="orderby">'; ?>
  <script type="text/javascript">
    function showEntrants(str){document.location = 'entrants.php?evt='+str;};
    function haveUpdate(){
            update_list="";
	    update_count=0;
	    up_buttons=document.getElementsByTagName("input");
	    for (let i = 0; i < up_buttons.length; i++) {
              if ( (up_buttons[i].disabled == false) && (up_buttons[i].value == "Update") ) {
		update_list=update_list + ";" + up_buttons[i].id;
		update_count++;
              }
            }
	    document.getElementById('update_list').value=update_list;
	    button1=document.getElementById('delete-ALL');
	    button2=document.getElementById('really-delete-ALL');
	    if (update_count > 1) {
		    button1.value="Update All";
		    button2.value="Yes";
	    } else {
		    button1.value="Delete ALL";
		    button2.value="Yes!";
	    }
    };
  </script>
<div align="center" style="padding-bottom:5px;">
 Entrants for <select name="EventList" style="width: 240px" onchange="showEntrants(this.value)">
   <?php echo $event_select;?>
 </select>
  <a href="events.php"> Return to Events </a>
</div>
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
  <table align=center border="2" cellpadding="4">
   <tr class="listheader">
      <td width=50 ondblclick="tb=document.getElementById('orderby');tb.value = 'Num';document.getElementById('frmEntrant').requestSubmit()">Num</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Title';document.getElementById('frmEntrant').requestSubmit()">Title</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Info';document.getElementById('frmEntrant').requestSubmit()">Info</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Special';document.getElementById('frmEntrant').requestSubmit()">Special</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Car';document.getElementById('frmEntrant').requestSubmit()">Car</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Class';document.getElementById('frmEntrant').requestSubmit()">Class</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Entrant';document.getElementById('frmEntrant').requestSubmit()">Entrant</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Order';document.getElementById('frmEntrant').requestSubmit()" title="NOTE: Alphabetic Order, NotNumeric">Order</td>
      <td> <input id="delete-ALL" type="button" name="delete-ALL" value="Delete ALL" onclick="document.getElementById('really-ALL').disabled=false" class="button"> </td>
      <td> <input id="really-ALL" type="button" name="really-ALL" value="Really?" onclick="document.getElementById('really-delete-ALL').disabled=false" class="button" disabled> </td>
      <td> <input id="really-delete-ALL" type="submit" name="really-delete-ALL" value="Yes!" formnovalidate formaction="?evt=<?php echo $evt;?>&id=ALL" class="button" disabled> </td>
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
    $safe_name=htmlspecialchars($row['car_name'],ENT_QUOTES);
    $safe_info=htmlspecialchars($row['car_info']);
    $safe_special=htmlspecialchars($row['special']);
    $safe_car=htmlspecialchars($row['car_car']);
    $safe_class=htmlspecialchars($row['class']);
    $safe_entrant=htmlspecialchars($row['car_entrant']);
    $safe_order=htmlspecialchars($row['run_order']);
    $row_id=$row['rowid'];
    echo "<td><input type=\"number\" placeholder=\"Num\" size=\"3\" name=\"EntNum-$row_id\" required min=\"1\" value=\"$safe_num\"";
    echo " class=\"input_number\" oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_num');haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigName-$row_id\" value=\"$safe_name\" id=\"OrigName-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Driver Name\" name=\"EntName-$row_id\" class=\"txtField\" required value=\"$safe_name\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigName-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigInfo-$row_id\" value=\"$safe_info\" id=\"OrigInfo-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Info\" size=\"8\" name=\"EntInfo-$row_id\" class=\"txtField\" value=\"$safe_info\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigInfo-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigSpecial-$row_id\" value=\"$safe_special\" id=\"OrigSpecial-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Special\" size=\"8\" name=\"EntSpecial-$row_id\" class=\"txtField\" value=\"$safe_special\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigSpecial-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigCar-$row_id\" value=\"$safe_car\" id=\"OrigCar-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Car\" name=\"EntCar-$row_id\" class=\"txtField\" value=\"$safe_car\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigCar-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigClass-$row_id\" value=\"$safe_class\" id=\"OrigClass-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Class\" size=\"41\" name=\"EntClass-$row_id\" class=\"txtField\" value=\"$safe_class\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigClass-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigEntrant-$row_id\" value=\"$safe_entrant\" id=\"OrigEntrant-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Entrant\" name=\"EntEntrant-$row_id\" class=\"txtField\" value=\"$safe_entrant\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigEntrant-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigOrder-$row_id\" value=\"$safe_order\" id=\"OrigOrder-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Order\" size=\"4\" name=\"EntOrder-$row_id\" class=\"txtField\" value=\"$safe_order\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigOrder-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Update\" tag=\"Update\" formaction=\"?evt=$evt&id=$row_id\" class=\"button\" disabled> </td>\n";
    echo "<td> <input id=\"delete-$row_id\" type=\"button\" name=\"delete-$row_id\" value=\"Delete\" onclick=\"document.getElementById('really-delete-$row_id').disabled=false\" class=\"button\"> </td>\n";
    echo "<td> <input id=\"really-delete-$row_id\" type=\"submit\" name=\"really-delete\" value=\"Yes!\" formnovalidate formaction=\"?evt=$evt&id=$row_id\" class=\"button\" disabled> </td>\n";
    echo "</tr>\n";
    $i++;
   }
   $ent_qry->close();
   if($i==0)
    $min_evt=1;
   else
    $min_evt=1 + $safe_num;
   if($i%2==0)
    $classname="class=\"evenRow\"";
   else
    $classname="class=\"oddRow\"";
   echo "<tr $classname>";
   $safe_num=""; $safe_name=""; $safe_info=""; $safe_special=""; $row_id=0; $safe_car=""; $safe_class=""; $safe_entrant=""; $safe_order="";
   #echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"EntNum-$row_id\" required min=\"$min_evt\" value=\"$min_evt\"";
   echo "<td><input type=\"number\" placeholder=\"Num\" size=\"4\" name=\"EntNum-$row_id\" required min=\"1\" value=\"$min_evt\"";
   echo " class=\"input_number\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Driver Name\" name=\"EntName-$row_id\" class=\"txtField\" value=\"$safe_name\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_name')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Info\" size=\"8\" name=\"EntInfo-$row_id\" class=\"txtField\" value=\"$safe_info\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_info')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Special\" size=\"8\" name=\"EntSpecial-$row_id\" class=\"txtField\" value=\"$safe_special\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_special')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Car\" name=\"EntCar-$row_id\" class=\"txtField\" value=\"$safe_car\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_car')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Class\" size=\"41\" name=\"EntClass-$row_id\" class=\"txtField\" value=\"$safe_class\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_class')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Entrant\" name=\"EntEntrant-$row_id\" class=\"txtField\" value=\"$safe_entrant\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_entrant')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Order\" size=\"4\" name=\"EntOrder-$row_id\" class=\"txtField\" value=\"$safe_order\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_order')\" ></td>\n";
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
