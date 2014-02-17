<?php


/**
 * Шаблон для построения классов типа Reestr_{int} 
 *
 * @author Михаил Орехов
 */
abstract class ReestrAbstract 
{
    public abstract function parse($param) ;
    public abstract function save($param) ;
    
    protected $html ;
    protected $log_id ;
    protected $config = array(); 
    protected $pdo = array(); 
    /** var $fields поля для парсинга */
    protected $fields = array(
        "link_id" => "",
        "link_number" => "",
        "p732" => "", 
        "p731" => "",
        "p732_init" => "",
        "p731_init" => "",
        "p731_date" => "" ,
        "p210" => "" ,
        "p111" => "" ,
        "p540_file" => "" ,
        "p540_txt" => "" ,
        "p740" => "" , 
        "p750" => "",
        "doc_link" => "" ,
        "doc_html_file" => "" ,
        "doc_img_file" => "" ,
        "doc_list1_file" => "" ,
        "reestr_id" => "" ,
        "status_id" => "" ,
        "status_date" => "" ,
        "doc_img_link" => "" ,
        "doc_list1_link" => ""
    );
    
    protected function select_html($html_file)
    {
        if($html = $this->LoadFile->select_html($html_file))
        {
            $this->log("document {$html_file} loaded");
            return $html ; 
        } 
        $this->log("document {$html_file} is not loaded");
        return false ; 
    }
    
    public function __construct($config , $pdo)
    {
        $this->config = $config ; 
        $this->pdo = $pdo ; 
        $this->LoadFile = new LoadFile($config) ; 
    }
    
    public function init($html)
    {
        $this->html = $html ; 
    }
    public function set_log($reeestr_id)
    {
        $this->log_id = (int)$reeestr_id ; 
    }
    
    /** Получить значение полей из запроса */
    protected function set_default_fields(array $row)
    {
        $this->fields['link_id'] = $row['id'];
        $this->fields['doc_link'] = $row['doc_link'];
        $this->fields['doc_html_file'] = $row['doc_html_file'];
        $this->fields['doc_img_file'] = $row['doc_img_file'];
        $this->fields['doc_list1_file'] = $row['doc_list1_file'];
        $this->fields['reestr_id'] = $row['reestr_id'];
    }
    /** Общий метод ля получения любого поля */
    protected function get_field($code)
    {
        if (preg_match("#\($code\).+?<B>(.*?)</B>#i", $this->html, $field_res))
        {
            return trim($field_res[1]);
        }
        return '';
    }
    
    /**
     * Логировать парсер
     */
    protected function log($msg)
    {
        $msg = "[" . $this->log_id . "]" . $msg . "\n";
        if ($this->config['DEBUGGING_PRINT'])
            print $msg;
        //Записать лог
        if ($this->config['DEBUGGING_LOG'])
        {
            $current = file_get_contents($this->config['DEBUGGING_LOG']);
            $current .= $msg;
            file_put_contents($this->config['DEBUGGING_LOG'], $current);
        }
    }
    

    
    


}

?>
