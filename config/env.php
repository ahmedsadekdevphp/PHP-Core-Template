<?php

return [
    'DB_HOST' => 'localhost:3306',
    'DB_NAME' => 'airport_management_sys',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'APPROOT' => dirname(dirname(__FILE__)),
    'URLROOT' => '',
    'SITENAME' => '',
    'DEFAULT_LANGUAGE' => 'en',
    'PAGINATE_NUM' => '10',
    'FIRST_PAGE' => '1',
    'USER_STATUS_APPROVED' => '1',
    'USER_STATUS_DISABLED' => '0',
    'APP_SECRET_KEY' => 'TYJHfksp49fmr948nmfmsddfskdsflp498mdslff',
    'RATE_LIMIT' => 5,
    'TIME_FRAME_IN_SECONDS' => 60,
    'throttle' => [
        'create' => [
            'count' => 10,
            'time_frame' => 30
        ],
        'update' => [
            'count' => 5,
            'time_frame' => 60
        ],
        'delete' => [
            'count' => 3,
            'time_frame' => 60
        ]
    ],
    'MAIL_HOST' => '',
    'MAIL_USERNAME' => '',
    'MAIL_PASSWORD' => '',
    'MAIL_PORT' => 465, 
    'MAIL_ENCRYPTION' => 'ssl', 
    'ADMIN_ROLE'=>'admin',
    'REDIS_HOST'=>'127.0.0.1',
    'REDIS_PORT'=>'6379',
    'REDIS_PASSWORD'=>null,
];
