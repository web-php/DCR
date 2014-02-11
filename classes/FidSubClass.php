<?php

class FidSubClass {

    const MKTU_0 = 0;

    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    //----------------------------------
    public function process_subclasses($html, $doc_id)
    {

        foreach ($this->parse_p511_arr($html) as $sublass_info)
        {


            $subclass_name = $sublass_info['subclass_name'];
            $class_id = $sublass_info['class_id'];

            //найти существующий уникальный подкласс с таким именем
            $existing_uniq_subclass_id = $this->get_unique_subclass_id($subclass_name);

            if ($existing_uniq_subclass_id === FALSE)
            {
                //print "no exists: $doc_id : $subclass_name\n";
                //найти похожие
                $uniq_subclasses_like = $this->get_uniq_subclass_like($subclass_name, $class_id);
                if (count($uniq_subclasses_like) > 0)
                {
                    //добавить привязку к похожим
                    foreach ($uniq_subclasses_like as $id => $uniq_subclass_like_id)
                    {
                        //print "$subclass_name is like $uniq_subclass_like_id\n";
                        $this->insert_doc_subclass($doc_id, $uniq_subclass_like_id);
                    }
                }
                else
                {
                    //похожие не найдены, добавить уникальный подкласс
                    //print "true uniq\n";
                    $uniq_subclass_id = $this->insert_uniq_subclass($subclass_name, $class_id, $is_uniq);
                    $subclass_id = $this->insert_subclass($subclass_name, $class_id, self::MKTU_0);
                    $this->insert_subclass_rel($subclass_id, $uniq_subclass_id);
                }
            }
            else
            {
                //подкласс неуникальный
                //print "nouniq\n";
                $this->insert_doc_subclass($doc_id, $existing_uniq_subclass_id);
            }
        }
    }

    //----------------------------------
    //Получить массив подклассов из html документа
    public function parse_p511_arr($html)
    {
        $retval = array();

        $html_parts = explode("(511)", $html);
        if (isset($html_parts[1]))
        {

            $classes_html = explode("<P CLASS=p1>", $html_parts[1]);

            for ($i = 1; $i < count($classes_html); $i++)
            {

                //получить class id
                if (preg_match("#<B>(\d+)#", $classes_html[$i], $classid_res))
                {
                    $class_id = $classid_res[1];
                }
                else
                {
                    continue;
                }
                //получить подклассы
                if (preg_match_all('#<B>\d+\s*-\s*(.*?)[;\.]</B>#i', $classes_html[$i], $subclasses_res))
                {
                    foreach ($subclasses_res[1] as $subclasses)
                    {
                        foreach (preg_split('/[;,]/', $subclasses) as $subclass)
                        {
                            $subclass = rtrim(ltrim(preg_replace("/\s+/", " ", $subclass)));
                            $retval[] = array('class_id' => $class_id, 'subclass_name' => $subclass);
                        }
                    }
                }
            }
        }
        //print_r($retval);
        return $retval;
    }

    //----------------------------------
    private function get_unique_subclass_id($name)
    {
        $sth = $this->pdo->prepare("
			SELECT	`id`
			FROM 	`uniq_subclass`
			WHERE	`name` = ?
		");
        $sth->execute(array($name));

        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            return $row['id'];
        }
        else
        {
            return FALSE;
        }
    }

    //----------------------------------
    //ищет похожие уникальные подклассы
    public function get_uniq_subclass_like($name, $class_id)
    {
        $sth = $this->pdo->prepare("
			SELECT	`id`
			FROM 	`uniq_subclass`
			WHERE	`name` like ? AND
					`class_id` = ?
		");
        $sth->execute(array('%' . $name . '%', $class_id));

        return $sth->fetchAll(PDO::FETCH_COLUMN);
    }

    //----------------------------------
    public function insert_uniq_subclass($name, $class_id, &$is_uniq)
    {

        $sth = $this->pdo->prepare("
			INSERT INTO `uniq_subclass`
				(`name`, `class_id`)
			VALUES
				(?, ?)
			ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
		");
        $sth->execute(
                array($name, $class_id)
        );
        $is_uniq = $sth->rowCount();
        return $this->pdo->lastInsertId();
    }

    //----------------------------------
    public function insert_subclass($name, $class_id, $mktu_version)
    {
        $sth = $this->pdo->prepare("
			INSERT INTO `subclass`
				(`name`, `class_id`, `mktu_version`, `code`)
			VALUES
				(?, ?, ?, '')
		");
        $sth->execute(
                array($name, $class_id, $mktu_version)
        );
        return $this->pdo->lastInsertId();
    }

    //----------------------------------
    public function insert_subclass_rel($subclass_id, $uniq_subclass_id)
    {
        $sth = $this->pdo->prepare("
			INSERT INTO `subclass_rel`
				(`uniq_subclass_id`, `subclass_id`)
			VALUES
				(?, ?)
		");
        $sth->execute(
                array($uniq_subclass_id, $subclass_id)
        );
        return $this->pdo->lastInsertId();
    }

    //----------------------------------
    private function insert_doc_subclass($doc_id, $uniq_subclass_id)
    {

        $sth = $this->pdo->prepare("
			INSERT INTO doc_subclass
				(`doc_id`, `uniq_subclass_id`)
			VALUES
				(?, ?)
			ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
		");
        $sth->execute(
                array($doc_id, $uniq_subclass_id)
        );
        return $this->pdo->lastInsertId();
    }

    //----------------------------------
    public function get_next_subclass()
    {
        static $dbres = NULL;

        if ($dbres == NULL)
        {
            $dbres = $this->pdo->query("
				SELECT	`id`, `name`, `class_id`
				FROM 	`subclass`
				ORDER BY id
			");
        }

        if (!($row = $dbres->fetch(PDO::FETCH_ASSOC)))
        {
            $dbres = NULL;
        }
        return $row;
    }

}