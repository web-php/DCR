<?php

/**
 * Description of DocumentIndexer
 *
 * @author Михаил Орехов
 */
require_once __DIR__ . '/ReestrHandler/ReestrAbstract.php';
require_once __DIR__ . '/ReestrHandler/TradeMark.php';
require_once __DIR__ . '/HandlerFactory.php';
require_once __DIR__ . '/LoadFile.php';
require_once __DIR__ . '/DbIndexer.php';
require_once __DIR__ . '/FidDocStatus.php';
require_once __DIR__ . '/SearchSubClass.php';

class DocumentIndexer {

    private $reestr_id;
    private $mode;
    private $doc_id;
    private $docs_added = 0;
    private $docs_patched = 0;
    private $HandlerReestr;
    private $config = array();

    public function __construct(array $router) {
        $this->reestr_id = $router['reestr_id'];
        $this->mode = $router['mode'];
        $this->doc_id = $router['doc_id'];
        $this->all = $router['all'];
        $this->field_map = $router['field_map'];
        $this->config = Registry::get("CONFIG");
        $this->DbIndexer = Registry::get("DbIndexer");
    }

    /**
     * Запуск индексатора документов
     */
    public function run() {
        foreach ($this->reestr_id as $reestr_id) {
            //Получить экземпляр класса для работы с требуемым реестром
            $this->HandlerReestr = HandlerFactory::GetInstance($reestr_id);

            $this->HandlerReestr->set_cash("fraza");
            //$this->HandlerReestr->loading_cash();
            $method = $this->mode;
            $this->$method($reestr_id);
            //$this->HandlerReestr->parse("");
        }
    }

    /**
     * Режим запуска обработчика документов по умолчанию , получает новые документы из таблицы link индексирует их
     */
    private function normal_start_mode($reestr_id) {
        while ($row = $this->DbIndexer->get_next_link($reestr_id)) {

            $this->HandlerReestr->parse($row);

            if (!empty($this->HandlerReestr->html)) {
                $this->HandlerReestr->save("insert_doc_data");
                $this->docs_added++;
            }
        }
    }

    /** Создать индекс для МКТУ  */
    private function add_index_mode() {
        Registry::get("Log")->log("START INDEXER");
        while ($row = $this->DbIndexer->get_mktu_ru()) {
            Registry::get("Log")->log("add index :" . $row['RU']);
            $this->HandlerReestr->table_indexer($row);
        }
    }

    private function field_map_mode($reestr_id) {
        //$this->field_map
        while ($row = $this->DbIndexer->select_doc_update($reestr_id, $this->doc_id, $this->all)) {
            $this->HandlerReestr->get_field_map($row, $this->field_map);
            $this->HandlerReestr->save("update_doc_data", $row['doc_data_id']);
        }
        Registry::get("Log")->log("exit p511 mode");
    }

    /**
     * Режим обновления документов 
     */
    private function doc_update_mode($reestr_id) {
        while ($row = $this->DbIndexer->select_doc_update($reestr_id, $this->doc_id, $this->all)) {
            $this->HandlerReestr->parse_update($row);
            if (!empty($this->HandlerReestr->html)) {
                Registry::get("Log")->log("update_doc_data");
                $this->HandlerReestr->save("update_doc_data", $row['doc_data_id']);
                $this->docs_patched++;
            }
        }
    }

    /**
     * TODO : После перепроверки всех возможных результатов 
     * Режим обработки p511 поля с его последующей индексацией
     */
    private function p511_mode($reestr_id) {
        $i = 0;
        while ($row = $this->DbIndexer->select_doc_update($reestr_id, $this->doc_id, $this->all)) {
            $i++;
            $this->HandlerReestr->p511($row);
            //если установлено количесто тестовых документов то после их окончания работа завершится
            if ($this->config['TEST_RUN'])
                if ($i == $this->config['TEST_RUN'])
                    break;
        }

        Registry::get("Log")->log("exit p511 mode");
    }

    /** Получить количество добавленных документов */
    public function get_docs_added() {
        return $this->docs_added;
    }

    /** Получить количестов пропатченых документов */
    public function get_docs_patched() {
        return $this->docs_patched;
    }

    public function __destruct() {
        $this->HandlerReestr->set_cash("fraza");
        Registry::get("Log")->log("exit programm");
    }

}

?>
