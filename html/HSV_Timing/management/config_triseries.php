<?php
  $config_base="/etc/timing/TriSeries/";
  $config_name="TriSeriesScores.yaml";
  $prev_results_file = $config_base . $config_name;
  //var_dump($_POST);
  //var_dump($_FILES);
  function chk_chnged($name) {
    return (isset($_POST[$name])&&isset($_POST["Orig".$name])&&($_POST[$name]!=$_POST["Orig".$name]));
  }
  function parse_points() {
    $scores=array();
    $num_event=array();
    $last_event="";
    foreach ($_POST["evt_name"] as $num => $evt_name)
      if ($evt_name != "") {
        $num_event[$num] = $evt_name;
	$scores[$evt_name]=array();
        $last_event=$evt_name;
      }
    $num_club=array();
    foreach ($_POST["club"] as $num => $club)
      if ($club != "") {
        $num_club[$num] = $club;
	$scores[$last_event][$club]=0;
      }
    if (isset($_POST["points"]))
      foreach ($_POST["points"] as $evt_num => $evt_points)
        if (isset($num_event[$evt_num]))
          foreach ($evt_points as $club_num => $points)
            if (isset($num_event[$evt_num]) && isset($num_club[$club_num]))
              $scores[$num_event[$evt_num]][$num_club[$club_num]] = $points;
    return $scores;
  }

  $message="";

  if(count($_POST)>0) {
    $file_changed = 0;
    if(isset($_POST['submit-changes'])&&('Save Changes' == $_POST['submit-changes'])&&
       isset($_POST['update_list'])&&('' != $_POST['update_list'])) {
      if (file_exists($prev_results_file))
        $config = yaml_parse_file($prev_results_file);	// Read in current config
      if(true) {
        if ($_POST['update_list'] != ';DefaultReport') {
          if(chk_chnged('Title'))	{ $config['title'] = $_POST['Title']; };
          if(chk_chnged('Comment'))	{ $config['comment'] = $_POST['Comment']; };
          if(chk_chnged('Local_Name'))	{ $config['Local_Name'] = $_POST['Local_Name']; };
          if(chk_chnged('Score_Style'))	{ $config['Score_Style'] = $_POST['Score_Style']; };
	  $config['Points'] = parse_points();
          if (yaml_emit_file("$config_base/_$config_name", $config))
            if (rename("$config_base/_$config_name", "$config_base/$config_name")) {
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
          if (copy("$prev_results_file", "$config_base/$name.yaml")) {
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
    if(isset($_POST['submit-load'])&&('Load' == $_POST['submit-load'])) {
      if(isset($_POST['LoadConfig'])&&('' != $_POST['LoadConfig'])) {
        $name=$_POST['LoadConfig'];
        if (!ctype_print($name) || !preg_match('/^(?:[a-z0-9_-]|\.(?!\.))+$/iD', $name)) {
          $message = "<font color=\"#c00000\"> Load Failed: Illegal File Name </font>";
        }
        else {
          if (copy("$config_base/$name", $prev_results_file)) {
            $message = "<font color=\"#00a000\"> Config Loaded </font>";
            $file_changed = 1;
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
      $try_config = yaml_parse_file($_FILES["Upload_Config"]["tmp_name"]);
      if (false === $try_config) {
        $message = "<font color=\"#c00000\"> Uploaded file not valid - bad yaml</font>";
      }
      elseif (isset($try_config['Score_Style'])) {
          if (rename($_FILES["Upload_Config"]["tmp_name"], $prev_results_file)) {
            $message = "<font color=\"#00a000\"> Config Loaded </font>";
            $file_changed = 1;
            unset($config);
            $config = $try_config;
          }
          else {
            $errors = error_get_last();
            $message = "<font color=\"#c00000\"> Load Failed: " . $errors['message'] . "</font>";
          }
      }
      else {
        $message = "<font color=\"#c00000\"> Uploaded file not valid - missing Score_Style</font>";
      }
    }
  }

  unset($config);
  $scores = array();
  $TriSeries = array();
  if (file_exists($prev_results_file)) {
    $TriSeries = yaml_parse_file( $prev_results_file );
    if (isset($TriSeries)) {
      #var_dump($TriSeries);
      if (isset($TriSeries["Points"]) && is_array($TriSeries["Points"]))
        $scores = $TriSeries["Points"];
      else
        printf("Points was not an array\n");
    }   
  }

  $score_style_undef = '<option value="2025">2025</option> <option value="2026">2026+</option>';
  $score_style_opt["2025"] = '<option value="2025" selected>2025</option> <option value="2026">2026+</option>';
  $score_style_opt["2026"] = '<option value="2025">2025</option> <option value="2026" selected>2026+</option>';

  $safe_title="";
  $safe_comment="";
  $safe_Local_Name="";
  $safe_Score_Style="bogus";
  $safe_Score_Style_opt=$score_style_undef;
  if (false === $TriSeries) {
    $message = "<font color=\"#c00000\"> No Config File </font>";
  }
  else {
    if (isset($TriSeries['title']))
      $safe_title=htmlspecialchars($TriSeries['title'],ENT_QUOTES);
    if (isset($TriSeries['comment']))
      $safe_comment=htmlspecialchars($TriSeries['comment'],ENT_QUOTES);
    if (isset($TriSeries['Local_Name']))
      $safe_Local_Name=htmlspecialchars($TriSeries['Local_Name'],ENT_QUOTES);
    if (isset($TriSeries['Score_Style']))
      $safe_Score_Style=htmlspecialchars($TriSeries['Score_Style']);
      if (isset($score_style_opt[$safe_Score_Style]))
        $safe_Score_Style_opt=$score_style_opt[$safe_Score_Style];
  }

  $possible_configs=scandir("$config_base", SCANDIR_SORT_ASCENDING);
  // var_dump($possible_configs);
  $conf_file_list="<option value=\"\" selected>&nbsp; -- Select config file -- &nbsp; </option>";
  foreach($possible_configs as $conf_num => $conf_file) {
    // echo "$conf_file_list  <br>\n";
    // echo "$conf_num  :  $conf_file     ";
    if (substr($conf_file,0,1) == ".") { continue ; };
    if ($conf_file == $config_name) { continue ; };
    $contents = yaml_parse_file( "$config_base/$conf_file");
    if (!(false === $contents) && isset($contents['title'])) {
      $title = $contents['title'];
      if (isset($TriSeries['comment']))
        $comment = $contents['comment'];
      else
        $comment = "";
      $conf_file_list = $conf_file_list . "<option value=\"$conf_file\" title=\"$comment\"> $conf_file &nbsp; : &nbsp; $title </option>";
      unset($contents);
    }
  }
  // var_dump($result_list);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>TriSeries Points Configuration</title>
    <link rel="stylesheet" href="style.css">
<?php
  $icon_file=dirname(__FILE__) . "/icons.inc";
  if (file_exists($icon_file))
    readfile($icon_file);
?>
  </head>
<body>
<?php
  #echo "<pre>";
  #var_dump($_POST);
  #echo "</pre>";
?>
  <div style="float:right">
   <a href="/">Main Menu</a>&nbsp; &nbsp; 
  </div>
 <div align="center" style="padding-bottom:5px;">
  <h2>Configuration</h2>
 </div>
  <form name="frmConfig" id="frmConfig" method="post" action="">
    <input type="hidden" name="update_list" value="" id="update_list">
  <script type="text/javascript">
    function haveUpdate(){
            update_list="";
            update_count=0;
            orig_val="";
            input_fields=document.getElementsByTagName("input");
            for (let i = 0; i < input_fields.length; i++) {
              if (input_fields[i].id.substr(0,4) == "Orig") {
                new_field=document.getElementById(input_fields[i].id.substr(4));
                if (new_field != null) {
                  if (input_fields[i].value != new_field.value) {
                    //update_list=update_list + ";" + input_fields[i].id.substr(4) + ":" + input_fields[i].value + ":" + new_field.value;
                    update_list=update_list + ";" + input_fields[i].id.substr(4);
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
    echo "<td colspan=\"3\"><input type=\"hidden\" name=\"OrigTitle\" value=\"$safe_title\" id=\"OrigTitle\">";
    echo "<input type=\"text\" size=\"30\" placeholder=\"Title\" name=\"Title\" id=\"Title\" class=\"txtField\" required value=\"$safe_title\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Comments </th>\n";
    echo "<td colspan=\"3\"><input type=\"hidden\" name=\"OrigComment\" value=\"$safe_comment\" id=\"OrigComment\">";
    echo "<input type=\"text\" size=\"60\" placeholder=\"Comments\" name=\"Comment\" id=\"Comment\" class=\"txtField\" value=\"$safe_comment\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    echo "<tr>\n <th class=\"listheader\"> Local_Name </th>\n";
    echo "<td colspan=\"1\"><input type=\"hidden\" name=\"OrigLocal_Name\" value=\"$safe_Local_Name\" id=\"OrigLocal_Name\">";
    echo "<input type=\"text\" size=\"20\" placeholder=\"Local_Name\" name=\"Local_Name\" id=\"Local_Name\" class=\"txtField\" value=\"$safe_Local_Name\" oninput=\"haveUpdate()\" ></td>\n";

    $score_width="120px";
    echo "<th class=\"listheader\"> Score Style </th>\n";
    echo "<td><input type=\"hidden\" name=\"OrigScore_Style\" value=\"$safe_Score_Style\" id=\"OrigScore_Style\">";
    echo "<select name=\"Score_Style\" id=\"Score_Style\" style=\"width: $score_width\" onchange=\"haveUpdate()\">  $safe_Score_Style_opt  </select></td>";
    echo "</tr>\n";

    echo "</tr>\n";

    echo "<tr>\n";

    echo "<th colspan=\"4\" class=\"listheader\"> Past Points </th>\n";

    echo "</tr>\n";

    echo "<tr><td colspan=\"4\"><table border=\"2\" cellpadding=\"4\">";
    echo "<tr>\n<th class=\"listheader\"> Event &nbsp  <div style=\"float:right\">&nbsp;<sup>Club</sup></div></th>\n";

    $club_num=array();
    $num_club=array();
    $i=1;
    foreach ($scores as $rnd => $points) {
      foreach ($points as $club => $pts) {
        if (!isset($club_num[$club])) {
          $safe_club=htmlspecialchars($club);
          echo "<td><input type=\"hidden\" value=\"$safe_club\" id=\"Origclub_$i\">";
          echo "<input type=\"text\" size=\"10\" placeholder=\"$safe_club\" name=\"club[$i]\" id=\"club_$i\" class=\"txtField\" value=\"$safe_club\" oninput=\"haveUpdate()\" ></td>\n";
          $num_club[$i]=$safe_club;
          $club_num[$safe_club]=$i++;
        }
      }
    }
    
    echo "<td><input type=\"hidden\" value=\"\" id=\"Origclub_$i\">";
    echo "<input type=\"text\" size=\"10\" placeholder=\"new\" name=\"club[$i]\" id=\"club_$i\" class=\"txtField\" value=\"\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";

    $i=1;
    foreach ($scores as $rnd => $points) {
      echo "<tr>\n";
      $safe_rnd=htmlspecialchars($rnd);
      echo "<td><input type=\"hidden\" value=\"$safe_rnd\" id=\"Origevt_name_$i\">";
      echo "<input type=\"text\" size=\"20\" placeholder=\"$safe_rnd\" name=\"evt_name[$i]\" id=\"evt_name_$i\" class=\"txtField\" value=\"$safe_rnd\" oninput=\"haveUpdate()\" ></td>\n";
      $j=1;
      while (isset($num_club[$j])) {
        if (isset($points[$num_club[$j]]))
          $safe_points=intval($points[$num_club[$j]]);
        else
          $safe_points=0;
        #echo "<td><input type=\"hidden\" name=\"Origpoints[$i][$j]\" value=\"$safe_points\" id=\"Origpoints_$i_$j\">";
        echo "<td><input type=\"hidden\" value=\"$safe_points\" id=\"Origpoints_$i-$j\">";
        echo "<input type=\"number\" size=\"3\" min=\"0\" placeholder=\"$safe_points\" name=\"points[$i][$j]\" id=\"points_$i-$j\" class=\"input_number\" value=\"$safe_points\" oninput=\"haveUpdate()\" ></td>\n";
        $j++;
      }
      echo "</tr>\n";
      $i++;
    }
    echo "<td><input type=\"hidden\" value=\"\" id=\"Origevt_name_$i\">";
    echo "<input type=\"text\" size=\"20\" placeholder=\"new\" name=\"evt_name[$i]\" id=\"evt_name_$i\" class=\"txtField\" value=\"\" oninput=\"haveUpdate()\" ></td>\n";
    echo "</tr>\n";
    echo "</table></td></tr>\n";

    echo "<td colspan=\"1\" align=right style=\"border: 0px\"><input id=\"submit-changes\" type=\"submit\" name=\"submit-changes\" value=\"Save Changes\" disabled formenctype=\"multipart/form-data\"></td>";
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
   <a href="config_triseries_save.php"> Download Config </a>
  </div>
  </form>
 </body>
</html>
