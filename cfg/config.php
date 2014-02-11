<?php

return array(
    'ENVIRONMENT' => "development", //development testing production
    'BASE_AUTH_USER' => '', //Логин для авторизации на сервере для получения картинок
    'BASE_AUTH_PASS' => '', //Пароль для авторизации на сервере для получения картинок
    'DEFAULT_REESTR' => array(6, 7, 9, 11, 12),
    'ALL_REESTR' => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
    /** var array DB настройки подключения к базам */
    'DB' => array(
            'BASE_HFT' => array(
                'HOST' => '',
                'USER' => '',
                'BASE' => '',
                'PASS' => ''
        ),
            'BASE_HTML' => array(
                'HOST' => '',
                'USER' => '',
                'BASE' => '',
                'PASS' => ''
        )
    ),
    'ERROR_LOG' => __DIR__ . '/../log/error.log',
    'APP_NUM' => '69',
    'APP_CODE' => 'DCR2',
    'APP_VERSION' => '2',
    'APP_NAME' => 'Индексатор html документов',
    'MAX_SIZE_ALLOWED' => 1024 * 768, //максимальный разрешенный размер файла html
    'DEBUGGING_PRINT' => TRUE
);
?>
