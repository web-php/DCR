<?php namespace DCR2;
use \PDO;
class Db extends PDO {

    public function __construct($config)
    {
        //print_r($config);
        parent::__construct(
                'mysql:host=' . $config['HOST'] . ';dbname=' . $config['BASE'], $config['USER'], $config['PASS'], array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // ERRMODE_EXCEPTION ERRMODE_EXCEPTION
                )
        );
    }

}

?>