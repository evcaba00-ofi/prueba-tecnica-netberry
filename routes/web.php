<?php

use App\Http\Controllers\TareaController;
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

Route::get('/', [TareaController::class, 'index'])->name('tareas.index');
Route::get('/tareas/buscar', [TareaController::class, 'buscar'])->name('tareas.buscar');
Route::post('/tareas', [TareaController::class, 'store'])->name('tareas.store');
Route::delete('/tareas/{id}', [TareaController::class, 'destroy'])->name('tareas.destroy');
