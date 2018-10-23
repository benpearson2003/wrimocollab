<?php
include "base.php";

header('Content-type: application/json');

function getRegionDetails($regionid, $spying = false) {
    $regionQuery = "SELECT
                  CASE WHEN clans.name IS NOT NULL THEN (clans.name || ' village') ELSE
                    CASE WHEN iswater IS FALSE THEN 'village' ELSE 'water' END
                  END
                  AS name,tribute,
                regions.spearfaes,regions.archers,regions.lights,regions.heavies FROM regions
                LEFT JOIN players ON regions.playerid=players.id
                LEFT JOIN clans ON players.clanid=clans.id WHERE regions.id=".$regionid.";";
    $regionSql = $GLOBALS[pdo]->prepare($regionQuery);
    $regionSql->execute();
    $region = [];
    while($result = $regionSql->fetch(PDO::FETCH_ASSOC)){
        $region[0] = $result['name'];
        $region[1] = $result['tribute'];
        $region[2] = [$result['spearfaes'],$result['archers'],$result['lights'],$result['heavies']];
    }
    $regionQuery = "SELECT improvements.id as id,improvements.name as name FROM improvements
                LEFT JOIN regionimprovement ON improvements.id=regionimprovement.improvementid
                LEFT JOIN regions ON regionimprovement.regionid=regions.id WHERE regions.id="
                . $regionid . ";";
    $regionSql = $GLOBALS[pdo]->prepare($regionQuery);
    $regionSql->execute();
    while($result = $regionSql->fetch(PDO::FETCH_ASSOC)){
        $region[3][$result['id']] = $result['name'];
    }
    return $region;
}

function getAvailableImprovements($regionid) {
    $improvementQuery = "SELECT name,cost FROM improvements;";
    $improvementSql = $GLOBALS[pdo]->prepare($improvementQuery);
    $improvementSql->execute();
    $availableImprovements = [];
    $i = 0;
    while($reult = $improvementSql->fetch(PDO::FETCH_ASSOC)){
        $availableImprovements[$i][0] = $result['name'];
        $availableImprovements[$i][1] = $result['cost'];
    }
    return $availableImprovements;
}

