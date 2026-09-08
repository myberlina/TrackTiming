<?php

  if(isset($argc) && ($argc>1))
    parse_str(implode('&',array_slice($argv, 1)), $_GET);

  $basename = "All_Timing_Config_" . date("Ymd_His") . ".zip";

  // Set PHP headers for CSV output.
  header('Content-Type: application/zip; charset=binary');
  header('Content-Disposition: attachment; filename=' . $basename);

  passthru("zip -r - /etc/timing/ /etc/radar_mqtt/");
  #readfile($db_file);

?>
