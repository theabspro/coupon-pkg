app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //Coupon
    when('/coupon-pkg/coupons', {
        template: '<coupons></coupons>',
        title: 'Coupons',
    }).
    when('/coupon-pkg/coupon/view/:id', {
        template: '<coupon-view></coupon-view>',
        title: 'Coupon',
    }).
    when('/coupon-pkg/claimed-coupons', {
        template: '<claimed-coupons></claimed-coupons>',
        title: 'Claimed Coupons',
    }).


}]);