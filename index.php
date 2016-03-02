<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PSA Player Database</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
  <script src="playerslist.js"></script>
</head>
<body class="hg">
<header><h1>PSA Players Database</h1></header>
<div ng-app="myApp" class="hg-body">
  <main class="hg-content" ng-controller="GetPlayers">
    Main Content
    <table id="playerTable">
      <tr>
        <th>Name</th><th>Rank</th><th>Age</th><th>Country</th><th>Sponsors</th><th><!--ID--></th>
      </tr>
      <tr ng-repeat="player in players">
        <td>{{player.name}}</td><td>{{player.rank}}</td><td>{{player.age}}</td>
        <td>{{player.country}}</td>
<!--        <td><span class="sponsors" ng-repeat="sponsor in player.sponsors">{{sponsor}}</span></td>-->
      </tr>
    </table>
  </main>
  <aside class="hg-filters">Filters</aside>
</div>
<footer>Footer</footer>
</body>
</html>