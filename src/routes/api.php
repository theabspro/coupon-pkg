<?php
Route::group(['namespace' => 'Abs\CouponPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'coupon-pkg/api'], function () {
		Route::post('coupon/get', 'CouponController@getCoupon');
		Route::post('coupons/redeem', 'CouponController@redeemCoupon');

		Route::group(['middleware' => ['auth:api']], function () {
		});
	});
});