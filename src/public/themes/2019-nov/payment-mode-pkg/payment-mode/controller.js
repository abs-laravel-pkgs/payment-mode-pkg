app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    when('/payment-modes', {
        template: '<payment-modes></payment-modes>',
        title: 'PaymentModes',
    });
}]);

app.component('paymentModes', {
    templateUrl: payment_mode_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http({
            url: laravel_routes['getPaymentModes'],
            method: 'GET',
        }).then(function(response) {
            self.payment_modes = response.data.payment_modes;
            $rootScope.loading = false;
        });
        $rootScope.loading = false;
    }
});