if (isset($_GET['myregions'])) {
    $query = "SELECT id,tribute,(spearfaes+archers+heavies+lights) AS defense FROM regions
                WHERE playerid=" . $_SESSION['PlayerId'] . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $data = [];
    $i = 0;
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $data[$i][0] = convertToX($result['id']);
        $data[$i][1] = convertToY($result['id']);
        $data[$i][2] = $result['tribute'];
        $data[$i][3] = $result['defense'];
        $i++;
    }
    echo json_encode($data);
} else if (isset($_GET['x']) && isset($_GET['y']) && isset($_GET['width']) && isset($_GET['height'])) { // retrieve range of regions
    $x = htmlspecialchars($_GET["x"]);
    $y = htmlspecialchars($_GET["y"]);
    $width = htmlspecialchars($_GET["width"]);
    $height = htmlspecialchars($_GET["height"]);
    $index = convertToIndex($x, $y);
    $endWidth = $x + $width;
    $endHeight = $y + $height;
    $skip = $GLOBALS[maxWidth] - $width;
    $end = $index + $width - 1;
    $playerQuery = "SELECT fhposition,
                fvposition,
                id,
                clanid FROM players WHERE fhposition BETWEEN "
                . $x . " AND " . $endWidth . " AND fvposition BETWEEN "
                . $y . " AND " . $endHeight . ";";
    $regionsQuery = "SELECT villages.id as id,
                iswater,
                regionsize,
                villages.playerid as playerid,
                clanid FROM".
             " (SELECT * FROM regions WHERE regions.id BETWEEN " . $index . " AND " . $end;
    for ($j = 1; $j < $height; $j++) {
        $index = $skip + $end + 1;
        $end = $index + $width - 1;
        $regionsQuery .= " UNION SELECT * FROM regions WHERE regions.id BETWEEN " . $index . " AND " . $end;
    }
    $regionsQuery .= " ) as villages".
              " LEFT JOIN players ON players.id = villages.playerid".
              " ORDER BY villages.id;";
    $regionsSql = $pdo->prepare($regionsQuery);
    $regionsSql->execute();
    $regions = [];
    $i = 0;
    while($result = $regionsSql->fetch(PDO::FETCH_ASSOC)){
        $regions[$i][0] = $result['iswater'] ? $result['iswater'] : false;
        $regions[$i][1] = $result['regionsize'];
        $regions[$i][2] = $result['playerid'] ? $result['playerid'] : 0;
        $regions[$i][3] = $result['id'];
        $regions[$i][4] = $result['clanid'];
        $i++;
    }
    $playerSql = $pdo->prepare($playerQuery);
    $playerSql->execute();
    $players = [];
    $i = 0;
    while($result = $playerSql->fetch(PDO::FETCH_ASSOC)){
        $players[$i][0] = $result['id'];
        $players[$i][1] = $result['fhposition'];
        $players[$i][2] = $result['fvposition'];
        $players[$i][3] = $result['clanid'];
        $i++;
    }
    $regionQuery = "SELECT iswater,
                regionsize,
                regions.playerid as playerid,
                regions.id as id,
                clanid FROM regions LEFT JOIN players ON players.id=regions.playerid WHERE regions.id="
                . convertToIndex($x+3, $y+3) . ";";
    $regionSql = $pdo->prepare($regionQuery);
    $regionSql->execute();
    $region = [];
    while($result = $regionSql->fetch(PDO::FETCH_ASSOC)){
        $region[0] = $result['iswater'] ? $result['iswater'] : false;
        $region[1] = $result['regionsize'];
        $region[2] = $result['playerid'] ? $result['playerid'] : 0;
        $region[3] = $result['id'];
        $region[4] = $result['clanid'];
    }
    $data[0] = $regions; $data[1] = $players; $data[2] = $region;
    echo json_encode($data);
} else if (isset($_GET['x']) && isset($_GET['y'])) {    // retrieve one region
    $x = htmlspecialchars($_GET["x"]);
    $y = htmlspecialchars($_GET["y"]);
    $query = "SELECT iswater,
                regionsize,
                regions.playerid as playerid,
                regions.id as id,
                clanid FROM regions LEFT JOIN players ON players.id=regions.playerid WHERE regions.id="
                . convertToIndex($x, $y) . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $data = [];
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $data[0] = $result['iswater'] ? $result['iswater'] : false;
        $data[1] = $result['regionsize'];
        $data[2] = $result['playerid'] ? $result['playerid'] : 0;
        $data[3] = $result['id'];
        $data[4] = $result['clanid'];
    }
    echo json_encode($data);
} else if (isset($_GET['id']) && isset($_GET['spying'])) { // spy on region
    $spying = htmlspecialchars($_GET["spying"]);
    $regionid = htmlspecialchars($_GET["id"]);
    if ($spying == 'true'){
        echo json_encode(getRegionDetails($regionid,true));
    } else {
        echo json_encode(getRegionDetails($regionid));
    }
} else if (isset($_POST['regionid']) && isset($_POST['moving'])) {
    $movingArray = explode(',',htmlspecialchars($_POST['moving']));
    $regionid = htmlspecialchars($_POST['regionid']);
    $query = "SELECT id,
                playerid,
                spearfaes,
                archers,
                lights,
                heavies FROM regions WHERE id="
                . $regionid . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $region = [];
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $region[0] = $result['id'];
        $region[1] = $result['playerid'] ? $result['playerid'] : 0;
        $region[2] = $result['spearfaes'];
        $region[3] = $result['archers'];
        $region[4] = $result['lights'];
        $region[5] = $result['heavies'];
    }

    $query = "SELECT id,
                fhposition,
                fvposition,
                spearfaes,
                lights,
                heavies,
                archers,
                clanid FROM players WHERE id=" . $_SESSION['PlayerId'] . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $player = [];
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $player[0] = $result['id'];
        $player[1] = $result['fhposition'];
        $player[2] = $result['fvposition'];
        $player[3] = $result['spearfaes'];
        $player[4] = $result['archers'];
        $player[5] = $result['lights'];
        $player[6] = $result['heavies'];
    }

    if(convertToIndex($player[1],$player[2]) == $regionid) {
        // min() is wasteful. I should write a function instead.
        if(min($movingArray) < 0) {
            if($player[0] == $region[1]) {
                $player[3] -= $player[3]-$movingArray[0] > -1 ? $movingArray[0] : 0;
                $region[2] += $region[2]+$movingArray[0] > -1 ? $movingArray[0] : 0;
                $player[4] -= $player[4]-$movingArray[1] > -1 ? $movingArray[1] : 0;
                $region[3] += $region[3]+$movingArray[1] > -1 ? $movingArray[1] : 0;
                $player[5] -= $player[5]-$movingArray[2] > -1 ? $movingArray[2] : 0;
                $region[4] += $region[4]+$movingArray[2] > -1 ? $movingArray[2] : 0;
                $player[6] -= $player[6]-$movingArray[3] > -1 ? $movingArray[3] : 0;
                $region[5] += $region[5]+$movingArray[3] > -1 ? $movingArray[3] : 0;
            }
            else {
                echo "does not govern region";
            }
        } else {
            $player[3] -= $player[3]-$movingArray[0] > -1 ? $movingArray[0] : 0;
            $region[2] += $region[2]+$movingArray[0] > -1 ? $movingArray[0] : 0;
            $player[4] -= $player[4]-$movingArray[1] > -1 ? $movingArray[1] : 0;
            $region[3] += $region[3]+$movingArray[1] > -1 ? $movingArray[1] : 0;
            $player[5] -= $player[5]-$movingArray[2] > -1 ? $movingArray[2] : 0;
            $region[4] += $region[4]+$movingArray[2] > -1 ? $movingArray[2] : 0;
            $player[6] -= $player[6]-$movingArray[3] > -1 ? $movingArray[3] : 0;
            $region[5] += $region[5]+$movingArray[3] > -1 ? $movingArray[3] : 0;
        }
        $sqlList = ["UPDATE regions SET spearfaes=" . $region[2] . ",
                        archers=" . $region[3] . ",
                        lights=" . $region[4] . ",
                        heavies=" . $region[5] . " WHERE id=" . $region[0] . ";",
                    "UPDATE players SET spearfaes=" . $player[3] . ",
                        archers=" . $player[4] . ",
                        lights=" . $player[5] . ",
                        heavies=" . $player[6] . " WHERE id=" . $player[0] . ";"
        ];
        foreach ($sqlList as $sql) {
            $GLOBALS[pdo]->exec($sql);
        }
    }
    $data[0]=getPlayerDetails(); $data[1]=getRegionDetails($regionid);
    echo json_encode($data);
} else if (isset($_POST['regionid']) && isset($_POST['recruits'])) {
    $recruitArray = explode(',',htmlspecialchars($_POST['recruits']));
    $regionid = htmlspecialchars($_POST['regionid']);
    $query = "SELECT regions.id,
                regions.playerid,
                regionsize,
                currency,
                (SELECT cost FROM units WHERE id=1) AS spearcost,
                (SELECT cost FROM units WHERE id=2) AS archercost,
                (SELECT cost FROM units WHERE id=3) AS lightcost,
                (SELECT cost FROM units WHERE id=4) AS heavycost
                FROM regions LEFT JOIN players ON players.id=regions.playerid WHERE regions.id="
                . $regionid . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $region = [];
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $region[0] = $result['id'];
        $region[1] = $result['playerid'] ? $result['playerid'] : 0;
        $region[2] = $result['regionsize'];
        $region[3] = $result['currency'];
        $region[4] = $result['spearcost'];
        $region[5] = $result['archercost'];
        $region[6] = $result['lightcost'];
        $region[7] = $result['heavycost'];
    }
    $totalCost = $recruitArray[0]*$region[4]+$recruitArray[1]*$region[5]+$recruitArray[2]*$region[6]+$recruitArray[3]*$region[7];
    if($_SESSION['PlayerId'] == $region[1]) {
        if(min($recruitArray) > -1 && $region[2] >= array_sum($recruitArray) && $totalCost <= $region[3]) {
            $sqlList = ["UPDATE regions SET regionsize=regionsize-" . array_sum($recruitArray) . ",
                            spearfaes=spearfaes+" . $recruitArray[0] . ",
                            archers=archers+" . $recruitArray[1] . ",
                            lights=lights+" . $recruitArray[2] . ",
                            heavies=heavies+" . $recruitArray[3] . " WHERE id=" . $region[0] . ";",
                        "UPDATE players SET currency=currency-" . $totalCost . " WHERE id=" . $_SESSION['PlayerId'] . ";"
            ];
            foreach ($sqlList as $sql) {
                $GLOBALS[pdo]->exec($sql);
            }
        }
    }
    $selectedQuery = "SELECT iswater,
                regionsize,
                regions.playerid as playerid,
                regions.id as id,
                clanid FROM regions LEFT JOIN players ON players.id=regions.playerid WHERE regions.id="
                . $regionid . ";";
    $selectedSql = $pdo->prepare($selectedQuery);
    $selectedSql->execute();
    $selected = [];//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    while($result = $selectedSql->fetch(PDO::FETCH_ASSOC)){
        $selected[0] = $result['iswater'] ? $result['iswater'] : false;
        $selected[1] = $result['regionsize'];
        $selected[2] = $result['playerid'] ? $result['playerid'] : 0;
        $selected[3] = $result['id'];
        $selected[4] = $result['clanid'];
    }
    $data[0]=getPlayerDetails(); $data[1]=$selected; $data[2]=getRegionDetails($regionid);
    echo json_encode($data);
}
