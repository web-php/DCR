<?php

/**
 * Шаблон для построения классов
 *
 * @author Михаил Орехов
 */
abstract class ReestrAbstract {

    public abstract function parse($param);

    public abstract function save($param);

    protected abstract function get_modification_field($row);

    public $html;
    protected $log_id;
    protected $config = array();
    protected $status_list = array(); //список статусов
    protected $DbIndexer;
    protected $SearchSubClass;
    protected $shd;
    protected $cash = array(
        "fraza" => array(),
        "kf511" => array(),
        "words" => array(),
        "symbol" => array()
    );
    protected $cash_counter = array(
        "fraza" => array(),
        "kf511" => array(),
        "words" => array(),
        "symbol" => array()
    );
    protected $cash_base = array(
        "fraza" => 0,
        "kf511" => 0,
        "words" => 0,
        "symbol" => 0
    );
    protected $cash_elements = 500000;

    /** var $fields поля для парсинга */
    protected $fields = array(
        "link_id" => "",
        "doc_number" => "",
        "p732" => "",
        "p731" => "",
        "p732_init" => "",
        "p731_init" => "",
        "p731_2_date" => "",
        "p210" => "",
        "p111" => "",
        "p220" => "",
        "p151" => "",
        "p540_file" => "",
        "p540_txt" => "",
        "p740" => "",
        "p750" => "",
        "doc_link" => "",
        "doc_html_file" => "",
        "doc_img_file" => "",
        "doc_list1_file" => "",
        "reestr_id" => "",
        "doc_status_id" => "",
        "doc_status_date" => "",
        "doc_img_link" => "",
        "doc_list1_link" => ""
    );
    protected $modification_fields = array(
        "date_pub" => "",
        "type_edits" => "",
        "p580" => "",
        "p732" => "",
        "p791" => "",
        "p740" => "",
        "p750" => "",
        "p770" => "",
        "hash" => ""
    );

    /**
     * Функция поиска значения в кэше
     * если значение не найдено идет обращение к базе данных из которой достается значение и ложится для дальнейшего использования в кэш
     */
    protected function get_cash($cash_name, $value)
    {
        $hash = md5($value);
        //$id = array_search($hash, $this->cash[$cash_name]); 
        $id = (isset($this->cash[$cash_name][$hash]) ? $this->cash[$cash_name][$hash] : $this->add_field($cash_name, $value, $hash));
        //Увеличиваем счетчик использования эллемента кэша
        $this->cash_counter[$cash_name][$id]++;
        return $id;
    }

    //Добавить строчку в базу
    private function add_field($cash_name, $value, $hash)
    {
        //Registry::get("Log")->log("add new cach element : $hash ");
        $id = $this->DbIndexer->add_field($cash_name, $value, "dcr", "hash");
        $this->cash[$cash_name][$hash] = $id;
        $this->cash_counter[$cash_name][$id] = 0 ;
        $this->cash_base[$cash_name]++;
        //если установленный кэш привышает допусттмый пересобрать кэш
        if ($this->cash_base[$cash_name] > $this->cash_elements)
        {
            $this->set_cash($cash_name);
        }
        return $id;
    }

    /** Установить кэш по требуемой таблице  */
    public function set_cash($cash_name)
    {
        if (!isset($this->cash_base[$cash_name]))
        {
            Registry::get("Log")->log("cash_name $cash_name not valid", "err");
            return;
        }
        //выгрузить статистику по кэшу в базу
        $this->DbIndexer->update_cash($cash_name, $this->cash_counter[$cash_name], "dcr");
        //греем кэш
        $cash_data = $this->DbIndexer->select_cash_data($cash_name, $this->cash_elements);
        //обнуляем имеющейся кэш
        $this->reset_cash($cash_name);
        //записываем полученный кэш в память
        foreach ($cash_data as $arr)
        {
            $this->cash[$cash_name][$arr['hash']] = $arr['id'];
            $this->cash_counter[$cash_name][$arr['id']] = $arr['count'];
        }
        
        Registry::get("Log")->log("install cash $cash_name ========================================> ".count($this->cash[$cash_name]));
        
        
    }

    public function loading_cash()
    {
        $cash_names = array_keys($this->cash);
        foreach ($cash_names as $cash_name)
        {
            print_r($cash_name);
            exit;
        }
    }

    protected function reset_cash($cash_name)
    {
        $this->cash[$cash_name] = array();
        $this->cash_counter[$cash_name] = array();
        $this->cash_base[$cash_name] = 0;
    }

    //Массив функций которые могут быть запущенны в отдельном режиме f 
    //пример p740 => get_p740 ; для получения поля исплользовать функцию. 
    protected $map_field = array(
        "p740" => array(
            "func" => "get_field",
            "param" => "740"),
        "p750" => array(
            "func" => "get_p750",
            "param" => ""),
    );

    /** Массив изменений по документам */
    protected $notice = array();

