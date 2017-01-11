<?php
include("Festival.php");

function showFestival($festivalName, $year, $oneAct)
{
    if ($festivalName == null) {
        printf("Festival name cannot be null");
        exit ();
    }

    if ($year == null) {
        $year = 2016;
    }
    if ($oneAct == null) {
        $oneAct = "";
    }

    printFestivalInfo($festivalName, $year, $oneAct);
}

function printFestivalInfo($festivalName, $year, $oneAct) {
    $conn = getDbConnection();

    $festival = getFestival($conn, $festivalName, $year, $oneAct);

    if ($festival == null){
        $conn->close();
        echo "<h2>No Data returned for Festival [" . $festivalName . "] for " . $year . "</h2>";
    }

    addNightsToFestival($conn, $festival, $year, $oneAct);

    printFormattedFestivalInfo($festival, $conn, $year, $oneAct);


}

function printFormattedFestivalInfo($festivalMetaInfo, $festivalNightsQueryResult, $festivalName, $conn, $year, $oneAct)
{
    echo "<header class=\"festival-header\">$festivalMetaInfo</header>";

    if ($festivalNightsQueryResult->num_rows == 0) {
        echo "<header class=\"festival-header\">";
        echo "<h2>No nights have been confirmed for this festival yet</h2>";
        echo "</header>";
        return;
    }

    echo "<ul class=\"nights\">";

    $festivalResults = getFestivalResults($festivalName, $conn, $year, $oneAct);
    while ($row = $festivalNightsQueryResult->fetch_assoc()) {
        $isOpen = isOpen($conn, $row ["GROUP"], $oneAct);

        if ($oneAct == "") {
            $play = getPlayForGroup($conn, $row ["GROUP"], $year);
        } else {
            $play = $row ["PLAY"];
        }

        if ($oneAct == "") {
            $result = getThreeActResultForNight($festivalResults, $isOpen, $row ["GROUP"]);
        } else {
            $result = getOneActResultForNight($festivalResults, $isOpen, $row ["GROUP"], $play);
        }

        $openOrConfinedSection = getOpenOrConfinedSection($isOpen, $result);

        $author = getAuthorOfPlay($conn, $play, $oneAct);
        $formattedDate = formatDate($row ["DATE"]);

        echo "<li class=\"night\">";

        echo $openOrConfinedSection;

        echo "<div class=\"night-details\">";
        echo "<h4>" . $row ["GROUP"] . "</h4>";
        printPresentLine($row ["GROUP"]);

        echo "<h4>" . $play . "<span class=\"by\">  by  </span>" . $author . "</h4>";
        echo "</div>";

        echo "<time class=\"date\">" . $formattedDate . "</time>";

        echo "</li>";
    }

    echo "</ul>";
}

function printPresentLine($groupName)
{
    if (substr($groupName, -1) == "s" || substr($groupName, -1) == "S") {
        echo "<h5>present</h5>";
    } else {
        echo "<h5>presents</h5>";
    }
}

function getOpenOrConfinedSection($isOpen, $result)
{
    $formattedResult = getOrdinal($result);
    if ($isOpen) {
        return "<span class=\"open competition\">Open$formattedResult</span>";
    } else {
        return "<span class=\"confined competition\">Confined$formattedResult</span>";
    }
}

function getOrdinal($result)
{
    if ($result == -1) {
        return "";
    }
    if ($result == 1) {
        return " 1st";
    } else if ($result == 2) {
        return " 2nd";
    } else {
        return " 3rd";
    }
}

function getOneActResultForNight($festivalResults, $isOpen, $group, $play)
{
    if ($festivalResults == null) {
        return -1;
    }

    if ($isOpen) {
        if ($group == $festivalResults ["OPEN_FIRST"] && $play == $festivalResults ["OPEN_FIRST_PLAY"]) {
            return 1;
        } else if ($group == $festivalResults ["OPEN_SECOND"] && $play == $festivalResults ["OPEN_SECOND_PLAY"]) {
            return 2;
        } else if ($group == $festivalResults ["OPEN_THIRD"] && $play == $festivalResults ["OPEN_THIRD_PLAY"]) {
            return 3;
        } else {
            return -1;
        }
    } else {
        if ($group == $festivalResults ["CONFINED_FIRST"] && $play == $festivalResults ["CONFINED_FIRST_PLAY"]) {
            return 1;
        } else if ($group == $festivalResults ["CONFINED_SECOND"] && $play == $festivalResults ["CONFINED_SECOND_PLAY"]) {
            return 2;
        } else if ($group == $festivalResults ["CONFINED_THIRD"] && $play == $festivalResults ["CONFINED_THIRD_PLAY"]) {
            return 3;
        } else {
            return -1;
        }
    }
}

