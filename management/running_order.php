<?php
  include_once 'database.php';

  $current = $db->query('select current_event, current_run from current_event, current_run;');
  if ($row = $current->fetchArray()) {
    $cur_evt = $row["current_event"];
    $cur_run = $row["current_run"];
  }

  if(count($_POST)>0) {
    if((isset($_POST['Change-Event'])) && ('Now' == $_POST['Change-Event']) && ($_POST["Event"] != $cur_evt)) {
      if ($post_qry = $db->prepare("UPDATE current_event set current_event=:num WHERE rowid=1")) {
        $post_qry->bindValue(':num', 0 + $db->escapeString($_POST["Event"]), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute()) {
          $message = "<font color=\"#00a000\"> Event Set Successfully";
	  $db->query('UPDATE current_run SET current_run = 0 WHERE rowid=1');
	  $db->query('DELETE FROM next_car');
	}
        else
          $message = "<font color=\"#c00000\"> Event Set failed for &nbsp; ".$_POST["Event"]."\n<BR>" . $db->lastErrorMsg();
      }
      else
        $message = "<font color=\"#c00000\"> Event Set failed for &nbsp; ".$_POST["Event"]."\n<BR>". $db->lastErrorMsg();
      $current = $db->query('select current_event, current_run from current_event, current_run;');
      if ($row = $current->fetchArray()) {
        $cur_evt = $row["current_event"];
        $cur_run = $row["current_run"];
      }
    }

    if((isset($_POST['NewRun-2'])) && ('Now' == $_POST['NewRun-2'])) {
      $db->query("BEGIN");
      $db->query("DELETE FROM next_car");
      if ($post_qry = $db->prepare("INSERT INTO next_car
             SELECT car_num, ROW_NUMBER() OVER ( ORDER BY car_num ) RowNum FROM entrant_info WHERE event=:event")) {
        $post_qry->bindValue(':event', 0 + $db->escapeString($_POST["Event"]), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute()) {
          $message = "<font color=\"#00a000\"> Entrants Loaded Successfully" ."\n<BR>";
          $db->query("UPDATE current_run SET current_run = current_run + 1 WHERE ROWID=1;");
          $db->query("COMMIT");
	}
        else {
          $message = "<font color=\"#c00000\"> Entrant Load failed \n<BR>". $db->lastErrorMsg();
          $db->query("ROLLBACK");
        }
      }
      else {
        $message = "<font color=\"#c00000\"> Entrant Load failed \n<BR>". $db->lastErrorMsg();
        $db->query("ROLLBACK");
      }

      $current = $db->query('select current_event, current_run from current_event, current_run;');
      if ($row = $current->fetchArray()) {
        $cur_evt = $row["current_event"];
        $cur_run = $row["current_run"];
      }
    }

    if((isset($_POST['submit'])) && (('Up' == $_POST['submit']) || ('Down' == $_POST['submit']))) {
      $move_vals=explode(":", $_POST['move_vals']);
      $db->query("BEGIN");
      if ($swap_qry = $db->prepare("UPDATE next_car SET ord = :new_ord WHERE rowid= :rowid")) {
	$swap_qry->bindValue(':rowid', $move_vals[0]);
	$swap_qry->bindValue(':new_ord', 999999);
	if ($update_result = $swap_qry->execute()) {
	  $swap_qry->bindValue(':rowid', $move_vals[2]);
	  $swap_qry->bindValue(':new_ord', $move_vals[3]);
	  if ($update_result = $swap_qry->execute()) {
	    $swap_qry->bindValue(':rowid', $move_vals[0]);
	    $swap_qry->bindValue(':new_ord', $move_vals[1]);
	    if ($update_result = $swap_qry->execute()) {
              $message = "<font color=\"#00a000\"> Entrants Move Successfully" ."\n<BR>";
              $db->query("COMMIT");
            }
	    else{
	      $message = "<font color=\"#c00000\"> Entrant Move failed \n<BR>". $db->lastErrorMsg();
	      $db->query("ROLLBACK");
            }
          }
	  else{
	    $message = "<font color=\"#c00000\"> Entrant Move failed \n<BR>". $db->lastErrorMsg();
	    $db->query("ROLLBACK");
          }
        }
	else{
	  $message = "<font color=\"#c00000\"> Entrant Move failed \n<BR>". $db->lastErrorMsg();
	  $db->query("ROLLBACK");
        }
      }
      else{
	$message = "<font color=\"#c00000\"> Entrant Move failed \n<BR>". $db->lastErrorMsg();
	$db->query("ROLLBACK");
      }
    }

    $current = $db->query('select current_event, current_run from current_event, current_run;');
    if ($row = $current->fetchArray()) {
      $cur_evt = $row["current_event"];
      $cur_run = $row["current_run"];
    }
  }


  if ($events = $db->query('SELECT num, name, COUNT() as entrants FROM event_info
  				LEFT JOIN entrant_info ON event = num
  				GROUP BY num ORDER BY num DESC; ')) {
    $event_select = "<option value=\"\">Please Select Date</option>";
    while($row = $events->fetchArray()) {
      $ev=$row['num']; $nm=$row['name'] . " - " . $row['entrants'] . " Entrants";
      if ($ev == $cur_evt)
        $event_select = "$event_select <option value=\"$ev\" selected>$nm</option>";
      else
        $event_select = "$event_select <option value=\"$ev\">$nm</option>";
    }
  }
  else
    $message = $message . "<BR><font color=\"#c00000\"> Database read failed\n<BR>" . $db->lastErrorMsg();

  $order = $db->query("SELECT next_car.rowid, next_car.car_num, car_name, ord FROM next_car
	  		LEFT JOIN entrant_info ON event=$cur_evt AND entrant_info.car_num = next_car.car_num ORDER BY ord");

?>

<html>
  <head>
    <title>Running Order</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<br>
  <br>
  <form name="frmRunOrd" method="post" action="">
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
    <div align="center" style="padding-bottom:5px;">
      Current Event
    <select name="Event" style="width: 240px" 
    oninput="document.getElementById('chEvt').disabled=(this.value == '<?php echo $cur_evt;?>')">
     <?php echo $event_select;?>
   </select>
   <input type="button" id="chEvt" name="chEvt" value="Change Event" onclick="document.getElementById('changeEvt').disabled=false" class="button" disabled>
   <input id="changeEvt" type="submit" name="Change-Event" value="Now" class="button" disabled>

  </div>

  <table align=center border="2" cellpadding="4">
   <tr class="listheader">
      <td width=50>Num</td>
      <td>Driver</td>
   <?php
   echo "<td>Run : $cur_run</td>";
   echo "</tr>";

   echo "<input type=\"hidden\" id=\"move_vals\" name=\"move_vals\" value=\"\">";

   $prev_row="";
   $prev_down="";
   $up_data="";
   $i=0;
   while(true) {
    $new_row = $order->fetchArray();
    if(is_array($prev_row)) {
      if($i%2==0)
       $classname="class=\"evenRow\"";
      else
       $classname="class=\"oddRow\"";
      echo "<tr $classname>";
      $row_id=$prev_row['rowid'];
      if (is_array($new_row)) {
	$down_disable = "";
        $down_data="$row_id:" . $new_row['ord'] . ":" . $new_row['rowid'] . ":" . $prev_row['ord'];
      }
      else {
	$down_disable = "disabled";
	$down_data="";
      }
      $safe_num=htmlspecialchars($prev_row['car_num']);
      $safe_name=htmlspecialchars($prev_row['car_name']);
      echo "<td align=\"center\">$safe_num</td>\n";
      echo "<td>$safe_name</td>\n";
      echo "<td> <input type=\"submit\" name=\"submit\" value=\"Down\" onclick=\"document.getElementById('move_vals').value='$down_data'\" class=\"button\" $down_disable> </td>\n";
      if ($i > 0) $up_disable = "";
      else $up_disable = "disabled";
      echo "<td> <input type=\"submit\" name=\"submit\" value=\"Up\" onclick=\"document.getElementById('move_vals').value='$up_data'\" class=\"button\" $up_disable> </td>\n";
      echo "</td></tr>\n";
      $up_data=$down_data;
      $i++;
    }
    if(!is_array($new_row)) break;
    $prev_row = $new_row;
   }
   ?>
  </table>
  <div align="center" style="padding-top:5px;">
   <a href="running_order.php"> Refresh </a> &nbsp; &nbsp; 
   <input type="button" id="NewRun-1" name="NewRun-1" value="Load Rew Run" onclick="document.getElementById('NewRun-2').disabled=false" class="button">
   <input type="submit" id="NewRun-2" name="NewRun-2" value="Now" class="button" disabled>
  </div>
  </form>
 </body>
</html>
