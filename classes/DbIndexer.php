<?php

/**
 * Логика обработки запросов  и обращений к базе данных программы индексатора документов 
 * @version dcr v2.0
 * @author Mikhail Orekhov <mikhail@edwaks.ru>
 */
class DbIndexer {

    private $pdo;
    private $pdo_html;
    private $prefix_mapping = array();
    public static $count_insert = 0;

    public function __construct(array $pdo) {
        $this->pdo = $pdo['DATA'];
        $this->pdo_html = $pdo['HTML'];
        $this->prefix_mapping['dcr'] = $pdo['HTML'];
        $this->prefix_mapping['data'] = $pdo['DATA'];
    }

    /**
     * Добавить документ в таблицу doc_data 
     */
    public function insert_doc_data(array $fields) {
        $field_key = array_keys($fields);
        $field_val = array_values($fields);
        $field_str = array_fill(0, count($fields), "?");

        $sth = $this->pdo->prepare("
			INSERT INTO doc_data 
                            (" . implode(",", $field_key) . ")
			VALUES
                            (" . implode(",", $field_str) . ")"
        );
        $sth->execute($field_val);
        return $this->pdo->lastInsertId();
    }

    //----------------------------------
    public function update_link_date($link_id) {
        $sth = $this->pdo->prepare("
			UPDATE link
			SET fid_html = NOW()
			WHERE id = ?
		");
        $sth->execute(
                array($link_id)
        );
        return $sth->rowCount();
    }

    public function update_global_date($doc_id) {
        $sth = $this->pdo->prepare("
			UPDATE link
			SET fid_html = NOW()
			WHERE id = ?
		");
        $sth->execute(
                array($doc_id)
        );
        return $sth->rowCount();
    }

    /**
     * Обновить поля таблицы doc_data p731 , p732 , верхние поля вынес в p731_init , p732_init p731_2_date
     */
    public function update_731($id, $p731, $p732, $p731_init, $p732_init, $p731_2_date) {
        $sth = $this->pdo->prepare("
			UPDATE doc_data SET 
                            `p731` = ? , 
                            `p732` = ? , 
                            `p731_init` = ? , 
                            `p732_init` = ? , 
                            `p731_2_date` = ?  
			WHERE doc_data.id = ?");
        if (empty($p731_2_date))
            $p731_2_date = NULL;
        $sth->execute(array($p731, $p732, $p731_init, $p732_init, $p731_2_date, $id));
        return $sth->rowCount();
    }

    /**
     * Обновить поля таблицы doc_data p220
     */
    public function update_220($id, $p220, $p151) {
        $sth = $this->pdo->prepare("
			UPDATE doc_data SET 
                            `p220` = ? ,
                            `p151` = ?
			WHERE doc_data.id = ?
		");
        $sth->execute(array($p220, $p151, $id));
        return $sth->rowCount();
    }

    /**
     * Обновить документы 9го реестра по полям 111 210 
     */
    public function update_9_reestr($id, $p210, $p111) {
        $sth = $this->pdo->prepare("
			UPDATE doc_data SET 
                            `p210` = ? , 
                            `p111` = ?  
			WHERE doc_data.id = ?
		");
        $sth->execute(array($p210, $p111, $id));
        return $sth->rowCount();
    }

    /**
     * Обновить документ
     * @param array $data $data['fields'] -> содержимое полей $data['id'] -> id документа который требуется обновить
     */
    public function update_doc_data(array $data) {
        $filer_field = array_filter($data['fields']);
        if (!empty($filer_field)) {
            $field_key = array_keys($filer_field);
            $field_val = array_values($filer_field);

            $field_val[] = $data['id'];
            $sql = "UPDATE doc_data SET                
                    " . implode(" = ? ,", $field_key) . " = ?
                WHERE doc_data.id = ? ";

            $sth = $this->pdo->prepare($sql);
            $sth->execute($field_val);
        }

        return $data['id'];
    }

    /**
     * Обновить изображения в doc_data
     */
    public function update_img_html($id, $doc_img_link, $doc_img_file, $status_id, $status_date) {
        $sth = $this->pdo->prepare("
			UPDATE doc_data SET 
                            `doc_img_file` = ? , 
                            `doc_img_link` = ? ,
                            `doc_status_id` = ?, 
                            `doc_status_date` = ? 
			WHERE doc_data.id = ?
		");
        $sth->execute(array($doc_img_file, $doc_img_link, $status_id, $status_date, $id));
        return $sth->rowCount();
    }

    public function get_p540_txt($doc_number) {
        $dbres = $this->pdo->prepare("
			SELECT	`p540_txt`
			FROM 	`doc_data`
			WHERE	`doc_number` = ?
		");
        $dbres->execute(array($doc_number));
        if ($row = $dbres->fetch(PDO::FETCH_ASSOC)) {
            return $row['p540_txt'];
        } else {
            return NULL;
        }
    }

    //получить документы из doc_data у которых появились изображения в link
    public function patch_doc_img_html($reestr_id = "") {
        static $dbres = NULL;

        if ($dbres == NULL) {
            $dbres = $this->pdo->query("
                SELECT
                        link.id,
                        link.doc_img_file ,
                        link.doc_html_file ,
                        doc_data.id AS dd_id 
                FROM
                        link
                LEFT JOIN `doc_data` ON link.id = doc_data.link_id
                WHERE
                        (doc_data.doc_img_file = '' OR doc_data.doc_img_link = '' )
                    AND
                        doc_data.doc_img_file != link.doc_img_file 
                        " . (!empty($reestr_id) ? "AND link.reestr_id = $reestr_id" : '') . "
                ORDER BY id       
			");
        }

        if (!($row = $dbres->fetch(PDO::FETCH_NUM))) {
            $dbres = NULL;
        }
        return $row;
    }

    //----------------------------------
    public function get_next_link($reestr_id) {
        static $dbres = NULL;

        if ($dbres == NULL) {
            $dbres = $this->pdo->query("
				SELECT	
                                    link.id , 
                                    `doc_html_file`, 
                                    `doc_img_file`, 
                                    `doc_link`, 
                                    reestr.p540_file_path, 
                                    `doc_link`, 
                                    `doc_html_file`, 
                                    `doc_img_file`, 
                                    `doc_list1_file`, 
                                    `reestr_id`
				FROM 
                                    link 
                                    LEFT JOIN `reestr` ON link.reestr_id = reestr.id
				WHERE	
                                    ( `parsed_date` IS NOT NULL OR `parsed_date` != 0 )
                                        AND
                                    ( `doc_html_file` IS NOT NULL OR `doc_html_file` != '' )    
                                        AND
                                    ( `fid_html` IS NULL OR `fid_html` = 0 )
                                    " . (!empty($reestr_id) ? "AND link.reestr_id = $reestr_id" : '') . "
				ORDER BY id
			");
        }

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC))) {
            $dbres = NULL;
        }
        return $row;
    }

    /**
     * Получить документы на обновление
     */
    public function select_doc_update($reestr_id = '', $id_doc = '', $all = '') {
        static $dbres = NULL;
        if ($dbres == NULL) {
            $sql = "SELECT	
                        link.id , 
                        doc_data.id as doc_data_id , 
                        doc_data.p540_txt , 
                        doc_data.p540_file , 
                        link.doc_html_file, 
                        link.doc_img_file, 
                        link.doc_link, 
                        reestr.p540_file_path, 
                        link.doc_list1_file, 
                        link.reestr_id
                    FROM 
                        link
                        LEFT JOIN doc_data ON link.id = doc_data.link_id
                        LEFT JOIN reestr ON link.reestr_id = reestr.id
                    WHERE
                        1 = 1
                        " . (!empty($id_doc) ? "AND doc_data.id = $id_doc " :
                            ( empty($all) ? " AND link.parsed_date > link.fid_html " : "" ) ) . "
                        " . (!empty($reestr_id) ? "AND doc_data.reestr_id = $reestr_id " : "")
                    . "
                    ORDER BY link.id ;";
            //print_r($sql);
            $dbres = $this->pdo->query($sql);
        }
        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC))) {
            $dbres = NULL;
        }
        return $row;
    }

    /**
     * Получить документы без заполненных полей p731`2_init
     * @param int $reestr_id № реестра (необязательный параметр)
     * @param int $id id строки (необязательный параметр для теста 1го документа)
     * @return Array $row выборка полей из doc_data
     */
    public function get_next_doc($reestr_id = '', $id = '') {
        static $dbres = NULL;
        if ($dbres == NULL) {
            $dbres = $this->pdo->query("
				SELECT
                                   id , reestr_id , doc_html_file  
				FROM 
                                    doc_data	
				WHERE	
                                    p731_init IS NULL AND p732_init IS NULL
                                    " . (!empty($reestr_id) ? "AND doc_data.reestr_id = $reestr_id" : '') . "
                                    " . (!empty($id) ? "AND doc_data.id = $id" : '') . "
				ORDER BY id
			");
        }

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC))) {
            $dbres = NULL;
        }
        return $row;
    }

