<?php

// use App\Http\Controllers\AuthController;
// use App\Http\Controllers\UserController;
// use App\Http\Controllers\UserReviewController;
/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Test route
$router->get('/test', 'UserController@test');

// Profile routes
$router->get('users/{user_id}', 'UserController@show');
$router->put('/users/{user_id}', 'UserController@update');
$router->post('/users/{user_id}/profile-picture', 'UserController@updateProfilePicture');
// $router->get('/users/{user_id}/reviews', 'UserReviewController@index');

// Auth routes
$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->post('get-otp', 'AuthController@getOtp');
    $router->post('logout','AuthController@logout');
    $router->post('reset-password', 'AuthController@resetPassword');
    $router->post('forgot-password', 'AuthController@forgotPassword');

    // Properties Management APIs
    $router->get('properties', 'PropertyController@getProperties');
    $router->post('properties', 'PropertyController@createProperties');
    $router->put('properties/{propertyId}', 'PropertyController@updateProperties');
    $router->get('properties/{propertyId}', 'PropertyController@getProperty');
    $router->delete('properties/{propertyId}', 'PropertyController@deleteProperty');
    $router->get('properties/{propertyId}/reviews', 'PropertyController@getPropertyReviews');
    $router->get('properties/{propertyId}/description', 'PropertyController@getPropertyDescription');


});

// Api version 1
$router->group(['prefix' => 'v1'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->post('get-otp', 'AuthController@getOtp');
    $router->post('logout','AuthController@logout');
    $router->post('reset-password', 'AuthController@resetPassword');
    $router->post('forgot-password', 'AuthController@forgotPassword');
    $router->get('properties', 'PropertyController@getProperties');
    $router->post('properties', 'PropertyController@createProperties');
    $router->put('properties/{propertyId}', 'PropertyController@updateProperties');
    $router->get('properties/{propertyId}', 'PropertyController@getProperty');
    $router->delete('properties/{propertyId}', 'PropertyController@deleteProperty');
    $router->get('properties/{propertyId}/reviews', 'PropertyController@getPropertyReviews');

    $router->get('properties/{propertyId}/description', 'PropertyController@getPropertyDescription');
    $router->get('properties/{propertyId}/price-details', 'PropertyController@getPropertyPriceDetails');
});

// Protected routes
$router->group(['middleware' => 'auth:sanctum'], function () use ($router) {
    $router->get('profile', 'UserController@profile');
});

