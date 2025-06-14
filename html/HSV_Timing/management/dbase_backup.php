<?php
  $db_file='/data/Track_Time/Track_Time.db';

  $config = yaml_parse_file( "/etc/timing/timing.conf");
  if ((isset($config['database_path'])) &&
      file_exists($config['database_path'])) {
      $db_file = $config['database_path'];
  }

  $db = new SQLite3($db_file, SQLITE3_OPEN_READWRITE);
  $db->exec('PRAGMA wal_checkpoint(TRUNCATE);');
  $db->close();

  if(isset($argc) && ($argc>1))
    parse_str(implode('&',array_slice($argv, 1)), $_GET);

  $basepos = strrpos($db_file, "/", 0) + 1;
  if (false === $basepos)
    $basename = $db_file;
  else
    $basename = substr($db_file, $basepos);

  // Set PHP headers for CSV output.
  header('Content-Type: application/vnd.sqlite3');
  header('Content-Disposition: attachment; filename=' . $basename);

  readfile($db_file);

?>