function getThreeActResultForNight($festivalResults, $isOpen, $group)
{
    if ($festivalResults == null) {
        return -1;
    }

    if ($isOpen) {
        if ($group == $festivalResults ["OPEN_FIRST"]) {
            return 1;
        } else if ($group == $festivalResults ["OPEN_SECOND"]) {
            return 2;
        } else if ($group == $festivalResults ["OPEN_THIRD"]) {
            return 3;
        } else {
            return -1;
        }
    } else {
        if ($group == $festivalResults ["CONFINED_FIRST"]) {
            return 1;
        } else if ($group == $festivalResults ["CONFINED_SECOND"]) {
            return 2;
        } else if ($group == $festivalResults ["CONFINED_THIRD"]) {
            return 3;
        } else {
            return -1;
        }
    }
}

function getFestivalResults($festivalName, $conn, $year, $oneAct)
{
    $formattedFestivalName = $conn->real_escape_string($festivalName);
    $sql = "SELECT * FROM " . $oneAct . "FESTIVAL_RESULTS_" . $year . " WHERE FESTIVAL='$formattedFestivalName'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row;
    } else {
        return null;
    }
}

function formatDate($sqlDate)
{
    return date("D", strtotime($sqlDate)) . " " . date("jS", strtotime($sqlDate)) . " " . date("M, Y", strtotime($sqlDate));
}

function isOpen($conn, $group, $oneAct)
{
    $formattedGroup = $conn->real_escape_string($group);
    $sql = "select LEVEL from " . $oneAct . "DRAMA_GROUP where NAME='$formattedGroup'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "No rows returned when querying competition level for group $formattedGroup";
    }
    if ($row ["LEVEL"] == "OPEN") {
        return true;
    } else if ($row ["LEVEL"] == "CONFINED") {
        return false;
    } else {
        return "Invalid value for Competition Level for group: $formattedGroup";
    }
}

function getPlayForGroup($conn, $group, $year)
{
    $formattedGroup = $conn->real_escape_string($group);
    $sql = "select PLAY_" . $year . " from DRAMA_GROUP where NAME='$formattedGroup'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row ["PLAY_2016"];
}

function getAuthorOfPlay($conn, $play, $oneAct)
{
    $formattedPlay = $conn->real_escape_string($play);
    $sql = "select AUTHOR from " . $oneAct . "PLAY where NAME='$formattedPlay'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row ["AUTHOR"];
}

function addNightsToFestival($conn, $festival, $year, $oneAct)
{
    $tableName = $oneAct . "NIGHTS_" . $year;
    if ($oneAct != ""){
        $joinWithGroupTable = " JOIN DRAMA_GROUP" . $year . " where ";
    }

    $sql = "SELECT DATE, GROUP, PLAY FROM " . $tableName . $joinWithGroupTable . " where FESTIVAL='?' order by DATE ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $festival->name);
        $stmt->execute();
        $stmt->bind_result($date, $group $play);

        while ($row = $stmt->fetch()) {
            $festival->addNight($date, $group, $play);
            echo $date . "---" . $group . "\n";
        }

        $stmt->close();

    }
}

function getFestival($conn, $festivalName, $year, $oneAct)
{
    $formattedFestivalName = $conn->real_escape_string($festivalName);
    $tableName = $oneAct . "FESTIVALS_" . $year;
    $sql = "SELECT NAME, ADDRESS1, ADDRESS2, ADDRESS3, ORDINAL, URL, ADJUDICATOR, ADMISSION, BOOKING, TIMES FROM " . $tableName . " where Name=?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $formattedFestivalName);

        $stmt->execute();


        $stmt->bind_result($name, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times);

        if ($row = $stmt->fetch()) {
            $formattedName = $conn->real_escape_string($name);
            $festival = new Festival($formattedName, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times);
            $returnVal = $festival->getFormattedHeader();
        } else {
            $returnVal = null;
        }

        $stmt->close();
        return $returnVal;
    } else {
        printf("unable to prepare statement");
        exit ();
    }
}

function getDbConnection() {


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
