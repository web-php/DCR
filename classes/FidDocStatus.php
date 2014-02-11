<?php

class FidDocStatus {

    private $markers = array('состояние делопроизводства', 'статус:');
    private $pdo;
    private $status_list = array(); //список статусов

    //----------------------------------

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->init_status_list();
    }

    /**
     * получить id , date , статуса из документа
     * @return array $status_info
     */
    public function parse_status_id($html, $reestr_id = NULL)
    {

        if (!($status_line_html = $this->get_status_line_html($html)))
        {
            return FALSE; //4
        }

        foreach ($this->status_list as $status_info)
        {

            if (mb_stristr($status_line_html, $status_info['title'], FALSE, 'UTF-8'))
            {
                //Получить Дату установки статуса  27.10.2013
                preg_match("#([0-9]{2}\.[0-9]{2}\.[0-9]{4})#i", $html , $date);
                $date = explode(".",$date[1]);
                $status_info['date'] = $date[2].$date[1].$date[0];
                return $status_info ;  
            }
        }
        //print $reestr_id;
        //print $status_line_html;
        return FALSE;
    }

    //----------------------------------
    // получить строку html, в которой потенциально находится статус
    private function get_status_line_html($html)
    {

        foreach (explode("\n", $html) as $line)
        {

            foreach ($this->markers as $marker)
            {
                if (mb_stristr($line, $marker, FALSE, 'UTF-8'))
                {
                    return $line;
                }
            }
        }
        return FALSE;
    }

    //----------------------------------
    private function init_status_list()
    {

        $dbres = $this->pdo->query("
			SELECT	`id`, `title`, `reestr_id`
			FROM 	`doc_status`
			ORDER BY id
		");

        $this->status_list = $dbres->fetchAll(PDO::FETCH_ASSOC);
    }

}

?>