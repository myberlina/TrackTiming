<?php
  include_once 'database.php';

  if (isset($_GET['evt']))
    $evt = 0 + $_GET['evt'];
  else
    $evt = 0;

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

  if (($evt<1)||(count($_POST)<1)||(('Upload'!=$_POST['submit'])&&('Submit'!=$_POST['submit'])&&('Rescan'!=$_POST['submit'])))
    die;

  $message="";

  if(($evt>0)&&(count($_POST)>0)) {
    if('Submit' == $_POST['submit']) {
      $can_load=true;
      $i=0; while (isset($_POST["col-$i"])) {
        if (($_POST["col-$i"] == 'number') && ! isset($col_num)) $col_num = $i;
        if (($_POST["col-$i"] == 'name1') && ! isset($col_name1)) $col_name1 = $i;
        if (($_POST["col-$i"] == 'name2') && ! isset($col_name2)) $col_name2 = $i;
        if (($_POST["col-$i"] == 'name3') && ! isset($col_name3)) $col_name3 = $i;
        if (($_POST["col-$i"] == 'info1') && ! isset($col_info1)) $col_info1 = $i;
        if (($_POST["col-$i"] == 'info2') && ! isset($col_info2)) $col_info2 = $i;
        if (($_POST["col-$i"] == 'info3') && ! isset($col_info3)) $col_info3 = $i;
        if (($_POST["col-$i"] == 'special') && ! isset($col_special)) $col_special = $i;
        if (($_POST["col-$i"] == 'class') && ! isset($col_class)) $col_class = $i;
        if (($_POST["col-$i"] == 'car') && ! isset($col_car)) $col_car = $i;
        if (($_POST["col-$i"] == 'entrant') && ! isset($col_entrant)) $col_entrant = $i;
        if (($_POST["col-$i"] == 'order') && ! isset($col_order)) $col_order = $i;
	$i=$i+1;
      }
      if (! isset($col_name1)) {
	$message = "<p style=\"color:red\"> No column selected as name1\n</p><BR>";
	$can_load=false;
      }
      if (! isset($col_num)){
	if ($next_num = $db->querySingle("select max(car_num) from entrant_info where event = $evt"))
	  $next_num = $next_num + 1;
	else
	  $next_num = 1;
      }
      if (! file_exists($save_file)) {
	$message = $message . "<p style=\"color:red\"> No column selected as name1\n</p><BR>";
	$can_load=false;
      }
      else {
        if (! ($handle = fopen($save_file, "r"))) {
	  $message = $message . "<p style=\"color:red\"> Could not open saved data file\n</p><BR>";
	  $can_load=false;
	}
	else
	  if (! ($upload_qry = $db->prepare("INSERT INTO entrant_info(event, car_num, car_name, car_info, special, class, car_car, car_entrant, run_order) VALUES(:event, :num, :name, :info, :special, :class, :car, :entrant, :order)"))) {
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
	    if (isset($col_num)) {
	      $num = $row[$col_num];
	      if ($num < 1) continue;
	    }else {
	      $num = $next_num++;
	    }

	    if (isset($col_name1)) {
	      $name=$row[$col_name1];
	      if (isset($col_name2)) {
	        $name=$name . " " . $row[$col_name2];
	        if (isset($col_name3)) {
	          $name=$name . " " . $row[$col_name3];
	        }
	      }
	    }

            $upload_qry->bindValue(':event', 0 + $db->escapeString($evt), SQLITE3_INTEGER);
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
	    
	    if (isset($col_special)) {
	      $special=$row[$col_special];
	    }
	    else $special="";
	    
	    if (isset($col_class)) {
	      $class=$row[$col_class];
	    }
	    else $class="";
	    
	    if (isset($col_car)) {
	      $car=$row[$col_car];
	    }
	    else $car="";
	    
	    if (isset($col_entrant)) {
	      $entrant=$row[$col_entrant];
	    }
	    else $entrant="";
	    
	    if (isset($col_order)) {
	      $order=$row[$col_order];
	    }
	    else $order="";
	    
            $upload_qry->bindValue(':event', 0 + $db->escapeString($evt), SQLITE3_INTEGER);
            $upload_qry->bindValue(':num', 0 + $db->escapeString($num), SQLITE3_INTEGER);
            $upload_qry->bindValue(':name', $name, SQLITE3_TEXT);
            $upload_qry->bindValue(':info', $info, SQLITE3_TEXT);
            $upload_qry->bindValue(':special', $special, SQLITE3_TEXT);
            $upload_qry->bindValue(':class', $class, SQLITE3_TEXT);
            $upload_qry->bindValue(':car', $car, SQLITE3_TEXT);
            $upload_qry->bindValue(':entrant', $entrant, SQLITE3_TEXT);
            $upload_qry->bindValue(':order', $order, SQLITE3_TEXT);
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
	  $message = $message . "<p style=\"color:green\"> Successfully inserted $inserted entrants</p>";
	else
	  $message = $message . "<p style=\"color:red\"> Successfully inserted $inserted entrants</p>";
	if ($failed > 0)
	  $message = $message . "<p style=\"color:red\"> Failed to insert $failed entrants</p>";
	$upload_qry->close();
      }
      if (isset($handle)) fclose($handle);
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
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Entrants File Upload</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<form name="frmEntrantUpload" method="post" action="">
  <div align="center" style="padding-bottom:5px;">
   Entrants for <select name="EventList" style="width: 240px">
     <?php echo $event_select;?>
   </select>
  </div>
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
   <input id="event-return" type="button" name="return" value="Return To Entrants" onclick="document.location = 'entrants.php?evt=<?php echo $evt?>'" >
  </div>
  <?php
  if (isset($save_file) && file_exists($save_file)){
    echo "<BR>Processing file '" . $nice_name . "'<br>";
    echo "<input type=\"hidden\" name=\"save_name\" value=\"$save_name\">";
    echo "<input type=\"hidden\" name=\"nice_name\" value=\"$nice_name\">";
    $col_type_sel='
	<option value="ignore">Ignore</option>
	<option value="number">Number</option>
	<option value="name1">Name1</option>
	<option value="name2">Name2</option>
	<option value="name3">Name3</option>
	<option value="info1">Info1</option>
	<option value="info2">Info2</option>
	<option value="info3">Info3</option>
	<option value="special">Special</option>
	<option value="class">Class</option>
	<option value="car">Car</option>
	<option value="entrant">Entrant</option>
	<option value="order">Run Order</option>
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
