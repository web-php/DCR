<?php

/**
 * @author Михаил Орехов
 */
class RouterMode {

    private $options = array();
    private $argv = array();
    private $config = array();

    /** var array $router режим запуска и реестр по которому запускать программу */
    private $router = array(
        "reestr_id" => '', "mode" => '' , "doc_id" => '' , "all" => '' , "field_map" => ''
    );

    public function __construct($argv)
    {
        $this->options = getopt("f:m:r:p:d:a");
        $this->argv = $argv;
        $this->config = Registry::get("CONFIG");
        $this->verbose();
        $this->set_reestr();
        $this->set_mode();
        $this->set_document();
        $this->set_field_map();
    }
    
    /**  
     * Режим частной обработки полей , установить массив полей которые требуют обновления
     */
    private function set_field_map()
    {
        if(!empty($this->options['f']))
        {
            $this->router['field_map'] = explode(" ", $this->options['f']);
        }
    }
    
    /**
     * @return array Получить массив режима запуска индексатора
     */
    public function get_router()
    {
        if(empty($this->options['r']))
        {
            exit("\n Укажите реестр к которому относится документ \n") ; 
        }   
        return $this->router;
    }
    
    /** 
     * установить отладочный документ 
     */
    private function set_document()
    {
        if (!empty($this->options['d']))
        {
            $this->router['doc_id'] = (int)$this->options['d'] ; 
        }
        
    }
    /**
     * Если скрипт запущен с параметом вывода информации вывести режимы запуска
     */
    private function verbose()
    {
        if (in_array(@$this->argv[1], array('--help', '-help', '-h', '-?', '/help', '/h', '/?')))
        {
            foreach ($this->config['MODE_METHOD'] as $description_mode)
            {
                @$cfg .= "Usage: php " . $this->argv[0] . " {$description_mode}\n";
            }
            exit("\n {$cfg} \n");
        }
    }

    /** 
     * Установить реестры с которыми работать 
     */
    private function set_reestr()
    {
        if (!empty($this->options['r']))
        {
            $reestr = explode(" ", $this->options['r']);
            //Проверяем валидность реестров
            
            foreach ($reestr as $id)
            {
                Registry::get("Log")->log($id);
                if (array_search($id, $this->config['ALL_REESTR']) !== FALSE)
                    $this->router['reestr_id'][$id] = $id;
            }
        }  
        if (empty($this->router['reestr_id']))
        {
            $this->router['reestr_id'] = $this->config['DEFAULT_REESTR'];
        }
    }

    /**
     * Установить режим запуска 
     */
    private function set_mode()
    {
        //Установить режим по умолчанию . 
        $this->router['mode'] = key( $this->config['MODE_METHOD'] );
        //Установить выбранный режим работы
        $mode = ( @$this->options['p'] ? $this->options['p'] : FALSE ); 
        //проверить валидностьб выбранного режима работы        
        if (!empty( $mode ))
        {
            $mode = trim(str_replace("-", "_", $mode));
            //Определяем запуск какого режима требуется запустить 
            if (!empty($this->config['MODE_METHOD'][$mode]))
            {
                $this->router['mode'] = $mode;
            }
            else
            {
                throw new Exception("\n Mode not specifed \n");
            }
        }
        
        $this->router['mode'] = $this->router['mode'] . "_mode";
        $this->router['all'] = (!empty($this->options['a']) ? TRUE : FALSE)  ; 
        
        //Проверить существование метода
        if (!method_exists('DocumentIndexer', $this->router['mode']))
        {
            throw new Exception("\n Method : " . $this->router['mode'] . " - not exists! \n");
        }
        
    }

}
?>