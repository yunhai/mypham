<?php

return [
    'debug' => 1,
    'locale' => [
        'available' => [
            1 => 'vi',
            2 => 'en'
        ],
        'default' => 'vi'
    ],

    'group' => [
        1 => 'editor',
        2 => 'admin'
    ],

    'app' => [
        'prefix' => ['backend'],
        'channel' => [
            1 => 'frontend',
            2 => 'backend'
        ],
        'view' => [
            'frontend' => 'frontend',
            'backend' => 'backend'
        ],
        'url' => [
            'domain' => 'http://www.toolmark.me',
            'asset' => 'http://www.toolmark.me/asset/',
            'media' => 'http://www.toolmark.me/media/'
        ],
        'upload' => [
            'location' => 'media'
        ]
    ],

    'authorize' => [
        'check' => ['backend'],
        'allow' => [
            'backend' => [
                'user' => ['login', 'logout'],
                'error' => []
            ]
        ],
        'modular' => [
            'frontend' => [
                'user' => ['me', 'balance', 'update-password'],
                'order' => ['detail']
            ]
        ],

        'login' => [ #//full
            'backend' => 'login@user'
        ]
    ],
    'api' => [
        'google-map' => 'AIzaSyA1jrQR6EsZO9tk5Z_rNb7NFjWEiKoj_DE'
    ]
];
