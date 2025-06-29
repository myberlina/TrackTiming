<?php
  $config_base="/etc/timing/";
  //var_dump($_POST);
  //var_dump($_FILES);
  function chk_chnged($name) {
    return (isset($_POST[$name])&&isset($_POST["Orig".$name])&&($_POST[$name]!=$_POST["Orig".$name]));
  }

  $message="";

  if(count($_POST)>0) {
    $restart_timing = 0;
    $restart_results = 0;
    $file_changed = 0;
    if(isset($_POST['submit-changes'])&&('Save Changes' == $_POST['submit-changes'])&&
       isset($_POST['update_list'])&&('' != $_POST['update_list'])) {
      $config = yaml_parse_file( "$config_base/timing.conf");	// Read in current config
      if(!isset($_POST['save_ver'])||($_POST['save_ver']!=htmlspecialchars($config['save_ver']))) {
        if($_POST['save_ver']<htmlspecialchars($config['save_ver']))
          $message = "<font color=\"#c00000\"> Save Failed: Miss-matched config file - form from an older version </font>";
        else
          $message = "<font color=\"#c00000\"> Save Failed: Miss-matched config file </font>";
      }
      else {
        if (str_contains($_POST['update_list'], 'DefaultReport') && isset($_POST['DefaultReport'])
          && isset($config['results']) && isset($config['results']['php_base']) && isset($config['results']['static_base']) ) {
          $safe_php_base=htmlspecialchars($config['results']['php_base'],ENT_QUOTES);
          $safe_static_base=htmlspecialchars($config['results']['static_base'],ENT_QUOTES);
          $new_def_report = $_POST['DefaultReport'];
          if (file_exists($safe_php_base . "/" . $new_def_report . ".php")) {  // Is a real report
            if (file_exists($safe_static_base . "/default.html"))
              unlink($safe_static_base . "/default.html");
            if (symlink($new_def_report . ".html", $safe_static_base . "/default.html"))
              $message = "<font color=\"#00a000\"> default result type changed </font><br>";
            else
              $message = "<font color=\"#c00000\"> Could not update default result type </font><br>";
          }
        }
        if ($_POST['update_list'] != ';DefaultReport') {
          if(chk_chnged('Title'))	{ $config['title'] = $_POST['Title']; };
          if(chk_chnged('Comment'))	{ $config['comment'] = $_POST['Comment']; };
          if(chk_chnged('DbPath'))	{ $config['database_path'] = $_POST['DbPath'];				$restart_timing=1; $restart_results=1; };
          if(chk_chnged('ButtonGPIO'))	{ $config['timing']['inputs']['button']['gpio'] = intval($_POST['ButtonGPIO']);			$restart_timing=1; };
          if(chk_chnged('ButtonEdge'))	{ $config['timing']['inputs']['button']['falling_edge'] = ('True' == $_POST['ButtonEdge']);	$restart_timing=1; };
          if(chk_chnged('GreenGPIO'))	{ $config['timing']['inputs']['green']['gpio'] = intval($_POST['GreenGPIO']);			$restart_timing=1; };
          if(chk_chnged('GreenEdge'))	{ $config['timing']['inputs']['green']['falling_edge'] = ('True' == $_POST['GreenEdge']);	$restart_timing=1; };
          if(chk_chnged('StartGPIO'))	{ $config['timing']['inputs']['start']['gpio'] = intval($_POST['StartGPIO']);			$restart_timing=1; };
          if(chk_chnged('StartEdge'))	{ $config['timing']['inputs']['start']['falling_edge'] = ('True' == $_POST['StartEdge']);	$restart_timing=1; };
          if(chk_chnged('FinishGPIO'))	{ $config['timing']['inputs']['finish']['gpio'] = intval($_POST['FinishGPIO']);			$restart_timing=1; };
          if(chk_chnged('FinishEdge'))	{ $config['timing']['inputs']['finish']['falling_edge'] = ('True' == $_POST['FinishEdge']);	$restart_timing=1; };
          if(chk_chnged('TimDebug'))	{ $config['timing']['debug'] = ('true' == $_POST['TimDebug']);					$restart_timing=1; };
          if(chk_chnged('WebBase'))	{ $config['results']['web_base'] = $_POST['WebBase'];				$restart_results=1; };
          if(chk_chnged('PHPBase'))	{ $config['results']['php_base'] = $_POST['PHPBase'];				$restart_results=1; };
          if(chk_chnged('StaticBase'))	{ $config['results']['static_base'] = $_POST['StaticBase'];			$restart_results=1; };
          if(chk_chnged('FwdCmd'))	{ $config['results']['forward_results_command'] = $_POST['FwdCmd'];		$restart_results=1; };
          if(chk_chnged('Interval'))	{ $config['results']['static_refresh'] = intval($_POST['Interval']);		$restart_results=1; };
          if(chk_chnged('WebPush'))	{ $config['results']['web_push'] = intval($_POST['WebPush']);			$restart_results=1; };
          if(chk_chnged('RunnersOnly'))	{ $config['results']['runners_only'] = ('true' == $_POST['RunnersOnly']);	$restart_results=1; };
          if(chk_chnged('SplitLine'))	{ $config['results']['split_line'] = ('true' == $_POST['SplitLine']); };
          if(chk_chnged('CSV_Quotes'))	{ $config['results']['csv_quotes'] = ('true' == $_POST['CSV_Quotes']); };
          $list_change=0;
          $i=0;
          $new_list=array();
          foreach($_POST as $name => $value) {
            if (substr($name,0,3)=='RP_') {
              if ($_POST[$name] == 'true') $new_list[$i++] = substr($name,3);
              if (chk_chnged($name))  $list_change = 1;
            }
            elseif (substr($name,0,7)=='OrigRP_') {
              $base=substr($name,4);
              if (!isset($_POST[$base])&&($_POST[$name]=='true'))  $list_change = 1;
            }
          }
          if ($list_change == 1) {
            $config['results']['result_types'] = $new_list;
            $restart_results=1;
            //foreach($config['results']['result_types'] as $num => $file) {
            //  echo "[$num] => '$file' <br>\n";
            //}
          }
          $config['save_ver']++;
          if (yaml_emit_file("$config_base/_timing.conf", $config))
            if (rename("$config_base/_timing.conf", "$config_base/timing.conf")) {
              $message = $message . "<font color=\"#00a000\"> Config Saved </font>";
              $file_changed = 1;
            }
            else {
              $errors = error_get_last();
              $message = $message . "<font color=\"#c00000\"> Save Failed: " . $errors['message'] . "</font>";
            }
          else {
            $errors = error_get_last();
            $message = $message . "<font color=\"#c00000\"> Save Failed: " . $errors['message'] . "</font>";
          }
        }
      }
    }
    if(isset($_POST['submit-save1'])&&('Save' == $_POST['submit-save1'])) {
      if(isset($_POST['SaveName'])&&('' != $_POST['SaveName'])) {
        $name=$_POST['SaveName'];
        if (!ctype_print($name)) {
          $message = "<font color=\"#c00000\"> Save Failed: Illegal File Name  ctype_alnum '$name'</font>";
        }
        elseif (!preg_match('/^(?:[a-z0-9_-]|\.(?!\.))+$/iD', $name)) {
          $message = "<font color=\"#c00000\"> Save Failed: Illegal File Name  regex '$name'</font>";
        }
        else {
          if (copy("$config_base/timing.conf", "$config_base/$name.conf")) {
            $message = "<font color=\"#00a000\"> Config Saved </font>";
          }
          else {
            $errors = error_get_last();
            $message = "<font color=\"#c00000\"> Save Failed: " . $errors['message'] . "</font>";
          }
        }
      }
      else {
        $message = "<font color=\"#c00000\"> No save name specified </font>";
      }
    }
    if(isset($_POST['submit-createdb'])&&('Create' == $_POST['submit-createdb'])) {
      $config = yaml_parse_file( "$config_base/timing.conf");	// Read in current config
      if(isset($_POST['save_ver'])&&($_POST['save_ver']==htmlspecialchars($config['save_ver']))
         && !chk_chnged('DbPath') && ($config['database_path'] == $_POST['DbPath'])
        ) {
        unset($results);
        if (!(false === exec("/usr/sbin/CreateTimingDatabase 2>&1", $results, $rc)) && ($rc == 0)) {
          $message = "<font color=\"#00a000\"> Creating Database </font>";
          $file_changed = 1;
          $restart_timing = 1;
          $restart_results = 1;
        }
        else {
          $error_text="";
          foreach($results as $num => $line) $error_text=$error_text."$line<br>";
          #if(!(strpos($error_text, "sudo: ")===false)) $error_text="sudo not correctly setup";
          $message = $message."<br><font color=\"#c00000\"> Create Database failed: $rc: $error_text</font>";
        }
      }
      else {
        $message = "<font color=\"#c00000\"> Problem with form - database not created </font>";
      }
    }
    if(isset($_POST['submit-load'])&&('Load' == $_POST['submit-load'])) {
      if(isset($_POST['LoadConfig'])&&('' != $_POST['LoadConfig'])) {
        $name=$_POST['LoadConfig'];
        if (!ctype_print($name) || !preg_match('/^(?:[a-z0-9_-]|\.(?!\.))+$/iD', $name)) {
          $message = "<font color=\"#c00000\"> Load Failed: Illegal File Name </font>";
        }
        else {
          if (copy("$config_base/$name", "$config_base/timing.conf")) {
            $message = "<font color=\"#00a000\"> Config Loaded </font>";
            $file_changed = 1;
            $restart_timing = 1;
            $restart_results = 1;
          }
          else {
            $errors = error_get_last();
            $message = "<font color=\"#c00000\"> Load Failed: " . $errors['message'] . "</font>";
          }
        }
      }
      else {
        $message = "<font color=\"#c00000\"> No save name specified </font>";
      }
    }
    if(isset($_POST['submit-really'])&&('Really' == $_POST['submit-really'])) {
      if(isset($_POST['LoadConfig'])&&('' != $_POST['LoadConfig'])) {
        $name=$_POST['LoadConfig'];
        if (!ctype_print($name) || !preg_match('/^(?:[a-z0-9_-]|\.(?!\.))+$/iD', $name)) {
          $message = "<font color=\"#c00000\"> Delete Failed: Illegal File Name </font>";
        }
        else {
          if (unlink("$config_base/$name")) {
            $message = "<font color=\"#00a000\"> Config Deleted </font>";
          }
          else {
            $errors = error_get_last();
            $message = "<font color=\"#c00000\"> Delete Failed: " . $errors['message'] . "</font>";
          }
        }
      }
      else {
        $message = "<font color=\"#c00000\"> No save name specified </font>";
      }
    }
    if (is_array($_FILES) && isset($_FILES["Upload_Config"]) && is_array($_FILES["Upload_Config"])
      && (0 == $_FILES["Upload_Config"]["error"])){
      $config = yaml_parse_file($_FILES["Upload_Config"]["tmp_name"]);
      if (false === $try_config) {
        $message = "<font color=\"#c00000\"> Uploaded file not valid </font>";
      }
      elseif (isset($config['timing']['inputs'])) {
          if (rename($_FILES["Upload_Config"]["tmp_name"], "$config_base/timing.conf")) {
            $message = "<font color=\"#00a000\"> Config Loaded </font>";
            $file_changed = 1;
            $restart_timing = 1;
            $restart_results = 1;
          }
          else {
            $errors = error_get_last();
            $message = "<font color=\"#c00000\"> Load Failed: " . $errors['message'] . "</font>";
          }
      }
    }

    if ($file_changed == 1) {
      if ($restart_timing >= 1) {
        unset($results);
        if (!(false === exec("sudo /usr/bin/systemctl restart timing.service 2>&1", $results, $rc)) && ($rc == 0)) {
          $message = $message."<br><font color=\"#00a000\"> Timing service restarted </font>";
        }
        else {
          $error_text="";
          foreach($results as $num => $line) $error_text=$error_text."$line<br>";
          if(!(strpos($error_text, "sudo: ")===false)) $error_text="sudo not correctly setup";
          $message = $message."<br><font color=\"#c00000\"> Timing restart failed: $rc: $error_text</font>";
        }
      }
      if ($restart_results >= 1) {
        unset($results);
        if (!(false === exec("sudo /usr/bin/systemctl restart results.service 2>&1", $results, $rc)) && ($rc == 0)) {
          $message = $message."<br><font color=\"#00a000\"> Results service restarted </font>";
        }
        else {
          $error_text="";
          foreach($results as $num => $line) $error_text=$error_text."$line<br>";
          if(!(strpos($error_text, "sudo: ")===false)) $error_text="sudo not correctly setup";
          $message = $message."<br><font color=\"#c00000\"> Results restart failed: $rc: $error_text</font>";
        }
      }
    }
  }

  unset($config);
  $config = yaml_parse_file( "$config_base/timing.conf");

  $rising = '<option value="False" selected>Rising  ^</option> <option value="True">Falling  v</option>';
  $falling = '<option value="False">Rising  ^</option> <option value="True" selected>Falling  v</option>';
  $off = '<option value="false" selected> Off </option> <option value="true"> On </option>';
  $on = '<option value="false"> Off </option> <option value="true" selected> On </option>';
  $all_entered = '<option value="false" selected>All Enrants</option> <option value="true">Runners Only</option>';
  $runners = '<option value="false">All Enrants</option> <option value="true" selected>Runners Only</option>';
  $same_line = '<option value="false" selected>Splits Same Line</option> <option value="true">Splits Own Line</option>';
  $new_line = '<option value="false">Splits Same Line</option> <option value="true" selected>Splits Own Line</option>';

  $safe_title="";
  $safe_comment="";
  $safe_db_path="";
  $safe_button_gpio="";
  $safe_button_edge="False";
  $safe_button_edge_opt=$rising;
  $safe_green_gpio="";
  $safe_green_edge="False";
  $safe_green_edge_opt=$rising;
  $safe_start_gpio="";
  $safe_start_edge="False";
  $safe_start_edge_opt=$rising;
  $safe_finish_gpio="";
  $safe_finish_edge="False";
  $safe_finish_edge_opt=$rising;
  $safe_tim_debug="false";
  $safe_tim_debug_opt=$off;
  $safe_web_base="";
  $safe_php_base="";
  $safe_static_base="";
  $safe_fwd_cmd="";
  $safe_refresh_time="";
  $safe_web_push="";
  $safe_runners_only="false";
  $safe_runners_only_opt=$all_entered;
  $safe_split_line="false";
  $safe_split_line_opt=$same_line;
  $safe_csv_quotes="false";
  $safe_csv_quotes_opt=$off;
  $safe_save_ver="0";
  if (false === $config) {
    $message = "<font color=\"#c00000\"> No Config File </font>";
  }
  else {
    $safe_title=htmlspecialchars($config['title'],ENT_QUOTES);
    $safe_comment=htmlspecialchars($config['comment'],ENT_QUOTES);
    $safe_db_path=htmlspecialchars($config['database_path'],ENT_QUOTES);
    $show_create_database_button = false;
    if (!file_exists($safe_db_path)) {
      $message = $message."<br><font color=\"#c00000\"> Database does not exist </font>";
      if (file_exists(dirname($safe_db_path)) && is_dir(dirname($safe_db_path))) {
        $show_create_database_button = true;
      }
      else {
        $message = $message."<br><font color=\"#c00000\"> Database directory does not exist </font>";
      }
    }
    if (isset($config['timing'])) {
      if (isset($config['timing']['inputs'])) {
        if (isset($config['timing']['inputs']['button'])) {
          $safe_button_gpio=htmlspecialchars($config['timing']['inputs']['button']['gpio']);
          $safe_button_edge=($config['timing']['inputs']['button']['falling_edge'])?"True":"False";
          $safe_button_edge_opt=($config['timing']['inputs']['button']['falling_edge'])?$falling:$rising;
        }
        if (isset($config['timing']['inputs']['green'])) {
          $safe_green_gpio=htmlspecialchars($config['timing']['inputs']['green']['gpio']);
          $safe_green_edge=($config['timing']['inputs']['green']['falling_edge'])?"True":"False";
          $safe_green_edge_opt=($config['timing']['inputs']['green']['falling_edge'])?$falling:$rising;
        }
        if (isset($config['timing']['inputs']['start'])) {
          $safe_start_gpio=htmlspecialchars($config['timing']['inputs']['start']['gpio']);
          $safe_start_edge=($config['timing']['inputs']['start']['falling_edge'])?"True":"False";
          $safe_start_edge_opt=($config['timing']['inputs']['start']['falling_edge'])?$falling:$rising;
        }
        if (isset($config['timing']['inputs']['finish'])) {
          $safe_finish_gpio=htmlspecialchars($config['timing']['inputs']['finish']['gpio']);
          $safe_finish_edge=($config['timing']['inputs']['finish']['falling_edge'])?"True":"False";
          $safe_finish_edge_opt=($config['timing']['inputs']['finish']['falling_edge'])?$falling:$rising;
        }
      }
      $safe_tim_debug=($config['timing']['debug'])?"true":"false";
      $safe_tim_debug_opt=($config['timing']['debug'])?$on:$off;
    }
    if (isset($config['results'])) {
      $safe_web_base=htmlspecialchars($config['results']['web_base'],ENT_QUOTES);
      $safe_php_base=htmlspecialchars($config['results']['php_base'],ENT_QUOTES);
      $safe_static_base=htmlspecialchars($config['results']['static_base'],ENT_QUOTES);
      $safe_fwd_cmd=htmlspecialchars($config['results']['forward_results_command'],ENT_QUOTES);
      $safe_refresh_time=htmlspecialchars($config['results']['static_refresh']);
      $safe_web_push=htmlspecialchars($config['results']['web_push']);
      $safe_runners_only=($config['results']['runners_only'])?"true":"false";
      $safe_runners_only_opt=($config['results']['runners_only'])?$runners:$all_entered;
      $safe_split_line=($config['results']['split_line'])?"true":"false";
      $safe_split_line_opt=($config['results']['split_line'])?$new_line:$same_line;
      if (isset($config['results']['csv_quotes']) && $config['results']['csv_quotes']) {
        $safe_csv_quotes=true;
        $safe_csv_quotes_opt=$on;
      }
    }
    $safe_save_ver=htmlspecialchars($config['save_ver']);
  }

  $possible_configs=scandir("$config_base", SCANDIR_SORT_ASCENDING);
  // var_dump($possible_configs);
  $conf_file_list="<option value=\"\" selected>&nbsp; -- Select config file -- &nbsp; </option>";
  foreach($possible_configs as $conf_num => $conf_file) {
    // echo "$conf_file_list  <br>\n";
    // echo "$conf_num  :  $conf_file     ";
    if (substr($conf_file,0,1) == ".") { continue ; };
    if ($conf_file == "timing.conf") { continue ; };
    $contents = yaml_parse_file( "$config_base/$conf_file");
    if (!(false === $contents) && isset($contents['title'])) {
      $title = $contents['title'];
      $comment = $contents['comment'];
      $conf_file_list = $conf_file_list . "<option value=\"$conf_file\" title=\"$comment\"> $conf_file &nbsp; : &nbsp; $title </option>";
    }
  }

  // Get the list of result programs
  $result_list = array();
  $result_pat = "Results" . "_Info: ";
  $list = popen("fgrep '$result_pat' \"$safe_php_base\"/*.php", "r");
  while (!feof($list)) {
    $line = fgets($list);
    $c = strpos($line, $result_pat);
    if (false === $c) { continue; };
    $comment = substr($line, $c + 14);
    $file = substr($line, 0, $c);
    $c = strrpos($file, ".php");
    if (false === $c) { continue; };
    $file = substr($file, 0, $c);
    $c = strrpos($file, "/");
    if (false === $c) { continue; };
    $file = substr($file, $c + 1);
    $result_list[$file] = $comment;
  }
  pclose($list);
  $def_report="";
  if (is_link($safe_static_base . "/default.html")) {
    $def_report = readlink($safe_static_base . "/default.html");
    if (".html" == substr($def_report,-5))
      $def_report = substr($def_report,0,-5);
  }
  // Add any that are in the config, but not found
  // echo "type of  \$config['results']['result_types'] is " . gettype($config['results']['result_types']) . "<br>";
  if (!(false === $config)) {
    foreach($config['results']['result_types'] as $num => $file) {
      $result_enabled[$file] = true;
      if (! isset($result_list[$file])) {
        $result_list[$file] = "";
      }
    }
  }
  // var_dump($result_list);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Configuration</title>
    <link rel="stylesheet" href="style.css">
