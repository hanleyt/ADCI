<?php
require 'commonFunctions.php';

//echo "TEST\n"; printResultsTable(2017, "Open", false);

function printResultsTable($year, $competition, $oneAct)
{
    assertValidOneAct($oneAct);
    assertValidCompetition($competition);
    assertValidYear($year);

    $oneActStr = getOneActString($oneAct);

    $resultsArray = buildResultsArray($competition, $year, $oneActStr);
    printTable($resultsArray);
}

function assertValidCompetition($competition)
{
    if ($competition === null) {
        pageError("competition cannot be null");
    }

    if ($competition !== "Open" && $competition !== 'Confined') {
        pageError("invalid competition parameter: [$competition]");
    }
}

function buildResultsArray($competition, $year, $oneAct)
{
    $conn = getDbConnection();
    $resultsArray = array(
        array()
    );
    $groupAndPlayList = getListOfGroupsAndPlays($conn, $competition, $year, $oneAct);

    $rowNum = 0;
    while ($row = $groupAndPlayList->fetch_assoc()) {

        $groupName = $row ['NAME'];
        $play = $row ["PLAY"];

        $resultsArray [$rowNum] ['Group'] = $groupName;
        $resultsArray [$rowNum] ['Play'] = $play;
        $resultsArray [$rowNum] ['numWins'] = getNumPlacings($conn, $groupName, $competition, $year, $oneAct, 'FIRST');
        $resultsArray [$rowNum] ['numSeconds'] = getNumPlacings($conn, $groupName, $competition, $year, $oneAct, 'SECOND');
        $resultsArray [$rowNum] ['numThirds'] = getNumPlacings($conn, $groupName, $competition, $year, $oneAct, 'THIRD');
        $resultsArray [$rowNum] ['numFestivalsEntered'] = getNumFestivalsEntered($conn, $groupName, $year, $oneAct);
        $resultsArray [$rowNum] ['totalPoints'] = calculatePointsFromTopThreeFestivals($resultsArray [$rowNum] ['numWins'], $resultsArray [$rowNum] ['numSeconds'], $resultsArray [$rowNum] ['numThirds']);
        $resultsArray [$rowNum] ['noPointsFestivals'] = getNumNoPlacings($conn, $groupName, $year, $oneAct);
        $rowNum++;
    }

    $sort = array();
    foreach ($resultsArray as $key => $row) {
        $sort ['totalPoints'] [$key] = $row ['totalPoints'];
        $sort ['numWins'] [$key] = $row ['numWins'];
        $sort ['numSeconds'] [$key] = $row ['numSeconds'];
        $sort ['numThirds'] [$key] = $row ['numThirds'];
        $sort ['noPointsFestivals'] [$key] = $row ['noPointsFestivals'];
    }
    array_multisort($sort ['numWins'], SORT_DESC, $sort ['numSeconds'], SORT_DESC, $sort ['numThirds'], SORT_DESC, $sort ['noPointsFestivals'], SORT_ASC, $resultsArray);

    return $resultsArray;
    $conn->close();
}

function printTable($resultsArray)
{
    $rowCount = count($resultsArray);
    printTableHeader();
    for ($rowNum = 0; $rowNum < $rowCount; $rowNum++) {
        $groupName = $resultsArray [$rowNum] ['Group'];
        $play = $resultsArray [$rowNum] ['Play'];
        $numWins = $resultsArray [$rowNum] ['numWins'];
        $numSeconds = $resultsArray [$rowNum] ['numSeconds'];
        $numThirds = $resultsArray [$rowNum] ['numThirds'];
        $numFestivalsEntered = $resultsArray [$rowNum] ['numFestivalsEntered'];
        $totalPoints = $resultsArray [$rowNum] ['totalPoints'];
        $noPointsFestivals = $resultsArray [$rowNum] ['noPointsFestivals'];
        $position = $rowNum + 1;

        echo "
                <tr>
                    <td class = \"text-right\">$position</td>
                    <td class = \"text-left\">$groupName</td>
                    <td class = \"text-left\">$play</td>    
                    <td class = \"text-right\">$numFestivalsEntered</td>
                    <td class = \"text-right\">$numWins</td>
                    <td class = \"text-right\">$numSeconds</td>
                    <td class = \"text-right\">$numThirds</td>
                    <td class = \"text-right\">$noPointsFestivals</td>
                    <td class = \"text-right\">$totalPoints</td>
                </tr>";
    }
    printTableEnd();
}

function printTableHeader()
{
    echo "
        <div class=\"table-wrapper\">
        <table id = \"table-fill\">
            <thead>
                <tr>
                    <th id = \"header\">#</th>
                    <th id = \"header\">Group</th>
                    <th id = \"header\">Play</th>
                    <th id = \"header\">Entered</th>
                    <th id = \"header\">Wins</th>
                    <th id = \"header\">2nds</th>
                    <th id = \"header\">3rds</th>
                    <th id = \"header\">NP</th>
                    <th id = \"header\">Points</th>
                </tr>
            </thead>
            
            <tbody class = \"table-hover\">";
}

function printTableEnd()
{
    echo "</tbody>
       </table>
       </div>";
}

