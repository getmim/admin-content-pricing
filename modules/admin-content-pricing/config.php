<?php

return [
    '__name' => 'admin-content-pricing',
    '__version' => '0.1.1',
    '__git' => 'git@github.com:getmim/admin-content-pricing.git',
    '__license' => 'MIT',
    '__author' => [
        'name' => 'Iqbal Fauzi',
        'email' => 'iqbalfawz@gmail.com',
        'website' => 'http://iqbalfn.com/'
    ],
    '__files' => [
        'modules/admin-content-pricing' => ['install','update','remove'],
        'theme/admin/content-pricing' => ['install','update','remove']
    ],
    '__dependencies' => [
        'required' => [
            [
                'admin' => NULL
            ],
            [
                'content-pricing' => NULL
            ],
            [
                'lib-form' => NULL
            ],
            [
                'lib-formatter' => NULL
            ],
            [
                'lib-pagination' => NULL
            ],
            [
                'lib-user' => NULL
            ],
            [
                'admin-user' => NULL
            ]
        ],
        'optional' => []
    ],
    'autoload' => [
        'classes' => [
            'AdminContentPricing\\Controller' => [
                'type' => 'file',
                'base' => 'modules/admin-content-pricing/controller'
            ]
        ],
        'files' => []
    ],
    'routes' => [
        'admin' => [
            'adminContentPricing' => [
                'path' => [
                    'value' => '/pricing'
                ],
                'method' => 'GET',
                'handler' => 'AdminContentPricing\\Controller\\Pricing::index'
            ],
            'adminContentPricingEdit' => [
                'path' => [
                    'value' => '/pricing/(:id)',
                    'params' => [
                        'id' => 'number'
                    ]
                ],
                'method' => 'GET|POST',
                'handler' => 'AdminContentPricing\\Controller\\Pricing::edit'
            ],
            'adminContentPricingRemove' => [
                'path' => [
                    'value' => '/pricing/(:id)/remove',
                    'params' => [
                        'id' => 'number'
                    ]
                ],
                'method' => 'GET',
                'handler' => 'AdminContentPricing\\Controller\\Pricing::remove'
            ]
        ]
    ],
    'adminUi' => [
        'sidebarMenu' => [
            'items' => [
                'pricing' => [
                    'label' => 'Pricing',
                    'icon' => '<i class="fas fa-money-check-alt"></i>',
                    'priority' => 0,
                    'route' => ['adminContentPricing'],
                    'perms' => 'read_content_pricing'
                ]
            ]
        ]
    ],
    'libForm' => [
        'forms' => [
            'admin.content-pricing.index' => [
                'user' => [
                    'label' => 'User',
                    'type' => 'select',
                    'sl-filter' => [
                        'route' => 'adminObjectFilter',
                        'params' => [],
                        'query' => ['type' => 'user']
                    ],
                    'rules' => []
                ],
                'month' => [
                    'label' => 'Month',
                    'type' => 'month',
                    'rules' => []
                ],
                'status' => [
                    'label' => 'Status',
                    'type' => 'select',
                    'options' => [
                        '1' => 'Priced',
                        '2' => 'Unpriced'
                    ],
                    'rules' => []
                ],
                'type' => [
                    'label' => 'Type',
                    'type' => 'select',
                    'rules' => []
                ]
            ],
            'admin.content-pricing.edit' => [
                'type' => [
                    'label' => 'Type',
                    'type' => 'text',
                    'rules' => [
                        'required' => true,
                        'enum' => 'content-pricing.type'
                    ]
                ],
                'object' => [
                    'label' => 'Object',
                    'type' => 'number',
                    'rules' => [
                        'required' => true
                    ]
                ],
                'price' => [
                    'label' => 'Price',
                    'type' => 'number',
                    'rules' => [
                        'required' => true,
                        'numeric' => [
                            'min' => 0
                        ]
                    ]
                ],
                'month' => [
                    'label' => 'Month',
                    'type' => 'month',
                    'rules' => [
                        'required' => true,
                        'date' => [
                            'format' => 'Y-m'
                        ]
                    ]
                ]
            ]
        ]
    ]
];
