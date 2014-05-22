<?php

/**
 * Description of Reestr_23 
 * класс миграции данных из FED в doc_data , временная реализация сводного реестра
 *
 * @author Михаил Орехов
 */
class Reestr_23 extends DataMigration {

    //put your code here
    public function __construct()
    {
        parent::__construct();
        $this->bd_prefix = "resurs";
        $this->table_migration = "organization";
        $this->add_condition = "WHERE org_status IN(187 , 2156 , 5826 , 129080 , 706 , 2980 , 1704 , 2106 , 1864 , 3188 , 4 , 145 , 2)";
    }

    /**
     * Установить перемапинг полей относительно текущего реестра
     */
    protected function set_maping()
    {
        $this->maping = array(
            "link_id" => "id",
            "p732" => "org_address",
            "p111" => "org_id",
            //"p732_init" => "org_address" , Поле исключено 483 16.05.14
            "p732" => "org_address",
            "doc_number" => "org_id",
            "p540_txt" => "org_name",
            "p750" => "postal_address",
            "doc_status_id" => "org_status",
            "doc_status_date" => "licvidation_date",
            "ogrn" => "ogrn");
    }

    /**
     * Почистить исходнуй фразу , 
     * выбрать текстовые данные из последней пары кавычек двойных или одинарных или одинарных по 2 к ряду
     * текст не может быть пустым , если нет совпадений удалить все незакрытые кавычки
     * @param array $array
     */
    protected function parse_field(array $array)
    {
        preg_match_all("#(\"|\'){1,2}\s*(?P<name>.*?)\s*(\"|\'){1,2}#i", $array['org_name'], $match);
        $name_arr = array_values(array_filter($match['name']));
        if ($count = count($name_arr))
        {
            if ($count > 1)
                $array['org_name'] = $name_arr[$count - 1];
            else
                $array['org_name'] = $name_arr[0];
        } else {
            $array['org_name'] = str_replace(array("\'", "\""), "", $array['org_name']);
        }
        //Registry::get("Log")->log($array['org_name']);
        return ($array['org_name'] ? $array : false) ;
    }

}

?>
