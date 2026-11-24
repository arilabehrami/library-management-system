<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api_auth_login' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/auth/login',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'login',
                    ],
                ],
            ],
            'api_loans_create' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/loans',
                    'defaults' => [
                        'controller' => Controller\LoanController::class,
                        'action'     => 'create',
                    ],
                ],
            ],
            'api_loans_return' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/loans/:id/return',
                    'constraints' => ['id' => '\d+'],
                    'defaults'    => [
                        'controller' => Controller\LoanController::class,
                        'action'     => 'return',
                    ],
                ],
            ],
            'api_loans_penalty_pay' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/loans/:id/penalty/pay',
                    'constraints' => ['id' => '\d+'],
                    'defaults'    => [
                        'controller' => Controller\LoanController::class,
                        'action'     => 'payPenalty',
                    ],
                ],
            ],
            'api_books' => [
                'type'          => Literal::class,
                'options'       => ['route' => '/api/books'],
                'may_terminate' => false,
                'child_routes'  => [
                    'list' => [
                        'type'    => Method::class,
                        'options' => [
                            'verb'     => 'get',
                            'defaults' => [
                                'controller' => Controller\BookController::class,
                                'action'     => 'list',
                            ],
                        ],
                    ],
                    'create' => [
                        'type'    => Method::class,
                        'options' => [
                            'verb'     => 'post',
                            'defaults' => [
                                'controller' => Controller\BookController::class,
                                'action'     => 'create',
                            ],
                        ],
                    ],
                ],
            ],
            'api_book' => [
                'type'          => Segment::class,
                'options'       => [
                    'route'       => '/api/books/:id',
                    'constraints' => ['id' => '\d+'],
                ],
                'may_terminate' => false,
                'child_routes'  => [
                    'show' => [
                        'type'    => Method::class,
                        'options' => [
                            'verb'     => 'get',
                            'defaults' => [
                                'controller' => Controller\BookController::class,
                                'action'     => 'show',
                            ],
                        ],
                    ],
                    'update' => [
                        'type'    => Method::class,
                        'options' => [
                            'verb'     => 'put',
                            'defaults' => [
                                'controller' => Controller\BookController::class,
                                'action'     => 'update',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type'    => Method::class,
                        'options' => [
                            'verb'     => 'delete',
                            'defaults' => [
                                'controller' => Controller\BookController::class,
                                'action'     => 'delete',
                            ],
                        ],
                    ],
                ],
            ],
            'api_me_loans' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/me/loans',
                    'defaults' => [
                        'controller' => Controller\MeController::class,
                        'action'     => 'loans',
                    ],
                ],
            ],
            'api_me_history' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/me/history',
                    'defaults' => [
                        'controller' => Controller\MeController::class,
                        'action'     => 'history',
                    ],
                ],
            ],
            'api_me_penalties' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/me/penalties',
                    'defaults' => [
                        'controller' => Controller\MeController::class,
                        'action'     => 'penalties',
                    ],
                ],
            ],
            'api_operator_loans' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/operator/loans',
                    'defaults' => [
                        'controller' => Controller\OperatorController::class,
                        'action'     => 'loans',
                    ],
                ],
            ],
            'api_operator_user_loans' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/operator/users/:id/loans',
                    'constraints' => ['id' => '\d+'],
                    'defaults'    => [
                        'controller' => Controller\OperatorController::class,
                        'action'     => 'userLoans',
                    ],
                ],
            ],
            'api_operator_user_penalties' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/operator/users/:id/penalties',
                    'constraints' => ['id' => '\d+'],
                    'defaults'    => [
                        'controller' => Controller\OperatorController::class,
                        'action'     => 'userPenalties',
                    ],
                ],
            ],
            'api_notifications_list' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/notifications',
                    'defaults' => [
                        'controller' => Controller\NotificationController::class,
                        'action'     => 'list',
                    ],
                ],
            ],
            'api_notifications_read' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/notifications/:id/read',
                    'constraints' => ['id' => '\d+'],
                    'defaults'    => [
                        'controller' => Controller\NotificationController::class,
                        'action'     => 'read',
                    ],
                ],
            ],
            'api_admin_users_list' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/admin/users',
                    'defaults' => [
                        'controller' => Controller\AdminController::class,
                        'action'     => 'users',
                    ],
                ],
            ],
            'api_admin_user' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/admin/users/:id',
                    'constraints' => ['id' => '\d+'],
                    'defaults'    => [
                        'controller' => Controller\AdminController::class,
                        'action'     => 'user',
                    ],
                ],
            ],
            'api_admin_settings' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/admin/settings',
                    'defaults' => [
                        'controller' => Controller\AdminController::class,
                        'action'     => 'settings',
                    ],
                ],
            ],
            'api_admin_setting' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/admin/settings/:key',
                    'constraints' => ['key' => '[a-z_]+'],
                    'defaults'    => [
                        'controller' => Controller\AdminController::class,
                        'action'     => 'setting',
                    ],
                ],
            ],
            'api_admin_logs' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api/admin/logs',
                    'defaults' => [
                        'controller' => Controller\AdminController::class,
                        'action'     => 'logs',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class        => InvokableFactory::class,
            Controller\AuthController::class         => Controller\AuthControllerFactory::class,
            Controller\LoanController::class         => Controller\LoanControllerFactory::class,
            Controller\BookController::class         => Controller\BookControllerFactory::class,
            Controller\MeController::class           => Controller\MeControllerFactory::class,
            Controller\OperatorController::class     => Controller\OperatorControllerFactory::class,
            Controller\NotificationController::class => Controller\NotificationControllerFactory::class,
            Controller\AdminController::class        => Controller\AdminControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\AuthService::class => Service\AuthServiceFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => false,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];