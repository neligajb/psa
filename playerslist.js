var app = angular.module('myApp', []);

app.controller('GetPlayers', function($scope, $http) {
    $http.get('playerdata.php').success(function(data) {
       $scope.players = data;
    });
});
    