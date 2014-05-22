<?php

/**
 * Description of DocumentIndexer
 *
 * @author Михаил Орехов
 */
require_once __DIR__ . '/ReestrHandler/ReestrAbstract.php';
require_once __DIR__ . '/ReestrHandler/TradeMark.php';
require_once __DIR__ . '/ReestrHandler/DataMigration.php';
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

    public function __construct(array $router)
    {
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
    public function run()
    {
        foreach ($this->reestr_id as $reestr_id)
        {
            //Получить экземпляр класса для работы с требуемым реестром
            $this->HandlerReestr = HandlerFactory::GetInstance($reestr_id);
            //$this->HandlerReestr->loading_cash();
            $method = $this->mode;
            $this->$method($reestr_id);
            //$this->HandlerReestr->parse("");
        }
    }

    private function reestr_migration_mode($reestr_id)
    {
        Registry::get("Log")->log("reestr_migration_mode run $reestr_id reestr");
        $this->HandlerReestr->run_migration($reestr_id);
    }

    /**
     * Режим запуска обработчика документов по умолчанию , получает новые документы из таблицы link индексирует их
     */
    private function normal_start_mode($reestr_id)
    {
        $this->HandlerReestr->set_cash("fraza");
        while ($row = $this->DbIndexer->get_next_link($reestr_id)) {

            $this->HandlerReestr->parse($row);

            if (!empty($this->HandlerReestr->html))
            {
                $this->HandlerReestr->save("insert_doc_data");
                $this->docs_added++;
            }
        }
    }

    /** Создать индекс для МКТУ  */
    private function add_index_mode()
    {
        Registry::get("Log")->log("START INDEXER");
        while ($row = $this->DbIndexer->get_mktu_ru()) {
            Registry::get("Log")->log("add index :" . $row['RU']);
            $this->HandlerReestr->table_indexer($row);
        }
    }

    private function field_map_mode($reestr_id)
    {
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
    private function doc_update_mode($reestr_id)
    {
        $this->HandlerReestr->set_cash("fraza");
        while ($row = $this->DbIndexer->select_doc_update($reestr_id, $this->doc_id, $this->all)) {
            $this->HandlerReestr->parse_update($row);
            if (!empty($this->HandlerReestr->html))
            {
                Registry::get("Log")->log("update_doc_data");
                $this->HandlerReestr->save("update_doc_data", $row['doc_data_id']);
                $this->docs_patched++;
            }
        }
    }

    /**
     * Режим обработки поля 540 . взять полу 540 , проиндесировать согласно алгоритму из задачи 446 redmine
     */
    private function p511_mode($reestr_id)
    {
        $this->HandlerReestr->set_cash("fraza");
        $i = 0;
        Registry::get("Log")->log("select disctinct doc_id ...");
        $doc_arr = $this->DbIndexer->select_id_distinct();


        while ($row = $this->DbIndexer->select_doc_update($reestr_id, $this->doc_id, $this->all)) {
            //заплатка TODO : удалить после перебора , брать только те документы которые еще не обрабатывались
            if (isset($doc_arr[$row['doc_data_id']]))
                continue;
            $i++;
            $this->HandlerReestr->p511($row);
            //если установлено количесто тестовых документов то после их окончания работа завершится
            if ($this->config['TEST_RUN'])
                if ($i == $this->config['TEST_RUN'])
                    break;
        }

        Registry::get("Log")->log("exit p511 mode");
    }

    /**
     * Режим проверки целостности документов 
     */
    private function p540_mode($reestr_id)
    {
        $i = 0;
        $this->HandlerReestr->set_cash("symbol");
        Registry::get("Log")->log("start indexer 540 fields...");
        while ($row = $this->DbIndexer->select_540($reestr_id, $this->doc_id, $this->all)) {
            $i++;
            $rel_count = $this->HandlerReestr->p540($row);
            //если установлено количесто тестовых документов то после их окончания работа завершится
            if ($this->config['TEST_RUN'])
                if ($i == $this->config['TEST_RUN'])
                    break;
        }
        Registry::get("Log")->log("Документов проиндексировано :" . $i);
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

    public function __destruct()
    {
        $this->HandlerReestr->set_cash("fraza");
        $this->HandlerReestr->set_cash("symbol");
        Registry::get("Log")->log("exit programm");
    }

}

?>
