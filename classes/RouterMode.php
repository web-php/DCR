<?php

/**
 * @author Михаил Орехов
 */

class RouterMode {

    private $argv = array();

    /** var array $router режим запуска и реестр по которому запускать программу */
    private $router = array("reestr_id" => '', "mode");

    /** var array $mode_method параметры запуска программы индексатора */
    public static $mode_method = array("normal_start", "patch_731_732", "patch_111_210", "patch_220" ,"patch_doc_img_html", "doc_update");

    public function __construct($argv)
    {
        $this->argv = $argv;
        $this->varbose();
        $this->set_routing();
    }
    
    /**
     * 
     */    
    public function get_router()
    {
        return $this->router ;
    }
    
    /**
     * 
     */
    private function varbose()
    {
        if (isset($this->argv[1]) AND in_array($this->argv[1], array('--help', '-help', '-h', '-?', '/help', '/h', '/?')))
        {
            //Обычный запуск
            print "\tUsage: php " . $this->argv[0] . " reestr_id  [des : normal start]\n";
            // Перебираем документы из определенного реестра,или все документы если реестр не указан заного собираем поля 731 - 732
            print "\tUsage: php " . $this->argv[0] . " -patch-731-732 reestr_id [des : reload reestr_id documents]\n";
            // Перебираем документы из определенного реестра,или все документы заного собираем поля 111 - 210 
            print "\tUsage: php " . $this->argv[0] . " -patch-111-210 reestr_id [des : reload reestr_id documents]\n";
            // пересобрать документы из doc_data у которых появились изображения в link
            print "\tUsage: php " . $this->argv[0] . " -patch-doc-img-html  reestr_id [des : reload reestr_id documents]\n";
            //общий патч , последовательно запустить все патчи
            print "\tUsage: php " . $this->argv[0] . " -doc-update  reestr_id [des : run all patch ]\n";
            //общий патч , последовательно запустить все патчи
            print "\tUsage: php " . $this->argv[0] . " -patch-220  reestr_id \n";

            exit("\n");
        }
    }
    
    /**
     * Установить режим запуска
     */
    private function set_routing()
    {
        //Определяем параметры запуска индексатора
        $this->router['mode'] = self::$mode_method[0];
        if (!empty($this->argv[1]))
        {
            if (is_numeric($this->argv[1]))
            {
                $this->router['reestr_id'] = (int) $this->argv[1];
            }
            else
            {
                //Определяем запуск какого режима требуется требуется
                if ($mode_key = array_search(substr(str_replace("-", "_", $this->argv[1]), 1), self::$mode_method))
                {
                    $this->router['mode'] = self::$mode_method[$mode_key];
                    if (!empty($this->argv[2]))
                    {
                        if (is_numeric($this->argv[2]))
                        {
                            $this->router['reestr_id'] = $this->argv[2];
                        }
                    }
                }
                else
                    exit("\n MODE NOT SPECIFIED! \n");
            }
        }
        //exit($this->router['mode']."\n");
    }

}

?>