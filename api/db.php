<?php
/**
 * Created by PhpStorm.
 * User: agust
 * Date: 9/30/2017
 * Time: 6:38 AM
 */
/**
 * Class db
 * Common database access methods
 */
error_reporting(E_ERROR | E_PARSE);

define('DB_HOST', 'localhost');
define('DB_USER', 'agustin');
define('DB_PASSWORD', '7788');
define('DB_DB', 'hack2019');

class db
{
    public $conn;
    private $returnType;
    /**
     * Connect to the NONO Database
     */
    public function __construct()
    {

        // Create connection
        $connect = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DB);
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        global $conn;
        $conn = $connect;
    }

    public function Get($sql)
    {
        global $conn;
        if (is_null($conn)) {
            echo "Database is not connected.";
        }
        $result = $conn->query($sql);
        $output = array();
        $output = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($output, JSON_PRETTY_PRINT);
    }
    public function GetAndReturn($sql)
    {
        global $conn;
        if (is_null($conn)) {
            echo "Database is not connected.";
        }
        $result = $conn->query($sql);
        return $result;
    }
    public function Insert($sql)
    {
        global $conn;
        $conn->query($sql);
    }
    public function InsertQuietly($sql)
    {
        global $conn;
        if ($conn->query($sql) === false) {
            //echo json_encode(array('success' => 'false'));
        } else {
            //echo json_encode(array('success' => 'true'));
        }
    }

    public function GetCredentials()
    {
        return (array("host" => DB_HOST, "user" => DB_USER, "password" => DB_PASSWORD, "database" => DB_DB));
    }
}
class HackData
{
    public function GetFoodEmissions($food)
    {
        $hd = new db();
        return $hd->Get("select co2ekg  from food, emissions where food.name='$food' and food.groupid=emissions.groupid");
    }
    public function GetGroups()
    {
        $hd = new db();
        return $hd->Get("select name,groupid from emissions order by name");
    }
    public function FindFood($search)
    {
        $hd = new db();
        return $hd->Get("select id, groupid, name from food where name like '$search%'");
    }
    public function GetGroupLessFoods()
    {
        $hd = new db();
        return $hd->Get("select id, groupid, name from food where groupid=0 order by name");
    }
    public function Barcode($barcode)
    {
        $cacheStatus = "not cached";
        $hd = new db();
        $url = "https://world.openfoodfacts.org/api/v0/product/" . $barcode . ".json";
        $filename = '/var/www/html/cache/' . $barcode;
        if (file_exists($filename)) {
            $json = file_get_contents($filename);
            $cacheStatus = "cached";
        } else {
            $json = file_get_contents($url);
            file_put_contents($filename, $json);
        }

        $weight = 16;
        $carbon = 0;
        $temp = '';
        $res = [];
        $high = 0;
        $low = 100;
        $obj = json_decode($json);
        $size = sizeof($obj->product->ingredients_hierarchy);
        ($size > 5 ? $size = 5 : $size);
        for ($x = 0; $x <= $size; $x++) {
            $name = str_replace('en:', '', $obj->product->ingredients_hierarchy[$x]);
            if ($name == '') {
                continue;
            }
            $name = strtolower($name);
            $res = $this->GetIngredient($name);
            if ($res[name] == '') {
                $temp = $this->InsertIngredient($name);
            } else {
                $res = $hd->GetAndReturn("select co2ekg from emissions where groupid='$res[groupid]'");
                $row = $res->fetch_assoc();
                $carbon = $carbon + ($row["co2ekg"] * $weight);
                $weight = $weight / 2;

                if ($row["co2ekg"] < $low) {
                    $low = $row["co2ekg"];
                }
                if ($row["co2ekg"] > $high) {
                    $high = $row["co2ekg"];
                }
            }

            $obj->product->ingredients_hierarchy[$x] = $name;
        }
        $carbon = $carbon / 31;

        $ingredients = $obj->product->ingredients_hierarchy;
        $b = array('name' => $obj->product->product_name,
            'ingredients' => $obj->product->ingredients_hierarchy,
            'image' => $obj->product->mage_front_thumb_url,
            'cache' => $filename,
            'cache_status' => $cacheStatus,
            'carbon' => $carbon,
            'highcarbon' => $high,
            'lowcarbon' => $low,
            'temp' => $res,
        );
        echo json_encode($b);
    }

    public function GetIngredient($name)
    {
        $hd = new db();
        $id = 0;
        $groupid = 0;
        $query = "select id,groupid,name from food where name='$name'";
        $res = $hd->GetAndReturn($query);
        while ($row = $res->fetch_assoc()) {
            $name = $row["name"];
            $groupid = $row["groupid"];
            $id = $row["id"];
            return array('name' => $row["name"], 'groupid' => $row["groupid"], 'id' => $row["id"]);
        }
    }

    public function InsertIngredient($name)
    {
        $hd = new db();
        $max = $hd->GetAndReturn("select coalesce(max(id)+1,1) as max from food;");
        while ($row = $max->fetch_assoc()) {
            $maxx = $row["max"];
            $query = "insert into food values($maxx,0,'$name'); ";
            $go = $hd->Insert($query);
            return $max;
        }
    }

    public function AssignGroup($groupid, $id)
    {
        $hd = new db();
        $res = $hd->Insert("update food set groupid = $groupid where id= $id;");
        echo json_encode($res);
    }

}
