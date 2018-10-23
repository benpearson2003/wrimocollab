<?php include "base.php"; ?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Cacoph: Call of War</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<?php
if(!empty($_SESSION['LoggedIn']) && !empty($_GET['logout'])) {
    session_start();
    session_unset();
    echo "<h1>See ya later!</h1>";
    echo "<p>You should be redirected to the login page.</p>";
    header('Location: index.php');
}
else if(!empty($_SESSION['LoggedIn']) && !empty($_SESSION['Username']) && !empty($_SESSION['ClanId']))
{ ?>
    <script>
        var canvas;
        var context;
        var tileSize = 125;
        var localWidth = 7;
        var localHeight = 7;
        var topCorner = [0, 0];
        var grid = [];
        var visiblePlayers = [];
        var worldWidth = <?php echo $GLOBALS[maxWidth] ?>;

        // [0] == playerid, [1] == x, [2] == y, [3] == water moves, [4] == land moves, [5] == clan id, [6] == currency,
        // [7] == spearfaes, [8] == archers, [9] == light groundfae, [10] == heavy groundfae, [11] == wingfae
        var player = [<?php echo $_SESSION['PlayerId'] ?>, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, ""];
        var merchActive = false;
        var selected = [];

        var percentages = []; // win conditions

        var mousedown = false;
        var mapActive = false;

        function convertToIndex(worldX, worldY){
            return ((worldX-1)*worldWidth+worldY);
        }
        function convertToX(worldWidth, index){
            return Math.ceil(index/worldWidth);
        }
        function convertToY(worldWidth, index){
            if (y = index%worldWidth) { return y; } else { return worldWidth; }
        }

        function startGame() {
            var data = [];
            percentages = [];
            player = [];
            grid = [];
            visiblePlayers = [];
            selected = [];

            var xhttp = new XMLHttpRequest();
            var url = "game.php";
            var params = "start=" + true;
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);

                    for(var item in parsed){
                      data.push(parsed[item]);
                    }
                    for(var item in data[0]){
                      percentages.push(data[0][item]);
                    }

                    player = data[1].slice();

                    for(var item in data[2]){
                      grid.push(data[2][item]);
                    }

                    for(var item in data[3]){
                      visiblePlayers.push(data[3][item]);
                    }

                    selected = data[4].slice();
                }
            };
            xhttp.open("POST", url, false);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(params);
        }

        function writeBattleReport(result) {
            /*
            false,                                      // result
            'playername',                               // enemy player
            'vicious and bloody struggle',              // casualties vs battle size
            'crushing weight of your mighty',           // size/skill of flock
            'was not enough against the enemy',         // result, but poetic
            'Your Flock has been completely routed.',   // what happens to the flock
            100,                                        // enemy casualties
            0,0,20,0,18,                                // player casualties breakdown
            0                                           // plunder
            */
            var battledesc = "<h2>In This Battle</h2>" +
            "<p>Your flock met a force under the command of "+result[1]+".</p>" +
            "<p>After a "+result[2]+", your flock emerged "+(result[0] ? "VICTORIOUS" : "DEFEATED")+".</p>" +
            "<p>The "+result[3]+" Flock "+result[4]+". "+result[5]+"</p>" +
            "<p>Your flock killed "+result[6]+" Enemy Fae.</p>";
            var totalCas = result[7] + result[8] + result[9] + result[10] + result[11];
            var casualties = "<h3>Your Flock suffered "+totalCas+" Casualties.</h3>" +
            "Spearfae = "+result[7]+" killed<br/>" +
            "Archers = "+result[8]+" killed<br/>" +
            "Light Groundfae = "+result[9]+" killed<br/>" +
            "Heavy Groundfae = "+result[10]+" killed<br/>" +
            "Wingfae = "+result[11]+" killed";
            var plunder = result[12] > 0 ?
                "<p>You have gained "+result[12]+" Gold in plunder from this territory</p>" : "";
            document.getElementById('battle-desc').innerHTML = battledesc;
            document.getElementById('casualties').innerHTML = casualties;
            document.getElementById('plunder').innerHTML = plunder;
            document.getElementById('battle-summary').style.display = "";
            document.getElementById('battle-summary').style.display = "";
            document.getElementById('myModal').style.display = "block";
        }

        function showMyRegions() {
            closeModal();
            var rows = "";

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);

                    document.getElementById('my-regions-table').innerHTML="<tr><th>Location</th><th>Tribute</th><th>Faeries</th><th>&nbsp;</th><th>&nbsp;</th></tr>";

                    for(var item in parsed){
                      rows += "<tr><td>" + parsed[item][0] + "," + parsed[item][1] + "</td><td>" + parsed[item][2] + "</td><td>" + parsed[item][3] + "</td>"
                            + "<td><button onclick='closeModal();document.getElementById(\"jumptoX\").value=" + parsed[item][0]
                            + ";document.getElementById(\"jumptoY\").value=" + parsed[item][1]
                            + ";viewOnMap()'>map</button></td>"
                            + "<td><button onclick='closeModal();document.getElementById(\"jumptoX\").value=" + parsed[item][0]
                            + ";document.getElementById(\"jumptoY\").value=" + parsed[item][1]
                            + ";jump()'>details</button></td></tr>";
                    }
                    document.getElementById('my-regions-table').innerHTML+=rows;
                    document.getElementById('myModal').style.display = "block";
                    document.getElementById('my-regions').style.display="";
                }
            };
            xhttp.open("GET", "region.php?myregions=" + true, false);
            xhttp.send();
        }

        function viewMap() {
            if(mapActive) {
                mapActive = false;
                drawAllTiles();
            } else {
                mapActive = true;
                var colors = ['#00eeff','#ff004d','#0036ee','#b700ee','#ffff00','#ff8827','#00a217']
                var fullMap = getAllCellsForMap(1,1);
                var i;
                for(i = 0; i < fullMap.length; i++) {
                	if(fullMap[i][0] == true) {
                    	fullMap[i] = '#001d7c';
                	} else if(fullMap[i][4] == 0 || fullMap[i][4] == null) {
                		fullMap[i] = colors[6];
                    } else {
                		fullMap[i] = colors[fullMap[i][4]%6];
                	}
                }
                canvas = document.getElementById('canvas');
                context = canvas.getContext('2d');
                context.fillStyle = "white";
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.font = "100px Arial";
                context.fillStyle = "red";
                context.fillText("X",10,75);
                for(i = 1; i <= fullMap.length; i++) {
                	x = convertToX(worldWidth, i);
                	y = convertToY(worldWidth, i);
                	context.fillStyle = fullMap[i-1];
                	context.fillRect(x*25+tileSize/2,y*25+tileSize/2,25,25);
                }
            }
        }

        function select(event) {
            if (mapActive == true) {
                mapActive = false;
                drawAllTiles();
            } else {
                var rect = canvas.getBoundingClientRect();
                var X = event.x - rect.left;
                var Y = event.y - rect.top;
                var worldX = Math.floor(X / tileSize) + topCorner[0]; // convert mouse coords to tile coords
                var worldY = Math.floor(Y / tileSize) + topCorner[1];
                highlight(worldX, worldY, false);
            }
        }

        function highlight(worldX, worldY, routed, start = false) {
            // check coordinates are in bounds
            if (worldX <= worldWidth && worldY <= worldWidth && worldX > 0 && worldY > 0) {
                prevSelected = selected;
                if(!start) { selected = getCell(worldX, worldY); }
                document.getElementById('attack').style.display = "none";
                document.getElementById('move').style.display = "none";
                if(player[1] == worldX && player[2] == worldY) {
                    if(selected[0] != 1) {
                        document.getElementById('open-recruit').style.display = "";
                        document.getElementById('available-villagers').innerHTML =
                            selected[1] == 1 ? "There is 1 villager ready for training." : "There are " + selected[1] + " villagers ready for training.";
                        document.getElementById('open-transfer-garrison').style.display = "";
                        if(selected[2]==player[0]) {
                            document.getElementById('move-to-flock').style.display = "";
                        } else {
                            document.getElementById('move-to-flock').style.display = "none";
                        }
                    }
                } else {
                    document.getElementById('open-transfer-garrison').style.display = "none";
                    document.getElementById('open-recruit').style.display = "none";
                    if(Math.abs(player[1] - worldX) < 2 && Math.abs(player[2] - worldY) < 2) {
                        if((selected[4] == player[5] && player[4] > 0) || (selected[0] == 1 && player[3] > 0)){
                            document.getElementById('move').style.display = "";
                            document.getElementById('attack').style.display = "none";
                        } else if(selected[0] != 1 && player[4] > 0){
                            document.getElementById('move').style.display = "none";
                            document.getElementById('attack').style.display = "";
                        }
                    }
                }
                if(selected[2] == player[0]) {
                    document.getElementById('open-recruit-garrison').style.display = "";
                    document.getElementById('available-villagers').innerHTML =
                        selected[1] == 1 ? "There is 1 villager ready for training." : "There are " + selected[1] + " villagers ready for training.";
                } else {
                    document.getElementById('open-recruit-garrison').style.display = "none";
                }
                drawTile(prevSelected);
                if (routed == true) {
                    var prevWorldX = convertToX(worldWidth, prevSelected[3]);
                    var prevWorldY = convertToY(worldWidth, prevSelected[3]);
                    context.drawImage(document.getElementById('flag'), (prevSelected[4] == 0 || prevSelected[4] == null ? 6*125 : prevSelected[4]%6*125), 1*125, 125, 125, (prevWorldX-topCorner[0]) * tileSize, (prevWorldY-topCorner[1]) * tileSize, tileSize, tileSize); // shield
                }
                drawTile(selected);
                // click occurs in the top left corner
            }
        }

        function jump() {
            var worldX = document.getElementById('jumptoX').value;
            var worldY = document.getElementById('jumptoY').value;
            highlight(worldX,worldY,false);
            viewRegion();
        }

        function viewOnMap() {
            var worldX = document.getElementById('jumptoX').value;
            var worldY = document.getElementById('jumptoY').value;
            topCorner = [worldX - Math.floor(localWidth/2), worldY - Math.floor(localHeight/2)];
            mapActive = true;
            drawAllTiles();
            highlight(worldX,worldY,false,true);
        }

        function viewRegion(region = false) {
            var tile = [];
            if(!region) { tile = getRegion(selected[3]); } else { tile = region; }
            document.getElementById('location-name').textContent = tile[0];
            document.getElementById('jumptoX').value = convertToX(worldWidth, selected[3]);
            document.getElementById('jumptoY').value = convertToY(worldWidth, selected[3]);
            if(selected[0] != 1) {
                document.getElementById('tribute').textContent = tile[1];
            	var defensiveGarrison = '<strong>Defensive Garrison</strong><table>';
                defensiveGarrison += (tile[2][0] != null) ? ('<tr><td id="spearfae-garrison">'+tile[2][0]+'</td><td> Spearfaes</td></tr>') : ('');
                defensiveGarrison += (tile[2][1] != null) ? ('<tr><td id="archer-garrison">'+tile[2][1]+'</td><td> Archers</td></tr>') : ('');
                defensiveGarrison += (tile[2][2] != null) ? ('<tr><td id="light-garrison">'+tile[2][2]+'</td><td> Light Groundfae</td></tr>') : ('');
                defensiveGarrison += (tile[2][3] != null) ? ('<tr><td id="heavy-garrison">'+tile[2][3]+'</td><td> Heavy Groundfae</td></tr>') : ('');
                defensiveGarrison += '</table>';
            	document.getElementById('defensive-garrison').innerHTML = defensiveGarrison;
                var improvements = '<strong>Improvements</strong><table>';
                var i;
                for(var k in tile[3]){
                    improvements += (tile[3][k] != null) ? ('<tr><td>'+tile[3][k]+'</td></tr>') : ('');
                }
                improvements += '</table>';
            	document.getElementById('improvements').innerHTML = improvements;
            }
            else {
                document.getElementById('defensive-garrison').innerHTML = '';
                document.getElementById('improvements').innerHTML = '';
            }

            document.getElementById('myModal').style.display = "block";
            document.getElementById('target-region').style.display ="";
        }

        function getRegion(id) {
            var tile = [];

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);

                    for(var item in parsed){
                      tile.push(parsed[item]);
                    }
                }
            };
            xhttp.open("GET", "region.php?id=" + id + "&spying=false", false);
            xhttp.send();

            return tile;
        }

        function move() {
            var xhttp = new XMLHttpRequest();
            var url = "player.php";
            var params = "ismerch=" + merchActive + "&regionid=" + selected[3];
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    player = getPlayer();
                    document.getElementById('water-moves').innerHTML = player[3] > 0 ? player[3] : "<span class='out-of-moves'>" + player[16] + " until next turn.</span>";
                    document.getElementById('land-moves').innerHTML = player[4] > 0 ? player[4] : "<span class='out-of-moves'>" + player[16] + " until next turn.</span>";
                    topCorner = [player[1] - Math.floor(localWidth/2), player[2] - Math.floor(localHeight/2)];
                    drawAllTiles();
                    highlight(player[1], player[2], false);
                    closeModal();
                }
            };
            xhttp.open("POST", url, false);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(params);
        }

        function attack() {
            var xhttp = new XMLHttpRequest();
            var url = "attack.php";
            var params = "playerid=" + player[0] + "&targetid=" + selected[3];
            var result = [];
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    player = getPlayer();
                    document.getElementById('land-moves').innerHTML = player[4] > 0 ? player[4] : "<span class='out-of-moves'>" + player[16] + " until next turn.</span>";
                    result = eval("(" + this.responseText + ")");
                    if (result[0] == true) {
                        topCorner = [player[1] - Math.floor(localWidth/2), player[2] - Math.floor(localHeight/2)];
                        drawAllTiles();
                        highlight(player[1], player[2], false);
                        document.getElementById('treasury').textContent = player[6];
                        document.getElementById('spearfae-unit').textContent = player[7];
                        document.getElementById('archer-unit').textContent = player[8];
                        document.getElementById('lightground-unit').textContent = player[9];
                        document.getElementById('heavyground-unit').textContent = player[10];
                        document.getElementById('wingfae-unit').textContent = player[11];
                    } else {
                        drawTile(selected);
                        highlight(player[1], player[2], true);
                        document.getElementById('treasury').textContent = player[6];
                        document.getElementById('spearfae-unit').textContent = player[7];
                        document.getElementById('archer-unit').textContent = player[8];
                        document.getElementById('lightground-unit').textContent = player[9];
                        document.getElementById('heavyground-unit').textContent = player[10];
                        document.getElementById('wingfae-unit').textContent = player[11];
                    }
                    document.getElementById('target-region').style.display = "none";
                    writeBattleReport(result);
                }
            };
            xhttp.open("POST", url, false);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(params);
        }

        function getPlayer() {
            var placeholder = []

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);

                    for(var item in parsed){
                      placeholder.push(parsed[item]);
                    }
                }
            };
            xhttp.open("GET", "player.php?playerid=" + player[0] + "&activeplayer=" + 1, false);
            xhttp.send();

            return placeholder;
        }

        function getAllCellsForMap(worldX, worldY) {
            var grid = [];

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);
                    var data = [];

                    for(var item in parsed){
                      data.push(parsed[item]);
                    }

                    for(var item in data[0]){
                      grid.push(data[0][item]);
                    }
                }
            };
            xhttp.open("GET", "region.php?x=" + worldX + "&y=" + worldY + "&width=" + worldWidth + "&height=" + worldWidth, false);
            xhttp.send();

            return grid;
        }

        function getAllCells(worldX, worldY) {
            var data = [];
            grid = [];
            visiblePlayers = [];
            selected = [];

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);

                    for(var item in parsed){
                      data.push(parsed[item]);
                    }

                    for(var item in data[0]){
                      grid.push(data[0][item]);
                    }

                    for(var item in data[1]){
                      visiblePlayers.push(data[1][item]);
                    }

                    selected = data[2].slice();
                }
            };
            xhttp.open("GET", "region.php?x=" + worldX + "&y=" + worldY + "&width=" + localWidth + "&height=" + localHeight, false);
            xhttp.send();
        }

        function getVisiblePlayers(worldX, worldY) {
            var players = [];

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);

                    for(var item in parsed){
                      players.push(parsed[item]);
                    }
                }
            };
            xhttp.open("GET", "player.php?x=" + worldX + "&y=" + worldY + "&width=" + localWidth + "&height=" + localHeight, false);
            xhttp.send();

            return players;
        }

        function getCell(worldX, worldY) {
            var tile = [];

            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);

                    for(var item in parsed){
                      tile.push(parsed[item]);
                    }
                }
            };
            xhttp.open("GET", "region.php?x=" + worldX + "&y=" + worldY, false);
            xhttp.send();

            return tile;
        }

        function recruit() {
            var recruitArray = [];
            recruitArray[0] = parseInt(document.getElementById('spearfae-recruit').value);
            recruitArray[1] = parseInt(document.getElementById('archer-recruit').value);
            recruitArray[2] = parseInt(document.getElementById('light-recruit').value);
            recruitArray[3] = parseInt(document.getElementById('heavy-recruit').value);
            recruitArray[4] = parseInt(document.getElementById('wingfae-recruit').value);
            var xhttp = new XMLHttpRequest();
            var url = "player.php";
            var params = "regionid=" + selected[3] + "&recruits=" + recruitArray;
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);
                    var data = [];

                    for(var item in parsed){
                      data.push(parsed[item]);
                    }

                    player = data[0].slice();

                    selected = data[1].slice();

                    highlight(convertToX(worldWidth,selected[3]),convertToY(worldWidth,selected[3]),false,true);
                    document.getElementById('spearfae-recruit').value = 0;
                    document.getElementById('archer-recruit').value = 0;
                    document.getElementById('light-recruit').value = 0;
                    document.getElementById('heavy-recruit').value = 0;
                    document.getElementById('wingfae-recruit').value = 0;
                    document.getElementById('treasury').textContent = player[6];
                    document.getElementById('spearfae-unit').textContent = player[7];
                    document.getElementById('archer-unit').textContent = player[8];
                    document.getElementById('lightground-unit').textContent = player[9];
                    document.getElementById('heavyground-unit').textContent = player[10];
                    document.getElementById('wingfae-unit').textContent = player[11];
                    closeModal();
                    showFlock();
                }
            };
            xhttp.open("POST", url, false);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(params);
        }

        function recruitGarrison() {
            var recruitArray = [];
            recruitArray[0] = parseInt(document.getElementById('spearfae-garrison-recruit').value);
            recruitArray[1] = parseInt(document.getElementById('archer-garrison-recruit').value);
            recruitArray[2] = parseInt(document.getElementById('light-garrison-recruit').value);
            recruitArray[3] = parseInt(document.getElementById('heavy-garrison-recruit').value);
            var xhttp = new XMLHttpRequest();
            var url = "region.php";
            var params = "regionid=" + selected[3] + "&recruits=" + recruitArray;
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);
                    var data = [];
                    var region = [];

                    for(var item in parsed){
                        data.push(parsed[item]);
                    }

                    player = data[0].slice();
                    selected = data[1].slice();
                    for(var item in data[2]){
                        region.push(data[2][item]);
                    }

                    highlight(convertToX(worldWidth,selected[3]),convertToY(worldWidth,selected[3]),false,true);
                    document.getElementById('spearfae-garrison-recruit').value = 0;
                    document.getElementById('archer-garrison-recruit').value = 0;
                    document.getElementById('light-garrison-recruit').value = 0;
                    document.getElementById('heavy-garrison-recruit').value = 0;
                    document.getElementById('treasury').textContent = player[6];
                    document.getElementById('spearfae-unit').textContent = player[7];
                    document.getElementById('archer-unit').textContent = player[8];
                    document.getElementById('lightground-unit').textContent = player[9];
                    document.getElementById('heavyground-unit').textContent = player[10];
                    document.getElementById('wingfae-unit').textContent = player[11];
                    closeModal();
                    viewRegion(region);
                }
            };
            xhttp.open("POST", url, false);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(params);
        }

        function moveToFlock() {
            var moveArray = [];
            moveArray[0] = parseInt(document.getElementById('move-spearfae').value)*-1;
            moveArray[1] = parseInt(document.getElementById('move-archer').value)*-1;
            moveArray[2] = parseInt(document.getElementById('move-light').value)*-1;
            moveArray[3] = parseInt(document.getElementById('move-heavy').value)*-1;
            var xhttp = new XMLHttpRequest();
            var url = "region.php";
            var params = "regionid=" + selected[3] + "&moving=" + moveArray;
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);
                    var data = [];

                    for(var item in parsed){
                        data.push(parsed[item]);
                    }

                    player = data[0].slice();

                    highlight(convertToX(worldWidth,selected[3]),convertToY(worldWidth,selected[3]),false,true);
                    document.getElementById('move-spearfae').value = 0;
                    document.getElementById('move-archer').value = 0;
                    document.getElementById('move-light').value = 0;
                    document.getElementById('move-heavy').value = 0;
                    document.getElementById('treasury').textContent = player[6];
                    document.getElementById('spearfae-unit').textContent = player[7];
                    document.getElementById('archer-unit').textContent = player[8];
                    document.getElementById('lightground-unit').textContent = player[9];
                    document.getElementById('heavyground-unit').textContent = player[10];
                    document.getElementById('wingfae-unit').textContent = player[11];
                    closeModal();
                    showFlock();
                }
            };
            xhttp.open("POST", url, false);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(params);
        }

        function moveToGarrison() {
            var moveArray = [];
            moveArray[0] = parseInt(document.getElementById('move-spearfae').value);
            moveArray[1] = parseInt(document.getElementById('move-archer').value);
            moveArray[2] = parseInt(document.getElementById('move-light').value);
            moveArray[3] = parseInt(document.getElementById('move-heavy').value);
            var xhttp = new XMLHttpRequest();
            var url = "region.php";
            var params = "regionid=" + selected[3] + "&moving=" + moveArray;
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var parsed = JSON.parse(this.responseText);
                    var data = [];
                    var region = [];

                    for(var item in parsed){
                        data.push(parsed[item]);
                    }

                    player = data[0].slice();
                    for(var item in data[1]){
                        region.push(data[1][item]);
                    }

                    highlight(convertToX(worldWidth,selected[3]),convertToY(worldWidth,selected[3]),false,true);
                    document.getElementById('move-spearfae').value = 0;
                    document.getElementById('move-archer').value = 0;
                    document.getElementById('move-light').value = 0;
                    document.getElementById('move-heavy').value = 0;
                    document.getElementById('treasury').textContent = player[6];
                    document.getElementById('spearfae-unit').textContent = player[7];
                    document.getElementById('archer-unit').textContent = player[8];
                    document.getElementById('lightground-unit').textContent = player[9];
                    document.getElementById('heavyground-unit').textContent = player[10];
                    document.getElementById('wingfae-unit').textContent = player[11];
                    closeModal();
                    viewRegion(region);
                }
            };
            xhttp.open("POST", url, false);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(params);
        }

        function drawArc(cx,cy,radius,startAngle,endAngle) {
        	context.moveTo(cx,cy);
            context.arc(cx,cy,radius,startAngle,endAngle);
            context.closePath();
            context.fill();
        }

        function drawTile(tile) {
            canvas = document.getElementById('canvas');
            context = canvas.getContext('2d');
            context.font = 'Bold 22px Arial';
            var worldX = convertToX(worldWidth, tile[3]);
            var worldY = convertToY(worldWidth, tile[3]);
        	var xFromTop = worldX-topCorner[0];
        	var yFromTop = worldY-topCorner[1];
            if(typeof tile != 'undefined') {
                context.strokeStyle = 'white';
                context.lineWidth = '2';
                if (tile[0] != 1) {
                    context.fillStyle = 'green';
                    context.fillRect((xFromTop) * tileSize, (yFromTop) * tileSize, tileSize, tileSize); // background
                    if ((tile[2] == player[0] || tile[2] != 0) && tile[4] != null) {
                        context.drawImage(document.getElementById('flag'), (tile[4]%6)*125, 0, 125, 125, (xFromTop) * tileSize, (yFromTop) * tileSize, tileSize, tileSize); // player flag
                    }
                    context.drawImage(document.getElementById('flag'), 0*125, 3*125, 125, 125, (xFromTop) * tileSize, (yFromTop) * tileSize, tileSize, tileSize); // village
                }
                for (var i = 0; i < visiblePlayers.length; i++) {
                    if (visiblePlayers[i][0] != player[0] && worldX == visiblePlayers[i][1] && worldY == visiblePlayers[i][2]) {
                        context.save();
                        context.scale(-1,1);
                        context.drawImage(document.getElementById('flag'),
                            visiblePlayers[i][3]%6*125,
                            4*125,
                            125,
                            125,
                            (xFromTop) * -1 * tileSize - tileSize/1.25,
                            (worldY-topCorner[1]) * tileSize - tileSize/4,
                            tileSize/1.25,
                            tileSize/1.25
                        );
                        context.restore();
                    }
                }
                if (worldX == player[1] && worldY == player[2]) {
                    context.drawImage(document.getElementById('flag'), (player[5]%6)*125, 4*125, 125, 125, (xFromTop) * tileSize, (yFromTop) * tileSize, tileSize, tileSize); // player
                    if (tile[3] == selected[3]) {
                        context.strokeStyle = 'black';
                    }
                } else if (tile[3] == selected[3]) {
                    context.strokeStyle = 'black';

                }
                context.strokeRect((xFromTop) * tileSize, (yFromTop) * tileSize, tileSize, tileSize); // outline
            }
        }
        function drawInsideCorners(tile) {
            var cornerPos = {
                "1": [0,0.75,0.25,0],
                "4": [0,0.75,0.25,0],
                "-3": [0,0.75,0.25,0],
                "2": [0,0,0.25,0.25],
                "3": [0.75,0,0,0.25],
                "-4": [0.75,0,0,0.25],
                "-1": [0.75,0.75,0,0],
                "-2": [0.75,0.75,0,0]
            };
            if(tile[0] != true) {
                canvas = document.getElementById('canvas');
                context = canvas.getContext('2d');
                var radius = tileSize/4;
                var worldX = convertToX(worldWidth, tile[3]);
                var worldY = convertToY(worldWidth, tile[3]);
            	var xFromTop = worldX-topCorner[0];
            	var yFromTop = worldY-topCorner[1];
            	var neighborXY = [
                    [1,0,1,1],
            		[0,-1,-1,-1],
                    [1,0,1,-1],
            		[0,1,1,1],
            		[-1,0,-1,-1],
            		[0,-1,-1,1],
                    [-1,0,-1,1]
            	];
                while(neighborXY.length > 0){
                    var cornerXDif = neighborXY[0][2];
                    var cornerYDif = neighborXY[0][3];
                    var edgeXDif = neighborXY[0][0];
                    var edgeYDif = neighborXY[0][1];
                	var edgeXFromTop = xFromTop+edgeXDif;
                	var edgeYFromTop = yFromTop+edgeYDif;

                    var corner = [];
                    var cornerIndex = convertToIndex(worldX+cornerXDif,worldY+cornerYDif);
            		for (var j = 0;j<grid.length;j++) {
            			if (grid[j][3] == cornerIndex){
            				corner=grid[j].slice();
            				break;
                        }
                    }
                    var edge = [];
                    var edgeIndex = convertToIndex(worldX+edgeXDif,worldY+edgeYDif);
            		for (var j = 0;j<grid.length;j++) {
            			if (grid[j][3] == edgeIndex){
            				edge=grid[j].slice();
            				break;
                        }
                    }
                    if(corner.length > 0 && edge.length > 0) {
                        if(corner[0] != true && edge[0] == true) {
                            var tileMult = cornerPos[(edgeXDif*2+edgeYDif+cornerXDif+cornerYDif).toString()];
                            var cx = edgeXFromTop * tileSize+(tileSize*tileMult[0]);
        					var cy = edgeYFromTop * tileSize+(tileSize*tileMult[1]);
                            var grd = context.createRadialGradient(
                                cx+(tileSize*tileMult[2]),
                                cy+(tileSize*tileMult[3]),
                                0,
                                cx+(tileSize*tileMult[2]),
                                cy+(tileSize*tileMult[3]),
                                radius);
                            grd.addColorStop(1,"green");
                            grd.addColorStop(0,"sandybrown");
                            context.fillStyle = grd;
        					context.fillRect(
                                cx,
                                cy,
                                tileSize/4,
                                tileSize/4);
                        }
                    }
                    neighborXY.splice(0,1);
                }
            }
        }
        function drawOutsideCorners(tile) {
            canvas = document.getElementById('canvas');
            context = canvas.getContext('2d');
            context.font = 'Bold 22px Arial';
            var worldX = convertToX(worldWidth, tile[3]);
            var worldY = convertToY(worldWidth, tile[3]);
        	var xFromTop = worldX-topCorner[0];
        	var yFromTop = worldY-topCorner[1];
        	var neighborXY = [
        		[1,1],
        		[-1,-1],
        		[-1,1],
        		[1,-1]
        	];
        	var neighbors = [];
        	for (var i = 0;i<neighborXY.length;i++){
        		var indie = convertToIndex(worldX+neighborXY[i][0],worldY+neighborXY[i][1]);
        		for (var j = 0;j<grid.length;j++) {
        			if (grid[j][3] == indie){
        				neighbors.push(grid[j]);
        				break;
                    }
                }
            }
            if(typeof tile != 'undefined') {
                context.strokeStyle = 'black';
                context.lineWidth = '2';
                if (tile[0] != 1) {
                    for (var i = 0; i < neighbors.length; i++) {
        				if(typeof neighbors[i] != 'undefined') {
                            if (neighbors[i][0] == 1) {
                                context.fillStyle = 'sandybrown';
        						var xDif = convertToX(30,neighbors[i][3])-worldX;
        						var yDif = convertToY(30,neighbors[i][3])-worldY;
        						var radius = tileSize/4;
        						if(xDif == -1) {
        							if(yDif == -1){
        								var cx = xFromTop*tileSize;
        								var cy = yFromTop*tileSize;
                                        var startAngle = Math.PI;
                                        var endAngle = Math.PI*1.5;
                                        var grd = context.createRadialGradient(cx,cy,0,cx,cy,radius);
                                        grd.addColorStop(0,"green");
                                        grd.addColorStop(1,"sandybrown");
                                        context.fillStyle = grd;
        								context.beginPath();
        								drawArc(cx,cy,radius,startAngle,endAngle);
        								context.strokeRect(
                                          cx-tileSize,
                                          cy-tileSize,
                                          tileSize, tileSize); // outline
                                	} else if(yDif == 1){
        								var cx = xFromTop*tileSize;
                                        var cy = yFromTop*tileSize+tileSize;
                                        var startAngle = Math.PI*0.5;
                                        var endAngle = Math.PI;
                                        var grd = context.createRadialGradient(cx,cy,0,cx,cy,radius);
                                        grd.addColorStop(0,"green");
                                        grd.addColorStop(1,"sandybrown");
                                        context.fillStyle = grd;
        								context.beginPath();
                                        drawArc(cx,cy,radius,startAngle,endAngle);
        								context.strokeRect(
                                          cx-radius*4,
                                          cy,
                                          radius*4, radius*4); // outline
                                    }
                                } else if (xDif == 1) {
        							if(yDif == -1){
        								var cx = (xFromTop+xDif) * tileSize;
        								var cy = yFromTop * tileSize;
                                        var startAngle = Math.PI*1.5;
                                        var endAngle = 0;
                                        var grd = context.createRadialGradient(cx,cy,0,cx,cy,radius);
                                        grd.addColorStop(0,"green");
                                        grd.addColorStop(1,"sandybrown");
                                        context.fillStyle = grd;
        								context.beginPath();
        								drawArc(cx,cy,radius,startAngle,endAngle);
        								context.strokeRect(
                                          cx+tileSize,
                                          cy-tileSize,
                                          tileSize, tileSize); // outline
                                	} else if(yDif == 1){
        								var cx = (xFromTop+xDif) * tileSize;
                                        var cy = yFromTop*tileSize+tileSize;
                                        var startAngle = 0;
                                        var endAngle = Math.PI*0.5;
                                        var grd = context.createRadialGradient(cx,cy,0,cx,cy,radius);
                                        grd.addColorStop(0,"green");
                                        grd.addColorStop(1,"sandybrown");
                                        context.fillStyle = grd;
        								context.beginPath();
                                        drawArc(cx,cy,radius,startAngle,endAngle);
        								context.strokeRect(
                                          cx+tileSize,
                                          cy+tileSize,
                                          radius*4, radius*4); // outline
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        function drawStraightBanks(tile) {
            canvas = document.getElementById('canvas');
            context = canvas.getContext('2d');
            context.font = 'Bold 22px Arial';
            var worldX = convertToX(worldWidth, tile[3]);
            var worldY = convertToY(worldWidth, tile[3]);
        	var xFromTop = worldX-topCorner[0];
        	var yFromTop = worldY-topCorner[1];
        	var neighborXY = [
        		[-1,0],
        		[0,-1],
        		[0,1],
        		[1,0]
        	];
        	var neighbors = [];
        	for (var i = 0;i<neighborXY.length;i++){
        		var indie = convertToIndex(worldX+neighborXY[i][0],worldY+neighborXY[i][1]);
        		for (var j = 0;j<grid.length;j++) {
        			if (grid[j][3] == indie){
        				neighbors.push(grid[j]);
        				break;
                    }
                }
            }
            if(typeof tile != 'undefined') {
                context.strokeStyle = 'black';
                context.lineWidth = '2';
                if (tile[0] != 1) {
                    for (var i = 0; i < neighbors.length; i++) {
        				if(typeof neighbors[i] != 'undefined') {
                            if (neighbors[i][0] == 1) {
                                context.fillStyle = 'sandybrown';
        						var xDif = convertToX(30,neighbors[i][3])-worldX;
        						var yDif = convertToY(30,neighbors[i][3])-worldY;
        						var radius = tileSize/4;
        						if(xDif == -1) {
    								var grd = context.createLinearGradient(
    									xFromTop * tileSize,
                                        0,
    									xFromTop * tileSize - tileSize/4,
                                        0);
                                    grd.addColorStop(0,"green");
                                    grd.addColorStop(1,"sandybrown");
    								context.fillStyle = grd;
                                    context.fillRect(
                                        (xFromTop+xDif) * tileSize + (tileSize*0.75),
                                        (yFromTop) * tileSize,
                                        tileSize/4,
                                        tileSize); // sandbank
                                    context.strokeRect(
                                        (xFromTop+xDif) * tileSize,
                                        (yFromTop+yDif) * tileSize,
                                        tileSize, tileSize); // outline
                                } else if (xDif == 1) {
        							var grd = context.createLinearGradient(
                                        xFromTop * tileSize + tileSize,
                                        0,
                                        xFromTop * tileSize + tileSize*1.25,
                                        0);
                                    grd.addColorStop(0,"green");
                                    grd.addColorStop(1,"sandybrown");
        							context.fillStyle = grd;
        							if(yDif == 0){
                                        context.fillRect(
                                            (xFromTop+xDif) * tileSize,
                                            yFromTop * tileSize,
                                            (tileSize/4),
                                            (tileSize)); // sandbank
                                    	context.strokeRect(
                                            (xFromTop+xDif) * tileSize,
                                            (yFromTop) * tileSize,
                                            tileSize, tileSize); // outline
                                    }
                                } else if(yDif == -1) {
        							var grd = context.createLinearGradient(
                                        0,
                                        yFromTop * tileSize,
                                        0,
                                        yFromTop * tileSize - tileSize/4);
                                    grd.addColorStop(0,"green");
                                    grd.addColorStop(1,"sandybrown");
        							context.fillStyle = grd;
                                    context.fillRect(
                                        (xFromTop) * tileSize,
                                        (yFromTop+yDif) * tileSize + (tileSize*0.75),
                                        (tileSize),
                                        (tileSize/4)); // sandbank
                                    context.strokeRect(
                                        (xFromTop) * tileSize,
                                        (yFromTop+yDif) * tileSize,
                                        tileSize, tileSize); // outline
                                } else if (yDif == 1) {
        							var grd = context.createLinearGradient(
                                        0,
                                        yFromTop * tileSize + tileSize,
                                        0,
                                        yFromTop * tileSize + tileSize*1.25);
                                    grd.addColorStop(0,"green");
                                    grd.addColorStop(1,"sandybrown");
        							context.fillStyle = grd;
                                    context.fillRect(
                                        (xFromTop) * tileSize,
                                        (yFromTop+yDif) * tileSize,
                                        (tileSize),
                                        (tileSize/4)); // sandbank
                                    context.strokeRect(
                                        (xFromTop) * tileSize,
                                        (yFromTop+yDif) * tileSize,
                                        tileSize, tileSize); // outline
                                }
                            }
                        }
                    }
                }
            }
        }
        function drawStars() {
            for (var worldX = topCorner[0]; worldX < topCorner[0]+localWidth; worldX++) {
                for (var worldY = topCorner[1]; worldY < topCorner[1]+localHeight; worldY++) {
                    context.fillStyle = 'black';
                    context.fillRect((worldX-topCorner[0]) * tileSize, (worldY-topCorner[1]) * tileSize, tileSize, tileSize); // background
                    for (var i = 0; i < 15; i++) {
                        var horiz, verti, s; //x-axis, y-axis, size
                        var center = tileSize/2;
                        horiz = (worldX-topCorner[0]) * tileSize + (Math.random() * tileSize-center);
                        verti = (worldY-topCorner[1]) * tileSize + (Math.random() * tileSize-center);

                        s = Math.random() * 2;

                        context.beginPath();
                        context.fillStyle = 'white';
                        context.arc(horiz,verti,s,0,Math.PI*2);
                        context.fill();
                    }
                }
            }
        }
        function drawWater(tile) {
            canvas = document.getElementById('canvas');
            context = canvas.getContext('2d');
            var worldX = convertToX(worldWidth, tile[3]);
            var worldY = convertToY(worldWidth, tile[3]);

            if(typeof tile != 'undefined') {
                context.strokeStyle = 'black';
                context.lineWidth = '2';
                context.fillStyle = 'blue';
                context.fillRect((worldX-topCorner[0]) * tileSize, (worldY-topCorner[1]) * tileSize, tileSize, tileSize); // background
            }
        }
        function drawAllTiles(start = false) {
            drawStars();
            if(!start) { getAllCells(topCorner[0],topCorner[1]); }
        	for (var i = 0; i < grid.length; i++) {
                drawWater(grid[i]);
            }
            for (var i = 0; i < grid.length; i++) {
                drawOutsideCorners(grid[i]);
            }
            for (var i = 0; i < grid.length; i++) {
                drawStraightBanks(grid[i]);
            }
            for (var i = 0; i < grid.length; i++) {
                drawInsideCorners(grid[i]);
            }
            for (var i = 0; i < grid.length; i++) {
                drawTile(grid[i]);
            }
        }

        window.onload = function() {
            startGame();
            canvas = document.getElementById('canvas');
            context = canvas.getContext('2d');
            context.canvas.width = 875;
            context.canvas.height = 885;

            topCorner = [player[1] - Math.floor(localWidth/2), player[2] - Math.floor(localHeight/2)];
            document.getElementById('water-moves').innerHTML = player[3] > 0 ? player[3] : "<span class='out-of-moves'>" + player[16] + " until next turn.</span>";
            document.getElementById('land-moves').innerHTML = player[4] > 0 ? player[4] : "<span class='out-of-moves'>" + player[16] + " until next turn.</span>";
            document.getElementById('treasury').textContent = player[6];
            document.getElementById('spearfae-unit').textContent = player[7];
            document.getElementById('archer-unit').textContent = player[8];
            document.getElementById('lightground-unit').textContent = player[9];
            document.getElementById('heavyground-unit').textContent = player[10];
            document.getElementById('wingfae-unit').textContent = player[11];
            drawAllTiles(true);
            highlight(player[1],player[2],false,true);
        }
    </script>

    <body>
        <div id="status">
            <div class="treasury"><strong>Treasury: </strong><span id="treasury"></span> Gold</div>
            <div id="moves">
                Water Moves: <span id="water-moves">0</span><hr>
                Land Moves: <span id="land-moves">0</span>
            </div>
            <button id="flock-button" onclick="showFlock();" title="view unit details">Flock</button>
        </div>
        <canvas id="canvas" title='click tile to act on' onmousedown="mousedown=true;select(event)" onmouseup="mousedown=false;"></canvas>
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="info" id="target-region" style="display:none;">
                    <div><strong id="location-name"></strong></div>
                    <div style="display:none;"><strong>Tribute: </strong><span id="tribute"></span></div>
                    <div id="defensive-garrison">
                    </div>
                    <div id="improvements"></div>
                    <button id="send-spy" style="display:none;" onclick="sendSpy();">
                        Send a spy to learn more<br/>
                        <span id="available-spies"></span> available spies
                    </button>
                    <button id="open-transfer-garrison" style="display:none;" onclick="openGarrison();">Transfer units between your flock and this region</button>
                    <button id="open-recruit-garrison" style="display:none;" onclick="openRecruitGarrison();">Recruit and train units for this region</button>
                    <button id="attack" style="display:none;" onclick="attack();">Attack this region</button>
                    <button id="move" style="display:none;" onclick="move();">Move to this region</button>
                    <input type="hidden" id="jumptoX"/>
                    <input type="hidden" id="jumptoY"/>
                    <button id="my-regions-button" onClick="showMyRegions();">My Regions</button>
                </div>
                <div class="info" id="battle-summary" style="display:none;">
                    <div><h1>Battle Summary</h1></div>
                    <article>
                        <section id="battle-desc"></section>
                        <section id="casualties"></section>
                        <section id="plunder"></section>
                    </article>
                </div>
                <div class="info" id="move-garrison" style="display:none;">
                    <table>
                        <tr>
                            <th>Number to move</th>
                            <th>Name</th>
                            <th>Offense</th>
                            <th>Defense</th>
                        </tr>
                        <tr>
                            <td><input id="move-spearfae" placeholder="0" type="number" value="0"/></td>
                            <td>Spearfae</td>
                            <td>1</td>
                            <td>2</td>
                        </tr>
                        <tr>
                            <td><input id="move-archer" placeholder="0" type="number" value="0"/></td>
                            <td>Archers</td>
                            <td>3</td>
                            <td>5</td>
                        </tr>
                        <tr>
                            <td><input id="move-light" placeholder="0" type="number" value="0"/></td>
                            <td>Light Groundfae</td>
                            <td>2</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td><input id="move-heavy" placeholder="0" type="number" value="0"/></td>
                            <td>Heavy Groundfae</td>
                            <td>5</td>
                            <td>3</td>
                        </tr>
                    </table>
                    <button id="move-to-flock" style="" onclick="moveToFlock();">Transfer units to your flock</button>
                    <button id="move-to-garrison" style="" onclick="moveToGarrison();">Transfer units to your garrison</button>
                </div>
                <div class="info" id="unit-info" style="display:none;">
                    <table>
                        <tr>
                            <td id="spearfae-unit"></td>
                            <td>Spearfaes</td>
                        </tr>
                        <tr>
                            <td id="archer-unit"></td>
                            <td>Archers</td>
                        </tr>
                        <tr>
                            <td id="lightground-unit"></td>
                            <td>Light Groundfae</td>
                        </tr>
                        <tr>
                            <td id="heavyground-unit"></td>
                            <td>Heavy Groundfae</td>
                        </tr>
                        <tr>
                            <td id="wingfae-unit"></td>
                            <td>Wingfae</td>
                        </tr>
                    </table>
                    <button id="open-recruit" style="display:none;" onclick="openRecruit();">Recruit and train additional units</button>
                </div>
                <div id="available-villagers" style="display:none;"></div>
                <div class="info" id="recruit" style="display:none;">
                    <table>
                        <tr>
                            <th>Number to recruit</th>
                            <th>Name</th>
                            <th>Cost</th>
                            <th>Offense</th>
                            <th>Defense</th>
                        </tr>
                        <tr>
                            <td><input id="spearfae-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Spearfae</td>
                            <td>20</td>
                            <td>1</td>
                            <td>2</td>
                        </tr>
                        <tr>
                            <td><input id="archer-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Archers</td>
                            <td>65</td>
                            <td>3</td>
                            <td>5</td>
                        </tr>
                        <tr>
                            <td><input id="light-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Light Groundfae</td>
                            <td>20</td>
                            <td>2</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td><input id="heavy-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Heavy Groundfae</td>
                            <td>50</td>
                            <td>5</td>
                            <td>3</td>
                        </tr>
                        <tr>
                            <td><input id="wingfae-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Wingfae</td>
                            <td>96</td>
                            <td>8</td>
                            <td>5</td>
                        </tr>
                    </table>
                    <button id="recruit-button" style="display:none;" onclick="recruit();">Recruit and train selected units</button>
                </div>
                <div class="info" id="recruit-garrison" style="display:none;">
                    <table>
                        <tr>
                            <th>Number to recruit</th>
                            <th>Name</th>
                            <th>Cost</th>
                            <th>Offense</th>
                            <th>Defense</th>
                        </tr>
                        <tr>
                            <td><input id="spearfae-garrison-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Spearfae</td>
                            <td>20</td>
                            <td>1</td>
                            <td>2</td>
                        </tr>
                        <tr>
                            <td><input id="archer-garrison-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Archers</td>
                            <td>65</td>
                            <td>3</td>
                            <td>5</td>
                        </tr>
                        <tr>
                            <td><input id="light-garrison-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Light Groundfae</td>
                            <td>20</td>
                            <td>2</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td><input id="heavy-garrison-recruit" placeholder="0" type="number" value="0"/></td>
                            <td>Heavy Groundfae</td>
                            <td>50</td>
                            <td>5</td>
                            <td>3</td>
                        </tr>
                    </table>
                    <button id="recruit-garrison-button" style="display:none;" onclick="recruitGarrison();">Recruit and train selected units</button>
                </div>
                <div class="info" id="my-regions" style="display:none;">
                    <strong>My Regions</strong>
                    <table id="my-regions-table">
                    </table>
                    <button id="open-recruit" style="display:none;" onclick="openRecruit();">Recruit and train additional units</button>
                </div>
            </div>
        </div>
        <button id="region-button" onclick="viewRegion();">Observe Region</button>
        <button id="map-button" onclick="viewMap();">Map</button>
        <script>
        var close = document.getElementsByClassName("close")[0];
        function openGarrison() {
            document.getElementById('target-region').style.display="none";
            document.getElementById('move-garrison').style.display="";
            document.getElementById('myModal').style.display = "block";
        }
        function openRecruitGarrison() {
            document.getElementById('target-region').style.display="none";
            document.getElementById('recruit-garrison').style.display="";
            document.getElementById('available-villagers').style.display="";
            document.getElementById('recruit-garrison-button').style.display="";
            document.getElementById('myModal').style.display = "block";
        }
        function openRecruit() {
            document.getElementById('unit-info').style.display="none";
            document.getElementById('recruit').style.display="";
            document.getElementById('available-villagers').style.display="";
            document.getElementById('recruit-button').style.display="";
            document.getElementById('myModal').style.display = "block";
        }
        function showFlock() {
            if(mapActive) {
                mapActive = false;
                topCorner = [player[1] - Math.floor(localWidth/2), player[2] - Math.floor(localHeight/2)];
                drawAllTiles();
                highlight(player[1], player[2], false, true);
            }
            document.getElementById('unit-info').style.display = "";
            document.getElementById('myModal').style.display = "block";
        }
        close.onclick = function() {
            closeModal();
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('myModal')) {
                closeModal();
            }
        }
        function closeModal() {
            document.getElementById('target-region').style.display = "none";
            document.getElementById('unit-info').style.display = "none";
            document.getElementById('battle-summary').style.display = "none";
            document.getElementById('move-garrison').style.display = "none";
            document.getElementById('recruit-garrison').style.display="none";
            document.getElementById('recruit').style.display = "none";
            document.getElementById('available-villagers').style.display="none";
            document.getElementById('my-regions').style.display="none";
            document.getElementById('myModal').style.display = "none";
        }
        </script>
        <div style="display:none;">
            <img id="flag" src="images/flag.png">
        </div>
    </body>

    </html>
<?php } elseif(!empty($_SESSION['LoggedIn']) && !empty($_SESSION['Username']) && !empty($_POST['clanid'])) {
    $_SESSION['ClanId'] = htmlspecialchars($_POST['clanid']);
    $sqlList = ["UPDATE players SET fhposition=(
                        SELECT ceil(regionid/".number_format($GLOBALS[maxWidth],2).") FROM clancapitol
                        WHERE clancapitol.clanid=".$_SESSION['ClanId']."),
                    fvposition=(
                        SELECT
                        CASE WHEN (regionid%(".$GLOBALS[maxWidth].")::integer)::boolean
                        THEN regionid%".$GLOBALS[maxWidth]."
                        ELSE ".$GLOBALS[maxWidth]."
                        END
                        FROM clancapitol
                        WHERE clancapitol.clanid=".$_SESSION['ClanId']."),
                    mhposition=(
                        SELECT ceil(regionid/".number_format($GLOBALS[maxWidth],2).") FROM clancapitol
                        WHERE clancapitol.clanid=".$_SESSION['ClanId']."),
                    mvposition=(
                        SELECT
                        CASE WHEN (regionid%(".$GLOBALS[maxWidth].")::integer)::boolean
                        THEN regionid%".$GLOBALS[maxWidth]."
                        ELSE ".$GLOBALS[maxWidth]."
                        END
                        FROM clancapitol
                        WHERE clancapitol.clanid=".$_SESSION['ClanId']."),
                    clanid=".$_SESSION['ClanId']."
                    WHERE id=".$_SESSION['PlayerId'].";",
                "UPDATE clans SET playercount = playercount + 1 WHERE id=".$_SESSION['ClanId'].";"];
    foreach ($sqlList as $sql) {
        $pdo->exec($sql);
    }
    echo "<h1>You've joined a clan!</h1>";
    echo "<p>Wait while the game loads.</p>";
    header('Location: index.php');
} elseif(!empty($_SESSION['LoggedIn']) && !empty($_SESSION['Username'])) {
    $query = "SELECT * FROM clans ORDER BY playercount limit 3";
    $sql = $pdo->prepare($query);
    $sql->execute();
    $data = [];
    $i = 0;
    while($result = $sql->fetch(PDO::FETCH_ASSOC)){
        $data[$i][0] = $result['id'];
        $data[$i][1] = $result['name'];
        $i++;
    }
    echo '<style>
    form {
        width: 100vw;
        margin: 20px;
        margin-left: auto;
        margin-right: auto;
    }
    h1 {
        text-align: center;
    }
    form div {
        margin-top: 10px;
        margin-bottom: 10px;
    }
    input {
        width: 100vw;
        height: 3em;
    }</style>
    <h1>Join a Clan</h1>
    <form method="post" action="index.php" name="clanselect" id="clanselect">
            <input type="hidden" name="clanid" id="clanid1" value="'.$data[0][0].'" />
            <input type="submit" value="'.ucfirst($data[0][1]).'"/>
        </form>
        <form method="post" action="index.php" name="clanselect" id="clanselect">
            <input type="hidden" name="clanid" id="clanid2" value="'.$data[1][0].'" />
            <input type="submit" value="'.ucfirst($data[1][1]).'"/>
        </form>
        <form method="post" action="index.php" name="clanselect" id="clanselect">
            <input type="hidden" name="clanid" id="clanid3" value="'.$data[2][0].'" />
            <input type="submit" value="'.ucfirst($data[2][1]).'"/>
        </form>';
} elseif(!empty($_POST['username']) && !empty($_POST['password'])) {
    $password = htmlspecialchars($_POST['password']);
    $username = htmlspecialchars($_POST['username']);

    $query = "SELECT * FROM players WHERE username = '".$username."'";
    $finduser = $pdo->prepare($query);
    $finduser->execute();

    if($finduser->rowCount() == 1) {
        $row = $finduser->fetch(PDO::FETCH_ASSOC);
        if(password_verify($password, $row['password'])) {
            $email = $row['emailaddress'];

            $_SESSION['Username'] = $username;
            $_SESSION['EmailAddress'] = $email;
            $_SESSION['PlayerId'] = $row['id'];
            $_SESSION['ClanId'] = $row['clanid'];
            $_SESSION['LoggedIn'] = 1;

            echo "<h1>Success</h1>";
            echo "<p>Wait while the game loads.</p>";
            header('Location: index.php');
        } else {
            echo "<h1>Error</h1>";
            echo "<p>Sorry, your account could not be found. Please <a href=\"index.php\">click here to try again</a>.</p>";
        }
    } else {
        echo "<h1>Error</h1>";
        echo "<p>Sorry, your account could not be found. Please <a href=\"index.php\">click here to try again</a>.</p>";
    }
}
else { ?>
    <h1>Member Login</h1>
    <form method="post" action="index.php" name="loginform" id="loginform">
        <div><input type="text" name="username" id="username" placeholder="Username"/></div>
        <div><input type="password" name="password" id="password" placeholder="Password"/></div>
        <input type="submit" name="login" id="login" value="Login" />
        <a href="register.php"><div class="button">Register</div></a>
    </form>
<?php } ?>
<nav>
    <a href="explanation.html">What is this?</a>
    <a href="credits.html">credits</a>
    <a href="index.php?logout=true">logout</a>
    <a href="https://ko-fi.com/benpearson">Support Independent Art</a>
</nav>
</html>
