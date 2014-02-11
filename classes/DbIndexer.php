<?php

/**
 * Логика обработки запросов  и обращений к базе данных программы индексатора документов 
 * @version dcr v1.1
 * @author Mikhail Orekhov <mikhail@edwaks.ru>
 */
class DbIndexer {

    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Добавить документ в таблицу doc_data 
     */
    public function insert_doc_data(
    $link_id, $link_number, $p732, $p731, $p732_init, $p731_init, $p731_date, $p210, $p111, $p540_file, $p540_txt, $doc_link, $doc_html_file, $doc_img_file, $doc_list1_file, $reestr_id, $status_id, $status_date, $doc_img_link, $doc_list1_link
    )
    {
        $sth = $this->pdo->prepare("
			INSERT INTO doc_data
				(`link_id`, `doc_number`, `p732`, `p731`, `p732_init`, `p731_init`, `p731_2_date` , `p210`, `p111`, `p540_file`, `p540_txt`,
				`doc_link`, `doc_html_file`, `doc_img_file`, `doc_list1_file`, `reestr_id`, `doc_status_id`, `doc_status_date` ,
				`doc_img_link`, `doc_list1_link`
				)
			VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , ?)"
        );
        $sth->execute(
                array($link_id, $link_number, $p732, $p731, $p732_init, $p731_init, $p731_date, $p210, $p111, $p540_file, $p540_txt,
                    $doc_link, $doc_html_file, $doc_img_file, $doc_list1_file, $reestr_id, $status_id, $status_date,
                    $doc_img_link, $doc_list1_link)
        );
        return $this->pdo->lastInsertId();
    }

    //----------------------------------
    public function update_link_date($link_id)
    {
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
    
    public function update_global_date($doc_id)
    {
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
    public function update_731($id, $p731, $p732, $p731_init, $p732_init, $p731_2_date)
    {
        $sth = $this->pdo->prepare("
			UPDATE doc_data SET 
                            `p731` = ? , 
                            `p732` = ? , 
                            `p731_init` = ? , 
                            `p732_init` = ? , 
                            `p731_2_date` = ?  
			WHERE doc_data.id = ?
		");
        if (empty($p731_2_date))
            $p731_2_date = NULL;
        $sth->execute(array($p731, $p732, $p731_init, $p732_init, $p731_2_date, $id));
        return $sth->rowCount();
    }
    
    /**
     * Обновить поля таблицы doc_data p220
     */
    public function update_220($id, $p220 , $p151)
    {
        $sth = $this->pdo->prepare("
			UPDATE doc_data SET 
                            `p220` = ? ,
                            `p151` = ?
			WHERE doc_data.id = ?
		");
        $sth->execute(array($p220, $p151 , $id));
        return $sth->rowCount();
    }

    /**
     * Обновить документы 9го реестра по полям 111 210 
     */
    public function update_9_reestr($id, $p210, $p111)
    {
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
     * обновить документ
     */
    public function update_doc_data(array $fields)
    {
        
        $sql = "UPDATE doc_data SET                
                    p210 =  ?, 
                    p111 = ? , 
                    p220 = ? ,
                    p151 = ? , 
                    p731= ? , 
                    p731_init = ? , 
                    p732 = ? , 
                    p732_init = ? , 
                    p731_2_date = ? , 
                    p750 = ? , 
                    doc_img_link = ? , 
                    doc_img_file = ? , 
                    doc_status_id = ? , 
                    doc_status_date = ? 
                WHERE doc_data.id = ? ";
        
        foreach($fields as $val)
            ($val ? $f[] = $val : $f[] = NULL); 
        $sth = $this->pdo->prepare($sql);
        //print_r($f);
        $sth->execute($f);
        return $sth->rowCount();
    }

    /**
     * Обновить изображения в doc_data
     */
    public function update_img_html($id, $doc_img_link, $doc_img_file, $status_id, $status_date)
    {
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

    public function get_p540_txt_7_reestr($doc_number)
    {
        $dbres = $this->pdo->prepare("
			SELECT	`p540_txt`
			FROM 	`doc_data`
			WHERE	`doc_number` = ?
		");
        $dbres->execute(array($doc_number));
        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['p540_txt'];
        }
        else
        {
            return NULL;
        }
    }

    /**
     * Выбрать документы для  9го реестра для патча полей 210 111
     */
    public function get_doc_9_reestr($id = '')
    {
        static $dbres = NULL;
        if ($dbres == NULL)
        {
            $dbres = $this->pdo->query("
				SELECT
                                   id , reestr_id , doc_html_file  
				FROM 
                                    doc_data	
				WHERE	
                                    doc_data.reestr_id = 9
                                    AND
                                    (p111 = 0 AND p210 = 0)
                                    " . (!empty($id) ? "AND doc_data.id = $id " : '') . "
				ORDER BY id
			");
        }

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC)))
        {
            $dbres = NULL;
        }
        return $row;
    }

    //получить документы из doc_data у которых появились изображения в link
    public function patch_doc_img_html($reestr_id = "")
    {
        static $dbres = NULL;

        if ($dbres == NULL)
        {
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

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC)))
        {
            $dbres = NULL;
        }
        return $row;
    }

    //----------------------------------
    public function get_next_link($reestr_id)
    {
        static $dbres = NULL;

        if ($dbres == NULL)
        {
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
                                    (`parsed_date` IS NOT NULL OR `parsed_date` != 0 )
                                        AND
                                    (`fid_html` IS NULL OR `fid_html` = 0)
                                    " . (!empty($reestr_id) ? "AND link.reestr_id = $reestr_id" : '') . "
				ORDER BY id
			");
        }

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC)))
        {
            $dbres = NULL;
        }
        return $row;
    }

    /**
     * Получить документы на обновление
     */
    public function select_doc_update($reestr_id = '' , $link_id)
    {
        static $dbres = NULL;
        if ($dbres == NULL)
        {
            $dbres = $this->pdo->query("
                                SELECT	
                                    l.id , 
                                    dd.id AS dd_id , 
                                    l.doc_html_file, 
                                    l.doc_img_file, 
                                    l.doc_link, 
                                    l.doc_list1_file, 
                                    l.reestr_id
				FROM 
                                    link AS l
                                    LEFT JOIN doc_data AS dd ON l.id = dd.link_id
				WHERE	
                                    l.parsed_date > l.fid_html
                                    " . (!empty($reestr_id) ? "AND dd.reestr_id = $reestr_id " : '') . "
                                        ".(!empty($link_id)? "AND l.id = $link_id" : '')."
				ORDER BY id
                                ");
        }
        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC)))
        {
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
    public function get_next_doc($reestr_id = '', $id = '')
    {
        static $dbres = NULL;
        if ($dbres == NULL)
        {
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

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC)))
        {
            $dbres = NULL;
        }
        return $row;
    }
    
    /**
     * Получить документы без заполненных полей p220
     */
    public function get_noupdate_p220($reestr_id = '', $id = '')
    {
        static $dbres = NULL;
        if ($dbres == NULL)
        {
            $sql = "
				SELECT
                                   id , reestr_id , doc_html_file  
				FROM 
                                    doc_data	
				WHERE	
                                    (p220 IS NULL OR p151 IS NULL)
                                    " . (!empty($reestr_id) ? "AND doc_data.reestr_id IN (".Cfg::FID_ALL_REESTR.")" : '') . "
                                    " . (!empty($id) ? "AND doc_data.id = $id" : '') . "
				ORDER BY id
			";
            //exit($sql);
            $dbres = $this->pdo->query($sql);
        }

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC)))
        {
            $dbres = NULL;
        }
        return $row;
    }

}

?>
