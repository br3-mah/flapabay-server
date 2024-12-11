<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

// Enable Facades (for helper functions like config(), view(), etc.)
$app->withFacades();

// Enable Eloquent ORM for database interaction
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Load all configuration files from the config directory. This is similar
| to how it works in Laravel. Make sure you create the "config" folder
| at the root of your project.
|
*/

$app->configure('app');         // App Configuration (name, env, etc.)
$app->configure('database');    // Database Configuration
$app->configure('corcel');      // Corcel Configuration (for WordPress integration)
$app->configure('cache');       // Cache Configuration (optional)
// $app->configure('mail');        // Mail Configuration (optional)
// $app->configure('services');    // Services Configuration (optional)

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Here you can register both global and route-specific middleware.
| Global middleware runs on every request, and route middleware runs
| on specific routes as needed.
|
*/

// Global Middleware (runs on every request)
$app->middleware([
    // Example of global middleware (uncomment as needed)
    // App\Http\Middleware\ExampleMiddleware::class,
]);

// Route Middleware (applied to specific routes only)
$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// Default Service Providers
$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);

// Custom Service Providers
$app->register(Irazasyed\Larasupport\Providers\ArtisanServiceProvider::class);

// Example of registering a custom service provider
// $app->register(App\Providers\CustomServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Here we load the routes defined in routes/web.php. You can group
| routes logically in the routes folder as per the project's needs.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This returns the Lumen application so it can be run.
|
*/

return $app;
