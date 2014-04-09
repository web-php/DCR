<?php

/**
 * Description Класс обращения к ядру систему с целью символьного поиска по списку мкту
 * 
 * @author Михаил Орехов
 */
class SearchSubClass {

    private $CONFIG = array();
    private $sub_class = array();
    private $Pdo;
    private $symbolic;
    private $map_field = array(
        "object_id" => "kf_id"
    );

    public function __construct()
    {
        include_once 'OPSCore.php';
        //Получить обьекты из реестра
        $this->CONFIG = Registry::get("CONFIG");
        $this->Pdo = Registry::get("Pdo");
        //Подключаюсь к ядру 
        $core = new Edwaks\OPSCore();
        $core->Init();
        $core->db_connect(
                $this->CONFIG['DB']['BASE_HTML']['HOST'], $this->CONFIG['DB']['BASE_HTML']['BASE'], $this->CONFIG['DB']['BASE_HTML']['USER'], $this->CONFIG['DB']['BASE_HTML']['PASS']);
        $agents = $core->Agents;
        $agents->Init();
        $this->symbolic = $agents->Symbolic;
        $this->symbolic->Init();
    }

    private function get_sub_class($class_id)
    {
        if (empty($this->sub_class[(int) $class_id]))
        {
            //Выборка по оригинальному справочнику мкту:
            //$sql = "SELECT id as mktu_catalog_id , RU as suggestion FROM mktu_catalog WHERE class = $class_id AND RU IS NOT NULL";
            //Выборка по модернизированому справочнку мкуту
            $sql = "SELECT mktu_catalog_id , kf as suggestion 
                    FROM mktu_catalog_rel
                    LEFT JOIN mktu_catalog ON mktu_catalog.id = mktu_catalog_rel.mktu_catalog_id
                    WHERE mktu_catalog.class = $class_id ;";
            $dbres = $this->Pdo['HTML']->query($sql);
            $row = $dbres->fetchAll(PDO::FETCH_ASSOC);
            $this->sub_class[$class_id] = $row;
        }
        return $this->sub_class[$class_id];
    }

    /**
     * Сформировать параметр array_in для  symbolic->run 
     * Получить список слов для докмента , для определенного класса
     */
    private function get_array_in($class_id, $doc_id)
    {
        $sql = "SELECT DISTINCT 
                    dcr_words.id as id , dcr_words.words as text 
                FROM dcr_words 
                LEFT JOIN dcr_words_rel ON dcr_words_rel.words_id = dcr_words.id
                LEFT JOIN dcr_kf511_rel ON dcr_kf511_rel.kf_id = dcr_words_rel.kf_id 
                LEFT JOIN dcr_fraza_rel ON dcr_fraza_rel.fraza_id = dcr_kf511_rel.fraza_id
                WHERE 
                    dcr_fraza_rel.class_id = $class_id 
                        AND 
                    dcr_fraza_rel.doc_id = $doc_id ;";
        //print_r($sql);
        $dbres = $this->Pdo['HTML']->query($sql);
        $row = $dbres->fetchAll(PDO::FETCH_ASSOC);
        return $row;
    }

    /** Установить параметр sqlQuery для формирования массива params параметра symbolic->run */
    private function get_sqlQuery($class_id)
    {

        $sqlQuery = "SELECT DISTINCT dcr_kf511.id AS object_id, dcr_words.id AS id, dcr_kf511.kf511 AS comparison 
                     FROM dcr_kf511
                     LEFT JOIN dcr_words_rel ON dcr_words_rel.kf_id = dcr_kf511.id
                     LEFT JOIN dcr_kf511_rel ON dcr_kf511_rel.kf_id = dcr_words_rel.kf_id
                     LEFT JOIN dcr_fraza_rel ON dcr_fraza_rel.fraza_id = dcr_kf511_rel.fraza_id
                     INNER JOIN dcr_words    ON dcr_words.id = dcr_words_rel.words_id
                     
                     WHERE dcr_fraza_rel.class_id = " . (int) $class_id . "
                     AND dcr_words.id IN (:array_ids) ";
        return $sqlQuery;
    }

    /** Получить массив params */
    private function get_params($suggestion, $class_id)
    {
        $params = array(
            "suggestion" => $suggestion,
            "fields" => array(
                "text" => "text",
                "comparison_id" => "id",
                "comparison" => "comparison",
                "id" => "object_id"
            ),
            "minimalSimilarPercent" => 75 ,
            "minimalPercent" => 30 ,
            //"debugLevel" => 2 ,
            "sorted" => SORT_DESC,
            "sqlQuery" => $this->get_sqlQuery($class_id)
        );
        return $params;
    }
    /** Выполнить символьный поиск */
    public function run($class_id, $doc_id)
    {
        Registry::get("Log")->log("run symbolic : $class_id");
        foreach ($this->get_sub_class($class_id) as $suggestion)
        {
            
            $arr = $this->symbolic->run(
                    $this->get_array_in($class_id, $doc_id), $this->get_params($suggestion['suggestion'], $class_id));
            //print_r($arr);
            if (empty($arr['data']))
                continue;
            //print_r($arr);
            foreach ($arr['data'] as $data)
            {
                $arr_rel[] = array(
                    "doc_id" => $doc_id,
                    "kf_id" => $data['object_id'],
                    "mktu_catalog_id" => $suggestion['mktu_catalog_id'],
                    "relevance" => $data['result']);
            }
        }
        return $arr_rel;
    }

}