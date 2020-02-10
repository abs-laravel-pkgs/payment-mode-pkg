<?php

Route::group(['namespace' => 'Abs\PaymentModePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'payment-mode-pkg'], function () {
	//FAQs
	Route::get('/payment-modes/get-list', 'PaymentModeController@getPaymentModeList')->name('getPaymentModeList');
	Route::get('/payment-mode/get-form-data', 'PaymentModeController@getPaymentModeFormData')->name('getPaymentModeFormData');
	Route::post('/payment-mode/save', 'PaymentModeController@savePaymentMode')->name('savePaymentMode');
	Route::get('/payment-mode/delete/{id}', 'PaymentModeController@deletePaymentMode')->name('deletePaymentMode');
});

Route::group(['namespace' => 'Abs\PaymentModePkg', 'middleware' => ['web'], 'prefix' => 'payment-mode-pkg'], function () {
	//FAQs
	Route::get('/payment-modes/get', 'PaymentModeController@getPaymentModes')->name('getPaymentModes');
});
