<?php

/**
 * Description of Log
 *
 * @author Михаил Орехов
 */
class Log {

    private $cfg;

    /** Кэш ошибок */
    private $error = array();

    //put your code here+
    public function __construct()
    {
        $this->time_start = $this->microtime_float();
        $this->cfg = Registry::get("CONFIG");
    }

    public function log($msg, $err = FALSE)
    {
        $this->error_log($msg, $err);
        if (!$this->cfg['DEBUGGING_PRINT'])
            return ;

        $this->time_end = $this->microtime_float();
        $id = (TradeMark::$link_id ? "link_id : " . TradeMark::$link_id : "");
        $msg = "[" . date("y.m d-h:i:s") . " ] $id  \t $msg \n";

        if ($this->cfg['DEBUGGING_TIME'])
            print "\t\t\t\t\t time:" . round(($this->time_end - $this->time_start), 4) . "\t memory usage:" . $this->convert(memory_get_usage(true)) . "\n";
        print $msg;

        //Записать лог
        if ($this->cfg['DEBUGGING_LOG'])
        {
            $current = file_get_contents($this->cfg['DEBUGGING_LOG']);
            $current .= $msg;
            file_put_contents($this->cfg['DEBUGGING_LOG'], $current);
        }
        $this->time_start = $this->microtime_float();
    }

    /**
     * Логировать ошибку
     */
    private function error_log($msg, $err = FALSE)
    {
        if (empty($err))
            return;
        if (!$err_id = array_search($msg, $this->error))
        {
            $err_id = Registry::get("DbIndexer")->add_field("error", $msg);
            $this->error[$err_id] = $msg;
        }
        if (!empty(TradeMark::$link_id))
        {
            Registry::get("DbIndexer")->add_doc_error(TradeMark::$link_id, $err_id);
        }
    }

    //замерить выполнение

    private function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    private function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

}

?>
