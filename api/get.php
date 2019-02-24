<?php
require_once 'db.php';
$data = new HackData();

switch ($_GET["type"]) {
    // http://129.8.229.220/api/get.php?type=GetFoodEmissions&food=beef
    case "GetFoodEmissions":
        $food = $_GET["food"];
        $data->GetFoodEmissions($food);
        break;
    // http://129.8.229.220/api/get.php?type=GetGroups
    case "GetGroups":
        $data->GetGroups();
        break;
    case "search":
        $search = $_GET["food"];
        $data->FindFood($search);
        break;
    case "upc":
        $search = $_GET["barcode"];
        $data->Barcode($search);
        break;
    case "groupless":
        $data->GetGroupLessFoods();
        break;
    case "assign":
        $gid = $_GET["groupid"];
        $id = $_GET["id"];
        $data->AssignGroup($gid, $id);
        break;
}
