<?php

  // Set PHP headers for YAML output.
  header('Content-Type: application/yaml; charset=utf-8');
  header('Content-Disposition: attachment; filename="timing.conf"');

  readfile("/etc/timing/timing.conf");
?>
