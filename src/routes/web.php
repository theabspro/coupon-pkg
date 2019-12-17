<?php

Route::group(['namespace' => 'Abs\CouponPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'coupon-pkg'], function () {
	Route::get('/coupons/get-list', 'CouponController@getCouponList')->name('getCouponList');
	Route::get('/coupon/view/{date}', 'CouponController@couponCodeListView')->name('couponCodeListView');
	Route::get('/coupons/get-code-list/{date}', 'CouponController@getCouponCodeList')->name('getCouponCodeList');
	Route::get('/coupon/view/{date}/{id}', 'CouponController@couponCodeView')->name('couponCodeView');

});