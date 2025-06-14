<?php
  include_once 'database.php';

  $save_dir="/var/tmp/Timing_uploads/";
  $good_row[0] = 1;
  $bad_row[0] = 1;

  if (is_array($_FILES) && isset($_FILES["Upload_CSV"]) && is_array($_FILES["Upload_CSV"]) 
      && (0 == $_FILES["Upload_CSV"]["error"])){
    $save_name=basename($_FILES["Upload_CSV"]["tmp_name"]) . "Keep_Me";
    $save_file=$save_dir . $save_name;
    if (!file_exists($save_dir)) mkdir($save_dir);
    $nice_name=basename($_FILES["Upload_CSV"]["name"]);
    move_uploaded_file($_FILES["Upload_CSV"]["tmp_name"], $save_file);
  }
  else {
    if (isset($_POST['nice_name'])) $nice_name = $_POST['nice_name'];
    if (isset($_POST['save_name'])) {
      $save_name = $_POST['save_name'];
      $save_file = $save_file=$save_dir . $save_name;
    }
    if (isset($_POST['separator'])) $form_sep = $_POST['separator'];
    if (isset($_POST['data_start'])) $data_start = $_POST['data_start'];
  }

  if ((count($_POST)<1)||(('Upload'!=$_POST['submit'])&&('Submit'!=$_POST['submit'])&&('Rescan'!=$_POST['submit'])))
    die;

  $message="";

  if(count($_POST)>0) {
    if('Submit' == $_POST['submit']) {
      $can_load=true;
      $i=0; while (isset($_POST["col-$i"])) {
        if (($_POST["col-$i"] == 'class1') && ! isset($col_class1)) $col_class1 = $i;
        if (($_POST["col-$i"] == 'class2') && ! isset($col_class2)) $col_class2 = $i;
        if (($_POST["col-$i"] == 'class3') && ! isset($col_class3)) $col_class3 = $i;
        if (($_POST["col-$i"] == 'info1') && ! isset($col_info1)) $col_info1 = $i;
        if (($_POST["col-$i"] == 'info2') && ! isset($col_info2)) $col_info2 = $i;
        if (($_POST["col-$i"] == 'info3') && ! isset($col_info3)) $col_info3 = $i;
        if (($_POST["col-$i"] == 'record') && ! isset($col_record)) $col_record = $i;
	$i=$i+1;
      }
      if (! isset($col_class1)) {
	$message = "<p style=\"color:red\"> No column selected as class1\n</p><BR>";
	$can_load=false;
      }
      if (! file_exists($save_file)) {
	$message = $message . "<p style=\"color:red\"> Cannot find uploaded file\n</p><BR>";
	$can_load=false;
      }
      else {
        if (! ($handle = fopen($save_file, "r"))) {
	  $message = $message . "<p style=\"color:red\"> Could not open saved data file\n</p><BR>";
	  $can_load=false;
	}
	else
	  if (! ($upload_qry = $db->prepare("INSERT OR REPLACE INTO class_info(class, class_info, record) VALUES(:class, :info, :record)"))) {
	    $message = $message . "<p style=\"color:red\"> Could not create base INSERT query\n</p><BR>";
	    $can_load=false;
	  }
      }
      if ($can_load) {
	$inserted=0;
	$failed=0;
	$file_row=0;
        while ($row = fgetcsv($handle, 0, "$form_sep")) {
	  $file_row=$file_row+1;
	  if ($file_row >= $data_start) {
	    if (isset($col_class1)) {
	      $class=$row[$col_class1];
	      if (isset($col_class2)) {
	        $class=$class . " " . $row[$col_class2];
	        if (isset($col_class3)) {
	          $class=$class . " " . $row[$col_class3];
	        }
	      }
	    }
	    if (isset($col_info1)) {
	      $info=$row[$col_info1];
	      if (isset($col_info2)) {
	        $info=$info . " " . $row[$col_info2];
	        if (isset($col_info3)) {
	          $info=$info . " " . $row[$col_info3];
	        }
	      }
	    }
	    else $info="";
	    
	    if (isset($col_record)) {
	      $record=$row[$col_record];
	    }
	    else $record="";
	    
            $upload_qry->bindValue(':class', $class, SQLITE3_TEXT);
            $upload_qry->bindValue(':info', $info, SQLITE3_TEXT);
            $upload_qry->bindValue(':record', $record, SQLITE3_TEXT);
	    if ($update_result = $upload_qry->execute()) {
	      $inserted++;
	      $good_row[$file_row] = 1;
            }
	    else {
	      $failed++;
	      $bad_row[$file_row] = 1;
            }
          }
        }
	if ($inserted > 0)
	  $message = $message . "<p style=\"color:green\"> Successfully inserted $inserted class_info</p>";
	else
	  $message = $message . "<p style=\"color:red\"> Successfully inserted $inserted class_info</p>";
	if ($failed > 0)
	  $message = $message . "<p style=\"color:red\"> Failed to insert $failed class_info</p>";
	$upload_qry->close();
      }
      if (isset($handle)) fclose($handle);
    }
  }
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Class Info File Upload</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<form name="frmClassUpload" method="post" action="">
  <br>
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>

  <br>
  <div align="center">
   First Row with Data is <select name="data_start">
        <option value="1">Row One</option>
        <option value="2">Row Two</option>
        <option value="3">Row Three</option>
        <option value="4">Row Four</option>
   </select> &nbsp; &nbsp; &nbsp; &nbsp;
   <input id="submit-csv" type="submit" name="submit" value="Submit" >
   &nbsp; &nbsp; &nbsp; &nbsp;
   <input id="class-return" type="button" name="return" value="Return To Class Info" onclick="document.location = 'class_info.php'" >
  </div>
  <?php
  if (isset($save_file) && file_exists($save_file)){
    echo "<BR>Processing file '" . $nice_name . "'<br>";
    echo "<input type=\"hidden\" name=\"save_name\" value=\"$save_name\">";
    echo "<input type=\"hidden\" name=\"nice_name\" value=\"$nice_name\">";
    $col_type_sel='
	<option value="ignore">Ignore</option>
	<option value="class1">Class1</option>
	<option value="class2">Class2</option>
	<option value="class3">Class3</option>
	<option value="info1">Info1</option>
	<option value="info2">Info2</option>
	<option value="info3">Info3</option>
	<option value="record">Record</option>
    ';
    if ($handle = fopen($save_file, "r")) {
      $sep=''; $sep_count=0;
      if (isset($form_sep))
        $seps_to_try=array("$form_sep");
      else
        $seps_to_try=array(',', "\t", ':', ';', '|');
      $j=0;
      while (($j++ < 10) && $line=fgets($handle)) {
        foreach($seps_to_try as $try) {
          if ($row = str_getcsv($line, "$try")) {
	    if ($sep_count < count($row)) {
	      $sep = $try;
	      $sep_count = count($row);
            }
          }
        }
      }
      fseek($handle, 0, SEEK_SET);
      if ("\t" == "$sep")
        echo "Found $sep_count columns using &lt;TAB&gt; as a separator. &nbsp; &nbsp;";
      else
        echo "Found $sep_count columns using $sep as a separator. &nbsp; &nbsp;";
      #echo "<input type=\"hidden\" name=\"separator\" value=\"$sep\">";
      echo "\n<select name=\"separator\">";
      $all_seps=array(',', "\t", ':', ';', '|', ' ');
      foreach($all_seps as $try) {
	if ($try == $sep) $selected="selected";
	else $selected="";
	if ($try == "\t") $sep_disp="&lt;TAB&gt;";
	elseif ($try == " ") $sep_disp="&lt;SPACE&gt;";
	else $sep_disp=$try;
	echo "<option value=\"$try\" $selected> &nbsp; $sep_disp &nbsp; </option>";
      }
      echo "</select>\n";
      echo "<input id=\"submit-rescan\" type=\"submit\" name=\"submit\" value=\"Rescan\"><br>";
      echo "<br><table class=\"tight_table\">";
      if ($row = fgetcsv($handle, 0, "$sep")) {
	echo "<tr><td>Row</td>";
	$i=0;
	while($i < $sep_count) {
	  echo "<td class=\"tight_table\"><select name=\"col-$i\"> $col_type_sel </select></td>";
	  $i=$i+1;
        }
	echo "</tr>\n";
      }
      fseek($handle, 0, SEEK_SET);
      $j=1;
      while ($row = fgetcsv($handle, 0, "$sep")) {
	if ($j%2 == 0) $class="tight_even";
	else $class="tight_odd";
	if (isset($good_row[$j]) && ($good_row[$j] == 1))
	  echo "<tr><td class=\"$class fg_green\">$j</td>";
	else if (isset($bad_row[$j]) && ($bad_row[$j] == 1))
	  echo "<tr><td class=\"$class fg_red\">$j</td>";
	else
	  echo "<tr><td class=\"$class fg_blue\">$j</td>";
	#$i=0;
	foreach($row as $cell) {
          echo "<td class=\"$class\">$cell</td>";
	  #echo "<input type=\"hidden\" name=\"val_$j-$i\" value=\"$cell\">";
	  #$i=$i+1;
        }
	echo "</tr>";
	$j=$j+1;
      }
      echo "</table>";
      fclose($handle);
    }
  }

  ?>
  </form>
 </body>
</html>
