<?php

/**
 * Description of Reestr_7
 * разобрать документы из 7го реестра , наследовать от обработчика 6го реестра , дополняет родителя 2 мя методами . 
 * @author Михаил Орехов
 */
require __DIR__ . '/Reestr_6.php';

class Reestr_7 extends Reestr_6 {

    public function parse($row)
    {
        parent::parse($row);
        //TODO : ПРоверить генерацию пути для файла , разобартся с $config['DATADIR']
        $this->fields['p540_file'] = $this->generate_p540_file($row['p540_file_path'], $config['DATADIR'] . $row['doc_html_file']);
        $this->fields['p540_txt'] = $this->get_p540_txt($p731['p210']);
    }

    /**
     * Получить поле p540_txt при обходе 7го рестра из заявки 6го реестра
     * @param int $doc_number Искомый номер документв , извлекаеттся из 210 поля документов 7го реестра
     * @return string поле p540_txt
     */
    private function get_p540_txt($doc_number)
    {
        if (empty($doc_number))
            return '';
        $p540_txt = $this->DbIndexer->get_p540_txt($doc_number);
        return $p540_txt;
    }

    /** сгенерировать путь до файла поля 540 */
    private function generate_p540_file($p540_file_path , $html_file_path)
    {
        if (empty($p540_file_path) || empty($p540_file_path))
            return '';
        $p540_file_path = preg_replace('#/+$#', '', $p540_file_path);
        return $p540_file_path . '/' . pathinfo($html_file_path, PATHINFO_FILENAME) . '.txt';
    }

}

?>
