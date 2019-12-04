<?php

Route::group(['namespace' => 'Abs\CouponPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'Coupon-pkg'], function () {
	Route::get('/Coupons/get-list', 'CouponController@getCouponList')->name('getCouponList');
	Route::get('/Coupon/get-form-data/{id?}', 'CouponController@getCouponFormData')->name('getCouponFormData');
	Route::post('/Coupon/save', 'CouponController@saveCoupon')->name('saveCoupon');
	Route::get('/Coupon/delete/{id}', 'CouponController@deleteCoupon')->name('deleteCoupon');

});