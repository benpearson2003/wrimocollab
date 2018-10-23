<?php
include "base.php";

header('Content-type: application/json');


if (isset($_GET['playerid'])) {
    echo json_encode(getPlayerDetails());
} else if (isset($_POST['ismerch']) && isset($_POST['regionid'])) {
    $merchActive = $_POST['ismerch'];
    $regionid = htmlspecialchars($_POST['regionid']);
    $query = "SELECT fhposition,
                fvposition,
                mhposition,
                mvposition,
                fwmoves,
                flmoves,
                mwmoves,
                mlmoves,
                clanid FROM players WHERE id=" . $_SESSION['PlayerId'] . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $player = [];
    if ($sql->rowCount() > 0) {
        while($result = $sql->fetch(PDO::FETCH_ASSOC)){
            $player[0] = $result['fhposition'];
            $player[1] = $result['fvposition'];
            $player[2] = $result['fwmoves'];
            $player[3] = $result['flmoves'];
            $player[4] = $result['mhposition'];
            $player[5] = $result['mvposition'];
            $player[6] = $result['mwmoves'];
            $player[7] = $result['mlmoves'];
            $player[8] = $result['clanid'];
        }
    }
    $query = "SELECT iswater,
                clanid FROM regions LEFT JOIN players ON players.id=regions.playerid WHERE regions.id="
                . $regionid . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $region = [];
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $region[0] = $result['iswater'] ? $result['iswater'] : false;
        $region[1] = $result['clanid'];
    }
    $x = convertToX($regionid);
    $y = convertToY($regionid);
    if ($x <= $GLOBALS[maxWidth] && $y <= $GLOBALS[maxWidth] && $x > 0 && $y > 0) {
        if ($player[2] == 2 && $player[3] == 2 && $player[6] == 4 && $player[7] == 3) {
            $sql = $GLOBALS[pdo]->prepare("UPDATE players SET updated = CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' WHERE id=" . $_SESSION['PlayerId'] . ";");
            $sql->execute();
        }
        if($merchActive == 'true') {
            if (abs($player[4] - $x) < 2 && abs($player[5] - $y) < 2) {
                if ($region[0] == true && $player[6] > 0) {
                    $query = "UPDATE players SET mwmoves=mwmoves". -1 . " WHERE id=" . $_SESSION['PlayerId'] . ";";
                    $sql = $GLOBALS[pdo]->prepare($query);
                    $sql->execute();
                    move($_SESSION['PlayerId'],$regionid,true);
                } else if ($region[0] == false && $player[7] > 0) {
                    $query = "UPDATE players SET mlmoves=mlmoves". -1 . " WHERE id=" . $_SESSION['PlayerId'] . ";";
                    $sql = $GLOBALS[pdo]->prepare($query);
                    $sql->execute();
                    move($_SESSION['PlayerId'],$regionid,true);
                }
            }
        } else {
            if (abs($player[0] - $x) < 2 && abs($player[1] - $y) < 2) {
                if ($region[0] == true && $player[2] > 0) {
                    $query = "UPDATE players SET fwmoves=fwmoves". -1 . " WHERE id=" . $_SESSION['PlayerId'] . ";";
                    $sql = $GLOBALS[pdo]->prepare($query);
                    $sql->execute();
                    move($_SESSION['PlayerId'],$regionid,false);
                } else if ($region[0] == false && $player[3] > 0 && $region[1] == $player[8]) {
                    $query = "UPDATE players SET flmoves=flmoves". -1 . " WHERE id=" . $_SESSION['PlayerId'] . ";";
                    $sql = $GLOBALS[pdo]->prepare($query);
                    $sql->execute();
                    move($_SESSION['PlayerId'],$regionid,false);
                }
            }
        }
    }
} else if (isset($_GET['x']) && isset($_GET['y']) && isset($_GET['width']) && isset($_GET['height'])) { // retrieve visible players
    $x = htmlspecialchars($_GET["x"]);
    $y = htmlspecialchars($_GET["y"]);
    $width = htmlspecialchars($_GET["width"]);
    $height = htmlspecialchars($_GET["height"]);
    $endWidth = $x + $width;
    $endHeight = $y + $height;
    $query = "SELECT fhposition,
                fvposition,
                players.id AS id,
                clanid FROM players WHERE fhposition BETWEEN "
                . $x . " AND " . $endWidth . " AND fvposition BETWEEN "
                . $y . " AND " . $endHeight . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $data = [];
    $i = 0;
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $data[$i][0] = $result['id'];
        $data[$i][1] = $result['fhposition'];
        $data[$i][2] = $result['fvposition'];
        $data[$i][3] = $result['clanid'];
        $i++;
    }
    echo json_encode($data);
} else if (isset($_POST['regionid']) && isset($_POST['recruits'])) {
    $recruitArray = explode(',',htmlspecialchars($_POST['recruits']));
    $regionid = htmlspecialchars($_POST['regionid']);
    $query = "SELECT id,
                regionsize,
                (SELECT cost FROM units WHERE id=1) AS spearcost,
                (SELECT cost FROM units WHERE id=2) AS archercost,
                (SELECT cost FROM units WHERE id=3) AS lightcost,
                (SELECT cost FROM units WHERE id=4) AS heavycost,
                (SELECT cost FROM units WHERE id=5) AS wingfaecost
                FROM regions WHERE id="
                . $regionid . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $region = [];
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $region[0] = $result['id'];
        $region[1] = $result['regionsize'];
        $region[2] = 0;
        $region[3] = $result['spearcost'];
        $region[4] = $result['archercost'];
        $region[5] = $result['lightcost'];
        $region[6] = $result['heavycost'];
        $region[7] = $result['wingfaecost'];
        $region[8] = 0;
        $region[9] = 0;
    }
    $query = "SELECT currency,
                fhposition,
                fvposition
                FROM players WHERE id="
                . $_SESSION['PlayerId'] . ";";
    $sql = $pdo->prepare($query);
    $sql->execute();
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $region[2] = $result['currency'];
        $region[8] = $result['fhposition'];
        $region[9] = $result['fvposition'];
    }

    if(convertToIndex($region[8],$region[9]) == $region[0]) {
        $totalCost = $recruitArray[0]*$region[3]+
                        $recruitArray[1]*$region[4]+
                        $recruitArray[2]*$region[5]+
                        $recruitArray[3]*$region[6]+
                        $recruitArray[4]*$region[7];
        if(min($recruitArray) > -1 && $region[1] >= array_sum($recruitArray) && $totalCost <= $region[2]) {
            $sqlList = ["UPDATE regions SET regionsize=regionsize-" . array_sum($recruitArray) . "
                            WHERE id=" . $region[0] . ";",
                        "UPDATE players SET currency=currency-" . $totalCost . ",
                            spearfaes=spearfaes+" . $recruitArray[0] . ",
                            archers=archers+" . $recruitArray[1] . ",
                            lights=lights+" . $recruitArray[2] . ",
                            heavies=heavies+" . $recruitArray[3] . ",
                            wingfae=wingfae+" . $recruitArray[4] . " WHERE id=" . $_SESSION['PlayerId'] . ";"
            ];

            foreach ($sqlList as $sql) {
                $GLOBALS[pdo]->exec($sql);
            }
        }
    }
    $data[0]=getPlayerDetails(); $data[1]=getRegion($regionid);
    echo json_encode($data);
}
