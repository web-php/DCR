<?php

/**
 * Индекстаор документов , программа сканирует сграбленные ссылки , получает содержилое документов , заности содержимое в таблицу doc_data
 */
date_default_timezone_set('Europe/Moscow'); 
error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . "/classes/Db.php";
require_once __DIR__ . "/classes/DocumentIndexer.php";
require_once __DIR__ . "/classes/FidReport.php";
require_once __DIR__ . "/classes/RouterMode.php";

require_once __DIR__ . "/classes/Registry.php";
require_once __DIR__ . "/classes/Log.php";
require_once __DIR__ . "/classes/Helpers.php";
require_once __DIR__ . "/classes/shd.php";


$config = require_once __DIR__ . "/cfg/config.php";
Registry::set($config, "CONFIG");
Registry::set(new Log());

try
{
    $time_begin = time();
    $Pdo['DATA'] = new \DCR2\Db($config['DB']['BASE_DATA']);
    $Pdo['HTML'] = new \DCR2\Db($config['DB']['BASE_HTML']);
    Registry::set( $Pdo , "Pdo");
    Registry::set(new DbIndexer($Pdo));
    Registry::set(new LoadFile());


    //Определить режимы запуска
    $RouterMode = new RouterMode($argv);
    //Запустить индексатор
    $router = $RouterMode->get_router();
    //Получить ъкземпляр расборщика документа 
    $DocumentIndexer = new DocumentIndexer($router);
    $DocumentIndexer->run();


    $docs_added = $DocumentIndexer->get_docs_added();
    $docs_patched = $DocumentIndexer->get_docs_patched();

    $FidReport = new FidReport($Pdo['DATA']);

    print "Функция:			Индексация документов - " . $router['mode'] . "\n";
    print "Реестры:			" . implode(" , ", $router['reestr_id']) . "\n";
    print "Исполняемый файл:		" . __FILE__ . "\n\n";

    print $FidReport->get_time_report($time_begin);

    print "Добавлено документов:	$docs_added \n";
    print "Документов пропатчено:       $docs_patched \n\n";

    print $FidReport->get_doc_data_report();
    print $FidReport->get_class_report();
}
catch (Exception $error)
{
    $errmsg = date("d.m.y H:i:s") . "\t" . $error->getMessage() . "\n";
    Registry::get("Log")->log($error->getMessage());
    file_put_contents($config['ERROR_LOG'], $errmsg, FILE_APPEND);
}
?>