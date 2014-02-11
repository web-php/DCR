<?php

require_once __DIR__ . "/FidKeyGen.php";

class FidReport {

    private $pdo;

    //----------------------------------------
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    //----------------------------------------
    public function get_doc_data_report()
    {
        $report = "ТАБЛИЦА doc_data\n";
        $report .= "Всего документов                    " . $this->get_total_doc_count() . "\n";
        $report .= "Документов (заполнено 111)          " . $this->get_111_doc_count() . "\n";
        $report .= "Документов (заполнено 210)          " . $this->get_210_doc_count() . "\n";
        $report .= "Документов (заполнено 731)          " . $this->get_731_doc_count() . "\n";
        $report .= "Документов (заполнено 732)          " . $this->get_732_doc_count() . "\n";
        $report .= "Документов (заполнено p540_file)    " . $this->get_p540_file_doc_count() . "\n";
        $report .= "Документов (заполнено p540_txt)     " . $this->get_p540_txt_doc_count() . "\n";
        $report .= "Документов (заполнено fid_540)      " . $this->get_fid_540_doc_count() . "\n";
        $report .= "Документов (заполнено p571_txt)     " . $this->get_p571_txt_doc_count() . "\n";
        $report .= "Документов (moder_status=1)         " . $this->get_moderated1_doc_count() . "\n";
        $report .= "Документов (moder_status=2)         " . $this->get_moderated2_doc_count() . "\n";
        $report .= "\n";

        return $report;
    }

    //----------------------------------------
    public function get_class_report()
    {
        $report = "ТАБЛИЦА class\n";
        $report .= "Всего классов				" . $this->get_class_count() . "\n";
        $report .= "\n";

        return $report;
    }

    //----------------------------------------
    public function get_subclass_report()
    {
        $report = "ТАБЛИЦА subclass\n";
        $report .= "Всего подклассов МКТУ			" . $this->get_subclass_count() . "\n";
        $report .= "\n";

        return $report;
    }

    //----------------------------------------
    public function get_uniq_subclass_report()
    {
        $report = "ТАБЛИЦА uniq_subclass\n";
        $report .= "Всего уникальных подклассов		" . $this->get_uniq_subclass_count() . "\n";
        $report .= "\n";

        return $report;
    }

    //----------------------------------------
    public function get_doc_subclass_report()
    {
        $report = "ТАБЛИЦА doc_subclass\n";
        $report .= "Всего записей				" . $this->get_doc_subclass_count() . "\n";
        $report .= "\n";

        return $report;
    }

    //----------------------------------------
    public function get_moder_key_report()
    {

        $valid_count = $this->get_valid_key_count();
        $unvalid_count = $this->get_unvalid_key_count();
        $done_count = $this->get_done_key_count();
        $total_count = $valid_count + $unvalid_count + $done_count;

        $report = "ТАБЛИЦА moder_key\n";
        $report .= "Всего ключей				$total_count\n";
        $report .= "Действительных				$valid_count\n";
        $report .= "Исполненных				$done_count\n";
        $report .= "Недействительных			$unvalid_count\n";
        $report .= "\n";

        return $report;
    }

    //----------------------------------------
    private function get_valid_key_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`moder_key`
			WHERE	`key_status` = " . FidKeyGen::KEY_STATUS_VALID
        );

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_unvalid_key_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`moder_key`
			WHERE	`key_status` = " . FidKeyGen::KEY_STATUS_UNVALID
        );

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_done_key_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`moder_key`
			WHERE	`key_status` = " . FidKeyGen::KEY_STATUS_DONE
        );

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    public function get_time_report($time_begin)
    {
        $time_end = time();
        $time_total = $time_end - $time_begin;
        $hours_total = floor($time_total / 3600);
        $min_total = floor(($time_total - $hours_total * 3600) / 60);
        $sec_total = $time_total - $hours_total * 3600 - $min_total * 60;

        $report = "";
        $report .= "Начала работу:      " . date("y.m.d H:i:s", $time_begin) . "\n";
        $report .= "Завершила работу:   " . date("y.m.d H:i:s", $time_end) . "\n";
        $report .= "Время работы:       $hours_total:$min_total:$sec_total\n\n";

        return $report;
    }

    //----------------------------------------
    private function get_class_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`class`
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_subclass_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`subclass`
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_uniq_subclass_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`uniq_subclass`
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_doc_subclass_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_subclass`
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_total_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_111_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`p111` != 0 AND `p111` IS NOT NULL
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_210_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`p210` != 0 AND `p210` IS NOT NULL
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_731_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`p731` != '' AND `p731` IS NOT NULL
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_732_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`p732` != '' AND `p732` IS NOT NULL
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_p540_file_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`p540_file` != '' AND `p540_file` IS NOT NULL
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_p540_txt_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`p540_txt` != '' AND `p540_txt` IS NOT NULL
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_fid_540_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`fid_540` IS NOT NULL
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_p571_txt_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`p571_txt` != '' AND `p571_txt` IS NOT NULL
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_moderated1_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`moder_status` = 1
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

    //----------------------------------------
    private function get_moderated2_doc_count()
    {

        $dbres = $this->pdo->query("
			SELECT	count(*) as cnt
			FROM 	`doc_data`
			WHERE	`moder_status` = 2
		");

        if ($row = $dbres->fetch(PDO::FETCH_ASSOC))
        {
            return $row['cnt'];
        }
    }

}

?>