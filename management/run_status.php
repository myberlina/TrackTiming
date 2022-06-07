<?php
  include_once 'database_ro.php';

  #$events = $db->query('SELECT rowid, num, name FROM event_info ORDER BY num DESC');

  $status = $db->querySingle('SELECT current_event, name, current_run, current_car, car_name
			FROM current_event LEFT JOIN event_info ON current_event = num
			LEFT JOIN current_run LEFT JOIN current_car
			LEFT JOIN entrant_info ON current_car = car_num AND current_event = event', true);
  $event_num = $status['current_event'];
  $event_name = $status['name'];
  $run = $status['current_run'];
  $curr_car = $status['current_car'];
  $curr_name = $status['car_name'];
  $next_car = $db->querySingle('SELECT car_num  FROM next_car ORDER BY ord LIMIT 1');
  if (isset($event_num) && isset($next_car))
    $next_name = $db->querySingle("SELECT car_name FROM entrant_info WHERE car_num = $next_car AND event = $event_num");
  else
    $next_name = "";
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Event Status</title>
    <link rel="stylesheet" href="style.css">
  </head>
<body>
  <table>
   <tr>
   <?php
    echo '<th colspan=2>Event</th>';
    echo "<th width=30 align=\"right\">$event_num</td>\n";
    echo "<th >$event_name</td>\n";
    echo '<th > &nbsp; Run</th>';
    echo "<th width=30 align=\"right\">$run</td>\n";
    echo '<th > &nbsp; Current</th>';
    echo "<td width=30 align=\"right\">$curr_car</td>\n";
    echo "<td width=140 align=\"left\">$curr_name</td>\n";
    echo '<th > &nbsp; Next</th>';
    echo "<td width=30 align=\"right\">$next_car</td>\n";
    echo "<td width=140 align=\"left\">$next_name</td>\n";
   ?>
   </tr>
  </table>
  </form>
 </body>
 <script>
  var ws = new WebSocket('ws://'+location.host+'/ws/status/green/');
  ws.onclose = function()	{ location.reload(true); };
  ws.onmessage = function(event){ location.reload(true); };
 </script>
</html>
