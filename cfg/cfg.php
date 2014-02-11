<?php

/**
 * Файл конфигурации программы индекстора документов DCR
 * @author edwaks  <info@edwaks.ru>
 * @author Mikhail Orekhov  <mikhail@edwaks.ru>
 */
class Cfg {

    const FIP_MYSQL_HOST = 'localhost';
    const FIP_MYSQL_DB = 'm4_fp';
    const FIP_MYSQL_USER = 'm4_om';
    const FIP_MYSQL_PASS = 'J2RSrRMto3QT';
    const ENVIRONMENT = 'development'; //development testing production
    const VERSION = 'v1.2';
    const BASE_AUTH_USER = 'www8';
    const BASE_AUTH_PASS = 'LdX1UTLe3e99';
    const FID_ERROR_LOG = 'log/fid_error.log';
    const FID_DATADIR = '/../../../fip/v2'; //рабочий каталог
    const FID_ALL_REESTR = "6,7,8,9,11,12";
    
    /**
     * Обработка ошибок , логирование , тестирование
     */
    const TEST_ID = ""; // 504246 994724 Отладочный документ для проверки работы патчей ключ на doc_data.id
    const DEBUGGING_PRINT = TRUE; //Вывод сообщений парсера на экран , использовать во время отладки при работе через командную строку . при работе через крон отключить .
    const DEBUGGING_LOG = FALSE; //Записывать сообщение парсера в лог , использовать для отладки 
    const DEBUGGING_FILE = "log/debugging_log"; //Файл лога

}

?>
