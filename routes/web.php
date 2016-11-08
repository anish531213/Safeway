<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/map-app', function() {
	return view('app');
});

Route::get('/walk', function() {

	return view('walk');
});

Route::get('/decode/{encoded}', 'mapsAPI@decodePolylineToArray');

Route::get('directions/{startln}/{endln}/{mode}', 'mapsAPI@getDirections');

Route::get('main', function() {
	return view('index');								
});

Route::get('crimescore/{lat}/{lng}', 'mapsAPI@crimeAPI');
