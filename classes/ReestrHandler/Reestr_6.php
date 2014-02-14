<?php

/**
 * Обработчик документов из 6 реестров
 * @author Михаил Орехов
 */
class Reestr_6 extends ReestrAbstract {
    //put your code here 
    public function parse($param) 
    {
        print_r($this->config) ;
        print_r($this->pdo) ;
    }
    public function save($param) 
    {
        
    }
}

?>
