<?php

  $db = new SQLite3('/home/dave/Track_Time/Track_Time.db', SQLITE3_OPEN_READWRITE);
  $db->busyTimeout(5000);

?>

