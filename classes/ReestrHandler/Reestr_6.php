<?php

/**
 * Обработчик документов из 6 реестров
 * @author Михаил Орехов
 */
class Reestr_6 extends ReestrAbstract {

    //put your code here 
    public function parse($row)
    {
        if (!$html = $this->select_html($row['doc_html_file']))
            return false;
        //Обработать стандратные поля
        $this->set_default_fields($row);  
        //Получаем поля документа
        $p731 = $this->get_p731($html);
        $p732 = $this->get_p732($html);
        $status = $this->FidDocStatus->parse_status_id($html, $reestr_id);
        $this->fields['link_number'] = $this->get_link_number($link);
        $this->fields['p732'] = $p732['732'];
        $this->fields['p731'] = $p731['731'];
        $this->fields['p732_init'] = $p732['732_init'];
        $this->fields['p731_init'] = $p731['731_init'];
        $this->fields['p731_date'] = $this->get_published($html);
        $this->fields['p210'] = $this->get_p210($html);
        $this->fields['p111'] = $this->get_p111($html);
        $this->fields['p740'] = $this->get_field(740) ; 
        $this->fields['p750'] = $this->get_field(750); 
        $this->fields['status_id'] = ( $status['id'] ? $status['id'] : 4);
        $this->fields['status_date'] = $status['date'];
        $this->fields['doc_img_link'] = $this->get_img_href($html);
        $this->fields['doc_list1_link'] = $this->get_list1_href($html);
        //print_r($html);
    }
    
    
    /** Получить DocNumber */
    protected function get_link_number($link)
    {
        if (preg_match("#DocNumber=(\d+)#", $link, $link_num_res))
        {
            return $link_num_res[1];
        }
    }

    // Номер регистрации
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

    //----------------------------------
    // Номер заявки
    protected function get_p210($html)
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
    protected function get_date_field($field, $html)
    {
        //exit($html);
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

    //----------------------------------
    // Правообладатель
    protected function get_p732($html)
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
                $p732 = array(
                    "732" => trim($p732_res[1][count($p732_res[1]) - 1]),
                    "732_init" => trim($p732_res[1][0]));
                return $p732;
            }
        }
        return FALSE;
    }

    //Взять полсденюю публикацию изменений
    protected function get_published($html)
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

    public function save($save_method)
    {
        if (method_exists($this->DbIndexer, $save_method))
        {
            $this->DbIndexer->$save_method($this->fields) ; 
        } else {
            throw new Exception(" Method not exist ");
        }
    }

}

?>
