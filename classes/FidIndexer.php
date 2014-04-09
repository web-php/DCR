<?php

require_once __DIR__ . "/FidDocStatus.php";
require_once __DIR__ . "/FidSubClass.php";
require_once __DIR__ . "/DbIndexer.php";

class FidIndexer {

    const REESTR_9 = 9;
    const REESTR_7 = 7;
    const REESTR_11 = 11;

    private $datadir;
    private $FidDocStatus;
    private $FidSubClass;
    private $DbIndexer;
    private $docs_added = 0;
    private $docs_patched = 0;
    private $log_id = "";
    private $test_id = Cfg::TEST_ID; // протестировать документ exemple: 3 , 994722 , 998023 - 9 реестр с п
    private $link_id; // = 211822653;

    //----------------------------------

    public function __construct($pdo)
    {
        $this->DbIndexer = new DbIndexer($pdo);
        $this->FidDocStatus = new FidDocStatus($pdo);
        $this->FidSubClass = new FidSubClass($pdo);
    }

    /**
     * Запуск обработчика ссылок 
     * @param int $reestr_id Из какого реестра брать ссылки для обработки , может быть пустым , тогда будут обрабатыватся все ссылки без исключения
     * @param string $mode режим запуска индексатора "normal_start"  - Обычный режим работы (Документы из link) 
     *                                               "patch_731_732" - Режим патча , пересобрать поля 731-732 с новой логикой (Документы из doc_data)
     *                                               "patch_111_211" - Режим патча , пересобрать поля 111-211 с новой логикой (Документы из doc_data) 9 реестр
     */
    public function run($reestr_id = '', $mode = 'normal_start')
    {
        $this->log_id = $reestr_id;
        //роутинг запускаемых функций
        $method = (string) $mode . "_mode";
        //Проверяем есть ли запускаемый метод в классе , если есть запускаем его 
        if (method_exists($this, $method))
            $this->$method($reestr_id);
        else
        {
            Registry::get("Log")->log("the method is not defined => " . $method , "err");
            exit;
        }
    }

    /**
     * пройти все патчи + пройти старые документы у которых измнилось что то в документе 
     */
    private function doc_update_mode($reestr_id = '')
    {
        while ($row = $this->DbIndexer->select_doc_update($reestr_id, $this->link_id)) {

            if (!$html = $this->select_html($row['doc_html_file']))
            {
                Registry::get("Log")->log( "file not found : " , "err" );
                Registry::get("Log")->log( $row['doc_html_file'] );
                continue;
            }
            
            Registry::get("Log")->log( "Update file : " . $row['doc_html_file'] );

            $p731 = $this->get_p731($html);
            $p732 = $this->get_p732($html);
            $status = $this->FidDocStatus->parse_status_id($html, $reestr_id);

            //собрать массив для обновления 
            $fields['p210'] = $this->get_p210($html);
            $fields['p111'] = $this->get_p111($html);
            $fields['p220'] = $this->get_date_field(220,$html);
            $fields['p151'] = $this->get_date_field(151,$html);   
            $fields['p731'] = $p731[0];
            $fields['p731_init'] = $p731[1];
            $fields['p732'] = $p732[0];
            $fields['p732_init'] = $p732[1];
            $fields['p731_2_date'] = $this->get_published($html);
            $fields['p750'] = $this->get_p750($html);
            $fields['doc_img_link'] = $this->get_img_href($html);
            $fields['doc_img_file'] = $row['doc_img_file'];
            //Установить статус документа , если статус отсутствет установить id = 4 "нет данных"
            $fields['doc_status_id'] = ( $status['id'] ? $status['id'] : 4 ) ; 
            $fields['doc_status_date'] = ( $status['date'] ? $status['date'] : "NOW ()"); 
            
            
            $fields['id'] = $row['dd_id'];
            //print_r($fields);
            $this->docs_patched += $this->DbIndexer->update_doc_data($fields);
            $this->DbIndexer->update_link_date($row['id']);
            $this->DbIndexer->update_global_date($row['dd_id']);
            unset($html);
        }
    }

