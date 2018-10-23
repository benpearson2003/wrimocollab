<?php
include "base.php";

header('Content-type: application/json');

if (isset($_POST['newgame']) && isset($_POST['newmap']) /*&& isset($_POST['playerid'])*/ && isset($_POST['capitols'])) {
    /*$playerid = htmlspecialchars($_POST['playerid']);*/
    if (/*$playerid == $_SESSION['PlayerId'] && $playerid == $GLOBALS[adminId]*/ true) {
        $newGame = $_POST['newgame'];
        $newMap = explode(',',htmlspecialchars($_POST['newmap']));
        $capitols = explode(',',htmlspecialchars($_POST['capitols']));
        $lengthMap = count($newMap);
        $lengthCapitols = count($capitols);
        if ($newGame == true) {
            $createMap = "INSERT INTO regions (iswater,regionsize,tribute,spearfaes,archers,lights,heavies,updated) VALUES";
            for($i = 0; $i < $lengthMap; $i++) {
                $createMap .= " (".$newMap[$i]."::boolean,
                                    (NOT ".$newMap[$i]."::boolean)::integer*(random()*16+1),
                                    (NOT ".$newMap[$i]."::boolean)::integer*100,
                                    (NOT ".$newMap[$i]."::boolean)::integer*(random()*4+1),
                                    0,
                                    (NOT ".$newMap[$i]."::boolean)::integer*(random()*4+1),
                                    0,
                                    CURRENT_TIMESTAMP)";
                if($i < $lengthMap-1){$createMap .= ",";}
            }
            $createMap .= ";";
            $createCapitols = "INSERT INTO clancapitol (clanid,regionid) VALUES";
            for($i = 0; $i < $lengthCapitols; $i++) {
                $createCapitols .= " (".($i+1).",".$capitols[$i].")";
                if($i < $lengthCapitols-1){$createCapitols .= ",";}
            }
            $createCapitols .= ";";
            $sqlList = ["SET TIMEZONE='America/Chicago';",
                        "CREATE TABLE IF NOT EXISTS clans (
                            id SERIAL PRIMARY KEY,
                            name VARCHAR(65),
                            playercount INTEGER DEFAULT 0
                        );",
                        "CREATE TABLE IF NOT EXISTS players (
                            id SERIAL PRIMARY KEY,
                            username VARCHAR(65) NOT NULL,
                            password VARCHAR(255) NOT NULL,
                            emailaddress VARCHAR(255) NOT NULL,
                            fhposition INTEGER DEFAULT 0,
                            fvposition INTEGER DEFAULT 0,
                            mhposition INTEGER DEFAULT 0,
                            mvposition INTEGER DEFAULT 0,
                            fwmoves INTEGER DEFAULT 0,
                            flmoves INTEGER DEFAULT 0,
                            mwmoves INTEGER DEFAULT 0,
                            mlmoves INTEGER DEFAULT 0,
                            spearfaes INTEGER DEFAULT 0,
                            archers INTEGER DEFAULT 0,
                            lights INTEGER DEFAULT 0,
                            heavies INTEGER DEFAULT 0,
                            wingfae INTEGER DEFAULT 0,
                            currency INTEGER DEFAULT 0,
                            totaltribute INTEGER DEFAULT 0,
                            lasttribute TIMESTAMP,
                            clanid INTEGER REFERENCES clans(id),
                            updated TIMESTAMP
                        );",
                        "CREATE TABLE IF NOT EXISTS regions (
                            id SERIAL PRIMARY KEY,
                            iswater BOOLEAN,
                            regionsize INTEGER,
                            spearfaes INTEGER,
                            archers INTEGER,
                            lights INTEGER,
                            heavies INTEGER,
                            tribute INTEGER,
                            updated TIMESTAMP,
                            playerid INTEGER REFERENCES players(id)
                        );",
                        "CREATE TABLE IF NOT EXISTS units (
                            id SERIAL PRIMARY KEY,
                            name VARCHAR(65),
                            offense INTEGER,
                            defense INTEGER,
                            cost INTEGER
                        );",
                        "CREATE TABLE IF NOT EXISTS items (
                            id SERIAL PRIMARY KEY,
                            name VARCHAR(65),
                            description VARCHAR(255),
                            value INTEGER,
                            effects VARCHAR(65)
                        );",
                        "CREATE TABLE IF NOT EXISTS flockitem (
                            id SERIAL PRIMARY KEY,
                            playerid INTEGER REFERENCES players(id),
                            itemid INTEGER REFERENCES items(id),
                            count INTEGER
                        );",
                        "CREATE TABLE IF NOT EXISTS merchantitem (
                            id SERIAL PRIMARY KEY,
                            playerid INTEGER REFERENCES players(id),
                            itemid INTEGER REFERENCES items(id),
                            count INTEGER
                        );",
                        "CREATE TABLE IF NOT EXISTS regionitem (
                            id SERIAL PRIMARY KEY,
                            regionid INTEGER REFERENCES regions(id),
                            itemid INTEGER REFERENCES items(id)
                        );",
                        "CREATE TABLE IF NOT EXISTS clancapitol (
                            id SERIAL PRIMARY KEY,
                            clanid INTEGER REFERENCES clans(id),
                            regionid INTEGER REFERENCES regions(id)
                        );",
                        "CREATE TABLE IF NOT EXISTS improvements (
                            id SERIAL PRIMARY KEY,
                            name VARCHAR(65),
                            description VARCHAR(255),
                            cost INTEGER,
                            effects VARCHAR(65)
                        );",
                        "CREATE TABLE IF NOT EXISTS regionimprovement (
                            id SERIAL PRIMARY KEY,
                            regionid INTEGER REFERENCES regions(id),
                            improvementid INTEGER REFERENCES improvements(id)
                        );",
                        "INSERT INTO improvements (name,description,cost) VALUES
                            ('town hall','center of organization that allows the construction of other improvements',10000),
                            ('woodwright','necessary for building a wooden palisade',1000),
                            ('stone mason','necessary for building a stone wall',1000),
                            ('blacksmith','necessary for building a castle',2000),
                            ('winery','creates wine for consumption or sale',4000),
                            ('barracks','trains and houses combat faes',3000),
                            ('aviary','specialized barracks for wingfae fae',3000),
                            ('inn','place to sleep and gossip',4000),
                            ('wooden palisade','barrier made of wood that helps the defensive garrison',50000),
                            ('stone wall','barrier made of stone that helps the defensive garrison',150000),
                            ('castle','the peak of fae defense',350000);",
                        "INSERT INTO items (name,description,value) VALUES
                            ('honeycomb','hexagonal matrix of beeswax filled with honey',2),
                            ('mushroom spores','used to seed mushrooms',2),
                            ('clay pot','pot made of clay',2),
                            ('wooden shield','shield made of wood',2),
                            ('hour glass','time keeping device',2),
                            ('rune stone','small stone carved with symbols used for divination',2),
                            ('charcoal','pyrolyzed wood used for hotter, less smokey fires',2),
                            ('woven armor','armor made by tightly weaving fibrous plants',2),
                            ('cactus fruit','fruit from a cactus',2),
                            ('timber','wood for building or burning',2),
                            ('jar of souls','it looks empty',2),
                            ('saber','sharp, short sword meant for mounted use',2),
                            ('crystal circlet','fancy headwear',2),
                            ('yew bow','preferred weapon of archers',2);",
                        $createMap,
                        "INSERT INTO units (name,offense,defense,cost) VALUES
                            ('spearfae',1,2,20),
                            ('archer',3,5,65),
                            ('light groundfae',2,1,20),
                            ('heavy groundfae',5,3,50),
                            ('wingfae',8,5,96);",
                        "INSERT INTO clans (name) VALUES
                            ('pulosh'),
                            ('charucher'),
                            ('shoof'),
                            ('tisheon'),
                            ('song'),
                            ('spichacule');",
                        "INSERT INTO players (username,password,emailaddress,clanid) VALUES
                            ('pulosh monarch','password','none',1),
                            ('charucher monarch','password','none',2),
                            ('shoof monarch','password','none',3),
                            ('tisheon monarch','password','none',4),
                            ('song monarch','password','none',5),
                            ('spichacule monarch','password','none',6);",
                        $createCapitols,
                        "UPDATE regions SET playerid=1 WHERE regions.id=(SELECT regionid FROM clancapitol WHERE clanid=1);",
                        "UPDATE regions SET playerid=2 WHERE regions.id=(SELECT regionid FROM clancapitol WHERE clanid=2);",
                        "UPDATE regions SET playerid=3 WHERE regions.id=(SELECT regionid FROM clancapitol WHERE clanid=3);",
                        "UPDATE regions SET playerid=4 WHERE regions.id=(SELECT regionid FROM clancapitol WHERE clanid=4);",
                        "UPDATE regions SET playerid=5 WHERE regions.id=(SELECT regionid FROM clancapitol WHERE clanid=5);",
                        "UPDATE regions SET playerid=6 WHERE regions.id=(SELECT regionid FROM clancapitol WHERE clanid=6);"
                    ];
            foreach ($sqlList as $sql) {
                echo $pdo->exec($sql);
            }
            /*
                CODE FOR GENERATING NEW MAP
document.body.innerHTML += "<canvas id='canvas' title='click tile to act on' onmousedown='mousedown=true;select(event)' onmouseup='mousedown=false;'></canvas>"
canvas = document.getElementById('canvas');
context = canvas.getContext('2d');
context.canvas.width = 875;
context.canvas.height = 885;
context.fillStyle = "green";
context.fillRect(0,0,8*125,8*125);
var newImg = document.createElement("img");
newImg.id = "bub";
newImg.src = "images/map.png";
var element = document.body;
element.appendChild(newImg);
context.drawImage(document.getElementById('bub'),0,0);
var imgData = context.getImageData(0, 0, 500, 500).data;
var i = 0; var j = 0; var x = 0; var newMap = []; var capitols = [];
while(i<imgData.length){
    if(imgData[i] == 0){
    	newMap[x] = 1;
    } else {
    	newMap[x] = 0;
        if(imgData[i] == 200) {
            capitols[j] = x+1;
            j++;
        }
    }
    x++;
    i=i+4;
}
            */
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
