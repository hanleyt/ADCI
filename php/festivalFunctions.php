<?php
printCss();

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

    $festivalMetaInfo = getFestivalMetaInfo($conn, $festivalName, $year, $oneAct);

    $festivalNightsQueryResult = queryFestivalNights($conn, $festivalName, $year, $oneAct);

    printFormattedFestivalInfo($festivalMetaInfo, $festivalNightsQueryResult, $festivalName, $conn, $year, $oneAct);

    $conn->close();
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
            $play = getPlayForGroup($conn, $row ["GROUP"], $year, $oneAct);
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

function getPlayForGroup($conn, $group, $year, $oneAct)
{
    $formattedGroup = $conn->real_escape_string($group);
    $sql = "select PLAY_" . $year . " from " . $oneAct . "DRAMA_GROUP where NAME='$formattedGroup'";
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

function queryFestivalNights($conn, $festivalName, $year, $oneAct)
{
    $formattedFestivalName = $conn->real_escape_string($festivalName);
    $tableName = $oneAct . "NIGHTS_" . $year;

    $sql = "SELECT DATE, GROUP FROM " . $tableName . " where FESTIVAL='$formattedFestivalName' order by DATE ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $formattedFestivalName);
        $stmt->execute();
        $stmt->bind_result($date, $group);

        if ($row = $stmt->fetch()) {
            echo $date . "---" . $group . "\n";
        } else {
            echo "<h2>No Data returned for Festival [" . $formattedFestivalName . "] for " . $year . "</h2>";
        }

        while ($row = $stmt->fetch()) {
            echo $date . "---" . $group . "\n";
        }

        $stmt->close();

    }
}

function getFestivalMetaInfo($conn, $festivalName, $year, $oneAct)
{
    $formattedFestivalName = $conn->real_escape_string($festivalName);
    $tableName = $oneAct . "FESTIVALS_" . $year;
    $sql = "SELECT NAME, ADDRESS1, ADDRESS2, ADDRESS3, ORDINAL, URL, ADJUDICATOR, ADMISSION, BOOKING, TIMES FROM " . $tableName . " where Name=?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $formattedFestivalName);

        $stmt->execute();
        $stmt->bind_result($name, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times);
        if ($row = $stmt->fetch()) {
            $returnStr = formatFestivalMetaInfoQuery($name, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times);
        } else {
            $returnStr = "<h2>No Data returned for Festival [" . $formattedFestivalName . "] for " . $year . "</h2>";
        }
        $stmt->close();
        return $returnStr;
    } else {
        printf("unable to prepare statement");
        exit ();
    }
}

function formatFestivalMetaInfoQuery($name, $addr1, $addr2, $addr3, $ordinal, $url, $adjudicator, $admission, $booking, $times)
{
    $returnString = "";

    $returnString .= "<h1>";
    if ($ordinal != null) {
        $returnString .= $ordinal . " ";
    }
    $returnString .= $name . " Drama Festival</h1>";

    if ($addr1 != null) {
        $returnString .= "<h2>" . $addr1 . ", ";
    }
    if ($addr2 != null) {
        $returnString .= $addr2 . ", ";
    }
    if ($addr3 != null) {
        $returnString .= "Co. " . $addr3 . "</h2>";
    }
    if ($url != null) {
        $returnString .= "<h3><a href=\"http://$url\">$url</a></h3>";
    }
    if ($adjudicator != null) {
        $returnString .= "<h3>Adjudicator: " . $adjudicator . "</h3>";
    }
    if ($admission != null) {
        $returnString .= "<h3>Admission: " . $admission . "</h3>";
    }
    if ($booking != null) {
        $returnString .= "<h3>Booking: " . $booking . "</h3>";
    }
    if ($times != null) {
        $returnString .= "<h3>Curtain: " . $times . "</h3>";
    }

    return $returnString;
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

function printCss()
{
    $filename1 = 'http://adci.ie/css/festival.css';
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$filename1\" media=\"all\">";
}
