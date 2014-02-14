<?php

/**
 * Индекстаор документов , программа сканирует сграбленные ссылки , получает содержилое документов , заности содержимое в таблицу doc_data
 */

error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . "/classes/Db.php";
require_once __DIR__ . "/classes/DocumentIndexer.php";
require_once __DIR__ . "/classes/FidReport.php";
require_once __DIR__ . "/classes/RouterMode.php";

$config = require_once __DIR__ . "/cfg/config.php";


try
{
    $time_begin = time();
    $pdo['DATA']  = new Db($config['DB']['BASE_DATA']);
    $pdo['HTML'] = new Db($config['DB']['BASE_HTML']);
    //Определить режимы запуска
    $RouterMode = new RouterMode($config , $argv);
    //Запустить индексатор
    $router = $RouterMode->get_router() ; 
    
    $DocumentIndexer = new DocumentIndexer( $router , $config , $pdo );
    $DocumentIndexer->run();
    
    
    $docs_added = $DocumentIndexer->get_docs_added();
    $docs_patched = $DocumentIndexer->get_docs_patched();
    
    $FidReport = new FidReport($pdo['DATA']);
    
    print "Функция:			Индексация документов - ".$router['mode']."\n";
    print "Реестры:			".implode(" , ",$router['reestr_id'])."\n";
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
    $ferr = fopen($config['ERROR_LOG'], 'a');
    fwrite($ferr, $errmsg);
    fclose($ferr);
}
?>