<?php
include "base.php";

header('Content-type: application/json');

if (isset($_POST['newgame'])) {
    /*$playerid = htmlspecialchars($_POST['playerid']);*/
    if (/*$playerid == $_SESSION['PlayerId'] && $playerid == $GLOBALS[adminId]*/ true) {
        $newGame = $_POST['newgame'];
        $newMap = explode(',',htmlspecialchars($_POST['newmap']));
        $capitols = explode(',',htmlspecialchars($_POST['capitols']));
        $lengthMap = count($newMap);
        $lengthCapitols = count($capitols);
        if ($newGame == true) {
            $sqlList = ["SET TIMEZONE='America/Chicago';",
                        "CREATE TABLE IF NOT EXISTS projects (
                            id SERIAL PRIMARY KEY,
                            name VARCHAR(65),
                            writercount INTEGER DEFAULT 0
                        );",
                        "CREATE TABLE IF NOT EXISTS writers (
                            id SERIAL PRIMARY KEY,
                            username VARCHAR(65) NOT NULL,
                            password VARCHAR(255) NOT NULL,
                            emailaddress VARCHAR(255) NOT NULL,
                            mostcontributions INTEGER DEFAULT 0,
                            mostcontributed INTEGER DEFAULT 0,
                            finished BOOLEAN DEFAULT FALSE,
                            updated TIMESTAMP
                        );"
                    ];
            foreach ($sqlList as $sql) {
                echo $pdo->exec($sql);
            }
        }
    } else {
        echo "you are not authorized to do that. notify Ben.";
    }
} else if (isset($_POST['grow'])) {
    $grow = $_POST['grow'];
    if ($grow == true) {
        $sqlList = ["UPDATE regions SET
                        regionsize = CASE
                        WHEN ((EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3)
                        AND (regionsize < 2000)
                        THEN regionsize +
                        ((EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600)::integer
                        ELSE regionsize
                        END
                        WHERE iswater IS FALSE;",
                    "UPDATE regions SET
                        updated = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3 OR
                        updated IS NULL
                        THEN CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' ELSE updated
                        END
                        WHERE iswater IS FALSE;",
                    "UPDATE players SET
                        fwmoves = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3 OR
                        updated IS NULL
                        THEN 2 ELSE fwmoves
                        END,
                        flmoves = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3 OR
                        updated IS NULL
                        THEN 2 ELSE flmoves
                        END,
                        mwmoves = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3 OR
                        updated IS NULL
                        THEN 4 ELSE mwmoves
                        END,
                        mlmoves = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3 OR
                        updated IS NULL
                        THEN 3 ELSE mlmoves
                        END,
                        updated = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3 OR
                        updated IS NULL
                        THEN CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' ELSE updated
                        END,
                        currency = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM lasttribute))/3600 >= 24
                        THEN currency+totaltribute ELSE currency
                        END,
                        lasttribute = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM lasttribute))/3600 >= 24 OR
                        lasttribute IS NULL
                        THEN CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' ELSE lasttribute
                        END;"];
        foreach ($sqlList as $sql) {
            $pdo->exec($sql);
        }
        $query = "SELECT clanid, (count(*)::DECIMAL/(SELECT count(*) FROM regions WHERE iswater IS FALSE)) as percentregions
                      FROM regions
                      JOIN players ON players.id=regions.playerid GROUP BY clanid;";
        $sql = $pdo->prepare($query);
        $sql->execute();
        $percentages = [];
        while($result = $sql->fetch(PDO::FETCH_ASSOC)){
            $percentages[$result['clanid']][0] = $result['clanid'];
            $percentages[$result['clanid']][1] = $result['percentregions'];
        }
        $query = "SELECT players.clanid, (count(*)::DECIMAL/6) as percentcapitols
                      FROM clancapitol JOIN regions on clancapitol.regionid=regions.id
                      JOIN players ON regions.playerid=players.id GROUP BY players.clanid;";
        $sql = $pdo->prepare($query);
        $sql->execute();
        while($result = $sql->fetch(PDO::FETCH_ASSOC)){
            $percentages[$result['clanid']][2] = $result['percentcapitols'];
        }
        echo json_encode($percentages);
    }
}  else if (isset($_POST['start'])) {
    $start = $_POST['start'];
    if ($start == true) {
        $sqlList = ["UPDATE regions SET
                        regionsize = CASE
                        WHEN ((EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3)
                        AND (regionsize < 2000)
                        THEN regionsize +
                        ((EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600)::integer
                        ELSE regionsize
                        END
                        WHERE iswater IS FALSE;",
                    "UPDATE regions SET
                        updated = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3 OR
                        updated IS NULL
                        THEN CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' ELSE updated
                        END
                        WHERE iswater IS FALSE;",
                    "UPDATE players SET
                        fwmoves = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3
                        THEN 2 ELSE fwmoves
                        END,
                        flmoves = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3
                        THEN 2 ELSE flmoves
                        END,
                        mwmoves = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3
                        THEN 4 ELSE mwmoves
                        END,
                        mlmoves = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3
                        THEN 3 ELSE mlmoves
                        END,
                        updated = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM updated))/3600 >= 3 OR
                        updated IS NULL
                        THEN CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' ELSE updated
                        END,
                        currency = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM lasttribute))/3600 >= 24
                        THEN currency+totaltribute ELSE currency
                        END,
                        lasttribute = CASE
                        WHEN (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago') - EXTRACT(EPOCH FROM lasttribute))/3600 >= 24 OR
                        lasttribute IS NULL
                        THEN CURRENT_TIMESTAMP AT TIME ZONE 'America/Chicago' ELSE lasttribute
                        END;"];
        foreach ($sqlList as $sql) {
            $pdo->exec($sql);
        }
        $query = "SELECT clanid, (count(*)::DECIMAL/(SELECT count(*) FROM regions WHERE iswater IS FALSE)) as percentregions
                      FROM regions
                      JOIN players ON players.id=regions.playerid GROUP BY clanid;";
        $sql = $pdo->prepare($query);
        $sql->execute();
        $percentages = [];//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        while($result = $sql->fetch(PDO::FETCH_ASSOC)){
            $percentages[$result['clanid']][0] = $result['clanid'];
            $percentages[$result['clanid']][1] = $result['percentregions'];
        }
        $query = "SELECT players.clanid, (count(*)::DECIMAL/6) as percentcapitols
                      FROM clancapitol JOIN regions on clancapitol.regionid=regions.id
                      JOIN players ON regions.playerid=players.id GROUP BY players.clanid;";
        $sql = $pdo->prepare($query);
        $sql->execute();
        while($result = $sql->fetch(PDO::FETCH_ASSOC)){
            $percentages[$result['clanid']][2] = $result['percentcapitols'];
        }

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
        $playerSql = $pdo->prepare($playerQuery);
        $playerSql->execute();
        $player = [];//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
        $width = 7;
        $height = 7;
        $x = $player[1] - floor($width/2);
        $y = $player[2] - floor($height/2);
        $index = convertToIndex($x, $y);
        $endWidth = $x + $width;
        $endHeight = $y + $height;
        $skip = $GLOBALS[maxWidth] - $width;
        $end = $index + $width - 1;
        $playersQuery = "SELECT fhposition,
                    fvposition,
                    id,
                    clanid FROM players WHERE fhposition BETWEEN "
                    . $x . " AND " . $endWidth . " AND fvposition BETWEEN "
                    . $y . " AND " . $endHeight . ";";
        $regionQuery = "SELECT villages.id as id,
                    iswater,
                    regionsize,
                    villages.playerid as playerid,
                    clanid FROM".
                 " (SELECT * FROM regions WHERE regions.id BETWEEN " . $index . " AND " . $end;
        for ($j = 1; $j < $height; $j++) {
            $index = $skip + $end + 1;
            $end = $index + $width - 1;
            $regionQuery .= " UNION SELECT * FROM regions WHERE regions.id BETWEEN " . $index . " AND " . $end;
        }
        $regionQuery .= " ) as villages".
                  " LEFT JOIN players ON players.id = villages.playerid".
                  " ORDER BY villages.id;";
        $regionSql = $pdo->prepare($regionQuery);
        $regionSql->execute();
        $regions = [];//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $i = 0;
        while($result = $regionSql->fetch(PDO::FETCH_ASSOC)){
            $regions[$i][0] = $result['iswater'] ? $result['iswater'] : false;
            $regions[$i][1] = $result['regionsize'];
            $regions[$i][2] = $result['playerid'] ? $result['playerid'] : 0;
            $regions[$i][3] = $result['id'];
            $regions[$i][4] = $result['clanid'];
            $i++;
        }
        $playersSql = $pdo->prepare($playersQuery);
        $playersSql->execute();
        $players = [];//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $i = 0;
        while($result = $playersSql->fetch(PDO::FETCH_ASSOC)){
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
                    . convertToIndex($player[1], $player[2]) . ";";
        $regionSql = $pdo->prepare($regionQuery);
        $regionSql->execute();
        $region = [];//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        while($result = $regionSql->fetch(PDO::FETCH_ASSOC)){
            $region[0] = $result['iswater'] ? $result['iswater'] : false;
            $region[1] = $result['regionsize'];
            $region[2] = $result['playerid'] ? $result['playerid'] : 0;
            $region[3] = $result['id'];
            $region[4] = $result['clanid'];
        }
        $data[0]=$percentages; $data[1]=$player; $data[2]=$regions; $data[3]=$players; $data[4]=$region;
        echo json_encode($data);
    }
} else if (isset($_GET['allplayers'])) { // retrieve all players
    $query = "SELECT id,username FROM players;";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $data = [];
    $i = 0;
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $data[$i][0] = $result['id'];
        $data[$i][1] = $result['username'];
        $i++;
    }
    echo json_encode($data);
} else if (isset($_POST['playerid'])) { // delete a player
    $playerid = htmlspecialchars($_POST["playerid"]);
    $sqlList = ["DELETE FROM players WHERE id=" . $playerid . ";"
    ];
    foreach ($sqlList as $sql) {
        $GLOBALS[pdo]->exec($sql);
    }
}
