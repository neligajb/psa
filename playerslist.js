$(document).ready(function () {
    var $playerTable = $('#playerTable');

    getPlayers();

    function getPlayers() {
        $.post('playerdata.php', function (data) {
            if (data == 'bad data output') {
                alert('bad database request');
            }
            else {
                $players_obj = jQuery.parseJSON(data);
                addPlayers($players_obj);
            }
        });
    }


    function addPlayers($players_obj) {
        if ($players_obj[0] != NULL) {
            for (var player in $players_obj) {
                $playerTable.append("<tr><td>" + $player[prop]['name'] + "</td>" +
                "<td>" + $player[prop]['age'] + "</td>" + "<td>" + $player[prop]['rank'] + "</td>" +
                "<td>" + $player[prop]['country'] + "</td><td>");
                for (var sponsor in $player.sponsors) {
                    if (sponsor != NULL) {
                        $playerTable.append($player.sponsors[prop]['name'] + "<br>");
                    }
                }
                $playerTable.append("</td></tr>");
            }
        }
    }
});
