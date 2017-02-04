<?php
/**
 * Created by IntelliJ IDEA.
 * User: tomashanley
 * Date: 04/02/2017
 * Time: 15:50
 */

function getDbConnection()
{
    $servername = "tangelo.webhostingireland.ie";
    $username = "adciie_default";
    $password = "Default123";
    $dbname = "adciie_database";

    $conn = new mysqli ($servername, $username, $password, $dbname); // Create connection

    if (!$conn->set_charset('utf8')) {
        printf("Error loading character set utf8: %s\n", $conn->error);
        exit ();
    }

    if ($conn->connect_error) { // Check connection
        die ("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}


function getOneActString($oneAct)
{
    if ($oneAct) {
        return "ONE_ACT_";
    } else {
        return "";
    }
}

function assertValidOneAct($oneAct)
{
    if (!is_bool($oneAct)) {
        pageError("oneAct: [$oneAct] is not a boolean");
    }
}

function assertValidYear($year)
{
    if ($year === null) {
        pageError("year cannot be null");
    }

    if (!is_numeric($year)) {
        pageError("year: [$year] is not a number");
    }

    if (strlen($year) != 4) {
        pageError("year length must be equal 4");
    }
}

function pageError($msg)
{
    error_log($msg, 0);
    http_response_code(404);
    include(__DIR__ . '/../404.php');
    die();
}
