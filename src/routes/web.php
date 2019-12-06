<?php

Route::group(['namespace' => 'Abs\CouponPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'coupon-pkg'], function () {
	Route::get('/coupons/get-list', 'CouponController@getCouponList')->name('getCouponList');
	Route::get('/coupon/get-form-data/{id?}', 'CouponController@getCouponFormData')->name('getCouponFormData');
	Route::post('/coupon/save', 'CouponController@saveCoupon')->name('saveCoupon');
	Route::get('/coupon/delete/{id}', 'CouponController@deleteCoupon')->name('deleteCoupon');

});