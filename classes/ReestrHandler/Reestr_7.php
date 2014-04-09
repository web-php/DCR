<?php

/**
 * Description of Reestr_7
 * разобрать документы из 7го реестра , наследовать от обработчика 6го реестра , дополняет родителя 2 мя методами . 
 * @author Михаил Орехов
 */
require_once __DIR__ . '/Reestr_6.php';
class Reestr_7 extends Reestr_6 
{

    public function parse($row)
    {
        parent::parse($row);
        //TODO : ПРоверить генерацию пути для файла , разобартся с $config['DATADIR']
        $this->fields['p540_file'] = $this->generate_p540_file($row['p540_file_path'], $this->config['DATADIR'] . $row['doc_html_file']);
        $this->fields['p540_txt'] = $this->get_p540_txt($this->fields['p210']);
    }

    /**
     * Получить поле p540_txt при обходе 7го рестра из заявки 6го реестра
     * @param int $doc_number Искомый номер документв , извлекаеттся из 210 поля документов 7го реестра
     * @return string поле p540_txt
     */
    protected function get_p540_txt($doc_number)
    {
        if (empty($doc_number))
            return '';
        $p540_txt = $this->DbIndexer->get_p540_txt($doc_number);
        return $p540_txt;
    }
}

?>
