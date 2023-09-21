<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/




/*Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');*/

Route::middleware('auth')->group(function () {

    Route::get('/', [\App\Http\Controllers\MainController::class, 'index'])->name('main.index');

    Route::get('/members', [\App\Http\Controllers\MemberController::class, 'index'])->name('members.index');
    Route::get('/members/create', [\App\Http\Controllers\MemberController::class, 'create'])->name('members.create');
    Route::post('/members', [\App\Http\Controllers\MemberController::class, 'store'])->name('members.store');
    Route::get('/members/{id}/edit', [\App\Http\Controllers\MemberController::class, 'edit'])->name('members.edit');
    Route::put('/members/{id}', [\App\Http\Controllers\MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{id}', [\App\Http\Controllers\MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [\App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{id}/edit', [\App\Http\Controllers\PaymentController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{id}/', [\App\Http\Controllers\PaymentController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{id}', [\App\Http\Controllers\PaymentController::class, 'destroy'])->name('payments.destroy');

    Route::resource('/schedules', \App\Http\Controllers\PaymentScheduleController::class);


   /* Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');*/
});

require __DIR__.'/auth.php';
