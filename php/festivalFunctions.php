<?php
include("Festival.php");
include("Group.php");

date_default_timezone_set("Europe/Dublin");
//printFestivalInfo("Clare", 2016, "");

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

    $nights = $festival->nights;

    foreach ($nights as &$night) {
        $isOpen = $night->group->isOpen();

        $result = getGroupsResultInFestival($night->group, $festival->name, $conn, $year, $oneAct);

        $openOrConfinedSection = getOpenOrConfinedSection($isOpen, $result);

        $author = getAuthorOfPlay($conn, $night->play, $oneAct);
        $formattedDate = formatDate($night->date);

        echo "<li class=\"night\">\n";

        echo $openOrConfinedSection;

        echo "<div class=\"night-details\">\n";
        echo "<h4>" . $night->group->name . "</h4>\n";
        printPresentLine($night->group->name);

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

function getGroupsResultInFestival($group, $festivalName, $conn, $year, $oneAct)
{
    $sql = "SELECT OPEN_FIRST, OPEN_SECOND, OPEN_THIRD, CONFINED_FIRST, CONFINED_SECOND, CONFINED_THIRD 
FROM " . $oneAct . "FESTIVAL_RESULTS_" . $year . " WHERE FESTIVAL=?";

    if ($stmt = $conn->prepare($sql)) {

        $stmt->bind_param("s", $festivalName);
        $stmt->execute();

        $stmt->bind_result($open1, $open2, $open3, $confined1, $confined2, $confined3);

        if ($row = $stmt->fetch()) {
            if ($group->isOpen()) {
                if ($group->name == $open1) {
                    return 1;
                } else if ($group->name == $open2) {
                    return 2;
                } else if ($group->name == $open3) {
                    return 3;
                } else {
                    return -1;
                }
            } else {
                if ($group->name == $confined1) {
                    return 1;
                } else if ($group->name == $confined2) {
                    return 2;
                } else if ($group->name == $confined3) {
                    return 3;
                } else {
                    return -1;
                }
            }
        } else {
            return -1;
        }
    }else {
        return -1;
    }

}

function formatDate($sqlDate)
{
    return date("D", strtotime($sqlDate)) . " " . date("jS", strtotime($sqlDate)) . " " . date("M, Y", strtotime($sqlDate));
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

    //TODO Change db table DRAMA_GROUP to DRAMA_GROUP_2016 in database
    if ($year == 2016) {
        $tableToJoin = $oneAct . "DRAMA_GROUP";
        $playCol = "PLAY_2016";
    } else {
        $tableToJoin = $oneAct . "DRAMA_GROUP_" . $year;
        $playCol = "PLAY";
    }
    $joinWithGroupTable = " JOIN " . $tableToJoin . " on " . $tableToJoin . ".NAME=" . $tableName . ".`GROUP`";


    $sql = "SELECT DATE, `GROUP`, " . $playCol . ", LEVEL FROM " . $tableName . $joinWithGroupTable . " where " . $tableName . ".FESTIVAL=? order by DATE ASC";
    if ($stmt = $conn->prepare($sql)) {

        $stmt->bind_param("s", $festival->name);
        $stmt->execute();

        $stmt->bind_result($date, $group, $play, $level);

        while ($row = $stmt->fetch()) {
            $group = $conn->real_escape_string($group);
            $festival->addNight($date, new Group($group, $level), $play);
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
