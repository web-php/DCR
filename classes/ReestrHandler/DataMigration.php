<?php

/**
 * часть проекта RDP
 * Клас позволяющий осуществлять сбор данных из разных реестров в своднуюталицу ops_doc_map
 * @author Михаил Орехов
 */
abstract class DataMigration {

    public function __construct()
    {
        $this->config = Registry::get("CONFIG");
        $this->set_maping();
    }

    protected $molule_cfg = array(
        "data_length" => 500000
    );
    /**
     * дополнительное sql условие
     * @var string
     */
    protected $add_condition = false ; 
    protected $add_fields = false ;



    /**
     * !!!!!!!!!!! НЕ ЗАБЫВАТЬ ЧТО ПОЛЯ LINK_ID обязательно для мапинга !!!!!!!!!!
     * 
     * Перемапинг полей требуемого реестра под doc_data
     * значение базы данных doc_data => поле базы данных из которой берутся данные
     */
    protected $maping = array() ;

    /**
     * Префикс базы данных из которой будут мигрировать данные
     */
    protected $bd_prefix ;

    /**
     * таблица из которой будут мигрировать данные
     */
    protected $table_migration;

    /**
     * таблица из которой будут мигрировать данные
     */
    protected $data = array() ;

    /**
     * Создать перемапинг по полям требуемого реестра
     */
    protected abstract function set_maping();
    
    /**
     * Отредактировать требуемые поля документа , для определенного реестра
     */
    protected abstract function parse_field(array $array);

    /**
     * Запустит переливку информации
     */
    public function run_migration($reestr_id)
    {
        $i = 0;
        Registry::get("DbIndexer")->action_keys("data","doc_data","disable");
        while ($array = Registry::get("DbIndexer")->get_data_migration($this->maping, $this->bd_prefix, $this->table_migration , $this->add_condition , $this->add_fields)) {
            $i++;
            //Отредактировать требуемые поля , если основные поля в массиве пустые , пропустим итерацию 
            $this->data[] = $this->parse_field($array) ; 
            //проверим размер массива , если он равен размеру указаному в конфиге , зальем данные
            if (isset($this->data[$this->molule_cfg['data_length']]))
            {
                Registry::get("Log")->log("INSERT DATA " . count($this->data)." doc Iteration : $i");
                Registry::get("DbIndexer")->insert_data_migration($this->maping, $this->data, $reestr_id);
                $this->data = array();
            }
        }
        //Перепроверим незалитые данные 
        if (count($this->data) > 0)
        {
            Registry::get("Log")->log("INSERT DATA . last iteration " . count($this->data)." doc");
            Registry::get("DbIndexer")->insert_data_migration($this->maping, $this->data, $reestr_id);
        }
        Registry::get("DbIndexer")->action_keys("data","doc_data","enable");
        //Запишем статистику
        Registry::set( $i , "docs_added" , true );
    }

}

?>
