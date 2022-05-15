<?php

  include_once 'database.php';

  $current = $db->query('select current_event, current_run from current_event, current_run;');
  if ($row = $current->fetchArray()) {
    $cur_evt = $row["current_event"];
    $cur_run = $row["current_run"];
  }
  $events = $db->query('SELECT ROWID, num, name FROM event_info ORDER BY num DESC');

?>

<html>
  <head>
    <title>Events</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
<script type="text/javascript">function showTiming(str){document.location = 'events.php?evt='+str;}</script>
<br>
  <?php
    echo "Current event $cur_evt run $cur_run";
    echo "<br>POST DATA Count ";
    echo count($_POST);
    var_dump($_POST);
  ?>
  <br>
  <form name="frmEvent" method="post" action="">
  <table align=center border="2" cellpadding="4">
   <tr class="listheader">
      <td>Num</td>
      <td>Title</td>
   </tr>
   <?php
   $i=0;
   $prev_car = "";

   while($row = $events->fetchArray()) {
    if($i%2==0)
     $classname="class=\"evenRow\"";
    else
     $classname="class=\"oddRow\"";
    if ($cur_evt == $row["num"])
     $classname="$classname-hilight"
   ?>
   <tr "<?php if(isset($classname)) echo $classname;?>">
     <td><input type="number" placeholder="Event Number" size="4" name="EvtNum" class="txtField" required min="1" value="<?php echo htmlspecialchars($row['num']); ?>"></td>
     <td><input type="text" placeholder="Event Name" name="EvtName" class="txtField" required value="<?php echo htmlspecialchars($row['name']); ?>"> </td>

     <td> <input type="submit" name="submit" value="Update" class="button"> </td>

     <td><a href="entrants.php?evt=<?php echo htmlspecialchars($row["num"]); ?>">Entrants</a></td>
   </tr>
   <?php
   $i++;
   }
   ?>
  </table>
  </form>
 </body>
</html>





