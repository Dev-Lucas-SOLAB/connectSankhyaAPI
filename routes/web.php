<?php

use App\Http\Controllers\Auth\AuthorizationController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\RecoardController;
use Illuminate\Support\Facades\Route;



Route::post('/auth/user', [AuthorizationController::class, 'AuthorizationUser']);
Route::post('/logout', [LogoutController::class, 'logout']);


Route::post('/recoard/save', [RecoardController::class, 'saveRecoardFunction']);
Route::get('/recoard/load', [RecoardController::class, 'loadRecoardFunction']);





