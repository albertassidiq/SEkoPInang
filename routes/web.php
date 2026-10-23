<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KedaiKopiController;

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

// Route untuk form kedai kopi MANDIRI (public access)
Route::get('/', [KedaiKopiController::class, 'create']);
Route::get('/form', [KedaiKopiController::class, 'create'])->name('form');
Route::post('/kedai-kopi', [KedaiKopiController::class, 'store'])->name('kedai-kopi.store');

// Route untuk form kedai kopi MITRA (public access) - TAMBAHAN BARU
Route::get('/mitra', [KedaiKopiController::class, 'createMitra'])->name('mitra');
Route::post('/kedai-kopi/mitra', [KedaiKopiController::class, 'storeMitra'])->name('kedai-kopi.store-mitra');

// Route untuk monitor public (tanpa authentication)
Route::get('/monitor', [KedaiKopiController::class, 'monitor'])->name('monitor');

// Route untuk dashboard (dengan authentication)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // TAMBAHAN BARU - Route untuk halaman peta (authenticated)
    Route::get('/peta', [KedaiKopiController::class, 'peta'])->name('peta');

    // Resource routes untuk CRUD kedai kopi (authenticated)
    Route::resource('kedai-kopi', KedaiKopiController::class)->except(['create', 'store']);

    // Additional routes for soft delete management
    Route::post('/kedai-kopi/{id}/restore', [KedaiKopiController::class, 'restore'])->name('kedai-kopi.restore');
    Route::delete('/kedai-kopi/{id}/force-delete', [KedaiKopiController::class, 'forceDelete'])->name('kedai-kopi.force-delete');

    // Route untuk statistik (untuk dashboard atau API)
    Route::get('/kedai-kopi-stats', [KedaiKopiController::class, 'getStatistics'])->name('kedai-kopi.stats');
});
