<?php
    return [
        'default' => [
            'deliver' => 'phpmailer',
            'transport' => 'mail',
            'host' => 'localhost',
            'post' => 25,
            'charset' => 'utf-8'
        ],

        'smtp' => [
            'deliver' => 'phpmailer',
            'transport' => '',
            'host' => '',
            'port' => '',
            'username' => '',
            'password' => '',
            'protocol' => '',
            'charset' => ''
        ]
    ];
