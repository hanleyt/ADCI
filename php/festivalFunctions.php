<?php
include("Festival.php");

date_default_timezone_set("Europe/Dublin");

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

function printFestivalInfo($festivalName, $year, $oneAct)
{
    $conn = getDbConnection();

    $festival = getFestival($conn, $festivalName, $year, $oneAct);

    if ($festival == null) {
        $conn->close();
        echo "<h2>No data returned for festival [" . $festivalName . "] for " . $year . "</h2>";
    }

    addNightsToFestival($conn, $festival, $year, $oneAct);

    echo $festival->getFormattedHeader();

    printFormattedFestivalInfo($festival, $conn, $year, $oneAct);


}

function printFormattedFestivalInfo($festival, $conn, $year, $oneAct)
{
    if ($festival->length() == 0) {
        echo "<header class=\"festival-header\">";
        echo "<h2>No nights have been confirmed for this festival yet</h2>";
        echo "</header>";
        return;
    }

    echo "<ul class=\"nights\">\n";

    $festivalResults = getFestivalResults($festival->name, $conn, $year, $oneAct);

    foreach ($festival->getNights() as &$night) {
        $isOpen = isOpen($conn, $night->group, $oneAct);


        if ($oneAct == "") {
            $result = getThreeActResultForNight($festivalResults, $isOpen, $night->group);
        } else {
            $result = getOneActResultForNight($festivalResults, $isOpen, $night->group, $night->play);
        }

        $openOrConfinedSection = getOpenOrConfinedSection($isOpen, $result);

        $author = getAuthorOfPlay($conn, $night->play, $oneAct);
        $formattedDate = formatDate($night->date);

        echo "<li class=\"night\">\n";

        echo $openOrConfinedSection;

        echo "<div class=\"night-details\">\n";
        echo "<h4>" . $night->group . "</h4>\n";
        printPresentLine($night->group);

        echo "<h4>" . $night->play . "<span class=\"by\">  by  </span>" . $author . "</h4>\n";
        echo "</div>\n";

        echo "<time class=\"date\">" . $formattedDate . "</time>\n";

        echo "</li>\n";
    }

    echo "</ul>\n";
}

function printPresentLine($groupName)
{
    if (substr($groupName, -1) == "s" || substr($groupName, -1) == "S") {
        echo "<h5>present</h5>\n";
    } else {
        echo "<h5>presents</h5>\n";
    }
}

function getOpenOrConfinedSection($isOpen, $result)
{
    $formattedResult = getOrdinal($result);
    if ($isOpen) {
        return "<span class=\"open competition\">Open$formattedResult</span>\n";
    } else {
        return "<span class=\"confined competition\">Confined$formattedResult</span>\n";
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
    if ($oneAct == "") {
        $tableToJoin = "DRAMA_GROUP_" . $year;
//        $tableToJoin = "DRAMA_GROUP";
        //TODO Change db table DRAMA_GROUP to DRAMA_GROUP_2016 in database
        $joinWithGroupTable = " JOIN " . $tableToJoin . " on " . $tableToJoin . ".NAME=" . $tableName . ".`GROUP`";
    } else {
        $joinWithGroupTable = "";
    }

    $sql = "SELECT DATE, `GROUP`, PLAY FROM " . $tableName . $joinWithGroupTable . " where " . $tableName . ".FESTIVAL=? order by DATE ASC";

    if ($stmt = $conn->prepare($sql)) {

        $stmt->bind_param("s", $festival->name);
        $stmt->execute();
        $stmt->bind_result($date, $group, $play);

        while ($row = $stmt->fetch()) {
            $festival->addNight($date, $group, $play);
        }

        $stmt->close();

    } else {
        printf("unable to prepare statement to get festival nights");
        exit ();

    }
}

function getFestival($conn, $festivalName, $year, $oneAct)
{
    $formattedFestivalName = $conn->real_escape_string($festivalName);
    $tableName = $oneAct . "FESTIVALS_" . $year;
    $sql = "SELECT ADDRESS1, ADDRESS2, ADDRESS3, ORDINAL, URL, ADJUDICATOR, ADMISSION, BOOKING, TIMES FROM " . $tableName . " where Name=?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $formattedFestivalName);

        $stmt->execute();

        $stmt->bind_result($addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times);

        if ($row = $stmt->fetch()) {
            $festival = new Festival($formattedFestivalName, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times);
            $returnVal = $festival;
        } else {
            $returnVal = null;
        }

        $stmt->close();
        return $returnVal;
    } else {
        printf("unable to prepare statement to get festival info");
        exit ();
    }
}

function getDbConnection()
{


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
