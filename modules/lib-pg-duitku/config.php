<?php

return [
    '__name' => 'lib-pg-duitku',
    '__version' => '0.0.1',
    '__git' => 'git@github.com:getmim/lib-pg-duitku.git',
    '__license' => 'MIT',
    '__author' => [
        'name' => 'Iqbal Fauzi',
        'email' => 'iqbalfawz@gmail.com',
        'website' => 'https://iqbalfn.com/'
    ],
    '__files' => [
        'modules/lib-pg-duitku' => ['install','update','remove']
    ],
    '__dependencies' => [
        'required' => [
            [
                'lib-curl' => NULL
            ]
        ],
        'optional' => []
    ],
    'autoload' => [
        'classes' => [
            'LibPgDuitku\\Library' => [
                'type' => 'file',
                'base' => 'modules/lib-pg-duitku/library'
            ]
        ],
        'files' => []
    ],
    '__inject' => [
        [
            'name' => 'libPgDuitku',
            'children' => [
                [
                    'name' => 'merchantCode',
                    'question' => 'DUITKU Merchant Code',
                    'rule' => '!^.+$!'
                ],
                [
                    'name' => 'apiKey',
                    'question' => 'DUITKU API Key',
                    'rule' => '!^.+$!'
                ],
                [
                    'name' => 'host',
                    'question' => 'DUITKU API Hostname',
                    'rule' => '!^.+$!'
                ]
            ]
        ]
    ]
];
