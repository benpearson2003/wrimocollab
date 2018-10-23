<?php
include "base.php";

header('Content-type: application/json');

function updateRegionOwner($playerid, $regionid){
    $sqlList = ["UPDATE players SET totaltribute=totaltribute-(SELECT tribute FROM regions WHERE regions.id="
                    . $regionid . ") WHERE players.id=(SELECT playerid FROM regions WHERE regions.id="
                    . $regionid . ");",
                "UPDATE regions SET playerid=" . $playerid . " WHERE id=" . $regionid . ";",
                "UPDATE players SET totaltribute=totaltribute+(SELECT tribute FROM regions WHERE regions.id="
                    . $regionid . ") WHERE players.id=" . $playerid . ";"
    ];
    foreach ($sqlList as $sql) {
        $GLOBALS[pdo]->exec($sql);
    }
}
function routeFlock() {
    return false;
}
function attackerWins($player,$target){
    $expectedAttackerCasualty = 0.05*$player[5];
    $wingfaeRange = (1/8) * $player[10];
    $heavyRange = (1/5) * $player[9];
    $lightRange = (1/2) * $player[8];
    $archerRange = (1/3) * $player[7];
    $spearRange = (1/1) * $player[6];
    $total = $wingfaeRange + $heavyRange + $lightRange + $archerRange + $spearRange;
    $aw = $ah = $al = $aa = $as = 0;

    while($expectedAttackerCasualty > 0){
        $casualty = mt_rand(0 * 100, $total * 100) / 100;
        if($casualty < $wingfaeRange) {
            $player[10]--;
            $expectedAttackerCasualty -= 8;
            $wingfaeRange = (1/8) * $player[10];
            $aw++;
        } else if($casualty < $wingfaeRange + $heavyRange) {
            $player[9]--;
            $expectedAttackerCasualty -= 5;
            $heavyRange = (1/5) * $player[9];
            $ah++;
        } else if($casualty < $wingfaeRange + $heavyRange + $lightRange) {
            $player[8]--;
            $expectedAttackerCasualty -= 2;
            $lightRange = (1/2) * $player[8];
            $al++;
        } else if($casualty < $wingfaeRange + $heavyRange + $lightRange + $archerRange) {
            $player[7]--;
            $expectedAttackerCasualty -= 3;
            $archerRange = (1/3) * $player[7];
            $aa++;
        } else if($casualty < $wingfaeRange + $heavyRange + $lightRange + $archerRange + $spearRange) {
            $player[6]--;
            $expectedAttackerCasualty -= 1;
            $spearRange = (1/1) * $player[6];
            $as++;
        } else {
            break; // I really hope this doesn't happen.
        }
        $total = $wingfaeRange + $heavyRange + $lightRange + $archerRange + $spearRange;
    }

    $expectedDefenderCasualty = 0.05*$target[4];
    $heavyRange = (1/3) * $target[8];
    $lightRange = (1/1) * $target[7];
    $archerRange = (1/5) * $target[6];
    $spearRange = (1/2) * $target[5];
    $total = $heavyRange + $lightRange + $archerRange + $spearRange;
    $dh = $dl = $da = $ds = 0;

    while($expectedDefenderCasualty > 0){
        $casualty = mt_rand(0 * 100, $total * 100) / 100;
        if($casualty < $heavyRange) {
            $target[8]--;
            $expectedDefenderCasualty -= 5;
            $heavyRange = (1/5) * $target[8];
            $dh++;
        } else if($casualty < $heavyRange + $lightRange) {
            $target[7]--;
            $expectedDefenderCasualty -= 2;
            $lightRange = (1/2) * $target[7];
            $dl++;
        } else if($casualty < $heavyRange + $lightRange + $archerRange) {
            $target[6]--;
            $expectedDefenderCasualty -= 3;
            $archerRange = (1/3) * $target[6];
            $da++;
        } else if($casualty < $heavyRange + $lightRange + $archerRange + $spearRange) {
            $target[5]--;
            $expectedDefenderCasualty -= 1;
            $spearRange = (1/1) * $target[5];
            $ds++;
        } else {
            break; // I really hope this doesn't happen.
        }
        $total = $heavyRange + $lightRange + $archerRange + $spearRange;
    }
    $sqlList = ["UPDATE regions SET spearfaes=" . $target[5] . ",
                    archers=" . $target[6] . ",
                    lights=" . $target[7] . ",
                    heavies=" . $target[8] . " WHERE id=" . $target[2] . ";",
                "UPDATE players SET updated =
                    CASE
                    WHEN flmoves=2 AND fwmoves=2 AND mlmoves=3 AND mwmoves=4
                    THEN CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago'
                    ELSE updated
                    END
                    WHERE id=" . $_SESSION['PlayerId'] . ";",
                "UPDATE players SET spearfaes=" . $player[6] . ",
                    archers=" . $player[7] . ",
                    lights=" . $player[8] . ",
                    heavies=" . $player[9] . ",
                    wingfae=" . $player[10] . ",
                    flmoves=flmoves-1 WHERE id=" . $player[0] . ";"
            ];
    foreach ($sqlList as $sql) {
        $GLOBALS[pdo]->exec($sql);
    }
    updateRegionOwner($player[0],$target[2]);
    move($player[0],$target[2],false);
    return [/* result */
            true,
            /* enemy player */
            $target[1],
            /* casualties vs battle size */
            $as+$aa+$al+$ah+$aw < $expectedAttackerCasualty/2 ? 'small fight' : 'vicious and bloody struggle',
            /* size/skill of flock */
            $target[4]/$player[5] < 0.20 ? 'crushing weight of your mighty' : 'skill of your',
            /* result, but poetic */
            $target[4]/$player[5] < 0.20 ? 'obliterated the enemy' : 'soundly defeated the enemy',
            /* what happens to the flock */
            $target[5]+$target[6]+$target[7]+$target[8] > 0 ? 'The enemy flock has surrendered.' : 'The enemy flock has been completely routed.',
            /* enemy casualties */
            $ds+$da+$dl+$dh,
            /* player casualties breakdown */
            $as,$aa,$al,$ah,$aw,
            /* plunder */
            0
        ];
}
function defenderWins($player,$target){
    $expectedAttackerCasualty = 0.05*$player[5];
    $wingfaeRange = (1/8) * $player[10];
    $heavyRange = (1/5) * $player[9];
    $lightRange = (1/2) * $player[8];
    $archerRange = (1/3) * $player[7];
    $spearRange = (1/1) * $player[6];
    $total = $wingfaeRange + $heavyRange + $lightRange + $archerRange + $spearRange;
    $aw = $ah = $al = $aa = $as = 0;

    while($expectedAttackerCasualty > 0){
        $casualty = mt_rand(0 * 100, $total * 100) / 100;
        if($casualty < $wingfaeRange) {
            $player[10]--;
            $expectedAttackerCasualty -= 8;
            $wingfaeRange = (1/8) * $player[10];
            $aw++;
        } else if($casualty < $wingfaeRange + $heavyRange) {
            $player[9]--;
            $expectedAttackerCasualty -= 5;
            $heavyRange = (1/5) * $player[9];
            $ah++;
        } else if($casualty < $wingfaeRange + $heavyRange + $lightRange) {
            $player[8]--;
            $expectedAttackerCasualty -= 2;
            $lightRange = (1/2) * $player[8];
            $al++;
        } else if($casualty < $wingfaeRange + $heavyRange + $lightRange + $archerRange) {
            $player[7]--;
            $expectedAttackerCasualty -= 3;
            $archerRange = (1/3) * $player[7];
            $aa++;
        } else if($casualty < $wingfaeRange + $heavyRange + $lightRange + $archerRange + $spearRange) {
            $player[6]--;
            $expectedAttackerCasualty -= 1;
            $spearRange = (1/1) * $player[6];
            $as++;
        } else {
            break; // I really hope this doesn't happen.
        }
        $total = $wingfaeRange + $heavyRange + $lightRange + $archerRange + $spearRange;
    }

    $expectedDefenderCasualty = 0.05*$target[4];
    $heavyRange = (1/3) * $target[8];
    $lightRange = (1/1) * $target[7];
    $archerRange = (1/5) * $target[6];
    $spearRange = (1/2) * $target[5];
    $total = $heavyRange + $lightRange + $archerRange + $spearRange;
    $dh = $dl = $da = $ds = 0;

    while($expectedDefenderCasualty > 0){
        $casualty = mt_rand(0 * 100, $total * 100) / 100;
        if($casualty < $heavyRange) {
            $target[8]--;
            $expectedDefenderCasualty -= 5;
            $heavyRange = (1/5) * $target[8];
            $dh++;
        } else if($casualty < $heavyRange + $lightRange) {
            $target[7]--;
            $expectedDefenderCasualty -= 2;
            $lightRange = (1/2) * $target[7];
            $dl++;
        } else if($casualty < $heavyRange + $lightRange + $archerRange) {
            $target[6]--;
            $expectedDefenderCasualty -= 3;
            $archerRange = (1/3) * $target[6];
            $da++;
        } else if($casualty < $heavyRange + $lightRange + $archerRange + $spearRange) {
            $target[5]--;
            $expectedDefenderCasualty -= 1;
            $spearRange = (1/1) * $target[5];
            $ds++;
        } else {
            break; // I really hope this doesn't happen.
        }
        $total = $heavyRange + $lightRange + $archerRange + $spearRange;
    }
    $sqlList = ["UPDATE regions SET spearfaes=" . $target[5] . ",
                    archers=" . $target[6] . ",
                    lights=" . $target[7] . ",
                    heavies=" . $target[8] . " WHERE id=" . $target[2] . ";",
                "UPDATE players SET spearfaes=" . $player[6] . ",
                    archers=" . $player[7] . ",
                    lights=" . $player[8] . ",
                    heavies=" . $player[9] . ",
                    wingfae=" . $player[10] . " WHERE id=" . $player[0] . ";"
            ];
    foreach ($sqlList as $sql) {
        $GLOBALS[pdo]->exec($sql);
    }
    $fullRoute = $player[10]+$player[9]+$player[8]+$player[7]+$player[6] < $as+$aa+$al+$ah+$aw;
    if($fullRoute) {
        routeFlock($player);
    }
    return [/* result */
            false,
            /* enemy player */
            $target[1],
            /* casualties vs battle size */
            $casualty < $expectedCasualty/2 ? 'small fight' : 'vicious and bloody struggle',
            /* size/skill of flock */
            $target[4]/$player[5] < 0.20 ? 'crushing weight of your mighty' : 'skill of your',
            /* result, but poetic */
            $target[4]/$player[5] < 0.20 ? 'still could not defeat the enemy' : 'was not enough against the enemy',
            /* what happens to the flock */
            $fullRoute ? 'Your flock has been completely routed.' : 'Your flock does not move.',
            /* enemy casualties */
            $ds+$da+$dl+$dh,
            /* player casualties breakdown */
            $as,$aa,$al,$ah,$aw,
            /* plunder */
            0
        ];
}

