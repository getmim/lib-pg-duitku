<?php

return [
    '__name' => 'lib-pg-duitku',
    '__version' => '0.4.0',
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
                    'name' => 'payment',
                    'children' => [
                        [
                            'name' => 'merchantCode',
                            'question' => 'DUITKU Payment Merchant Code',
                            'rule' => '!^.+$!'
                        ],
                        [
                            'name' => 'apiKey',
                            'question' => 'DUITKU Payment API Key',
                            'rule' => '!^.+$!'
                        ],
                        [
                            'name' => 'host',
                            'question' => 'DUITKU Payment API Hostname',
                            'rule' => '!^.+$!'
                        ]
                    ]
                ],
                [
                    'name' => 'transfer',
                    'children' => [
                        [
                            'name' => 'userId',
                            'question' => 'DUITKU Transfer User ID',
                            'rule' => '!^.+$!'
                        ],
                        [
                            'name' => 'email',
                            'question' => 'DUITKU Transfer User Email',
                            'rule' => '!^.+$!'
                        ],
                        [
                            'name' => 'secretKey',
                            'question' => 'DUITKU Transfer Secret Key',
                            'rule' => '!^.+$!'
                        ],
                        [
                            'name' => 'sandbox',
                            'question' => 'DUITKU Transfer Is Sandbox',
                            'rule' => 'boolean'
                        ]
                    ]
                ]
            ]
        ]
    ]
];