<?php
  $icon_file=dirname(__FILE__) . "/icons.inc";
  if (file_exists($icon_file))
    readfile($icon_file);
?>
  </head>
<body>
  <div style="float:right">
   <a href="management.html">Main Menu</a>&nbsp; &nbsp; 
  </div>
 <div align="center" style="padding-bottom:5px;">
  <h2>Configuration</h2>
 </div>
  <form name="frmConfig" id="frmConfig" method="post" action="">
    <input type="hidden" name="update_list" value="" id="update_list">
    <input type="hidden" name="save_ver" value="<?php echo "$safe_save_ver";?>">
  <script type="text/javascript">
    function haveUpdate(){
            update_list="";
            update_count=0;
            orig_val="";
            var radioButtons = document.getElementsByName("DefaultReport");
            for (var i = 0; i < radioButtons.length; i++) {
              if (radioButtons[i].checked) {
                orig_val=radioButtons[i].value;
              }
            }
            input_fields=document.getElementsByTagName("input");
            for (let i = 0; i < input_fields.length; i++) {
              if (input_fields[i].name.substr(0,4) == "Orig") {
                new_field=document.getElementById(input_fields[i].name.substr(4));
                if (new_field != null) {
                  if (input_fields[i].value != new_field.value) {
                    update_list=update_list + ";" + input_fields[i].name.substr(4);
                    update_count++;
                  }
                }
                else if ((input_fields[i].name == "OrigDefaultReport")&&(input_fields[i].value != orig_val)) {
                  report=document.getElementById("RP_" + orig_val);
                  if ((report != null) && report.checked) {
                    update_list=update_list + ";" + input_fields[i].name.substr(4);
                    update_count++;
                  }
                }
              }
            }
            document.getElementById('update_list').value=update_list;
            document.getElementById('submit-changes').disabled=(update_count == 0);
    };
  </script>
  <div class="message"><?php if(isset($message)) { echo $message; } ?> </div>
  <table align=center border="2" cellpadding="4">
