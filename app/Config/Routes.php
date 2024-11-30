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

// laporan bug
$routes->get('getAllLaporan', 'LaporanBugController::index');
$routes->post('createLaporan', 'LaporanBugController::createLaporan');
$routes->put('updateLaporan/(:hash)', 'LaporanBugController::updateLaporan/$1');
$routes->delete('deleteLaporan/(:hash)', 'LaporanBugController::deleteLaporan/$1');