    public function __construct()
    {
        //основные пакеты
        $this->config = Registry::get("CONFIG");
        $this->DbIndexer = Registry::get("DbIndexer");
        $this->LoadFile = Registry::get("LoadFile");
        //символьный поииск
        $this->SearchSubClass = new SearchSubClass();
        $this->shd = new simple_html_dom();

        $this->status_list = $this->DbIndexer->init_status_list();
    }

    /**
     * Проеверить html на целостность
     */
    public function html_check($row)
    {
        if (!$this->set_document($row))
            return $row['id'];
        else
        {
            if (stristr($this->html, '</body>') === FALSE)
                return $row['id'];
        }
        return false ;
    }

    /**
     * Получить html файл с основного сервера
     * @param string $html_file относительный путь до фала ключ на doc_data.doc_html_file
     */
    protected function select_html($html_file)
    {
        if ($html = $this->LoadFile->select_html($html_file))
        {
            Registry::get("Log")->log("document {$html_file} loaded");
            $this->html = $html;
            return $html;
        }
        Registry::get("Log")->log("document is not loaded", "err");
        $this->html = "";
        return false;
    }

    public function get_reestr()
    {
        return $this->fields['reestr_id'];
    }

    /** Получить значение полей из запроса */
    protected function set_default_fields(array $row)
    {
        //TODO : Документы с префиксом не правельно сохраняются , требуется правка в FIP грабере 00105123A1.html
        $this->fields['doc_number'] = $this->get_doc_number($row['doc_link']);
        $this->fields['link_id'] = $row['id'];
        $this->fields['doc_link'] = $row['doc_link'];
        $this->fields['doc_html_file'] = $row['doc_html_file'];
        $this->fields['doc_img_file'] = $row['doc_img_file'];
        $this->fields['doc_list1_file'] = $row['doc_list1_file'];
        $this->fields['reestr_id'] = $row['reestr_id'];
        $this->fields['reestr_id'] = $row['reestr_id'];
    }

    /**
     * Общий метод для получения любого нумерованного или помеченого поля , ищет только первое вхождение
     * @param int/str $code код поля или строкововый аналог , (можно обрезать строковый аналог до 2-3 слов , обязательно экранировать спец символы : \)\( )
     * @param string $html блок разметки в которой будет производится поиск вхождения , если не указан то будет произведен поиск по всему документу
     * 
     */
    protected function get_field($code, $html = "")
    {
        $html = ($html ? $html : $this->html);
        $condition = (is_numeric($code) ? "\($code\).*?" : "<I>$code.*?</I>.*?");

        $patern = "#$condition<B>(?P<value>.*?)</B>#i";
        //print("\n $html \n $patern \n") ;
        if (preg_match($patern, $html, $field_res))
        {
            return trim($field_res["value"]);
        }
        return NULL;
    }

    /** Получить DocNumber */
    protected function get_doc_number($link)
    {
        if (preg_match("#DocNumber=(\d+)#", $link, $link_num_res))
        {
            return $link_num_res[1];
        }
    }

    /**
     * получить id , date , статуса из документа
     * @return array $status_info
     */
    protected function parse_status_id($html)
    {
        if (!($status_line_html = $this->get_status_line_html($html)))
        {
            return FALSE; //4
        }

        foreach ($this->status_list as $status_info)
        {
            if (mb_stristr($status_line_html, $status_info['title'], FALSE, 'UTF-8'))
            {
                //Получить Дату установки статуса  27.10.2013
                preg_match("#([0-9]{2}\.[0-9]{2}\.[0-9]{4})#i", $html, $date);
                $date = explode(".", $date[1]);
                $status_info['date'] = $date[2] . $date[1] . $date[0];
                return $status_info;
            }
        }
        return FALSE;
    }

    // получить строку html, в которой потенциально находится статус
    protected function get_status_line_html($html)
    {

        $markers = array('состояние делопроизводства', 'статус:');
        foreach (explode("\n", $html) as $line)
        {

            foreach ($markers as $marker)
            {
                if (mb_stristr($line, $marker, FALSE, 'UTF-8'))
                {
                    return $line;
                }
            }
        }
        return FALSE;
    }

    /** Получить тип изменения документа , относится к модификациям документа */
    protected function get_type_edits($html)
    {
        if (preg_match("#^<B>(?P<type_edits>.*?)</B>#im", $html, $field_res))
        {
            return $this->DbIndexer->insert_type_edits(
                            $this->get_reestr(), trim($field_res["type_edits"]));
        }
        return '';
    }

    /**
     * Парсинг модификаций документа 
     */
    protected function select_modification()
    {
        $marker = $this->marker_split;
        $notice = array();
        //Получить поле модификаций
        $modification = preg_split("#$marker[0]#im", $this->html);
        if (!empty($modification[1]))
        {
            //разбить модификации на составляющие блоки
            $block = preg_split("#$marker[1]#im", $modification[1]);
            //получить поля модификаций в каждом блоке
            unset($block[0]);
            unset($block[count($block)]);
            foreach ($block as $key => $part)
            {
                $notice[$key] = $this->get_modification_field($part);
                $notice[$key]['hash'] = md5(implode(".", array_values($notice[$key])));
            }
        }
        $this->notice = $notice;
    }

}

?>
