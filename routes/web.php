<?php

use Illuminate\Support\Facades\Route;

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
    return "ops";
});

Route::group(['prefix' => 'goods'], function () {
    Route::post('combine_spec/{gid}', 'GoodsManagement\GoodsController@combineSkuSpec');
    Route::post('switch/{gid}', 'GoodsManagement\GoodsController@switch');
});
Route::resource('/goods', 'GoodsManagement\GoodsController', ['only' => [
    'index', 'show', 'store', 'edit', 'update', 'destroy'
]]);

Route::group(['prefix' => 'category'], function () {
    Route::get('spec_temp/{id}', 'GoodsManagement\CategoryController@categorySpecTemp');
    Route::post('link_spec_temp', 'GoodsManagement\CategoryController@linkCategorySpecTemp');
});
Route::resource('/category', 'GoodsManagement\CategoryController', ['only' => [
    'index', 'show', 'store', 'edit', 'update', 'destroy'
]]);

Route::group(['prefix' => 'spec_temp'], function () {
});
Route::resource('/spec_temp', 'GoodsManagement\SpecTempController', ['only' => [
    'index', 'show', 'store', 'edit', 'update', 'destroy'
]]);
