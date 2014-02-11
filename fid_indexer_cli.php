<?php

/**
 * Индекстаор документов , программа сканирует сграбленные ссылки , получает содержилое документов , заности содержимое в таблицу doc_data
 */

error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . "/classes/FidIndexer.php";
require_once __DIR__ . "/classes/FidReport.php";
require_once __DIR__ . "/classes/RouterMode.php";
require_once __DIR__ . "/cfg/cfg.php";


try
{
    $time_begin = time();
    $pdo = new PDO(
            'mysql:host=' . Cfg::FIP_MYSQL_HOST . ';dbname=' . Cfg::FIP_MYSQL_DB, Cfg::FIP_MYSQL_USER, Cfg::FIP_MYSQL_PASS, array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            )
    );

    $RouterMode = new RouterMode($argv);
    $router = $RouterMode->get_router();
    $FidIndexer = new FidIndexer($pdo);
    $FidIndexer->set_datadir(__DIR__ . Cfg::FID_DATADIR);
    $FidIndexer->run($router['reestr_id'], $router['mode']);
    
    
    $docs_added = $FidIndexer->get_docs_added();
    $docs_patched = $FidIndexer->get_docs_patched();

    $FidReport = new FidReport($pdo);
    
    print "Функция:			Индексация документов\n";
    print "Исполняемый файл:		" . __FILE__ . "\n\n";

    print $FidReport->get_time_report($time_begin);

    print "Добавлено документов:	$docs_added\n";
    print "Документов пропатчено:       $docs_patched\n\n";

    print $FidReport->get_doc_data_report();
    print $FidReport->get_class_report();
    print $FidReport->get_subclass_report();
    print $FidReport->get_uniq_subclass_report();
    print $FidReport->get_doc_subclass_report();
}
catch (Exception $e)
{
    $errmsg = date("d.m.y H:i:s") . "\t" . $e->getMessage() . "\n";
    print($e->getMessage());
    $ferr = fopen(Cfg::FID_ERROR_LOG, 'a');
    fwrite($ferr, $errmsg);
    fclose($ferr);
}
?>