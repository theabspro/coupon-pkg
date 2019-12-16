<?php

Route::group(['namespace' => 'Abs\CouponPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'coupon-pkg'], function () {
	Route::get('/coupons/get-list', 'CouponController@getCouponList')->name('getCouponList');
	Route::get('/coupon/view/{id}', 'CouponController@couponCodeView')->name('couponCodeView');
	Route::post('/coupon/import', 'CouponController@couponCodeImport')->name('couponCodeImport');

});