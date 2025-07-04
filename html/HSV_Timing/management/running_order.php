<?php
  if(count($_POST)>0) {
    include_once 'database.php';
    //var_dump($_POST);

    $current = $db->query('select current_event, current_run from current_event, current_run;');
    if ($row = $current->fetchArray()) {
      $cur_evt = $row["current_event"];
      $cur_run = $row["current_run"];
      $prev_evt = $cur_evt;
      $prev_run = $cur_run;
    }

    $refetch_current_run = false;
    if(isset($_POST['Change-Event']) && ('Now' == $_POST['Change-Event']) && ($_POST["Event"] != $cur_evt)) {
      if ($post_qry = $db->prepare("UPDATE current_event set current_event=:num WHERE rowid=1")) {
        $post_qry->bindValue(':num', intval($db->escapeString($_POST["Event"])), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute()) {
          $message = "<font color=\"#00a000\"> Event Set Successfully";
	  $db->query('UPDATE current_run SET current_run = 1 WHERE rowid=1');
	  //$db->query('DELETE FROM next_car');
	  $_POST['NewRun-2'] = 'Now';
          $_POST['NewRun-1'] = "Load";
	}
        else
          $message = "<font color=\"#c00000\"> Event Set failed for &nbsp; ".$_POST["Event"]."\n<BR>" . $db->lastErrorMsg();
	$post_qry->close();
      }
      else
        $message = "<font color=\"#c00000\"> Event Set failed for &nbsp; ".$_POST["Event"]."\n<BR>". $db->lastErrorMsg();
      $refetch_current_run = true;
    }

    if(isset($_POST['NewRun-1'])) $op = $_POST['NewRun-1'];
    else $op = "";
    if(isset($_POST['NewRun-2']) && ('Now' == $_POST['NewRun-2']) && ($op == 'SetOrder')) {
      if ($db->query("UPDATE set_order SET run_order = new_order;")) {
        $message = "<font color=\"#00a000\"> Run Order Updated" ."\n<BR>";
      }
      else {
        $message = "<font color=\"#c00000\"> Run Order Update Failed \n<BR>". $db->lastErrorMsg();
      }
    }
    elseif(isset($_POST['NewRun-2']) && ('Now' == $_POST['NewRun-2']) && ($op != '')) {
      $db->query("BEGIN");
      $db->query("DELETE FROM next_car"); # True for all operations
      if (($op == "NR-Load") || ($op == "Load")) {
        if ($op == "NR-Load")
	  $cur_run++;
        $load_qry = "INSERT INTO next_car SELECT entrant_info.car_num, ROW_NUMBER() OVER ( ORDER BY entrant_info.run_order, entrant_info.car_num ) RowNum
		FROM entrant_info 
                LEFT JOIN finish_time ON finish_time.car_num = entrant_info.car_num AND finish_time.event = entrant_info.event AND finish_time.run=:run
                WHERE entrant_info.event=:event AND finish_time.car_num IS NULL";
        #  $load_qry = "INSERT INTO next_car SELECT car_num, ROW_NUMBER() OVER ( ORDER BY car_num ) RowNum FROM entrant_info WHERE event=:event";
        if ($post_qry = $db->prepare($load_qry)) {
          $post_qry->bindValue(':event', intval($db->escapeString($_POST["Event"])), SQLITE3_INTEGER);
          $post_qry->bindValue(':run', intval($cur_run), SQLITE3_INTEGER);
          if ($update_result = $post_qry->execute()) {
            #$message = "<font color=\"#00a000\"> Entrants Loaded Successfully" ."\n<BR>";
	    if ($op == "NR-Load") {
	      $db->query("UPDATE current_run SET current_run = current_run + 1 WHERE ROWID=1;");
              $refetch_current_run = true;
	    }
            $db->query("COMMIT");
	  }
          else {
            $message = "<font color=\"#c00000\"> Entrant Load failed \n<BR>". $db->lastErrorMsg();
            $db->query("ROLLBACK");
          }
	  $post_qry->close();
        }
        else {
          $message = "<font color=\"#c00000\"> Entrant Load failed \n<BR>". $db->lastErrorMsg();
          $db->query("ROLLBACK");
        }
      }
      elseif ($op == "NewRun") {
        if ($db->query("UPDATE current_run SET current_run = current_run + 1 WHERE ROWID=1;")) {
          $db->query("COMMIT");
          $refetch_current_run = true;
	}
        else {
          $message = "<font color=\"#c00000\"> Run Change failed \n<BR>". $db->lastErrorMsg();
          $db->query("ROLLBACK");
        }
      }
      elseif ($op == "PrevRun") {
        if ($db->query("UPDATE current_run SET current_run = current_run - 1 WHERE ROWID=1 AND current_run > 1")) {
          $db->query("COMMIT");
          $refetch_current_run = true;
	}
        else {
          $message = "<font color=\"#c00000\"> Run Change failed \n<BR>". $db->lastErrorMsg();
          $db->query("ROLLBACK");
        }
      }
      elseif ($op == "Clear") {
        $db->query("COMMIT");
      }
    }

    if((isset($_POST['submit'])) && (('Up' == $_POST['submit']) || ('Dn' == $_POST['submit']))) {
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
              #$message = "<font color=\"#00a000\"> Entrants Move Successfully" ."\n<BR>";
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
	$swap_qry->close();
      }
      else{
	$message = "<font color=\"#c00000\"> Entrant Move failed \n<BR>". $db->lastErrorMsg();
	$db->query("ROLLBACK");
      }
    }

    if((!isset($_POST['submit'])) && isset($_POST['move_vals']) && ("Drag:"==substr($_POST['move_vals'],0,5))) {
      $move_vals=explode(":", $_POST['move_vals']);
      $orig_row=$move_vals[1];
      $orig_ord=$move_vals[2];
      $dest_row=$move_vals[3]; // Dont really care about this
      $dest_ord=$move_vals[4];
      $max_order=$_POST['last_ord'] + 3;
      if ($orig_ord < $dest_ord) {
	// Shuffle everyone up the order, plus an offset past the end to avoid UNIQUE constraint
        $qry = "UPDATE next_car SET ord = ord - 1 + :offset WHERE ord <= :dest_ord AND ord > :orig_ord";
        $offset=$max_order + ( $max_order - $orig_ord );
      }
      else {
        // Shuffle everyone down the order, plus an offset past the end to avoid UNIQUE constraint
        $qry = "UPDATE next_car SET ord = ord + 1 + :offset WHERE ord >= :dest_ord AND ord < :orig_ord";
        $offset=$max_order + ( $max_order - $dest_ord );
      }
      $db->query("BEGIN");
      if ( ($set_qry = $db->prepare("UPDATE next_car SET ord = :new_ord WHERE rowid= :rowid")) &&
           ($back_qry = $db->prepare("UPDATE next_car SET ord = ord - :offset WHERE ord  >= :max_order")) &&
           ($shuffle_qry = $db->prepare($qry)) ) {
	$shuffle_qry->bindValue(':dest_ord', $dest_ord);
	$shuffle_qry->bindValue(':orig_ord', $orig_ord);
	$shuffle_qry->bindValue(':offset', $offset);
	if ($update_result = $shuffle_qry->execute()) {
	  $set_qry->bindValue(':rowid', $orig_row);
	  $set_qry->bindValue(':new_ord', $dest_ord);
	  if ($update_result = $set_qry->execute()) {
	    $back_qry->bindValue(':offset', $offset);
	    $back_qry->bindValue(':max_order', $max_order - 1);
	    if ($update_result = $back_qry->execute()) {
              #$message = "<font color=\"#00a000\"> Entrants Move Successfully" ."\n<BR>";
              $db->query("COMMIT");
            }
	    else{
	      $message = "<font color=\"#c00000\"> Entrant Move failed Shuffle Back\n<BR>". $db->lastErrorMsg();
	      $db->query("ROLLBACK");
            }
          }
	  else{
	    $message = "<font color=\"#c00000\"> Entrant Move failed Assign\n<BR>". $db->lastErrorMsg();
	    $db->query("ROLLBACK");
          }
        }
	else{
	  $message = "<font color=\"#c00000\"> Entrant Move failed First Shuffle\n<BR>". $db->lastErrorMsg();
	  $db->query("ROLLBACK");
        }
	$set_qry->close();
	$back_qry->close();
	$shuffle_qry->close();
      }
      else{
	$message = "<font color=\"#c00000\"> Entrant Move failed prepairing queries\n<BR>". $db->lastErrorMsg();
	$db->query("ROLLBACK");

      }
    }

    if((isset($_POST['submit'])) && ('P3' == $_POST['submit'])) {
      $move_vals=explode(":", $_POST['move_vals'] . ":" . $_POST["last_ord"]);
      $rowid=$move_vals[0];
      $p2_ord=$move_vals[1];
      $db->query("BEGIN");
      if ( ($move1_qry = $db->prepare("UPDATE next_car SET ord = ord-1 WHERE ord < :p2_ord")) &&
           ($move2_qry = $db->prepare("UPDATE next_car SET ord = ord-1 WHERE ord = :p2_ord")) &&
           ($move3_qry = $db->prepare("UPDATE next_car SET ord = :new_ord WHERE rowid= :rowid")) ) {
	$move1_qry->bindValue(':p2_ord', $p2_ord);
	if ($update_result = $move1_qry->execute()) {
	  $move2_qry->bindValue(':p2_ord', $p2_ord);
	  if ($update_result = $move2_qry->execute()) {
	    $move3_qry->bindValue(':rowid', $move_vals[0]);
	    $move3_qry->bindValue(':new_ord', $move_vals[1]);
	    if ($update_result = $move3_qry->execute()) {
              #$message = "<font color=\"#00a000\"> Entrants Move Successfully" ."\n<BR>";
              $db->query("COMMIT");
            }
	    else{
	      $message = "<font color=\"#c00000\"> Entrant Move failed Assign to P3\n<BR>". $db->lastErrorMsg();
	      $db->query("ROLLBACK");
            }
          }
	  else{
	    $message = "<font color=\"#c00000\"> Entrant Move Shuffle Move P2\n<BR>". $db->lastErrorMsg();
	    $db->query("ROLLBACK");
          }
        }
	else{
	  $message = "<font color=\"#c00000\"> Entrant Move failed Shuffle P1\n<BR>". $db->lastErrorMsg();
	  $db->query("ROLLBACK");
        }
	$move3_qry->close();
	$move2_qry->close();
	$move1_qry->close();
      }
      else{
	$message = "<font color=\"#c00000\"> Entrant Move failed prepairing queries\n<BR>". $db->lastErrorMsg();
	$db->query("ROLLBACK");

      }
    }

    if((isset($_POST['submit'])) && (('Top' == $_POST['submit']) || ('Bot' == $_POST['submit']))) {
      $move_vals=explode(":", $_POST['move_vals'] . ":" . $_POST["last_ord"]);
      if ($swap_qry = $db->prepare("UPDATE next_car SET ord = :new_ord WHERE rowid= :rowid")) {
	$swap_qry->bindValue(':rowid', $move_vals[0]);
	$swap_qry->bindValue(':new_ord', $move_vals[1]);
	if ($update_result = $swap_qry->execute()) {
          #$message = "<font color=\"#00a000\"> Entrants Move Successfully" ."\n<BR>";
        }
	else{
	  $message = "<font color=\"#c00000\"> Entrant Move failed \n<BR>". $db->lastErrorMsg();
        }
	$swap_qry->close();
      }
      else{
	$message = "<font color=\"#c00000\"> Entrant Move failed \n<BR>". $db->lastErrorMsg();
      }
    }

    if((isset($_POST['ReallyAdd'])) && ('Add' == $_POST['ReallyAdd'])) {
      if ($post_qry = $db->prepare("INSERT INTO next_car VALUES (:car, :ord)")) {
        $post_qry->bindValue(':car', intval($db->escapeString($_POST["AddEntrant"])), SQLITE3_INTEGER);
        $post_qry->bindValue(':ord', intval($db->escapeString($_POST["last_ord"])), SQLITE3_INTEGER);
        if ($update_result = $post_qry->execute()) {
          $message = "<font color=\"#00a000\"> Entrant Re-added Successfully" ."\n<BR>";
	}
        else {
          $message = "<font color=\"#c00000\"> Entrant Re-add failed \n<BR>". $db->lastErrorMsg();
        }
	$post_qry->close();
      }
      else {
        $message = "<font color=\"#c00000\"> Entrant Re-add failed \n<BR>". $db->lastErrorMsg();
      }
    }

    if ($refetch_current_run) {
      $current = $db->query('select current_event, current_run from current_event, current_run;');
      if ($row = $current->fetchArray()) {
        $cur_evt = $row["current_event"];
        $cur_run = $row["current_run"];
      }
    }
  }
  else {
    include_once 'database_ro.php';
    $current = $db->query('select current_event, current_run from current_event, current_run;');
    if ($row = $current->fetchArray()) {
      $cur_evt = $row["current_event"];
      $cur_run = $row["current_run"];
    }

  }


  if ($cur_evt == 0)
    $event_select = "<option value=\"0\" selected> -- Select an event -- </option>";
  else
    $event_select = "";
  if ($events = $db->query('SELECT num, name, COUNT(event) as entrants FROM event_info
  				LEFT JOIN entrant_info ON event = num
  				GROUP BY num ORDER BY num DESC; ')) {
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


  if ($entrant_qry = $db->prepare("SELECT car_num, car_name FROM entrant_info WHERE event=:event")) {
    $entrant_qry->bindValue(':event', intval($cur_evt), SQLITE3_INTEGER);
    if ($entrants_res = $entrant_qry->execute()) {
      while ($row = $entrants_res->fetchArray()) {
        $entrants[$row['car_num']] = $row['car_name'];
      }
    }
    $entrant_qry->close();
  }

  $order = $db->query("SELECT next_car.rowid, next_car.car_num, car_name, ord FROM next_car
	  		LEFT JOIN entrant_info ON event=$cur_evt AND entrant_info.car_num = next_car.car_num ORDER BY ord");
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Running Order</title>
    <link rel="stylesheet" href="style.css">
<?php
    if ((isset($prev_evt) && ($prev_evt != $cur_evt)) || (isset($prev_run) && ($prev_run != $cur_run)))
      echo "<script type=\"text/javascript\"> window.top.location.reload(); </script>\n";
?>
    <script type="text/javascript">
      function dragstartHandler(ev) {
        ev.dataTransfer.setData("text/plain", ev.target.id);
	block_refresh=1;
      }
      function dragoverHandler(ev) {
        const start = ev.dataTransfer.getData("text/plain");
	end = ev.currentTarget.id;
	if ( start != end ) {
          ev.preventDefault();
	}
      }
      function dropHandler(ev) {
        ev.preventDefault();
        const start = ev.dataTransfer.getData("text/plain");
	end = ev.target.id;
	if ( start != end ) {
	  document.getElementById('move_vals').value="Drag:" + start + ":" + ev.target.id;
	  document.getElementById('frmRunOrd').requestSubmit();
	}
	block_refresh=0;
      }
    </script>
  </head>
<body>
  <form name="frmRunOrd" id="frmRunOrd" method="post" action="">
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
    <div align="center" style="padding-bottom:5px;">
      Current Event
    <select name="Event" style="width: 240px" 
    onfocus="block_refresh=1"
    oninput="document.getElementById('chEvt').disabled=(this.value == '<?php echo $cur_evt;?>')">
     <?php echo $event_select;?>
   </select>
   <input type="button" id="chEvt" name="chEvt" value="Change Event" onclick="block_refresh=1;document.getElementById('changeEvt').disabled=false" class="button" disabled>
   <input id="changeEvt" type="submit" name="Change-Event" value="Now" class="button" disabled>

  </div>

  <table align=center border="1" cellpadding="2">
   <tr class="listheader">
      <td width=40>Num</td>
      <td>Driver</td>
   <?php
   echo "<td colspan=2>Run : $cur_run</td>";
   echo "</tr>";

   echo "<input type=\"hidden\" id=\"move_vals\" name=\"move_vals\" value=\"\">";

   $prev_row="";
   $prev_down="";
   $up_data="";
   $i=0;
   $top=0;
   while(true) {
    $new_row = $order->fetchArray();
    if(is_array($prev_row)) {
      if($i%2==0)
       $classname="class=\"evenRow\"";
      else
       $classname="class=\"oddRow\"";
      echo "<tr $classname>";
      $row_id=$prev_row['rowid'];
      $ord=$prev_row['ord'];
      if (is_array($new_row)) {
	$down_disable = "";
        $down_data="$row_id:" . $new_row['ord'] . ":" . $new_row['rowid'] . ":" . $prev_row['ord'];
      }
      else {
	$down_disable = "disabled";
	$down_data="";
      }
      if ($i > 0) {
	$up_disable = "";
	$top_data = "$row_id:$top";
      }
      else {
	$up_disable = "disabled";
	$top_data = "";
	$top=$prev_row['ord'] - 1;
      }
      if ($i > 2) {
	$top_2_disable = "";
	$top_2_data = "$row_id:$top_2_num";
      }
      else {
	$top_2_disable = "disabled";
	$top_2_data = "";
	$top_2_num = $prev_row['ord']-1;
      }
      $safe_num=htmlspecialchars($prev_row['car_num']);
      $safe_name=htmlspecialchars($prev_row['car_name']);
      echo "<td align=\"center\" id=\"$row_id:$ord\" ondrop=\"dropHandler(event)\"   ondragover=\"dragoverHandler(event)\" draggable=\"true\" ondragstart=\"dragstartHandler(event)\">$safe_num</td>\n";
      echo "<td>$safe_name</td>\n";
      echo "<td>";
      echo " <input type=\"submit\" name=\"submit\" value=\"Dn\" onclick=\"document.getElementById('move_vals').value='$down_data'\" class=\"button\" $down_disable>\n";
      echo " <input type=\"submit\" name=\"submit\" value=\"Up\" onclick=\"document.getElementById('move_vals').value='$up_data'\" class=\"button\" $up_disable>\n";
      #echo " <input type=\"image\" name=\"Down\" src=\"down_arrow.png\" alt=\"Down\" title=\"Down\" onclick=\"document.getElementById('move_vals').value='$down_data'\" class=\"button\" $down_disable>\n";
      #echo " <input type=\"image\" name=\"Up\" src=\"up_arrow.png\" alt=\"Up\" title=\"Up\" onclick=\"document.getElementById('move_vals').value='$up_data'\" class=\"button\" $up_disable>\n";
      echo "</td><td>";
      echo " <input type=\"submit\" name=\"submit\" value=\"Bot\" onclick=\"document.getElementById('move_vals').value='$row_id'\" class=\"button\" $down_disable>\n";
      echo " <input type=\"submit\" name=\"submit\" value=\"P3\" onclick=\"document.getElementById('move_vals').value='$top_2_data'\" class=\"button\" $top_2_disable>\n";
      echo " <input type=\"submit\" name=\"submit\" value=\"Top\" onclick=\"document.getElementById('move_vals').value='$top_data'\" class=\"button\" $up_disable>\n";
      #echo " <input type=\"image\" name=\"Bot\" src=\"bottom_arrow.png\" alt=\"Bottom\" title=\"Bottom\" onclick=\"document.getElementById('move_vals').value='$row_id'\" class=\"button\" $down_disable>\n";
      #echo " <input type=\"image\" name=\"Top\" src=\"top_arrow.png\" alt=\"Top\" title=\"Top\" onclick=\"document.getElementById('move_vals').value='$top_data'\" class=\"button\" $up_disable>\n";
      echo "</td></tr>\n";
      $up_data=$down_data;
      unset($entrants[$safe_num]);
      $i++;
    }
    if(!is_array($new_row)) break;
    $prev_row = $new_row;
   }
   if ($i > 0) {
     $last_ord = 1 + $prev_row['ord'];
   }
   else $last_ord = 1;
   echo "<input type=\"hidden\" id=\"last_ord\" name=\"last_ord\" value=\"$last_ord\">";
   if (isset($entrants) && (is_array($entrants)) && (count($entrants) > 0 )) {
     if($i%2==0)
       $classname="class=\"evenRow\"";
     else
       $classname="class=\"oddRow\"";
     echo "<tr $classname><td colspan=2>";
     echo " &nbsp; &nbsp; <select name=\"AddEntrant\" style=\"width: 180px\" onfocus=\"block_refresh=1\" oninput=\"document.getElementById('AddEnt').disabled=(this.value == '')\">";
     foreach($entrants as $car => $name) {
      echo "<option value=\"$car\"> $car &nbsp; &nbsp; " . $name . "</option>";
     }
     echo "<option value=\"\" selected> --  ReRun Entrant -- </option>";
     echo "</select></td><td>";
     echo "<input type=\"button\" id=\"AddEnt\" name=\"AddEnt\" value=\"Entrant\" onclick=\"block_refresh=1;document.getElementById('ReallyAdd').disabled=false\" class=\"button\" disabled>";
     echo "</td><td>";
     echo "<input id=\"ReallyAdd\" type=\"submit\" name=\"ReallyAdd\" value=\"Add\" class=\"button\" disabled>";
     echo "</td></tr>";
   }
   ?>
  </table>
  <div align="center" style="padding-top:5px;">
   <a href="running_order.php"> Refresh </a> &nbsp; &nbsp; 
   <hide-input type="button" id="NewRun-1" name="NewRun-1" value="Load New Run" onclick="block_refresh=1;document.getElementById('NewRun-2').disabled=false" class="button">
   <select name="NewRun-1" onfocus="block_refresh=1" oninput="document.getElementById('NewRun-2').disabled=(this.value == '')">
    <option value='' selected> -- Operation -- </option>
    <option value="NR-Load"> <strong> New Run &amp; Load </strong></option>
    <option value="NewRun"> New Run </option>
    <option value="Load"> Load </option>
    <option value="Clear"> Clear </option>
    <option value="PrevRun"> Prev Run </option>
    <option value="SetOrder"> Set This Order </option>
   </select>
   <input type="submit" id="NewRun-2" name="NewRun-2" value="Now" class="button" disabled>
  </div>
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
  /*refesh_timeout=setTimeout(refesh_page, 3000);*/
  var ws = new WebSocket('ws://'+location.host+'/ws/status/newcar/');
  ws.onclose = function()       { refesh_page(); };
  ws.onmessage = function(event){ refesh_page(); };
 </script>
</html>
