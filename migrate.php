<?php
include "base.php";

header('Content-type: application/json');

if (isset($_POST['migrate']) && isset($_POST['playerid'])) {
    $playerid = htmlspecialchars($_POST['playerid']);
    if ($playerid == $_SESSION['PlayerId'] && $playerid == $GLOBALS[adminId]) {
        $migrate = $_POST['migrate'];
        if ($migrate == true) {
            // use this when changes have to be made to live database
            $sqlList = ["ALTER TABLE players ADD COLUMN totaltribute INTEGER;",
                        "ALTER TABLE players ADD COLUMN lasttribute TIMESTAMP;",
                        "ALTER TABLE players ADD COLUMN currency INTEGER;",
                        "ALTER TABLE cells ADD COLUMN tribute INTEGER;",
                        "UPDATE players SET totaltribute=100*(SELECT count(*) FROM cells WHERE playerid=players.id);",
                        "UPDATE players SET lasttribute=CURRENT_TIMESTAMP;",
                        "UPDATE cells SET tribute=100;"
            ];
            foreach ($sqlList as $sql) {
                $pdo->exec($sql);
            }
        }
    } else {
        echo "that is not something you are authorized to do. notify Ben.";
    }
}
