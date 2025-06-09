<?php
  if (isset($_GET['id']) && is_numeric($_GET['id']))
    $row_id = 0 + $_GET['id'];
  else
    $row_id = 0;

  $orderby = "Class";
  $orderbyq = "class";
  if (isset($_POST['orderby'])&&("" != $_POST['orderby'])) {
    //var_dump($_POST['orderby']);
    switch ($_POST['orderby']) {
      case "Class" :	$orderby = $_POST['orderby']; $orderbyq = "class" ; break;
      case "Info" :	$orderby = $_POST['orderby']; $orderbyq = "class_info, class" ; break;
      case "Record" :	$orderby = $_POST['orderby']; $orderbyq = "record, class" ; break;
    }
  }

  if(count($_POST)>0) {
    include_once 'database.php';
    if(isset($_POST['submit'])&&('Update' == $_POST['submit'])&&($row_id>0)) {
      if ($post_qry = $db->prepare("UPDATE class_info set class=:class, class_info=:class_info, record=:record WHERE rowid=:row")){
        $post_qry->bindValue(':class', htmlspecialchars_decode($_POST["Class-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':class_info', htmlspecialchars_decode($_POST["ClassInfo-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':record', htmlspecialchars_decode($_POST["ClassRecord-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':row', 0 + $row_id, SQLITE3_INTEGER);
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
      if ($post_qry = $db->prepare("INSERT INTO class_info(class, class_info, record) VALUES(:class, :class_info, :record)")){
        $post_qry->bindValue(':class', htmlspecialchars_decode($_POST["Class-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':class_info', htmlspecialchars_decode($_POST["ClassInfo-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':record', htmlspecialchars_decode($_POST["ClassRecord-$row_id"]), SQLITE3_TEXT);
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
      if ($post_qry = $db->prepare("DELETE FROM class_info WHERE class=:class AND class_info=:class_info AND record=:record AND rowid=:row")){
        $post_qry->bindValue(':class', htmlspecialchars_decode($_POST["Class-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':class_info', htmlspecialchars_decode($_POST["ClassInfo-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':record', htmlspecialchars_decode($_POST["ClassRecord-$row_id"]), SQLITE3_TEXT);
        $post_qry->bindValue(':row', 0 + $row_id, SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute())
          $message = "<font color=\"#00a000\"> Record Deleted Successfully for ".$_POST["Class-$row_id"]."\n<BR>";
        else
          $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["Class-$row_id"]."\n<BR>". $db->lastErrorMsg();
        $post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Record Delete failed for &nbsp; ".$_POST["Class-$row_id"]."\n<BR>". $db->lastErrorMsg();
    }
    if(isset($_POST['really-update-ALL'])&&('Yes!' == $_POST['really-update-ALL']) &&
	     isset($_GET['id']) && ('ALL' == $_GET['id']) &&
             isset($_POST['update_list'])&&("" != $_POST['update_list'])) {
      //var_dump($_POST);
      $update_list = explode (";submit-", $_POST['update_list']); 
      //var_dump($update_list);
      if (sizeof($update_list) > 1) {
        if ($post_qry = $db->prepare("UPDATE class_info set class=:class, class_info=:class_info, record=:record WHERE rowid=:row")){
	  $good=0;
	  $bad_message="";
          foreach($update_list as $row_id) {
	    if ($row_id < 1) continue;
            $post_qry->bindValue(':class', htmlspecialchars_decode($_POST["Class-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':class_info', htmlspecialchars_decode($_POST["ClassInfo-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':record', htmlspecialchars_decode($_POST["ClassRecord-$row_id"]), SQLITE3_TEXT);
            $post_qry->bindValue(':row', 0 + $row_id, SQLITE3_INTEGER);
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
  }
  else {
    include_once 'database_ro.php';
  }

  
  if ($ent_qry = $db->prepare("SELECT rowid, class, class_info, record FROM class_info ORDER BY $orderbyq")) {
    $class_infos = $ent_qry->execute();
  }
  else
    $message = $message . "<BR><font color=\"#c00000\"> Database read failed\n<BR>" . $db->lastErrorMsg();

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Class Infomation</title>
    <link rel="stylesheet" href="style.css">
<?php
  $icon_file=dirname(__FILE__) . "/icons.inc";
  if (file_exists($icon_file))
    readfile($icon_file);
?>
  </head>
<body>
<center>
<h2>Class Information</h2>
  <form name="frmClass_Info" id="frmClass_Info" method="post" action="">
    <input type="hidden" name="update_list" value="" id="update_list">
<?php echo '<input type="hidden" name="orderby" value="' . $orderby . '" id="orderby">'; ?>
  <script type="text/javascript">
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
	    button1=document.getElementById('update-ALL');
	    button2=document.getElementById('really-update-ALL');
	    if (update_count > 1) {
		    //button1.value="Update All";
		    button1.disabled=false;
		    button2.value="Yes!";
	    } else {
		    //button1.value="update ALL";
		    button1.disabled=true;
		    button2.value="Yes!";
	    }
    };
  </script>
<div align="center" style="padding-bottom:5px;">
  <a href="management.html"> Main Management Page </a>
</div/
<br>
  <br>
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
  <table align=center border="2" cellpadding="4">
   <tr class="listheader">
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Class';document.getElementById('frmClass_Info').requestSubmit()">Class</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Information';document.getElementById('frmClass_Info').requestSubmit()">Information</td>
      <td ondblclick="tb=document.getElementById('orderby');tb.value = 'Record';document.getElementById('frmClass_Info').requestSubmit()">Record</td>
      <td colspan="2"> <input id="update-ALL" type="button" name="update-ALL" value="Update ALL" onclick="document.getElementById('really-update-ALL').disabled=false" class="button" disabled> </td>
      <td> <input id="really-update-ALL" type="submit" name="really-update-ALL" value="Yes!" formnovalidate formaction="?&id=ALL" class="button" disabled> </td>
   </tr>
   <?php

   $class_size=38;
   $info_size=34;

   $i=0;
   while(isset($class_infos) && $row = $class_infos->fetchArray()) {
    if($i%2==0)
     $classname="class=\"evenRow\"";
    else
     $classname="class=\"oddRow\"";
    echo "<tr $classname>";
    // $safe_name=htmlspecialchars($row['car_name'],ENT_QUOTES);
    $safe_class=htmlspecialchars($row['class']);
    $safe_class_info=htmlspecialchars($row['class_info']);
    $safe_record=htmlspecialchars($row['record']);
    $row_id=$row['rowid'];

    echo "<input type=\"hidden\" name=\"OrigClass-$row_id\" value=\"$safe_class\" id=\"OrigClass-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Class\" size=\"$class_size\" name=\"Class-$row_id\" class=\"txtField\" value=\"$safe_class\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigClass-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigClassInfo-$row_id\" value=\"$safe_class_info\" id=\"OrigClassInfo-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Information\" size=\"$info_size\" name=\"ClassInfo-$row_id\" class=\"txtField\" value=\"$safe_class_info\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigClassInfo-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<input type=\"hidden\" name=\"OrigRecord-$row_id\" value=\"$safe_record\" id=\"OrigRecord-$row_id\">";
    echo "<td><input type=\"text\" placeholder=\"Record\" size=\"4\" name=\"ClassRecord-$row_id\" class=\"txtField\" value=\"$safe_record\"";
    echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == document.getElementById('OrigRecord-$row_id').value);haveUpdate()\" ></td>\n";

    echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Update\" tag=\"Update\" formaction=\"?id=$row_id\" class=\"button\" disabled> </td>\n";
    echo "<td> <input id=\"delete-$row_id\" type=\"button\" name=\"delete-$row_id\" value=\"Delete\" onclick=\"document.getElementById('really-delete-$row_id').disabled=false\" class=\"button\"> </td>\n";
    echo "<td> <input id=\"really-delete-$row_id\" type=\"submit\" name=\"really-delete\" value=\"Yes!\" formnovalidate formaction=\"?id=$row_id\" class=\"button\" disabled> </td>\n";
    echo "</tr>\n";
    $i++;
   }
   $ent_qry->close();
   if($i%2==0)
    $classname="class=\"evenRow\"";
   else
    $classname="class=\"oddRow\"";
   echo "<tr $classname>";
   $row_id=0; $safe_class=""; $safe_class_info=""; $safe_record="";
   echo "<td><input type=\"text\" placeholder=\"Class\" size=\"$class_size\" name=\"Class-$row_id\" class=\"txtField\" value=\"$safe_class\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_class')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Information\" size=\"$info_size\" name=\"ClassInfo-$row_id\" class=\"txtField\" value=\"$safe_class_info\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_class_info')\" ></td>\n";
   echo "<td><input type=\"text\" placeholder=\"Record\" size=\"4\" name=\"ClassRecord-$row_id\" class=\"txtField\" value=\"$safe_record\"";
   echo " oninput=\"document.getElementById('submit-$row_id').disabled=(this.value == '$safe_record')\" ></td>\n";
   echo "<td> <input id=\"submit-$row_id\" type=\"submit\" name=\"submit\" value=\"Create\" formaction=\"?id=$row_id\" class=\"button\" disabled> </td>\n";
   echo "</td></tr>\n";
   ?>
  </table>
  </form>
 </body>
</html>