    //
    public function get_mktu_ru() {
        static $dbres = NULL;
        if ($dbres == NULL) {
            $sql = "SELECT id , RU FROM mktu_catalog WHERE RU IS NOT NULL ORDER BY id ;";
            $dbres = $this->pdo_html->query($sql);
        }

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC))) {
            $dbres = NULL;
        }
        return $row;
    }

    /**
     * Получить документы без заполненных полей p220
     */
    public function get_noupdate_p220($reestr_id = '', $id = '') {
        static $dbres = NULL;
        if ($dbres == NULL) {
            $sql = "
				SELECT
                                   id , reestr_id , doc_html_file  
				FROM 
                                    doc_data	
				WHERE	
                                    (p220 IS NULL OR p151 IS NULL)
                                    " . (!empty($reestr_id) ? "AND doc_data.reestr_id IN (" . Cfg::FID_ALL_REESTR . ")" : '') . "
                                    " . (!empty($id) ? "AND doc_data.id = $id" : '') . "
				ORDER BY id
			";
            //exit($sql);
            $dbres = $this->pdo->query($sql);
        }

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC))) {
            $dbres = NULL;
        }
        return $row;
    }

    /**
     * Добавить в таблицу m4_html.doc_f511 данные о мкту документа
     * @param array $mktu массив в виде mktu => description
     * @param array $doc_data_id id документа ключ на doc_data.id
     */
    public function insert_mktu(array $mktu, $doc_data_id) {
        $update = "";
        foreach ($mktu as $key => $val) {
            $update .= "f511_{$key} = '" . $val . "' , ";
        }
        $sql = "INSERT INTO doc_f511
                        (	id,
                                f511_" . implode(",f511_", array_keys($mktu)) . " ,
                                created
                        )
                VALUES
                        (	'$doc_data_id',
                                '" . implode("','", $mktu) . "' ,
                                NOW()
                        )
                ON DUPLICATE KEY UPDATE
                                $update
                                `updated` = NOW()";
        //print $sql."\n";

        $sth = $this->pdo_html->prepare($sql);
        $sth->execute();
        return $sth->rowCount();
    }

    /** Удалить все мкту определенного документа */
    public function delete_doc_class_rel($doc_data_id) {
        $sth = $this->pdo->prepare("DELETE 
                FROM doc_class_rel 
                WHERE (doc_id= ? )");
        $sth->execute(
                array($doc_data_id)
        );
    }

    /**
     * Добавить классы мкту в табилцу doc_class_rel
     * @param array $mktu массив в виде mktu => description , преобразуется в массив ключей
     * @param array $doc_data_id id документа ключ на doc_data.id
     */
    public function insert_doc_class_rel(array $mktu, $doc_data_id) {
        //TODO : Заменить конструкцию на более безопасный вариант $stmt->bindParam(':value', $value);
        $mktu = array_keys($mktu);
        $query = array();
        foreach ($mktu as $val) {
            $query[] = "($doc_data_id," . (int) $val . ")";
        }

        $sql = "INSERT INTO `doc_class_rel` VALUES " . implode(",", $query) . " ;";
        $dbres = $this->pdo->query($sql);
    }

    /** Добавить модификации документа */
    public function insert_doc_modification($doc_id, array $notice, array $modification_fields) {

        $fields = array_keys($modification_fields);
        $sql = "INSERT INTO doc_modification 
                    (doc_id , " . implode(",", $fields) . ") 
                VALUES 
                    (:doc_id , :" . implode(", :", $fields) . " ) 
                ON DUPLICATE KEY UPDATE 
                    `hash` = :hash ; ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':doc_id', $doc_id);
        //Создать бинды по полям
        foreach ($modification_fields as $name => $val) {
            $stmt->bindParam(':' . $name, $modification_fields[$name]);
        }
        //Обойти все модификации по документу
        foreach ($notice as $value) {
            foreach ($modification_fields as $name => $val) {
                $modification_fields[$name] = (!empty($value[$name]) ? $value[$name] : NULL);
            }
            $stmt->execute();
        }
    }

    /**
     * @param int $reestr_id ид реестра
     * @param string $type_edits тип изменений  
     */
    public function insert_type_edits($reestr_id, $type_edits) {
        $sql = "INSERT INTO type_edits 
                    (reestr_id , type_edits , hash) 
                VALUES 
                    (:reestr_id , :type_edits , :hash  ) 
                ON DUPLICATE KEY UPDATE 
                    `id` = LAST_INSERT_ID(id) ; ";

        $stmt = $this->pdo->prepare($sql);
        $hash = md5($reestr_id . $type_edits);
        $stmt->bindParam(':reestr_id', $reestr_id);
        $stmt->bindParam(':type_edits', $type_edits);
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();


        return $this->pdo->lastInsertId();
    }

    /** Общая связь фраз к классам */
    public function dcr_fraza_rel($doc_id, $values) {
        $pdo = $this->pdo_html ; 
        $pdo->query("alter table dcr_fraza_rel disable keys");
        $stmt = $pdo->prepare("
			INSERT IGNORE INTO `dcr_fraza_rel`
				( `class_id`, `doc_id` , `fraza_id`)
			VALUES
				( :class_id, :doc_id , :fraza_id) ; ");
        
        $stmt->bindValue(":doc_id", $doc_id);
        $call = function($values) use ($stmt) {
            $stmt->bindValue(":class_id", $values['class_id']);
            $stmt->bindValue(":fraza_id", $values['fraza_id']);
            $stmt->execute();
        };
        $dbh = $pdo->beginTransaction();
        if (is_array($values)) {  
            
            array_walk($values, $call);
            
            
        } else {
            $return = false;
            $call($values);
        }
        $pdo->commit();
        $pdo->query("alter table dcr_fraza_rel enable keys");

    }
    
    public function update_cash($table_name, $values, $prefix_table = '')
    {
        $prefix = ($prefix_table ? $prefix_table : "html");
        $pdo = $this->prefix_mapping[$prefix];
        
        $sql = "UPDATE " . ($prefix_table ? $prefix_table . "_" : "") . "$table_name SET 
                    count = :count
                WHERE 
                    id = :id ;";
        $stmt = $pdo->prepare($sql);
        //Замыкание на добавление новго запроса в транзакцию
        $call = function($value) use ($stmt) {
            $stmt->bindValue( ":count" , $value['id'] );
            $stmt->bindValue( ":id" , $value['count'] );
            $stmt->execute();
        };
        //старт транзакции
        $dbh = $this->prefix_mapping[$prefix]->beginTransaction();
        array_walk($values, $call);
        $this->prefix_mapping[$prefix]->commit();
    }
    
    /** Получить топ кеша*/
    public function select_cash_data($table_name , $cash_elements)
    {
         $dbres = $this->pdo_html->query("
                SELECT `id`, `hash`, `count`
                FROM `dcr_$table_name`
                ORDER BY count DESC LIMIT 0 , ".(int)$cash_elements.";
                ");

        return $dbres->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Индексы  */
    public function add_index($table, array $param, $prefix = '') {
        $sth = $this->pdo_html->prepare("
			INSERT INTO " . (!$prefix ? "dcr" : $prefix) . "_" . $table . "_rel
				( " . $param[0] . " , " . $param[1] . " )
			VALUES
				( " . $param[2] . " , " . $param[3] . " )
			ON DUPLICATE KEY
				UPDATE `id` = LAST_INSERT_ID(`id`); ");

        if (!$sth->execute())
            Registry::get("Log")->log(implode(" , ", $stmt->errorInfo()) . " $table", "err");
    }

    /** Добавтить связь между документами , фразами документа и подклассами справочника МКТУ */
    public function insert_doc_mktu_rel(array $arr_rel) {
        $sql = "INSERT INTO `doc_mktu_rel`
                        ( `doc_id` , `kf_id` , `mktu_catalog_id` , `sub_class_relevance` )
                VALUES
                        ( :doc_id, :kf_id , :mktu_catalog_id , :sub_class_relevance ) 
                ON DUPLICATE KEY
                        UPDATE `id` = LAST_INSERT_ID(`id`); ";

        $sth = $this->pdo_html->prepare($sql);
        $sth->bindParam(':doc_id', $doc_id);
        $sth->bindParam(':kf_id', $kf_id);
        $sth->bindParam(':mktu_catalog_id', $mktu_catalog_id);
        $sth->bindParam(':sub_class_relevance', $sub_class_relevance);

        foreach ($arr_rel as $relevance) {
            $doc_id = $relevance['doc_id'];
            $kf_id = $relevance['kf_id'];
            $mktu_catalog_id = $relevance['mktu_catalog_id'];
            $sub_class_relevance = $relevance['relevance'];
            if (!$sth->execute())
                Registry::get("Log")->log(implode(" , ", $stmt->errorInfo()) . " doc_mktu_rel ", "err");
        }
    }

    /** Получить все статусы документов */
    public function init_status_list() {

        $dbres = $this->pdo->query("
                SELECT `id`, `title`, `reestr_id`
                FROM `doc_status`
                ORDER BY id
                ");

        return $dbres->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Методы для работы с ошибками */

    /** Функция обслуживания однотипных таблиц */
    public function add_field($table_name, $values, $prefix_table = '', $hash = '') {
        $return_array = (is_array($values) ? TRUE : FALSE);
        
        $prefix = ($prefix_table ? $prefix_table : "data");
        $pdo = $this->prefix_mapping[$prefix];
        
        $sql = "INSERT IGNORE INTO " . ($prefix_table ? $prefix_table . "_" : "") . "$table_name 
                    ($table_name " . ( $hash ? ", hash , count" : "" ) . ") 
                VALUES 
                    (:value  " . ( $hash ? ", :hash  , 1" : "") . ") ";
        //print_r($sql);
        $stmt = $pdo->prepare($sql);
        //Замыкание на добавление новго запроса в транзакцию
        $call = function($value) use ($stmt, $hash) {
            $stmt->bindValue(':value', $value);
            if ($hash)
                $stmt->bindValue(':hash', md5($value));
            $stmt->execute();
        };
        //Замыкание на получение id вставленных документов
        $count_id = function() use ($return_array, $prefix_table, $pdo, $table_name, $values) {
            if ($return_array) {
                return $pdo->query("SELECT id FROM " . ($prefix_table ? $prefix_table . "_" : "") . "$table_name ORDER BY `id` DESC LIMIT " . count($values) . " ;")->fetchAll(PDO::FETCH_ASSOC);
            } else
                return $pdo->lastInsertId();
        };
        //старт транзакции
        $dbh = $this->prefix_mapping[$prefix]->beginTransaction();
        if ($return_array) {
            array_walk($values, $call);
        } else {
            $call($values);
        }
        $id = $count_id();
        $this->prefix_mapping[$prefix]->commit();
        //print("\n".$id."\n"); exit;
        return $id ;



        //return $this->prefix_mapping[$prefix]->lastInsertId();
    }

    /** Удалить запись об ошибке для определенного линка */
    public function del_doc_err($link_id) {
        $sql = "DELETE FROM `dcr_error` WHERE (link_id=:link_id) ;";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':link_id', $link_id);
        $stmt->execute();
    }

    /** Добавить ошибку для линка */
    public function add_doc_error($link_id, $err_id) {
        $sql = "INSERT INTO dcr_error 
                   (link_id , error , attempt , parsed_date , state) 
                VALUES 
                    (   :link_id , 
                        :err_id , 
                        (attempt+1) , 
                        NOW() , 
                        0   ) 
                ON DUPLICATE KEY UPDATE 
                    `parsed_date` = NOW() , attempt = (attempt+1) ;";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':link_id', $link_id);
        $stmt->bindParam(':err_id', $err_id);
        $stmt->execute();
    }

}

?>
