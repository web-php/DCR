<?php

/**
 * Модель приложения РАБОЧЕГО СТОЛА
 */
require_once __DIR__ . "/FidImageTag.php";

class FidDesktop {

    private $all_reestr = array(6, 7, 9);
    private $reestr_8_11_12 = array(8, 11, 12);
    private $total_reestr = array(6, 7, 8, 9, 11, 12);
    //документ считается действующим, если в doc_status это (для определенных реестров):
    private $status_worked = array(1, 2, 4, 5, 6, 8, 9, 10);
    private $status_worked_reestr = array(6, 7);
    private $pdo;
    private $mode = array();
    private $active_reestr_id_arr = array(6);
    private $curr_reestr_id;
    private $curr_doc_num;
    private $datadir;
    private $basedir;
    private $class_mktu;
    private $active_pm;
    private $FidImageTag;
    private $p731_p732;
    private $p540_txt;
    private $doc_number;
    private $active_feature;

    //----------------------------------------
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->basedir = __DIR__ . '/..';
        $this->FidImageTag = new FidImageTag($pdo);
    }

    //Сбросить фильтры
    public function set_disable_filters()
    {
        $this->mode['witherror'] = FALSE;
        $this->mode['unmoderated'] = FALSE;
        $this->mode['moderated'] = FALSE;
        $this->set_active_reestr($this->total_reestr);
        $this->set_class_mktu("");
        $this->set_active_pm("");
        $this->set_active_feature("");
    }

    public function set_active_pm($active_pm)
    {
        $this->active_pm = $active_pm;
    }

    //Установить set_doc_number
    public function set_doc_number($doc_number)
    {
        $this->doc_number = $doc_number;
    }

    //Установить set_p540_txt
    public function set_p540_txt($p540_txt)
    {
        $this->p540_txt = addslashes(trim($p540_txt));
    }

    //Установить set_p731_p732
    public function set_p731_p732($p731_p732)
    {
        $this->p731_p732 = addslashes(trim($p731_p732));
    }

    //----------------------------------------
    public function set_mode($mode)
    {
        $this->mode = $mode;
    }

    //----------------------------------------
    public function set_data_dir($datadir)
    {
        $this->datadir = $datadir;
    }

    /**
     * Получить изобразительные теги
     */
    public function get_img_tags()
    {
        return $this->FidImageTag->get_img_tags();
    }

    public function set_active_feature($active_feature)
    {
        $this->active_feature = $active_feature;
    }

    //----------------------------------------
    public function set_class_mktu($class_mktu)
    {
        $this->class_mktu = $class_mktu;
    }

    /**
     * Выбираем дополнительные требуемые параметры
     */
    public function get_param($param)
    {
        //Определить что выбирать
        switch ($param)
        {
            //классы мкту
            case "mktu":
                $table = "class";
                $columns = "";
                break;
            //программы модератора
            case "pm":
                $table = "moder2_program";
                $columns = "";
                break;
            default:
                return FALSE;
                break;
        }

        //выбрать все классы из бд
        $dbres = $this->pdo->query("SELECT * FROM $table ;");
        $dbres->execute();
        if ($row = $dbres->fetchAll(PDO::FETCH_ASSOC))
        {
            return $row;
        }
        else
        {
            return FALSE;
        }
    }

    //----------------------------------------
    public function get_reestr()
    {
        $dbres = $this->pdo->query("
			SELECT	`id` as rid, `name`, `link_num_processed`, (
						SELECT	count(doc_data.id) as cnt
						FROM 	`doc_data`
						WHERE	doc_data.reestr_id = `rid`
				) as total_cnt, (
						SELECT	count(doc_data.id) as cnt
						FROM 	`doc_data`
						WHERE	doc_data.reestr_id = `rid`
								AND `moder_status` > 0
				) as moderated_cnt

			FROM	`reestr`
			WHERE	`id` IN (" . implode(',', $this->all_reestr) . ")"
        );

        $retval = $dbres->fetchAll(PDO::FETCH_ASSOC);
        $retval[] = $this->get_group_reestr($this->reestr_8_11_12);
        //print_r($retval);
        return $retval;
    }

    //----------------------------------------
    private function get_group_reestr($reestr_arr)
    {

        $dbres = $this->pdo->query("
			SELECT	group_concat(id) as rid, group_concat(`name`) as name, sum(`link_num_processed`) as link_num_processed, (
						SELECT	count(doc_data.id) as cnt
						FROM 	`doc_data`
						WHERE	doc_data.reestr_id IN (" . implode(',', $reestr_arr) . ")
				) as total_cnt, (
						SELECT	count(doc_data.id) as cnt
						FROM 	`doc_data`
						WHERE	doc_data.reestr_id IN (" . implode(',', $reestr_arr) . ")
								AND `moder_status` > 0
				) as moderated_cnt
			FROM	`reestr`
			WHERE	id IN (" . implode(',', $reestr_arr) . ")
		");
        $retval = $dbres->fetchAll(PDO::FETCH_ASSOC);
        if (isset($retval[0]))
        {
            return $retval[0];
        }
    }

    //----------------------------------------
    private function check_reestr_status($reestr_arr)
    {
        foreach ($reestr_arr as $id => $info)
        {
            if (in_array($reestr_arr[$id]['rid'], $this->active_reestr_id_arr))
            {
                $reestr_arr[$id]['status'] = 1;
            }
        };
        return $reestr_arr;
    }

    //----------------------------------------
    public function get_doc_info($number)
    {
        if (!$this->active_reestr_id_arr OR !$number)
        {
            return FALSE;
        }

        $sth = $this->pdo->prepare("
			SELECT	`doc_html_file`, `doc_img_file`,
					`doc_link`, `p571_color`,
					`doc_list1_file`, `doc_number`, `reestr_id`,
					`p540_txt`, `p571_txt`, `moder_status`, id as document_id,
					`doc_img_link`, `doc_list1_link`,
					DATE_FORMAT(moder_date, '%d-%m-%Y') as moder_date,
					(
						SELECT	group_concat(`img_tag_id`)
						FROM	`doc_img_tag`
						WHERE	doc_img_tag.doc_id = document_id
					) as img_tags

			FROM 	`doc_data`
			WHERE	`doc_number` = ?
		");
        $sth->execute(
                array($number)
        );
        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            return $row;
        }
    }

    //----------------------------------------
    private function get_moder_id($doc_id)
    {
        $sth = $this->pdo->prepare("
			SELECT	`moder_key_id`
			FROM 	`doc_data`
			WHERE	`id` = ?
		");

        $sth->execute(array($doc_id));

        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            return $row['moder_key_id'];
        }
        else
        {
            return FALSE;
        }
    }

    //----------------------------------------
    private function update_moder_counter($moder_key_id)
    {
        $sth = $this->pdo->prepare("
			UPDATE	moder_key
			SET 	last_moder_date = NOW(),
					moder_doc_num = moder_doc_num + 1
			WHERE	id = ?

		");
        $sth->execute(
                array($moder_key_id)
        );

        return $sth->rowCount();
    }

    //----------------------------------------
    public function save($doc_id, $p540txt, $p571txt, $image_tags, $p571_color, $moder_status)
    {

        $this->FidImageTag->process_image_list($doc_id, $image_tags);
        if ($moder_key_id = $this->get_moder_id($doc_id))
        {
            $this->update_moder_counter($moder_key_id);
        }
        $sth = $this->pdo->prepare("
			UPDATE doc_data
			SET p540_txt = ?,
				p571_txt = ?,
				p571_color = ?,
				moder_status = ?,
				moder_date = NOW()
			WHERE doc_data.id = ?
		");
        $sth->execute(
                array($p540txt, $p571txt, $p571_color, $moder_status, $doc_id)
        );
        return $sth->rowCount();
    }

    //----------------------------------------
    public function set_active_reestr($reestr_id_arr)
    {
        $this->active_reestr_id_arr = $reestr_id_arr;
    }

    //----------------------------------------
    //проверяет наличие картинок на диске
    private function check_images($doc_arr)
    {

        return $doc_arr;
    }

    /**
     * Выбрать все документы применив фильтры пользователя
     */
    public function get_all_docs($offset = 0, $limit = 3, $moder_key)
    {


        //если выборка по ключу, то фильтры реестров отключить
        if (!empty($moder_key))
        {
            $this->active_reestr_id_arr = $this->total_reestr;
        }
        if (!$this->active_reestr_id_arr)
        {
            return array();
        }

        //LEFT JOIN `doc_class_rel` ON doc_data.id = (SELECT doc_class_rel.doc_id FROM doc_class_rel WHERE doc_class_rel.class_id = 1 LIMIT 1)
        $query = "
			SELECT SQL_CALC_FOUND_ROWS
					`doc_html_file`,
					`doc_img_file`,
					`doc_link`,
					`p571_color`,
					`doc_list1_file`,
					`doc_number`,
					 doc_data.`reestr_id`,
					`p540_txt`,
					`p571_txt`,
					`moder_status`,
					 doc_data.id as document_id,
					`doc_img_link`,
					`doc_list1_link`,
					DATE_FORMAT(moder_date, '%d-%m-%Y') as moder_date,
					(
						SELECT	group_concat(`img_tag_id`)
						FROM	`doc_img_tag`
						WHERE	doc_img_tag.doc_id = document_id
					) as img_tags
			FROM 	`doc_data`	
                                        " . (!empty($moder_key) ? "LEFT JOIN `moder_key`     ON doc_data.moder_key_id = moder_key.id" : '') . "
                                        " . (!empty($this->class_mktu) ? "LEFT JOIN `doc_class_rel` ON doc_data.id = doc_class_rel.doc_id" : '') . "
                                        " . (!empty($this->active_feature) ? "LEFT JOIN `doc_img_tag`   ON doc_data.id = doc_img_tag.doc_id" : '') . "
                                        " . (!empty($this->active_pm) ? "LEFT JOIN `moder2_doc`    ON doc_data.id = moder2_doc.doc_id" : '') . "
			WHERE
					doc_data.reestr_id IN (" . implode(',', $this->active_reestr_id_arr) . ")
					" . (!empty($this->doc_number) ? "AND doc_data.doc_number = " . $this->doc_number . "" : '') . "
					" . (!empty($this->p540_txt) ? "AND doc_data.p540_txt  LIKE '%" . $this->p540_txt . "%'" : '') . "
					" . (!empty($this->p731_p732) ? "AND ( doc_data.p731 LIKE '%" . $this->p731_p732 . "%' OR doc_data.p732 LIKE '%" . $this->p731_p732 . "%' )" : '') . "
					" . (!empty($moder_key) ? "AND moder_key.moder_key = '" . $moder_key . "'" : '') . "
					" . (!empty($this->class_mktu) ? "AND doc_class_rel.class_id IN (" . implode(',', $this->class_mktu) . ")" : '') . "
					" . (!empty($this->active_feature) ? "AND doc_img_tag.img_tag_id IN (" . implode(',', $this->active_feature) . ")" : '') . "
					" . (!empty($this->active_pm) ? "AND moder2_doc.program_id = '" . $this->active_pm . "'" : '') . "
					" . ($this->mode['witherror'] ? "AND moder_status = 2" : '') . "
					" . ($this->mode['unmoderated'] ? 'AND moder_status = 0' : '') . "
					" . ($this->mode['moderated'] ? 'AND moder_status = 1' : '') . "
					AND (
						" . ($this->mode['p540_filters'] ? '1=0' : '1=1') . "
						" . ($this->mode['text_only'] ? 'OR p540_txt != ""' : '') . "
						" . ($this->mode['text_empty'] ? 'OR p540_txt = ""' : '') . "
						" . ($this->mode['empty'] ? 'OR p540_txt = "" ' : '') . "
					)
					" . (($this->mode['text_only'] + $this->mode['empty']) ? 'AND NOT EXISTS (select doc_img_tag.img_tag_id from doc_img_tag where doc_img_tag.doc_id =doc_data.id) ' : '') . "

					" . (!empty($this->mode['date']) ? 'AND DATE_FORMAT(moder_date, "%d-%m-%Y")="' . $this->mode['date'] . '"' : '') . "
					" . ($this->mode['worked'] ? '
							AND (
									doc_status_id IN (' . implode(',', $this->status_worked) . ')
								OR	doc_data.reestr_id NOT IN (' . implode(',', $this->status_worked_reestr) . ')
							)
					' : '') . "
			ORDER BY doc_data.reestr_id ASC, `doc_number` ASC
			LIMIT	$offset, $limit
		";
        //print $query;
        $sth = $this->pdo->prepare($query);
        $sth->execute();
        $all_docs = $this->check_images($sth->fetchall(PDO::FETCH_ASSOC));
        if ($this->mode['random'])
        {
            shuffle($all_docs);
        }

        $dbres = $this->pdo->query('SELECT FOUND_ROWS()');
        $rowCount = (int) $dbres->fetchColumn();

        return array(
            'docs' => $all_docs,
            'real_count' => $rowCount
        );
    }

}

?>