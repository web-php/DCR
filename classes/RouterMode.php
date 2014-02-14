<?php

/**
 * @author Михаил Орехов
 */
class RouterMode {

    private $options = array();
    private $argv = array();
    private $config = array();

    /** var array $router режим запуска и реестр по которому запускать программу */
    private $router = array("reestr_id" => '', "mode" => '');

    public function __construct(array $config, $argv)
    {
        $this->options = getopt("r:p:");
        $this->argv = $argv;
        $this->config = $config;
        $this->verbose();
        $this->set_reestr();
        $this->set_mode();
    }

    /**
     * @return array Получить массив режима запуска индексатора
     */
    public function get_router()
    {
        return $this->router;
    }

    /**
     * Если скрипт запущен с параметом вывода информации вывести режимы запуска
     */
    private function verbose()
    {
        if (in_array($this->argv[1], array('--help', '-help', '-h', '-?', '/help', '/h', '/?')))
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
                if (array_key_exists($id, $this->config['ALL_REESTR']))
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
        $this->router['mode'] = key($this->config['MODE_METHOD']);
        if (!empty($this->options['p']))
        {
            $mode = trim(str_replace("-", "_", $this->options['p']));
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
        //Проверить существование метода
        if (!method_exists('DocumentIndexer', $this->router['mode']))
        {
            throw new Exception("\n Method : " . $this->router['mode'] . " - not exists! \n");
        }
    }

}
?>