    /**
     * пересобрать документы из doc_data у которых появились изображения в link
     * @param $reestr_id int реест документов
     * TODO : Добавить обработку остальных тегов документа если нужно .
     */
    private function patch_doc_img_html_mode($reestr_id = "")
    {
        while ($row = $this->DbIndexer->patch_doc_img_html($reestr_id)) {
            //Получить html
            if (!$html = $this->select_html($row['doc_html_file']))
                continue;

            $doc_img_link = $this->get_img_href($html);
            $status = $this->FidDocStatus->parse_status_id($html);
            $this->docs_patched += $this->DbIndexer->update_img_html($row['dd_id'], $doc_img_link, $row['doc_img_file'], $status['id'], $status['date']);
            //print_r($row);
            unset($html);
        }
    }

    /**
     * Запуск патчера документов . режим patch_731_732 . Обработка документов из doc_data c целью перепарсинга полей 731-732
     */
    private function patch_731_732_mode($reestr_id)
    {
        //Получаем документы , если нужен тест 1го документа добавить id.doc_data
        while ($row = $this->DbIndexer->get_next_doc($reestr_id, $this->test_id)) {
            if (!$html = $this->select_html($row['doc_html_file']))
                continue;
            $p731 = $this->get_p731($html);
            $p732 = $this->get_p732($html);
            $p731_date = $this->get_published($html);
            //обновить статистику получаемых документов
            $this->docs_patched += $this->DbIndexer->update_731($row['id'], $p731[0], $p732[0], $p731[1], $p732[1], $p731_date);
            //TODO : реализовать конвертирование ASCII в UTF-8

        }
    }

    /**
     * Запуск патчера документов . режим p220 .
     */
    private function patch_220_mode($reestr_id)
    {
        
        //Получаем документы , если нужен тест 1го документа добавить id.doc_data
        while ($row = $this->DbIndexer->get_noupdate_p220($reestr_id, $this->test_id)) {
            if (!$html = $this->select_html($row['doc_html_file']))
                continue;
            $p220 = $this->get_date_field(220,$html);
            $p151 = $this->get_date_field(151,$html);
            //обновить статистику получаемых документов
            $this->docs_patched += $this->DbIndexer->update_220($row['id'], ($p220 ? $p220 : NULL) , ($p151 ? $p151 : NULL));
            
        }
    }

    /**
     * Запуск патчера документов . режим patch_111_211 . Обработка документов из doc_data c целью перепарсинга полей 111 - 210
     */
    private function patch_111_210_mode()
    {
        while ($row = $this->DbIndexer->get_doc_9_reestr($this->test_id)) {
            $html = $this->select_html($row['doc_html_file']);
            $p210 = $this->get_p210($html);
            $p111 = $this->get_p111($html);
            //Обновить документ
            $this->docs_patched += $this->DbIndexer->update_9_reestr($row['id'], $p210, $p111);
        }
    }

