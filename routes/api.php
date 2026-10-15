
<?php


use Illuminate\Support\Facades\Route;
Route::get('/db-check', function () {
    return response()->json([
        'environment' => app()->environment(),
        'env_connection' => env('DB_CONNECTION'),
        'default_connection' => config('database.default'),
        'pgsql_database' => config('database.connections.pgsql.database'),
        'sqlite_database' => config('database.connections.sqlite.database'),
    ]);
});