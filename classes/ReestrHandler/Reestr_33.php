<?php

/**
 * Description of Reestr_23 
 * класс миграции данных из FED в doc_data , временная реализация сводного реестра
 *
 * @author Михаил Орехов
 */
class Reestr_33 extends DataMigration {

    //put your code here
    public function __construct()
    {
        parent::__construct();
        $this->bd_prefix = "resurs";
        $this->table_migration = "domains";
        //$this->add_condition = " WHERE zone = 'rf' ";
        $this->add_fields = " , zone " ; 
        $this->converter = new idna_convert(array('idn_version' => 2008));
    }

    /**
     * Установить перемапинг полей относительно текущего реестра
     */
    protected function set_maping()
    {
        $this->maping = array(
            "link_id" => "id",
            "p540_txt" => "domain",
            "doc_status_id" => "type",
            "doc_status_date" => "registered"
        );
    }

    /**
     * Почистить исходнуй фразу , 
     * выбрать текстовые данные из последней пары кавычек двойных или одинарных или одинарных по 2 к ряду
     * текст не может быть пустым , если нет совпадений удалить все незакрытые кавычки
     * @param array $array
     */
    protected function parse_field(array $array)
    {
        if($array['zone'] != "rf")
            return $array ;
        $array['domain'] = $this->converter->decode( strtolower($array['domain']) );
        return $array;
    }

}

?>
