<?php

/**
 * Description of DocumentIndexer
 *
 * @author Михаил Орехов
 */

require_once __DIR__.'/ReestrHandler/ReestrAbstract.php';
require_once __DIR__.'/HandlerFactory.php';
require_once __DIR__.'/LoadFile.php';
require_once __DIR__.'/DbIndexer.php';

class DocumentIndexer {
    
    private $reestr_id;
    private $mode;
    private $docs_added = 0;
    private $docs_patched = 0;
    private $HandlerReestr ; 
    private $config = array();
    private $pdo = array();
    private $LoadFile ; 
    
    public function __construct( array $router , array $config , array $pdo )
    {
        $this->reestr_id = $router['reestr_id']; 
        $this->mode = $router['mode']; 
        $this->config = $config;
        $this->pdo = $pdo ; 
        $this->DbIndexer = new DbIndexer($pdo);
    }
    
    /**
     * Запуск индексатора документов
     */
    public function run()
    {
        foreach($this->reestr_id as $reestr_id)
        {
            //Получить экземпляр класса для работы с требуемым реестром
            $this->HandlerReestr = HandlerFactory::GetInstance($reestr_id , $this->config , $this->pdo);
            $method = $this->mode;
            $this->$method($reestr_id) ; 
            //$this->HandlerReestr->parse("");
        }
    }
    
    /**
     * Режим запуска обработчика документов по умолчанию , получает новые документы из таблицы link индексирует их
     */
    private function normal_start_mode($reestr_id)
    {
        while ($row = $this->DbIndexer->get_next_link($reestr_id)) 
        {
            if($this->HandlerReestr->parse($row))
            {
                $this->HandlerReestr->save( "insert_doc_data" );
            }
        }
    }
    
    private function doc_update_mode()
    {
        
    }
    
    
    /** Получить количество добавленных документов */
    public function get_docs_added()
    {
        return $this->docs_added;
    }

    /** Получить количестов пропатченых документов */
    public function get_docs_patched()
    {
        return $this->docs_patched;
    }
    
}

?>
