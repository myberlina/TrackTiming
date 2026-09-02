<?php

  // Set PHP headers for YAML output.
  header('Content-Type: application/yaml; charset=utf-8');
  header('Content-Disposition: attachment; filename="radar_mqtt.conf"');

  readfile("/etc/radar_mqtt/radar_mqtt.conf");
?>