<?php
    echo "<tr>\n <th class=\"listheader\"> Config Title </th>\n";
    echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigTitle\" value=\"$safe_title\" id=\"OrigTitle\">";
    echo "<input type=\"text\" size=\"30\" placeholder=\"Title\" name=\"Title\" id=\"Title\" class=\"txtField\" required value=\"$safe_title\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Comments </th>\n";
    echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigComment\" value=\"$safe_comment\" id=\"OrigComment\">";
    echo "<input type=\"text\" size=\"50\" placeholder=\"Comments\" name=\"Comment\" id=\"Comment\" class=\"txtField\" required value=\"$safe_comment\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Database File Path </th>\n";
    echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigDbPath\" value=\"$safe_db_path\" id=\"OrigDbPath\">";
    echo "<input type=\"text\" size=\"50\" placeholder=\"Database File Path\" name=\"DbPath\" id=\"DbPath\" class=\"txtField\" required value=\"$safe_db_path\" oninput=\"haveUpdate()\" >\n";
    if (true === $show_create_database_button) {
      echo "<input id=\"submit-createdb\" type=\"submit\" name=\"submit-createdb\" value=\"Create\" formnovalidate formenctype=\"multipart/form-data\">";
    }
    echo "</td></tr>\n";

    echo "<tr>\n <th colspan=\"3\" class=\"listheader\"> Inputs </th></tr>\n";
    echo "<tr>\n <th class=\"listheader\"> Drag / HillClimb</th><th>GPIO</th><th>Trigger Edge</th></tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Button / Green </th>\n";
    echo "<td><input type=\"hidden\" name=\"OrigButtonGPIO\" value=\"$safe_button_gpio\" id=\"OrigButtonGPIO\">";
    echo "<input type=\"number\" size=\"4\" placeholder=\"11\" name=\"ButtonGPIO\" id=\"ButtonGPIO\" class=\"input_number\" required value=\"$safe_button_gpio\" oninput=\"haveUpdate()\" ></td>\n";
    echo "<td><input type=\"hidden\" name=\"OrigButtonEdge\" value=\"$safe_button_edge\" id=\"OrigButtonEdge\">";
    echo "<select name=\"ButtonEdge\" id=\"ButtonEdge\" style=\"width: 240px\" onchange=\"haveUpdate()\">$safe_button_edge_opt</select></td>";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Green / Start </th>\n";
    echo "<td><input type=\"hidden\" name=\"OrigGreenGPIO\" value=\"$safe_green_gpio\" id=\"OrigGreenGPIO\">";
    echo "<input type=\"number\" size=\"4\" placeholder=\"11\" name=\"GreenGPIO\" id=\"GreenGPIO\" class=\"input_number\" required value=\"$safe_green_gpio\" oninput=\"haveUpdate()\" ></td>\n";
    echo "<td><input type=\"hidden\" name=\"OrigGreenEdge\" value=\"$safe_green_edge\" id=\"OrigGreenEdge\">";
    echo "<select name=\"GreenEdge\" id=\"GreenEdge\" style=\"width: 240px\" onchange=\"haveUpdate()\">$safe_green_edge_opt</select></td>";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Start / Split </th>\n";
    echo "<td><input type=\"hidden\" name=\"OrigStartGPIO\" value=\"$safe_start_gpio\" id=\"OrigStartGPIO\">";
    echo "<input type=\"number\" size=\"4\" placeholder=\"11\" name=\"StartGPIO\" id=\"StartGPIO\" class=\"input_number\" required value=\"$safe_start_gpio\" oninput=\"haveUpdate()\" ></td>\n";
    echo "<td><input type=\"hidden\" name=\"OrigStartEdge\" value=\"$safe_start_edge\" id=\"OrigStartEdge\">";
    echo "<select name=\"StartEdge\" id=\"StartEdge\" style=\"width: 240px\" onchange=\"haveUpdate()\">$safe_start_edge_opt</select></td>";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Finish </th>\n";
    echo "<td><input type=\"hidden\" name=\"OrigFinishGPIO\" value=\"$safe_finish_gpio\" id=\"OrigFinishGPIO\">";
    echo "<input type=\"number\" size=\"4\" placeholder=\"11\" name=\"FinishGPIO\" id=\"FinishGPIO\" class=\"input_number\" required value=\"$safe_finish_gpio\" oninput=\"haveUpdate()\" ></td>\n";
    echo "<td><input type=\"hidden\" name=\"OrigFinishEdge\" value=\"$safe_finish_edge\" id=\"OrigFinishEdge\">";
    echo "<select name=\"FinishEdge\" id=\"FinishEdge\" style=\"width: 240px\" onchange=\"haveUpdate()\">$safe_finish_edge_opt</select></td>";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Timing Debug </th>\n";
    echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigTimDebug\" value=\"$safe_tim_debug\" id=\"OrigTimDebug\">";
    echo "<select name=\"TimDebug\" id=\"TimDebug\" style=\"width: 240px\" onchange=\"haveUpdate()\">$safe_tim_debug_opt</select></td>";
    echo "</tr>\n";

    echo "<tr>\n <th colspan=\"3\" class=\"listheader\"> Results </th></tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Web Base Path </th>\n";
    echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigWebBase\" value=\"$safe_web_base\" id=\"OrigWebBase\">";
    echo "<input type=\"text\" size=\"50\" placeholder=\"Web Base Path\" name=\"WebBase\" id=\"WebBase\" class=\"txtField\" required value=\"$safe_web_base\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> PHP Base Path </th>\n";
    echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigPHPBase\" value=\"$safe_php_base\" id=\"OrigPHPBase\">";
    echo "<input type=\"text\" size=\"50\" placeholder=\"PHP Base Path\" name=\"PHPBase\" id=\"PHPBase\" class=\"txtField\" required value=\"$safe_php_base\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Static Base Path </th>\n";
    echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigStaticBase\" value=\"$safe_static_base\" id=\"OrigStaticBase\">";
    echo "<input type=\"text\" size=\"50\" placeholder=\"Static Base Path\" name=\"StaticBase\" id=\"StaticBase\" class=\"txtField\" required value=\"$safe_static_base\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Forward Command </th>\n";
    echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigFwdCmd\" value=\"$safe_fwd_cmd\" id=\"OrigFwdCmd\">";
    echo "<input type=\"text\" size=\"50\" placeholder=\"Forward Results Command\" name=\"FwdCmd\" id=\"FwdCmd\" class=\"txtField\" value=\"$safe_fwd_cmd\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th colspan=\"1\" class=\"listheader\"> Result Types <div style=\"float:right\">&nbsp;<sub>Def</sub></div></th><th colspan=\"2\" align=\"left\"><sub>Enabled</sub>&nbsp;</th></tr>\n";
    echo "<input type=\"hidden\" name=\"OrigDefaultReport\" value=\"$def_report\" id=\"OrigDefaultReport\">";
    foreach($result_list as $name => $comment) {
      if (isset($result_enabled[$name])) {
        $is_set="true";
        $is_checked="checked";
        $radio_dis="";
        if ($name == $def_report)
          $radio_checked="checked";
        else
          $radio_checked="";
      }
      else {
        $is_set="false";
        $is_checked="";
        $radio_dis="disabled";
        $radio_checked="";
      }
      echo "<tr>\n <td align=right>";
      //echo " <label for=\"RP_$name\"> $name </label>\n";
      echo " <label for=\"def_$name\"> $name </label>\n";
      echo " <input type=\"radio\" id=\"def_$name\" name=\"DefaultReport\" $radio_checked $radio_dis value=\"$name\" oninput=\"haveUpdate();\">";
      echo "</td>\n";
      echo "<td colspan=\"2\"><input type=\"hidden\" name=\"OrigRP_$name\" value=\"$is_set\" id=\"OrigRP_$name\">";
      echo "<input type=\"checkbox\" name=\"RP_$name\" id=\"RP_$name\" value=\"$is_set\" $is_checked oninput=\"this.value=(this.checked?'true':'false');haveUpdate();document.getElementById('def_$name').disabled=!this.checked\"><label for=\"RP_$name\"> $comment </label></td>\n";
      echo "</tr>\n";
    }

    echo "<tr>\n <th class=\"listheader\"> Show in Results </th>\n";
    echo "<td><input type=\"hidden\" name=\"OrigRunnersOnly\" value=\"$safe_runners_only\" id=\"OrigRunnersOnly\">";
    echo "<select name=\"RunnersOnly\" id=\"RunnersOnly\" style=\"width: 240px\" onchange=\"haveUpdate()\">$safe_runners_only_opt</select></td>";
    echo "<td><input type=\"hidden\" name=\"OrigSplitLine\" value=\"$safe_split_line\" id=\"OrigSplitLine\">";
    echo "<select name=\"SplitLine\" id=\"SplitLine\" style=\"width: 240px\" onchange=\"haveUpdate()\">$safe_split_line_opt</select></td>";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Refresh Interval </th>\n";
    echo "<td><input type=\"hidden\" name=\"OrigInterval\" value=\"$safe_refresh_time\" id=\"OrigInterval\">";
    echo "<input type=\"number\" size=\"4\" placeholder=\"20\" name=\"Interval\" id=\"Interval\" class=\"input_number\" required value=\"$safe_refresh_time\" oninput=\"haveUpdate()\" > Seconds</td>\n";
    echo "<td><input type=\"hidden\" name=\"OrigWebPush\" value=\"$safe_web_push\" id=\"OrigWebPush\">";
    echo "<input type=\"number\" size=\"4\" placeholder=\"100\" name=\"WebPush\" id=\"WebPush\" class=\"input_number\" required value=\"$safe_web_push\" oninput=\"haveUpdate()\" > Web Push Min Interval</td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Quotes in CSV </th>\n";
    echo "<td><input type=\"hidden\" name=\"OrigCSV_Quotes\" value=\"$safe_csv_quotes\" id=\"OrigCSV_Quotes\">";
    echo "<select name=\"CSV_Quotes\" id=\"CSV_Quotes\" style=\"width: 240px\" onchange=\"haveUpdate()\">$safe_csv_quotes_opt</select></td>";
    echo "<td align=right style=\"border: 0px\"><input id=\"submit-changes\" type=\"submit\" name=\"submit-changes\" value=\"Save Changes\" disabled formenctype=\"multipart/form-data\"></td>";
    echo "</tr>\n";

