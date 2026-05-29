<?php

use Webman\Route;
use Yllumi\Sayagi\app\controller\AuthController;
use Yllumi\Sayagi\app\controller\IndexController;
use Yllumi\Sayagi\app\controller\DashboardController;
use Yllumi\Sayagi\app\controller\UserController;
use Yllumi\Sayagi\app\controller\RoleController;
use Yllumi\Sayagi\app\controller\PrivilegeController;
use Yllumi\Sayagi\app\controller\SettingController;
use Yllumi\Sayagi\app\controller\RedisController;
use Yllumi\Sayagi\app\controller\PanelmenuController;
use Yllumi\Sayagi\app\controller\EntryController;

Route::get('/panel/auth/login',     [AuthController::class, 'login']);
Route::post('/panel/auth/login',    [AuthController::class, 'doLogin']);
Route::post('/panel/auth/register', [AuthController::class, 'doRegister']);
Route::get('/panel/auth/logout',    [AuthController::class, 'logout']);
Route::get('/panel/auth/forgot',    [AuthController::class, 'forgot']);
Route::post('/panel/auth/forgot',   [AuthController::class, 'doForgot']);
Route::get('/panel/auth/reset',     [AuthController::class, 'reset']);
Route::post('/panel/auth/reset',    [AuthController::class, 'doReset']);

// Dashboard
Route::get('/panel', [IndexController::class, 'index']);
Route::get('/panel/dashboard', [DashboardController::class, 'index']);

// User CRUD
Route::get('/panel/user',  [UserController::class, 'index']);
Route::get('/panel/user/index',  [UserController::class, 'index']);
Route::get('/panel/user/data',   [UserController::class, 'data']);
Route::get('/panel/user/create', [UserController::class, 'create']);
Route::post('/panel/user/store', [UserController::class, 'store']);
Route::get('/panel/user/edit',   [UserController::class, 'edit']);
Route::post('/panel/user/update',[UserController::class, 'update']);
Route::post('/panel/user/delete',[UserController::class, 'delete']);

// Role CRUD
Route::get('/panel/role',  [RoleController::class, 'index']);
Route::get('/panel/role/index',  [RoleController::class, 'index']);
Route::get('/panel/role/data',   [RoleController::class, 'data']);
Route::get('/panel/role/create', [RoleController::class, 'create']);
Route::post('/panel/role/store', [RoleController::class, 'store']);
Route::get('/panel/role/edit',   [RoleController::class, 'edit']);
Route::post('/panel/role/update',[RoleController::class, 'update']);
Route::post('/panel/role/delete',[RoleController::class, 'delete']);

// Privilege CRUD
Route::get('/panel/privilege',   [PrivilegeController::class, 'index']);
Route::get('/panel/privilege/index',   [PrivilegeController::class, 'index']);
Route::get('/panel/privilege/data',    [PrivilegeController::class, 'data']);
Route::get('/panel/privilege/features', [PrivilegeController::class, 'features']);
Route::get('/panel/privilege/create',  [PrivilegeController::class, 'create']);
Route::post('/panel/privilege/store',  [PrivilegeController::class, 'store']);
Route::get('/panel/privilege/edit',    [PrivilegeController::class, 'edit']);
Route::post('/panel/privilege/update', [PrivilegeController::class, 'update']);
Route::post('/panel/privilege/delete', [PrivilegeController::class, 'delete']);

// Setting
Route::get('/panel/setting',  [SettingController::class, 'index']);
Route::get('/panel/setting/index',  [SettingController::class, 'index']);
Route::get('/panel/setting/data',   [SettingController::class, 'data']);
Route::post('/panel/setting/save',  [SettingController::class, 'save']);

// Panel Menu CRUD
Route::get( '/panel/panelmenu',         [PanelmenuController::class, 'index']);
Route::get( '/panel/panelmenu/index',   [PanelmenuController::class, 'index']);
Route::post('/panel/panelmenu/store',   [PanelmenuController::class, 'store']);
Route::get( '/panel/panelmenu/edit',    [PanelmenuController::class, 'edit']);
Route::post('/panel/panelmenu/update',  [PanelmenuController::class, 'update']);
Route::post('/panel/panelmenu/delete',  [PanelmenuController::class, 'delete']);
Route::post('/panel/panelmenu/reorder', [PanelmenuController::class, 'reorder']);

// Redis Management
Route::get('/panel/redis',   [RedisController::class, 'index']);
Route::get('/panel/redis/index',   [RedisController::class, 'index']);
Route::get('/panel/redis/keys',    [RedisController::class, 'keys']);
Route::get('/panel/redis/get',     [RedisController::class, 'getKey']);
Route::post('/panel/redis/set',    [RedisController::class, 'setKey']);
Route::post('/panel/redis/delete', [RedisController::class, 'deleteKey']);
Route::post('/panel/redis/rename', [RedisController::class, 'renameKey']);
Route::post('/panel/redis/flush',  [RedisController::class, 'flush']);

// Dynamic Entry CRUD (driven by YAML schema)
Route::get( '/panel/entry/{slug}',          [EntryController::class, 'index']);
Route::get( '/panel/entry/{slug}/data',     [EntryController::class, 'data']);
Route::get( '/panel/entry/{slug}/create',   [EntryController::class, 'create']);
Route::post('/panel/entry/{slug}/store',    [EntryController::class, 'store']);
Route::get( '/panel/entry/{slug}/edit',     [EntryController::class, 'edit']);
Route::post('/panel/entry/{slug}/update',   [EntryController::class, 'update']);
Route::post('/panel/entry/{slug}/delete',   [EntryController::class, 'delete']);