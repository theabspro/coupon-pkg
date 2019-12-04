<?php
Route::group(['namespace' => 'Abs\CouponPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'coupon-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			Route::post('coupon/scan-for-redemption', 'CouponController@scanForRedemption');
		});
	});
});