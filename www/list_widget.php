<?php

// controls to add or removed item from list

require_once('../config.php');
require_once('../include/Authorisation.php');
require_once('../include/NameCache.php');

$wfo = @$_REQUEST['wfo'];
$source_id = (int)@$_REQUEST['source_id'];
$value_id = (int)@$_REQUEST['value_id'];
$toggle = @$_REQUEST['toggle'] && $_REQUEST['toggle'] == 'true' ? true : false;
$can_edit = false;

$can_edit = Authorisation::canEditSourceData($source_id);

// is it present or absent on the list
$response = $mysqli->query("SELECT * FROM wfo_scores WHERE wfo_id = '$wfo' AND source_id = $source_id AND value_id = $value_id ");
$rows = $response->fetch_all(MYSQLI_ASSOC);
$response->close();
$present = count($rows) > 0 ? true : false;

// do we want to switch it?
if($can_edit && $toggle){

    if($present){
        $mysqli->query("DELETE FROM wfo_scores WHERE wfo_id = '$wfo' AND source_id = $source_id AND value_id = $value_id ");
        $present = false;
    }else{
        $mysqli->query("INSERT INTO wfo_scores (wfo_id, source_id, value_id) VALUES ('$wfo', $source_id, $value_id)");
        NameCache::cacheName($wfo);
        $present = true;
    }

}

// now we display the appropriate button

if($can_edit){

    $button_colour = $present ? 'danger' : 'success';
    $button_label = $present ? 'Remove' : 'Add';

    echo "<a class=\"btn btn-sm btn-outline-$button_colour\" href=\"#\"
            onclick=\"event.preventDefault(); toggleListMembership(this.parentNode, '$wfo', $source_id, $value_id)\"
            role=\"button\">$button_label</a>";

}