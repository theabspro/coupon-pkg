@if(config('coupon-pkg.DEV'))
    <?php $coupon_pkg_path = 'packages/abs/coupon-pkg/src/'?>
@else
    <?php $coupon_pkg_path = ''?>
@endif

<script type="text/javascript">
app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
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
	//COUPON
	var coupon_codes_list_template_url = "{{URL::asset($coupon_pkg_path.'public/angular/coupon-pkg/pages/coupon/list.html')}}";
    var coupon_codes_list_view_template_url = "{{URL::asset($coupon_pkg_path.'public/angular/coupon-pkg/pages/coupon/view_list.html')}}";
    var coupon_codes_view_template_url = "{{URL::asset($coupon_pkg_path.'public/angular/coupon-pkg/pages/coupon/view.html')}}";
    var coupon_code_view_url = "{{url('coupon-pkg/coupon/view/')}}";
    var coupon_code_list_url = "{{url('coupon-pkg/coupons/get-code-list/')}}";
    var coupon_import_download_template_url = "{{URL::asset('/public/templates/coupon_codes.xlsx')}}";
</script>
<script src="{{asset($coupon_pkg_path.'public/angular/coupon-pkg/pages/coupon/controller.js') }}"></script>

	<!-- COUPON CLAIM -->
<script type="text/javascript">
    var claimed_coupon_list_template_url = "{{URL::asset($coupon_pkg_path.'public/angular/coupon-pkg/pages/claimed-coupon/list.html')}}";
    var claimed_coupon_view_template_url = "{{URL::asset($coupon_pkg_path.'public/angular/coupon-pkg/pages/claimed-coupon/view.html')}}";
</script>
<script type="text/javascript" src="{{URL::asset($coupon_pkg_path.'public/angular/coupon-pkg/pages/claimed-coupon/controller.js?v=2')}}"></script>

