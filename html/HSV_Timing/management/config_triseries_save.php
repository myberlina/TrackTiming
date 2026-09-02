<?php

  $config_base="/etc/timing/TriSeries/";
  $config_name="TriSeriesScores.yaml";
  $prev_results_file = $config_base . $config_name;

  // Set PHP headers for YAML output.
  header('Content-Type: application/yaml; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $config_name . '"');

  readfile($prev_results_file);
?>