    function insert_doc_data(array $row, $html = '')
    {

        $p540_file = '';
        $p540_txt = '';
        $link = $row['doc_link'];
        $link_id = $row['id'];
        $p540_file_path = $row['p540_file_path'];
        $reestr_id = $row['reestr_id'];
        if (!isset($row['doc_list1_file']))
        {
            $row['doc_list1_file'] = '';
        }

        $link_number = $this->get_link_number($link);
        $p210 = $this->get_p210($html);
        $p111 = $this->get_p111($html);
        $p731 = $this->get_p731($html);
        $p732 = $this->get_p732($html);
        $p750 = $this->get_p750($html);
        $p731_date = $this->get_published($html);
        $doc_img_link = $this->get_img_href($html);
        $doc_list1_link = $this->get_list1_href($html);

        //для 9-ого реестра особые условия
        if ($reestr_id == self::REESTR_9)
        {
            $p540_txt = $this->get_p540_txt($html);
        }
        //для 7 реестра получить p540_txt из документа 6го реестра
        if ($reestr_id == self::REESTR_7)
        {
            $p540_txt = $this->get_p540_txt_7_reestr($p210);
        }
        if ($row['doc_img_file'])
        {
            $p540_file = $this->generate_p540_file($p540_file_path, $this->datadir . $row['doc_html_file']);
        }
        $status = $this->FidDocStatus->parse_status_id($html, $reestr_id);
        
        $doc_id = $this->DbIndexer->insert_doc_data(
                $link_id, $link_number, $p732[0], $p731[0], $p732[1], $p731[1], $p731_date, $p210, $p111, $p540_file, $p540_txt, $row['doc_link'], $row['doc_html_file'], $row['doc_img_file'], $row['doc_list1_file'], $reestr_id, ( $status['id'] ? $status['id'] : 4 ) , $status['date'], $doc_img_link, $doc_list1_link);
        if (!$doc_id)
        {
            continue;
        }
        //$this->FidSubClass->process_subclasses($html , $doc_id);
        $this->docs_added += $this->DbIndexer->update_link_date($link_id);
    }

    /**
     * Запуск режима normal_start , Собрать не обработанные ссылки , добавить их в таблицу doc_data
     * @param int $reestr_id 
     */
    private function normal_start_mode($reestr_id)
    {
        while ($row = $this->DbIndexer->get_next_link($reestr_id)) {
            //Обработать документ
            if (!$html = $this->select_html($row['doc_html_file']))
                continue;
            else
                $this->insert_doc_data($row, $html);
        }
    }

    /**
     * Получить поле p540_txt при обходе 7го рестра из аналогичного документа 6го реестра
     * @param int $doc_number Искомый номер документв , извлекаеттся из 210 поля документов 7го реестра
     * @return string поле p540_txt
     */
    private function get_p540_txt_7_reestr($doc_number)
    {
        $p540_txt = $this->DbIndexer->get_p540_txt_7_reestr($doc_number);
        return $p540_txt;
    }

    //----------------------------------
    private function generate_p540_file($p540_file_path, $html_file_path)
    {
        $p540_file_path = preg_replace('#/+$#', '', $p540_file_path);
        return $p540_file_path . '/' . pathinfo($html_file_path, PATHINFO_FILENAME) . '.txt';
    }

    //----------------------------------
    private function get_link_number($link)
    {
        if (preg_match("#DocNumber=(\d+)#", $link, $link_num_res))
        {
            return $link_num_res[1];
        }
    }

    //----------------------------------
    // Воспроизведение знака, 9-ый реестр
    private function get_p540_txt($html)
    {
        if (preg_match("#<B>\(540\)</B>\s*Воспроизведение знака</TD>.*?<B>(.*?)</B>#i", $html, $p540_res))
        {
            return html_entity_decode(rtrim(ltrim($p540_res[1])), 0, 'UTF-8');
        }
        return FALSE;
    }

    // Адрес переписки
    private function get_p750($html)
    {
        if (preg_match("#<I>Адрес для переписки:</I><BR><B>(.*?)</B>#i", $html, $p750_res))
        {
            return rtrim(ltrim($p750_res[1]));
        }
        return FALSE;
    }

    // Номер регистрации
    private function get_p111($html)
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

    //----------------------------------
    // Номер заявки
    private function get_p210($html)
    {
        if (preg_match("#\(210\)\s*<I>.*?</I>\s*<B>(\d+)</B>#i", $html, $p210_res))
        {
            return trim($p210_res[1]);
            print $p210_res;
        }
        return FALSE;
    }

    /**
     * Получить поле c датой
     */
    private function get_date_field($field,$html)
    {
        //exit($html);
        if (preg_match("#\($field\).+?<B>(.*?)</B>#i", $html, $field_res))
        {
            $arr_date = date_parse(str_replace(".", "-", $field_res[1]));
            
            return $arr_date['year'].str_pad($arr_date['month'],2,0,STR_PAD_LEFT).str_pad($arr_date['day'],2,0,STR_PAD_LEFT);
        }
        return '';
    }

