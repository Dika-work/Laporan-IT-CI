<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');


$routes->get('users', 'UserController::index');
$routes->post('register-user', 'UserController::create');
$routes->put('users/(:hash)', 'UserController::update/$1');
$routes->delete('users/(:hash)', 'UserController::delete/$1');
$routes->post('login', 'UserController::login');
$routes->get('getDeHashUsername', 'UserController::getUsernameByHash');
$routes->setAutoRoute(true); // Pastikan auto-routing diaktifkan
$routes->get('uploads/(:any)', function ($path) {
    return view('path/to/uploads/' . $path);
});


// laporan bug
$routes->get('getAllLaporan', 'LaporanBugController::index');
$routes->get('get-laporan-admin', 'LaporanBugController::getAllLaporan');
$routes->post('createLaporan', 'LaporanBugController::createLaporan');
$routes->put('updateLaporan', 'LaporanBugController::updateLaporan');
$routes->delete('deleteLaporan', 'LaporanBugController::deleteLaporan');
$routes->put('status-kerja', 'LaporanBugController::changeStatusKerja');

// Aplikasi category
$routes->get('getCategoryApk', 'ApkCategoryController::index');

// Laporan pekerjaan
$routes->get('getLaporanPekerjaan', 'LaporanPekerjaanController::index');
$routes->post('laporan-pekerjaan', 'LaporanPekerjaanController::createLaporanPekerjaan');
$routes->put('update-pekerjaan', 'LaporanPekerjaanController::updatePekerjaan');
$routes->delete('delete-pekerjaan', 'LaporanPekerjaanController::deletePekerjaan');
