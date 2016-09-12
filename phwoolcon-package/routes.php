<?php
/* @var Phwoolcon\Router $this */

$this->prefix('/api', [
    'GET' => [
        '/:params' => 'Phwoolcon\Demo\Payment\Controllers\Api\AlipayController::missingMethod',
        '/' => function () {
            return Phalcon\Di::getDefault()
                ->getShared('response')
                ->setJsonContent(['content' => 'Phwoolcon Bootstrap'])
                ->setHeader('Content-Type', 'application/json');
        },
    ],
    'POST' => [
        '/alipay/pay-request' => 'Phwoolcon\Demo\Payment\Controllers\Api\AlipayController::postRequest',
    ],
], MultiFilter::instance()
    ->add(DisableSessionFilter::instance())
    ->add(DisableCsrfFilter::instance())
)->prefix('/admin', [
    'GET' => [
        '/:params' => 'Phwoolcon\Demo\Admin\Controllers\AccountController::missingMethod',
        '/' => 'Phwoolcon\Demo\Admin\Controllers\AccountController::getIndex',
        '/login' => 'Phwoolcon\Demo\Admin\Controllers\AccountController::getLogin',
    ],
    'POST' => [
        '/login' => 'Phwoolcon\Demo\Admin\Controllers\AccountController::postLogin',
    ],
])->prefix('/account', [
    'GET' => [
        '/' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::getIndex',
        '/login' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::getLogin',
        '/register' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::getRegister',
        '/logout' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::getLogout',
        '/confirm' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::getConfirm',
        '/activate' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::getActivate',
        '/forgot-password' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::getForgotPassword',
    ],
    'POST' => [
        '/login' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::postLogin',
        '/register' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::postRegister',
        '/forgot-password' => 'Phwoolcon\Demo\Auth\Controllers\AccountController::postForgotPassword',
    ],
])->prefix('/sso', [
    'GET' => [
        '/check' => 'Phwoolcon\Demo\Auth\Controllers\SsoController::getCheckIframe',
        '/redirect' => 'Phwoolcon\Demo\Auth\Controllers\SsoController::getRedirect',
    ],
    'POST' => [
        '/server-check' => [
            'Phwoolcon\Demo\Auth\Controllers\SsoController::postServerCheck',
            'filter' => DisableCsrfFilter::instance(),
        ],
    ],
])->prefix('/pay', [
    'GET' => [
        '/form' => 'Phwoolcon\Demo\Payment\Controllers\OrderController::getForm',
        '/demo-request-form' => 'Phwoolcon\Demo\Payment\Controllers\OrderController::getDemoRequestForm',
    ],
    'POST' => [
        '/order/place' => 'Phwoolcon\Demo\Payment\Controllers\OrderController::postPlace',
    ],
])->prefix('/catalog', [
    'GET' => [
        '/' => function () {
            return View::make('catalog', 'index', ['page_title' => __('Catalog') . ' - Phwoolcon']);
        },
    ],
]);

$this->addRoutes([
    'GET' => [
        '/' => function () {
            return View::make('', 'index', ['page_title' => 'Phwoolcon']);
        },
        'terms' => function () {
            return View::make('', 'terms', ['page_title' => __('Terms of Service') . ' - Phwoolcon']);
        },
        'about-us' => function () {
            return View::make('', 'about-us', ['page_title' => __('About Us') . ' - Phwoolcon']);
        },
    ],
]);