    /**
     * Получить Заявителя
     * @param string $html
     * @return array $p731 Верхнее и нижнее поле 
     */
    private function get_p731($html)
    {
        if (preg_match_all("#\(731\)\s*<I>.*?</I>\s*<BR><B>(.*?)</B>#i", $html, $p731_res))
        {
            $p731 = array(trim($p731_res[1][count($p731_res[1]) - 1]), trim($p731_res[1][0]));
            return $p731;
        }
        return FALSE;
    }

    //----------------------------------
    // Правообладатель
    private function get_p732($html)
    {
        $reg_arr[0] = "#\(732\)\s*<I>.*?</I><BR><B>(.*?)</B>#i";
        $reg_arr[1] = "#<I>Правообладатель.*?:</I><BR><B>(.*?)</B>#i";
        $reg_arr[2] = "#<B>\(732\)</B>\s*Имя и адрес владельца</TD><TD CLASS=CL1><B>(.*?)</B>#i"; //9 реестр
        //Правообладатель
        foreach ($reg_arr as $reg)
        {
            if (preg_match_all($reg, $html, $p732_res))
            {
                //print_r($p732_res);
                $p732 = array(trim($p732_res[1][count($p732_res[1]) - 1]),
                    trim($p732_res[1][0]));
                return $p732;
            }
        }
        return FALSE;
    }

    //Взять полсденюю публикацию изменений
    private function get_published($html)
    {
        //<I>Дата публикации:</I> <B><A HREF='http://www.fips.ru/cdfi/fips.dll?ty=29&docid=60&cl=9&path=http://195.208.85.248/Archive/TM/2007FULL/2007.03.12/DOC/DOCURUWK/DOC000V1/D00000D1/00000060/document.pdf' TARGET='_blank' TITLE='Официальная публикация в формате PDF'>12.03.2007</A></B>
        if (preg_match_all('#<I>.*?:</I>\s<B><a.*?>(.*?)</a></B>#i', $html, $p731_date))
        {
            $date = explode(".", $p731_date[1][count($p731_date[1]) - 1]);
            return $date[2] . $date[1] . $date[0];
        }
        return FALSE;
    }

    //----------------------------------------
    //href картинка в документе
    private function get_img_href($html)
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
    private function get_list1_href($html)
    {
        if (preg_match_all('#<A HREF="(.*?)".*?>Лист 1</A>#i', $html, $list1_res))
        {
            return $list1_res[1][0];
        }
        return FALSE;
    }

    //----------------------------------
    public function set_datadir($datadir)
    {
        $this->datadir = $datadir;
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

   
    /**
     * Получить html содержмое из файла
     */
    private function select_html($doc_html_file)
    {
        //В режиме разработки брать файл через отладочную заглушку , в боевом режиме брать с диска сервера
        if (Cfg::ENVIRONMENT == "development")
        {
            $html = $this->get_file($doc_html_file);
        }
        else
        {
            $doc_html_file = $this->datadir . $doc_html_file;
            if (!file_exists($doc_html_file))
            {
                Registry::get("Log")->log("html not exists" , "err");
                return FALSE;
            }
            $html = file_get_contents($doc_html_file);
        }
        return $html;
    }

    /**
     * отладочный метод для получения документов и изображений без установки скрипта в отладочную директорию
     * @param string $html_patch_file 
     */
    private function get_file($html_patch_file)
    {
        $server = "me4soft.ru";
        $dev_path = "/fp/";
        $path = "http://" . $server . $dev_path . substr($html_patch_file, 7);   //настроить относительно
        //Получить доступ к файлу
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, Cfg::BASE_AUTH_USER . ':' . Cfg::BASE_AUTH_PASS);
        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] == 200)
            return $content;
        else
            return FALSE;
    }

}

?>