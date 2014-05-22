<?php

/**
 * Description of TradeMark
 * Общий функционал для документов товарных знаков 6-7-9 реестр
 * @author Михаил Орехов
 */
abstract class TradeMark extends ReestrAbstract {

    public static $link_id;

    /** Общая для всех реестров товарных знаков функция парсинга мкту */
    abstract protected function parse_mktu();

//Маркеры по которым разюить страницу на состовляющие
    protected $marker_split = array(
        "<P.*?>Извещения об изменениях, относящихся к регистрации товарного знака</P>",
        "<HR STYLE=\"color:black; height:1px;\">");
    protected $mktu = array(
        "511" => "",
        "arr" => "",
        "sub" => "",
        "lang" => NULL);

    /** Утсновить id разбираемого линка , используется для прямого доступа к link.link_id */
    protected function set_link_id($id)
    {
        self::$link_id = $id;
    }

    public function parse($row)
    {

        if (!$this->set_document($row))
            return FALSE;
        //print_r($row);
        //Получить статус документа
        $this->parse_document_status($this->html);
        //Обработать стандратные поля
        $this->set_default_fields($row);
        //Получаем поля документа
        $this->get_document_fields($this->html);
        //Получить модификации документа
        $this->select_modification();
        //Разбираем дополнительно поле 511 по правилам на классы
        $this->parse_mktu();
    }

    public function parse_update($row)
    {
        $this->parse($row);
        $this->fields['p540_txt'] = ( $row['p540_txt'] ? $row['p540_txt'] : $this->fields['p540_txt'] );
        $this->fields['p540_file'] = ( $row['p540_file'] ? $row['p540_txt'] : $this->fields['p540_txt'] );
    }

    /**
     * Режим обработки документов исключительно поля 511 , индексация в классы
     */
    public function p511($row)
    {
        if (!$this->set_document($row))
            return FALSE;
        Registry::get("Log")->log("start parse_mktu");
        $this->parse_mktu();
        if (!empty($this->mktu['sub']))
        {
            Registry::get("Log")->log("insert_doc_fraza");
            $this->insert_doc_fraza($this->mktu['sub'], $row['doc_data_id']);
            Registry::get("Log")->log("save MKTU => count : (" . count($this->mktu['arr']) . ") list : " . implode(" , ", array_keys($this->mktu['arr'])));
            //$this->binding_sub_classes($this->mktu['sub'], $row['doc_data_id']);
        }
        $this->memory_clear();
    }

    /**
     * Режим обработки документов исключительно поля 540 , индексация в классы
     * 1 - номер слова 2 - символ 3 - позиция в фразе 4 - позиция в слове
     */
    public function p540($row)
    {

        $clause = mb_strtoupper(preg_replace("#\s{2,10}#i", "", $row['p540_txt']), "UTF-8");
        preg_match_all("#[\w\s\d]#u", $clause, $match);

        $word = 1;
        $position_word = 1;
        $values = array();
        foreach ($match[0] as  $value)
        {
            $space = false;
            $position_num = $word;
            if ($value == " ")
            {
                $word++;
                $space = true;
                if ($position_num > 1)
                    $position_num = $word - 1;
            }
            $symbol = $position_num . ":" . ($space ? "_" : $value) . ":" . $position_word;
            //Registry::get("Log")->log($symbol);
            $values[] = array(
                "doc_id" => $row['id'],
                "symbol_id" => $this->get_cash("symbol", $symbol)
            );
            $position_word++;
            if ($space)
                $position_word = 1;
        }
        $this->DbIndexer->add_index_rel($values, "dcr", "symbol_rel");
    }

    //Залить индекс 540

    /**
     * Обработка частных полей требуемых документов
     * @param $row поля полученной ссылки
     * @param $field_map обрабатываемые поля
     * $this->map_field - массив содержа инструкции как обрабатывать определенные поля
     */
    public function get_field_map(array $row, array $field_map)
    {
        if (!$this->set_document($row))
            return FALSE;
        foreach ($field_map as $field)
        {
            if (!empty($this->map_field[$field]))
            {
                $func = $this->map_field[$field]['func'];
                $param = $this->map_field[$field]['param'];
                $this->fields[$field] = $this->$func($param);
            }
            else
            {
                Registry::get("Log")->log("Выбрано не описаное поле $field", "err");
            }
        }
    }

