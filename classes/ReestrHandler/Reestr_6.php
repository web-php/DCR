<?php

/**
 * Обработчик документов из 6 реестров
 * @author Михаил Орехов
 */
class Reestr_6 extends TradeMark {

    protected function parse_mktu()
    {

        Registry::get("Log")->log("get_mktu");
        $this->mktu['arr'] = $this->alter_get_mktu();

        Registry::get("Log")->log("set mktu status");
        $this->mktu['511'] = (!empty($this->mktu['arr']) ? TRUE : FALSE);

        Registry::get("Log")->log("get_sub_class");
        $this->mktu['sub'] = $this->get_sub_class();
    }

    private $split_marker = array(
        '\(511\)',
        'Факсимильные изображения',
        'Извещения об изменениях, относящихся к регистрации товарного знака'
    );
    private $clean_from = array(
        'Классы МКТУ и перечень товаров и/или услуг:',
        'Перечень товаров дополнен изделиями класса '
    );

    private function extract_511_paragraph()
    {

        if (!strstr($this->html, "(511)"))
            return '';
        $split_pattern = '/(' . implode('|', $this->split_marker) . ')/i';
        $html_parts = preg_split($split_pattern, $this->html);
        if (isset($html_parts[1]))
            return trim(str_replace($this->clean_from, '', $html_parts[1]));
        else
            return '';
    }

    /**
     *  Альтернативаня функция получения мкту в документе  ,в 10 раз быстрей метода с регулярками
     */
    private function alter_get_mktu()
    {
        $mktu_arr = array();
        $this->shd->load($this->html);
        Registry::get("Log")->log("select mktu class start:");
        $mktu = $this->shd->find("p[class=p1] b");
        foreach ($mktu as $val)
        {
            $mktu_str = $val->innertext;
            if (preg_match("#^(?P<mktu>[0-9]{1,2}\s)#", $mktu_str, $mktu_num))
            {
                $mktu_str = trim($mktu_str);
                $mktu_num = str_pad(trim($mktu_num['mktu']), 2, "0", STR_PAD_LEFT);
                $mktu_str = str_replace($mktu_num, "", $mktu_str);
                
                $mktu_arr[$mktu_num] = $this->cheked_space($mktu_str);
            }
        }
        $this->shd->clear();
        return($mktu_arr);
    }

    /** получить классы мкту */
    private function get_mktu()
    {
        $mktu_arr = array();
        //найти все ключи МКТУ
        preg_match_all("#\D+(?P<mktu>[0-9]{1,2}\s)#", $this->mktu['511'], $mktu_num);
        if (empty($mktu_num['mktu']))
            return "";
        //найти все значения МКТУ
        foreach ($mktu_num['mktu'] as $mktu)
        {
            if ($mktu > 45)
                continue;
            preg_match("#$mktu.*?(?P<value>.*?)(\.|;)</b></p>#im", $this->mktu['511'], $mktu_text);
            if (!empty($mktu_text['value']))
            {
                //убрать лишние пробелы
                $mktu_text['value'] = $this->cheked_mktu($mktu_text['value']);
                $mktu = str_pad(trim($mktu), 2, "0", STR_PAD_LEFT);
                $mktu_arr[$mktu] = trim(str_replace("-", "", $mktu_text['value']));
            }
        }
        return $mktu_arr;
    }

    protected function get_designation($html)
    {
        $marker = array("Указание об изменениях", "Характер внесенных изменений");
        foreach ($marker as $value)
        {
            if ($designation = $this->get_field($value, $html))
                return $designation;
        }
        return "";
    }

    /** Получить поля характерные для 6-7 реестра */
    protected function get_modification_field( $part )
    {
        $notice['date_pub'] = $this->get_published($part);
        $notice['type_edits'] = $this->get_type_edits($part);
        $notice['designation'] = $this->get_designation($part);
        $notice['p580'] = $this->format_date(
                $this->get_field("Дата внесения изменений в Госреестр", $part));
        $notice['p732'] = $this->get_field(732, $part);
        $notice['p791'] = $this->get_field(791, $part);
        $notice['p793'] = $this->get_field(793, $part);
        $notice['p740'] = $this->get_field(740, $part);
        $notice['p770'] = $this->get_field(770, $part);
        $notice['p771'] = $this->get_field(771, $part);
        $notice['p750'] = $this->get_p750($part);
        return $notice;
    }

}

?>
