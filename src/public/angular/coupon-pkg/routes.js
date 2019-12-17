app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //Coupon
    when('/coupon-pkg/coupons', {
        template: '<coupon-codes></coupon-codes>',
        title: 'Coupon Codes',
    }).
    when('/coupon-pkg/coupon/view/:date', {
        template: '<coupon-codes-view></coupon-codes-view>',
        title: 'Coupon Code View',
    }).
    when('/coupon-pkg/coupon/view/:date/:id', {
        template: '<coupon-view></coupon-view>',
        title: 'Coupon Code View',
    }).
    when('/coupon-pkg/claimed-coupons', {
        template: '<claimed-coupons></claimed-coupons>',
        title: 'Claimed Coupons',
    });


}]);