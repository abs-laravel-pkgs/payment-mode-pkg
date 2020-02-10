<?php
Route::group(['namespace' => 'Abs\PaymentModePkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'payment-mode-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});