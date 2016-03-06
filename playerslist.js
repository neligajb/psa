var app = angular.module('myApp', []);

app.controller('GetPsaData', function($scope, $http) {
    $http.get("playerdata.php").success(function(data) {
       $scope.psaData = data;
    });

    $scope.filter = function() {
        var $query = 'playerdata.php?';
        $query += 'country=' + $scope.selectedCountry + '&sponsor=' + $scope.selectedSponsor + '&tournament=' 
            + $scope.selectedTournament;
        $http.get($query).success(function(data) {
            $scope.psaData = data;
        });
    };

    $scope.addPlayer = function () {
        $http({
            method: 'POST',
            url: 'playerdata.php',
            data: {
                'action' : 'addPlayer',
                'fname': $scope.new_fname,
                'lname': $scope.new_lname,
                'rank' : $scope.new_rank,
                'dob': $scope.new_dob,
                'country': $scope.new_country,
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function(data) {
            alert(data.toString());
        });
    };

    $scope.addTournament = function () {
        $http({
            method: 'POST',
            url: 'playerdata.php',
            data: {
                'action' : 'addTournament',
                'name': $scope.new_tournament_name,
                'winner': $scope.new_winner.id,
                'runnerup' : $scope.new_runnerup.id,
                'country': $scope.new_tournament_country,
                'city' : $scope.new_tournament_city,
                'prize_money' : $scope.new_tournament_prize_money,
                'year' : $scope.new_tournament_year
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function(data) {
            alert(data.toString());
        });
    };

    $scope.addSponsorship = function () {
        $http({
            method: 'POST',
            url: 'playerdata.php',
            data: {
                'action' : 'addSponsorship',
                'player': $scope.new_ps_player.id,
                'sponsor': $scope.new_ps_sponsor
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function(data) {
            alert(data.toString());
        });
    };

    $scope.addSponsor = function () {
        $http({
            method: 'POST',
            url: 'playerdata.php',
            data: {
                'action' : 'addSponsor',
                'name': $scope.new_sponsor,
                'country': $scope.new_sponsor_country
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function(data) {
            alert(data.toString());
        });
    };

    $scope.addCountry = function () {
        $http({
            method: 'POST',
            url: 'playerdata.php',
            data: {
                'action' : 'addCountry',
                'name': $scope.new_country,
                'population': $scope.new_population
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function(data) {
            alert(data.toString());
        });
    };
});
    