if (isset($_POST['playerid']) && isset($_POST['targetid'])) {
    $playerid = htmlspecialchars($_POST['playerid']);
    $targetid = htmlspecialchars($_POST['targetid']);
    if ($playerid == $_SESSION['PlayerId']) {
        $query = "SELECT fhposition,
                    fvposition,
                    id,
                    fwmoves,
                    flmoves,
                    (spearfaes*(SELECT offense FROM units WHERE id=1)+
                    archers*(SELECT offense FROM units WHERE id=2)+
                    lights*(SELECT offense FROM units WHERE id=3)+
                    heavies*(SELECT offense FROM units WHERE id=4)+
                    wingfae*(SELECT offense FROM units WHERE id=5)) as offense,
                    clanid,
                    spearfaes,archers,lights,heavies,wingfae FROM players WHERE players.id=" . $playerid . ";";
        $sql = $GLOBALS[pdo]->prepare($query);
        $sql->execute();
        $player = [];
        if ($sql->rowCount() > 0) {
            while($result = $sql->fetch(PDO::FETCH_ASSOC)){
                $player[0] = $result['id'];
                $player[1] = $result['fhposition'];
                $player[2] = $result['fvposition'];
                $player[3] = $result['flmoves'];
                $player[4] = $result['clanid'];
                $player[5] = $result['offense'];
                $player[6] = $result['spearfaes'];
                $player[7] = $result['archers'];
                $player[8] = $result['lights'];
                $player[9] = $result['heavies'];
                $player[10] = $result['wingfae'];
            }
        }
        $query = "SELECT iswater,
                    players.username as player,
                    regions.id as id,
                    (regions.spearfaes*(SELECT defense FROM units WHERE id=1)+
                    regions.archers*(SELECT defense FROM units WHERE id=2)+
                    regions.lights*(SELECT defense FROM units WHERE id=3)+
                    regions.heavies*(SELECT defense FROM units WHERE id=4)) as defense,
                    players.clanid as clanid,
                    regions.spearfaes as spearfaes,
                    regions.archers as archers,
                    regions.lights as lights,
                    regions.heavies as heavies FROM regions LEFT JOIN players ON players.id=regions.playerid WHERE regions.id="
                    . $targetid . ";";
        $sql = $GLOBALS[pdo]->prepare($query);
        $sql->execute();
        $target = [];
        while($result = $sql->fetch(PDO::FETCH_ASSOC)){
            $target[0] = $result['iswater'] ? $result['iswater'] : false;
            $target[1] = $result['player'] ? $result['player'] : 'a village governor';
            $target[2] = $result['id'];
            $target[3] = $result['clanid'];
            $target[4] = $result['defense'];
            $target[5] = $result['spearfaes'];
            $target[6] = $result['archers'];
            $target[7] = $result['lights'];
            $target[8] = $result['heavies'];
        }

        // player can't attack unless they have land moves and their clan doesn't own target
        if ($player[3] > 0 && $player[4] != $target[3]) {
            $attack = 0;
            $defense = 0;
            for($i = 0;$i<$player[5];$i++) {
                $attack += rand(1,6);
            }
            for($i = 0;$i<$target[4];$i++) {
                $defense += rand(1,6);
            }
            $attack > $defense ?
                $battleResult = attackerWins($player,$target) :
                $battleResult = defenderWins($player,$target);
            echo json_encode($battleResult);
        }
    } else {
        echo "your id doesn't match the one you logged in with. notify Ben.";
    }
}