?>
  </table>
  <br>
  <div align="center">
   
   <select name="LoadConfig" style="width: 240px" onchange="document.getElementById('submit-load').disabled=(this.value == '');document.getElementById('del').disabled=(this.value == '')"><?php echo $conf_file_list;?></select>
   <input id="submit-load" type="submit" name="submit-load" value="Load" disabled formnovalidate formenctype="multipart/form-data"> &nbsp; 
   <input id="del" type="button" name="del" value="Del" disabled onclick="document.getElementById('submit-really').disabled=false">
   <input id="submit-really" type="submit" name="submit-really" value="Really" disabled formnovalidate formenctype="multipart/form-data"> <br>
   <input type="text" size="30" placeholder="Save Name" name="SaveName" class="txtField" value="" oninput="document.getElementById('submit-save1').disabled=(this.value == '')">
   <input id="submit-save1" type="submit" name="submit-save1" value="Save" disabled formenctype="multipart/form-data"> <br> <br>
   <input type="file" name="Upload_Config" oninput="document.getElementById('submit-upload').disabled=false">
   <input id="submit-upload" type="submit" name="submit" value="Upload" disabled formnovalidate formenctype="multipart/form-data">
   <a href="config_save.php"> Download Config </a>
  </div>
  </form>
 </body>
</html>
