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
                'fname': $scope.new_fname,
                'lname': $scope.new_lname,
                'dob': $scope.new_dob,
                'country': $scope.new_country,
                'sponsor': $scope.new_sponsor
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function () {
            alert('Player Added');
        });
    };
});
    