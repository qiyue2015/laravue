<?php

use Illuminate\Http\Request;

//use \App\Laravue\Faker;
//use \App\Laravue\JsonResponse;
//use \App\Mongodb;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api'], function () {

    Route::post('auth/login', 'AuthController@login');

    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('auth/user', 'AuthController@user');
        Route::post('auth/logout', 'AuthController@logout');
    });

    Route::apiResource('users', 'UserController')->middleware('permission:' . \App\Laravue\Acl::PERMISSION_USER_MANAGE);
    Route::get('users/{user}/permissions', 'UserController@permissions')->middleware('permission:' . \App\Laravue\Acl::PERMISSION_PERMISSION_MANAGE);
    Route::put('users/{user}/permissions', 'UserController@updatePermissions')->middleware('permission:' . \App\Laravue\Acl::PERMISSION_PERMISSION_MANAGE);
    Route::apiResource('roles', 'RoleController')->middleware('permission:' . \App\Laravue\Acl::PERMISSION_PERMISSION_MANAGE);
    Route::get('roles/{role}/permissions', 'RoleController@permissions')->middleware('permission:' . \App\Laravue\Acl::PERMISSION_PERMISSION_MANAGE);
    Route::apiResource('permissions', 'PermissionController')->middleware('permission:' . \App\Laravue\Acl::PERMISSION_PERMISSION_MANAGE);

    Route::get('categories', 'CategoriesController@index')->name('categories.index');
    Route::get('categories/{category_id}', 'CategoriesController@list')->name('categories.list');

    Route::prefix('novel')->group(function () {
        Route::get('/info/{novel_id}', 'NovelsController@index')->name('novels.index');
        Route::get('/index/{novel_id}', 'NovelsController@all')->name('novels.all');
    });

    Route::resource('novels', 'NovelsController')->only([
        'store', 'update'
    ]);

    Route::get('search', 'SearchsController@index')->name('searchs.index');
});
