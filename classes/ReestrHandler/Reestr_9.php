<?php

/**
 * Description of Reestr_9 , обработков документов из 9го реестра ФИПСА . 
 *
 * @author Михаил Орехов
 */
class Reestr_9 extends TradeMark {

    protected $marker_split = array(
        "Извещения", 
        "<hr color=\"black\">");
    public function parse($row)
    {
        parent::parse($row);
        //TODO : ПРоверить генерацию пути для файла , разобартся с $config['DATADIR']
        $this->fields['p540_file'] = $this->generate_p540_file($row['p540_file_path'], $this->config['DATADIR'] . $row['doc_html_file']);
        $this->fields['p540_txt'] = $this->get_p540_txt();
    }
    
    //TODO : заглушка до реализации определения языка документа.
    protected function binding_sub_classes(array $sub_class, $doc_id)
    {
        return ;
    }
    
    protected function parse_mktu()
    {
        $this->mktu['511'] = $this->get_511() ; 
        $this->mktu['arr'] = $this->get_mktu() ; 
        $this->mktu['sub'] = $this->get_sub_class();
        $this->mktu['lang'] = $this->get_field(811);
    }

    /** Воспроизведение знака, 9-ый реестр */
    private function get_p540_txt()
    {
        if (preg_match("#<B>\(540\)</B>\s*Воспроизведение знака</TD>.*?<B>(.*?)</B>#i", $this->html, $p540_res))
        {
            return html_entity_decode(rtrim(ltrim($p540_res[1])), 0, 'UTF-8');
        }
        return FALSE;
    }
    
    /** Получить все поля мкту */
    private function get_511()
    {

        if (preg_match_all("#\(511\).+?<B>(.*?)</B>#i", $this->html, $field_res))
        {
            if (!empty($field_res[1]))
            {
                return implode("\n\n", $field_res[1]);
            }
        }
        return "" ;
    }
    
    /** Разбить поле на состовляющие эллементы */
    private function get_mktu()
    {
        $mktu_arr = "";
        $f511 = explode("\n\n", $this->mktu['511']);
        foreach ($f511 as $key => $value)
        {
            if (is_numeric($value))
            {
                $num = str_pad(trim($value), 2, "0", STR_PAD_LEFT);
                $mktu_value = $this->cheked_mktu($f511[$key + 1]);
                $mktu_arr[$num] = addslashes($mktu_value);
            }
        }
        return $mktu_arr;
    }
    
    /** Получить тип изменения документа , относится к модификациям документа */
    protected function get_type_edits($html)
    {
        if (preg_match("#<TD CLASS=CL1 colspan=2><B>(?P<type_edits>.*?)</B></TD>#im", $html, $field_res))
        {
            return $this->DbIndexer->insert_type_edits(
                                $this->get_reestr() , trim($field_res["type_edits"]));
        }
        return '';
    }
    
    /** Получить поля характерные для 9 реестра */
    protected function get_modification_field($part)
    {
        $notice['type_edits'] = $this->get_type_edits($part);
        $notice['p732'] = $this->get_field(732 , $part) ;
        $notice['p791'] = $this->get_field(791 , $part) ;
        $notice['p793'] = $this->get_field(793 , $part) ;
        $notice['p740'] = $this->get_field(740 , $part) ;
        $notice['p750'] = $this->get_field(750 , $part) ;
        $notice['p770'] = $this->get_field(770 , $part) ;
        $notice['p771'] = $this->get_field(771 , $part) ;
        return $notice;
    }
    
}

?>
