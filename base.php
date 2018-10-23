<?php
session_start();

$db = parse_url(getenv("DATABASE_URL"));

$pdo = new PDO("pgsql:" . sprintf(
    "host=%s;port=%s;user=%s;password=%s;dbname=%s",
    $db["host"],
    $db["port"],
    $db["user"],
    $db["pass"],
    ltrim($db["path"], "/")
));

$maxWidth = 30.0;
$adminId = 1;

function convertToIndex($x, $y){
    return (($x-1)*$GLOBALS[maxWidth]+$y);
}
function convertToX($index){
    return ceil($index/$GLOBALS[maxWidth]);
}
function convertToY($index){
    if ($y = $index%$GLOBALS[maxWidth]) { return $y; } else { return $GLOBALS[maxWidth]; }
}
function move($playerid,$regionid,$merchActive){
    $x = convertToX($regionid);
    $y = convertToY($regionid);
    if($merchActive) {
        $query = "UPDATE players SET mhposition=" . $x . ", mvposition=" . $y . " WHERE id=" . $playerid . ";";
        $sql = $GLOBALS[pdo]->prepare($query);
        $sql->execute();
    } else {
        $query = "UPDATE players SET fhposition=" . $x . ", fvposition=" . $y . " WHERE id=" . $playerid . ";";
        $sql = $GLOBALS[pdo]->prepare($query);
        $sql->execute();
    }
}
function getPlayerDetails(){
    $playerQuery = "SELECT fhposition,
                fvposition,
                mhposition,
                mvposition,
                id,
                fwmoves,
                flmoves,
                mwmoves,
                mlmoves,
                currency,
                spearfaes,
                lights,
                heavies,
                archers,
                wingfae,
                clanid,
                EXTRACT(HOURS FROM INTERVAL '3 hours' - (CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' - updated))
                || ' hours and ' || EXTRACT(MINUTES FROM INTERVAL '3 hours' - (CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' - updated))
                || ' minutes and ' || ROUND(EXTRACT(SECONDS FROM INTERVAL '3 hours' - (CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' - updated)))
                || ' seconds' as untilturn
                FROM players WHERE players.id=" . $_SESSION['PlayerId'] . ";";
    $playerSql = $GLOBALS[pdo]->prepare($playerQuery);
    $playerSql->execute();
    $player = [];
    if ($playerSql->rowCount() > 0) {
        while($result = $playerSql->fetch(PDO::FETCH_ASSOC)){
            $player[0] = $result['id'];
            $player[1] = $result['fhposition'];
            $player[2] = $result['fvposition'];
            $player[3] = $result['fwmoves'];
            $player[4] = $result['flmoves'];
            $player[5] = $result['clanid'];
            $player[6] = $result['currency'];
            $player[7] = $result['spearfaes'];
            $player[8] = $result['archers'];
            $player[9] = $result['lights'];
            $player[10] = $result['heavies'];
            $player[11] = $result['wingfae'];
            $player[12] = $result['mhposition'];
            $player[13] = $result['mvposition'];
            $player[14] = $result['mwmoves'];
            $player[15] = $result['mlmoves'];
            $player[16] = $result['untilturn'];
        }
    }
    return $player;
}
function getRegion($regionid){
    $regionQuery = "SELECT iswater,
                regionsize,
                regions.playerid as playerid,
                regions.id as id,
                clanid FROM regions LEFT JOIN players ON players.id=regions.playerid WHERE regions.id="
                . $regionid . ";";
    $regionSql = $GLOBALS[pdo]->prepare($regionQuery);
    $regionSql->execute();
    $region = [];
    while($result = $regionSql->fetch(PDO::FETCH_ASSOC)){
        $region[0] = $result['iswater'] ? $result['iswater'] : false;
        $region[1] = $result['regionsize'];
        $region[2] = $result['playerid'] ? $result['playerid'] : 0;
        $region[3] = $result['id'];
        $region[4] = $result['clanid'];
    }
    return $region;
}
