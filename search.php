<?php

$q = strtolower($_GET["term"]);
if (!$q) return;

include 'DB.php';
$DB = new DB(parse_ini_file('db-eve.ini'));

$results = $DB->qa("
    SELECT a.typeID, b.typeName FROM `dgmTypeAttributes` a
    INNER JOIN `invTypes` b ON (a.typeID = b.typeID)
    WHERE a.attributeID = 633 AND  (a.valueInt = 0 OR a.valueFloat = 0) AND b.typeName LIKE ?", '%'.$q.'%');

$return = array();
foreach ($results as $item) {
    array_push($return, array("typeID"=>$item[0], "typeName"=>$item[1], "label"=>$item[1]));
    if (count($return) > 11)
		break;
}    

echo json_encode($return);

?>
