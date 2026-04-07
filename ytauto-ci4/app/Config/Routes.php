<?php
// app/Config/Routes.php
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── Auth ─────────────────────────────────────────────────────
$routes->get('/',               'Auth::login');
$routes->get('login',           'Auth::login');
$routes->post('login',          'Auth::doLogin');
$routes->get('logout',          'Auth::logout');
$routes->get('register',        'Auth::register');
$routes->post('register',       'Auth::doRegister');

// ── Client (requer login + plano ativo) ──────────────────────
$routes->group('app', ['filter' => 'client'], function($routes) {
    $routes->get('/',               'Client\Dashboard::index');
    $routes->get('dashboard',       'Client\Dashboard::index');
    $routes->get('create',          'Client\VideoCreator::index');
    $routes->post('generate',       'Client\VideoCreator::generate');
    $routes->post('narrate',        'Client\VideoCreator::narrate');
    $routes->post('save-video',     'Client\VideoCreator::save');
    $routes->get('history',         'Client\VideoCreator::history');
    $routes->get('video/(:num)',    'Client\VideoCreator::show/$1');
    $routes->delete('video/(:num)','Client\VideoCreator::delete/$1');
    $routes->get('profile',         'Client\Profile::index');
    $routes->post('profile',        'Client\Profile::update');
    $routes->get('subscription',    'Client\Profile::subscription');
    $routes->get('voices',          'Client\VideoCreator::voices');
});

// ── Admin (requer login + role admin) ────────────────────────
$routes->group('admin', ['filter' => 'admin'], function($routes) {
    // Dashboard
    $routes->get('/',               'Admin\Dashboard::index');
    $routes->get('dashboard',       'Admin\Dashboard::index');

    // Usuários / Clientes
    $routes->get('users',           'Admin\Users::index');
    $routes->get('users/create',    'Admin\Users::create');
    $routes->post('users/create',   'Admin\Users::store');
    $routes->get('users/(:num)',    'Admin\Users::show/$1');
    $routes->get('users/(:num)/edit','Admin\Users::edit/$1');
    $routes->post('users/(:num)',   'Admin\Users::update/$1');
    $routes->delete('users/(:num)','Admin\Users::delete/$1');
    $routes->post('users/(:num)/toggle','Admin\Users::toggle/$1');

    // Planos
    $routes->get('plans',           'Admin\Plans::index');
    $routes->get('plans/create',    'Admin\Plans::create');
    $routes->post('plans/create',   'Admin\Plans::store');
    $routes->get('plans/(:num)/edit','Admin\Plans::edit/$1');
    $routes->post('plans/(:num)',   'Admin\Plans::update/$1');
    $routes->delete('plans/(:num)','Admin\Plans::delete/$1');

    // Permissões
    $routes->get('permissions',     'Admin\Plans::permissions');
    $routes->post('permissions/save','Admin\Plans::savePermissions');

    // Financeiro
    $routes->get('financial',       'Admin\Financial::index');
    $routes->get('financial/payments','Admin\Financial::payments');
    $routes->get('financial/subscriptions','Admin\Financial::subscriptions');
    $routes->post('financial/payment/(:num)/mark-paid','Admin\Financial::markPaid/$1');
    $routes->post('financial/payment/(:num)/mark-failed','Admin\Financial::markFailed/$1');
    $routes->post('financial/payment/create','Admin\Financial::createPayment');
    $routes->get('financial/overdue','Admin\Financial::overdue');
    $routes->get('financial/report', 'Admin\Financial::report');

    // Vozes
    $routes->get('voices',          'Admin\Voices::index');
    $routes->post('voices/sync',    'Admin\Voices::sync');
    $routes->post('voices/(:num)/toggle','Admin\Voices::toggle/$1');

    // Configurações
    $routes->get('settings',        'Admin\Settings::index');
    $routes->post('settings',       'Admin\Settings::save');

    // Vídeos (visualização admin)
    $routes->get('videos',          'Admin\Videos::index');
    $routes->get('videos/(:num)',   'Admin\Videos::show/$1');
});

// ── API (JSON) ────────────────────────────────────────────────
$routes->group('api', ['filter' => 'client'], function($routes) {
    $routes->post('narrate',        'Api\NarrateController::generate');
    $routes->get('voices',          'Api\NarrateController::voices');
});
