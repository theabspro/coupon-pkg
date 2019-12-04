app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //Coupon
    when('/Coupon-pkg/Coupon/list', {
        template: '<Coupon-list></Coupon-list>',
        title: 'Coupons',
    }).
    when('/Coupon-pkg/Coupon/add', {
        template: '<Coupon-form></Coupon-form>',
        title: 'Add Coupon',
    }).
    when('/Coupon-pkg/Coupon/edit/:id', {
        template: '<Coupon-form></Coupon-form>',
        title: 'Edit Coupon',
    });
}]);