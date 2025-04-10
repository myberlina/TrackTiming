<?php

  $db = new SQLite3('/data/Track_Time/Track_Time.db', SQLITE3_OPEN_READONLY);
  $db->busyTimeout(10000);

?>