    /**
     * Установить документ на обработку 
     */
    protected function set_document(array $row)
    {
        $this->set_link_id($row['id']);
        if (!$html = $this->select_html($row['doc_html_file']))
        {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Получить поля документа
     */
    protected function get_document_fields($html)
    {
        $p731 = $this->get_p731($html);
        $p732 = $this->get_p732($html);

        $this->fields['p732'] = $p732['732'];
        $this->fields['p731'] = $p731['731'];
        $this->fields['p732_init'] = $p732['732_init'];
        $this->fields['p731_init'] = $p731['731_init'];
        $this->fields['p731_2_date'] = $this->get_published($html);
        $this->fields['p210'] = $this->get_p210($html);
        $this->fields['p111'] = $this->get_p111($html);
        $this->fields['p220'] = $this->get_date_field(220, $html);
        $this->fields['p151'] = $this->get_date_field(151, $html);
        $this->fields['p740'] = $this->get_field(740);
        $this->fields['p750'] = $this->get_p750();
        $this->fields['doc_img_link'] = $this->get_img_href($html);
        $this->fields['doc_list1_link'] = $this->get_list1_href($html);
    }

    /** При получение  */
    protected function get_p750($html = '')
    {
        if (!$p750 = $this->get_field(750, $html))
        {
            $p750 = $this->get_field("Адрес для переписки:", $html);
        }
        return $p750;
    }

    protected function parse_document_status($html)
    {
        $status = $this->parse_status_id($html);
        $this->fields['doc_status_id'] = ( $status['id'] ? $status['id'] : 4);
        $this->fields['doc_status_date'] = ( $status['date'] ? $status['date'] : "NOW ()");
    }

    protected function get_p111($html)
    {
        $reg_arr[0] = "#\(111\)\s*<I>.*?</I>\s*<B>(\d+)</B>#i";
        $reg_arr[1] = "#<B>\(111\)</B>\s*Номер регистрации</TD><TD CLASS=CL1><B>(\d*)</B>#i";
        foreach ($reg_arr as $reg)
        {
            if (preg_match($reg, $html, $p111_res))
            {
                return trim(($p111_res[1]));
            }
        }
        return FALSE;
    }

// Номер заявки
    protected function get_p210($html)
    {
        if (preg_match("#\(210\)\s*<I>.*?</I>\s*<B>(\d+)</B>#i", $html, $p210_res))
        {
            return trim($p210_res[1]);
//print $p210_res;
        }
        return FALSE;
    }

    /**
     * Получить поле c датой
     */
    protected function get_date_field($field, $html)
    {
        if (preg_match("#\($field\).+?<B>(.*?)</B>#i", $html, $field_res))
        {
            $arr_date = date_parse(str_replace(".", "-", $field_res[1]));
            return $arr_date['year'] . str_pad($arr_date['month'], 2, 0, STR_PAD_LEFT) . str_pad($arr_date['day'], 2, 0, STR_PAD_LEFT);
        }
        return '';
    }

    /**
     * Получить Заявителя
     * @param string $html
     * @return array $p731 Верхнее и нижнее поле 
     */
    protected function get_p731($html)
    {
        if (preg_match_all("#\(731\)\s*<I>.*?</I>\s*<BR><B>(.*?)</B>#i", $html, $p731_res))
        {
            $p731 = array(
                "731" => trim($p731_res[1][count($p731_res[1]) - 1]),
                "731_init" => trim($p731_res[1][0]));
            return $p731;
        }
        return FALSE;
    }

    /**
     * Правообладатель
     * TODO : Требуется переработка , лишняя ответственность
     */
    protected function get_p732($html)
    {
        $reg_arr[0] = "#\(732\)\s*<I>.*?</I><BR><B>(.*?)</B>#i";
        $reg_arr[1] = "#<I>Правообладатель.*?:</I><BR><B>(.*?)</B>#i";
        $reg_arr[2] = "#<B>\(732\)</B>\s*Имя и адрес владельца</TD><TD CLASS=CL1><B>(.*?)</B>#i";
        //Правообладатель
        foreach ($reg_arr as $reg)
        {
            if (preg_match_all($reg, $html, $p732_res))
            {
                //print_r($p732_res);
                $p732 = array(
                    "732" => trim($p732_res[1][count($p732_res[1]) - 1]),
                    "732_init" => trim($p732_res[1][0]));
                return $p732;
            }
        }
        return FALSE;
    }

    protected function insert_doc_fraza(array $sub_class, $doc_id)
    {
        $values = array();
        arsort($sub_class);
        foreach ($sub_class as $class_id => $frazes)
        {
            foreach ($frazes as $fraza)
            {
                $values[] = array(
                    "class_id" => $class_id,
                    "fraza_id" => $this->get_cash("fraza", $fraza)
                );
            }
        }
        //print_r($values);
        $this->DbIndexer->dcr_fraza_rel($doc_id, $values);
        Registry::get("Log")->log(" cash length :" . count($this->cash["fraza"]) . "=======>");
        unset($values);
    }

    /** Индексация фраз и слов из мкту */
    protected function insert_doc_fraza_OLD(array $sub_class, $doc_id)
    {
        arsort($sub_class);
        foreach ($sub_class as $class_id => $frazes)
        {
            $i = 0;
            $frazes = array_unique($frazes);
            foreach ($frazes as $fraza)
            {
                //Выражения
                $fraza_id = $this->DbIndexer->add_field("fraza", $fraza, "dcr", "hash");
                //Registry::get("Log")->log("insert  fraza :");
                $this->DbIndexer->dcr_fraza_rel($class_id, $doc_id, $fraza_id);
                //Registry::get("Log")->log("insert  dcr_fraza_rel :");
                //Фразы
                $kf511 = explode(",", $fraza);
                $kf511 = array_unique($kf511);

                foreach ($kf511 as $kf)
                {
                    if (strlen($kf) < 3)
                        continue;
                    $kf_id = $this->DbIndexer->add_field("kf511", $kf, "dcr", "hash");
                    //Registry::get("Log")->log("insert  kf511 :");
                    $this->DbIndexer->add_index("kf511", array(
                        "fraza_id", "kf_id", $fraza_id, $kf_id));
                    //Registry::get("Log")->log("insert  add_index kf511 :");
                    //Слова
                    $words = explode(" ", $kf);
                    $words = array_unique($words);
                    foreach ($words as $word)
                    {
                        if (strlen($word) < 3)
                            continue;
                        $words_id = $this->DbIndexer->add_field("words", $word, "dcr");
                        //Registry::get("Log")->log("insert  words :");
                        $this->DbIndexer->add_index("words", array(
                            "kf_id", "words_id", $kf_id, $words_id));
                        //Registry::get("Log")->log("insert  add_index word :");
                    }
                }
                $i++;
                //if ($i == 10) exit; print"\n===========>\n";
            }
        }
        Registry::get("Log")->log("count document inserts:" . DbIndexer::$count_insert);
        //DbIndexer::$count_insert;
    }

    //одноразоая функция для создания индекса по мкту
    public function table_indexer($row)
    {
        $words = str_replace(",", " ", $row['RU']);
        $words = $this->cheked_mktu($words);
        $words = explode(" ", $words);
        $words = array_unique($words);
        $words = array_filter($words);
        $mktu_catalog_id = $row['id'];
        foreach ($words as $word)
        {

            $mktu_word_id = $this->DbIndexer->add_field("mktu_word", $word);
            $this->DbIndexer->add_index("word", array(
                "mktu_catalog_id", "mktu_word_id", $mktu_catalog_id, $mktu_word_id), "mktu");
        }
    }

    /** Привязка подклассов документа к справочнику МКТУ */
    protected function binding_sub_classes(array $sub_class, $doc_id)
    {
        //TODO : Символьный отключен по указу Эдуарда 29.03.14
        return;
        $keys = array_keys($sub_class);
        foreach ($keys as $class_id)
        {

            //Получить массив связей сохранить связи
            //TODO: Создать условие записи документов в которых не произошла привязка подклассов к справочнику . 
            if ($array_rel = $this->SearchSubClass->run($class_id, $doc_id))
                $this->DbIndexer->insert_doc_mktu_rel($array_rel);
        }
    }

//Взять полсденюю публикацию изменений
    protected function get_published($html)
    {
        if (preg_match_all('#<I>.*?:</I>\s<B><a.*?>(.*?)</a></B>#i', $html, $p731_date))
        {
            $date = $p731_date[1][count($p731_date[1]) - 1];
            return $this->format_date($date);
        }
        return FALSE;
    }

    protected function format_date($date)
    {
        if (!$date)
            return "";
        $date_arr = explode(".", $date);
        return $date_arr[2] . $date_arr[1] . $date_arr[0];
    }

    /**
     * сгенерировать путь до файла поля 540 
     */
    protected function generate_p540_file($p540_file_path, $html_file_path)
    {
        if (empty($p540_file_path) || empty($p540_file_path))
            return '';
        $p540_file_path = preg_replace('#/+$#', '', $p540_file_path);
        return $p540_file_path . '/' . pathinfo($html_file_path, PATHINFO_FILENAME) . '.txt';
    }

//href картинка в документе
    protected function get_img_href($html)
    {
        if (preg_match_all('#<IMG\s+SRC="(.*?)"#i', $html, $img_res))
        {

            foreach ($img_res[1] as $img_name)
            {
                if (!strstr($img_name, "RFP_LOGO"))
                {
                    return $img_name;
                }
            }
        }
        return FALSE;
    }

//----------------------------------------
//href скан заявления
    protected function get_list1_href($html)
    {
        if (preg_match_all('#<A HREF="(.*?)".*?>Лист 1</A>#i', $html, $list1_res))
        {
            return $list1_res[1][0];
        }
        return FALSE;
    }

    protected function cheked_space($value)
    {
        $value = preg_replace("#\s{2,10}#", " ", $value);
        $value = preg_replace("#;\s{1,10}#", ";", $value);
        //$value = preg_replace("#,\s{1,10}#", ",", $value);
        $value = preg_replace("#^\s+-\s+#", "", $value);
        return $value;
    }

    protected function cheked_mktu($value)
    {
        $punctuation = array("{", "}", ':', ')', '(', '[', ']', '/', '"', "\\", '*', '.', '—', '-');
        //убрать лишние пробелы
        $value = str_replace($punctuation, "", $value);
        $value = $this->cheked_space($value);
        //TODO : Реализовать замену всех условий => (\s|,)АБ{1,2}(\s|,) 
        $value = preg_replace("#(\s|,)[a-яА-Яa-zA-Z0-9]{1,2}(\s|,)#", " ", $value);
        return trim($value);
    }

    /** получить подклассы мкту , разбивает полученные классыф на подклассы через разделитель ; */
    protected function get_sub_class()
    {
        if (empty($this->mktu['arr']))
            return;
        foreach ($this->mktu['arr'] as $key => $mktu)
        {
            $this->mktu['sub'][$key] = explode(";", $mktu);
            $this->mktu['sub'][$key] = array_filter($this->mktu['sub'][$key]);
            $this->mktu['sub'][$key] = array_unique($this->mktu['sub'][$key]);
        }
        return $this->mktu['sub'];
    }

    /**
     * Сохранение / обновление результатов
     * @param string $save_method требуемый метод для сохранение результата
     * @param int $doc_data_id если требуется обновление , то параметр обязателен указывает какой документ обновить ключ на doc_data.id
     * если 2 параметр указан формируется массив где fields => текущие обработанные поля документа и doc_data_id id обновляемого документа 
     */
    public function save($save_method, $doc_data_id = NULL)
    {
        if (method_exists($this->DbIndexer, $save_method))
        {
            $data = $this->fields;
            if (!empty($doc_data_id))
            {
                $data = array(
                    "fields" => $this->fields,
                    "id" => (int) $doc_data_id);
            }

            $id = $this->DbIndexer->$save_method($data);

            $this->DbIndexer->update_link_date($this->fields['link_id']);

            //Добавить обновить МКТУ
            if (!empty($this->mktu['511']) AND count($this->mktu['arr']) > 0)
            {
                //Добавить мкту для документа переработанная функция FHT . m4_html.doc_f511 
                $this->DbIndexer->insert_mktu($this->mktu['arr'], $id);

                //Длюавить мкту в doc_class_rel переработанная функция FKF m4_fp.doc_class_rel
                $this->DbIndexer->delete_doc_class_rel($id);
                $this->DbIndexer->insert_doc_class_rel($this->mktu['arr'], $id);
                //Создать индекс для документ а
                if (!empty($this->mktu['sub']))
                {
                    $this->insert_doc_fraza($this->mktu['sub'], $id);
                    Registry::get("Log")->log("binding_sub_classes :");
                    $this->binding_sub_classes($this->mktu['sub'], $id);
                    Registry::get("Log")->log("exit binding_sub_classes");
                }
            }
            //если есть модификации сохраняем
            if (!empty($this->notice[1]['hash']))
            {
                //print_r($this->notice);
                $this->DbIndexer->insert_doc_modification($id, $this->notice, $this->modification_fields);
            }

            Registry::get("Log")->log("save => doc_number : " . $this->fields['doc_number'] . " doc_id : " . $id);
            Registry::get("Log")->log("save MKTU => count : (" . count($this->mktu['arr']) . ")");
            Registry::get("Log")->log("save doc_modification => : count(" . count($this->notice) . ")");

            $this->memory_clear();
            Registry::get("Log")->log("memory_clear");
            Registry::get("Log")->log("==========================NEXT=================================>");
        }
        else
        {
            throw new Exception("\n Method not exist \n");
        }
    }

    private function memory_clear()
    {
        $this->mktu = array();
        $this->fields = array();
        $this->modification_fields = array();
        $this->html = NULL;
    }

}

?>