function calculatePointsFromTopThreeFestivals($numWins, $numSeconds, $numThirds)
{
    $MAX_COUNTED_FESTIVALS = 3;

    $alreadyCountedFestivals = 0;
    $totalPoints = 0;

    if ($numWins >= $MAX_COUNTED_FESTIVALS) {
        $totalPoints = 12 * $MAX_COUNTED_FESTIVALS;
        return $totalPoints;
    } else {
        $totalPoints += $numWins * 12;
        $alreadyCountedFestivals += $numWins;
    }

    if ($numSeconds + $alreadyCountedFestivals >= $MAX_COUNTED_FESTIVALS) {
        $numSecondPlacesCounted = $MAX_COUNTED_FESTIVALS - $alreadyCountedFestivals;
        $totalPoints += $numSecondPlacesCounted * 5;
        return $totalPoints;
    } else {
        $totalPoints += $numSeconds * 5;
        $alreadyCountedFestivals += $numSeconds;
    }

    if ($numThirds + $alreadyCountedFestivals >= $MAX_COUNTED_FESTIVALS) {
        $numThirdPlacesCounted = $MAX_COUNTED_FESTIVALS - $alreadyCountedFestivals;
        $totalPoints += $numThirdPlacesCounted * 2;
        return $totalPoints;
    } else {
        $totalPoints += $numThirds * 2;
        $alreadyCountedFestivals += $numThirds;
    }
    return $totalPoints;
}

function array_sort_by_column(&$arr, $col, $dir = SORT_DESC)
{
    $sort_col = array();
    foreach ($arr as $key => $row) {
        $sort_col [$key] = $row [$col];
    }
    array_multisort($sort_col, $dir, $arr);
}

function getNumFestivalsEntered($conn, $groupName, $year, $oneAct)
{
    $formattedGroupName = $conn->real_escape_string($groupName);
    $sql = "SELECT count(*) as num_entries FROM " . $oneAct . "NIGHTS_" . $year . " where " . $oneAct . "NIGHTS_" . $year . ".GROUP='$formattedGroupName' 
        and " . $oneAct . "NIGHTS_" . $year . ".FESTIVAL != 'All-Ireland Open' 
        and " . $oneAct . "NIGHTS_" . $year . ".FESTIVAL != 'All-Ireland Confined'
        and " . $oneAct . "NIGHTS_" . $year . ".FESTIVAL != 'All-Ireland One Act'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row ['num_entries'];
}

function getNumNoPlacings($conn, $groupName, $year, $oneAct)
{
    $formattedGroupName = $conn->real_escape_string($groupName);

    $sql = "SELECT count(*) as num_entries FROM " . $oneAct . "NIGHTS_" . $year . " as N
    INNER JOIN " . $oneAct . "FESTIVAL_RESULTS_" . $year . " as R ON N.FESTIVAL = R.FESTIVAL
    where N.GROUP='$formattedGroupName' 
        and R.FESTIVAL != 'All-Ireland Open' 
        and R.FESTIVAL != 'All-Ireland Confined'
        and R.FESTIVAL != 'All-Ireland One Act' 
        and (R.OPEN_FIRST != '$formattedGroupName' or R.OPEN_FIRST is NULL)
        and (R.OPEN_SECOND != '$formattedGroupName' or R.OPEN_SECOND is NULL)
        and (R.OPEN_THIRD != '$formattedGroupName' or R.OPEN_THIRD is NULL)
        and (R.CONFINED_FIRST != '$formattedGroupName' or R.CONFINED_FIRST is NULL)
        and (R.CONFINED_SECOND != '$formattedGroupName' or R.CONFINED_SECOND is NULL)
        and (R.CONFINED_THIRD != '$formattedGroupName' or R.CONFINED_THIRD is NULL)";

    $result = $conn->query($sql);
    if ($result) {
        if ($row = $result->fetch_assoc()) {
            return $row ['num_entries'];
        }
    } else {
        return 0;
    }
}

function getNumPlacings($conn, $groupName, $competition, $year, $oneAct, $place)
{
    $formattedGroupName = $conn->real_escape_string($groupName);
    $groupNameColumn = $competition . "_" . $place;
    $resultsTable = $oneAct . "FESTIVAL_RESULTS_" . $year;

    $sql = "SELECT count(*) as num_placings FROM $resultsTable where $groupNameColumn='$formattedGroupName'
    and $resultsTable.FESTIVAL != 'All-Ireland Open'
    and $resultsTable.FESTIVAL != 'All-Ireland Confined'
    and $resultsTable.FESTIVAL != 'All-Ireland One Act'";
    
    $result = $conn->query($sql);
    if ($result) {
        if ($row = $result->fetch_assoc()) {
            return $row ['num_placings'];
        }
    }
    return 0;
}

function getListOfGroupsAndPlays($conn, $competition, $year, $oneAct)
{
    $groupTable = $oneAct . "DRAMA_GROUP_" . $year;
    $sql = "SELECT NAME, `PLAY` FROM $groupTable where LEVEL='$competition'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return $result;
    } else {
        pageError("No groups and plays returned for [$oneAct] [$competition] competition for [$year]. SQL=$sql");
    }

}
