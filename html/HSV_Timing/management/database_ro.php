<?php
  $db_file='/data/Track_Time/Track_Time.db';

  $config = yaml_parse_file( "/etc/timing/timing.conf");
  if ((isset($config['database_path'])) &&
      file_exists($config['database_path'])) {
      $db_file = $config['database_path'];
  }

  $db = new SQLite3($db_file, SQLITE3_OPEN_READONLY);
  $db->busyTimeout(10000);
?>

