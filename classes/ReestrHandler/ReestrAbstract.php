<?php


/**
 * Шаблон для построения классов типа Reestr_{int} 
 *
 * @author Михаил Орехов
 */
abstract class ReestrAbstract {
    
    protected $config = array(); 
    protected $pdo = array(); 
    /** var html Содержимое документа */
    protected $html ; 
    
    public function __construct($config , $pdo)
    {
        $this->config = $config ; 
        $this->pdo = $pdo ; 
    }
    
    public function init($html)
    {
        $this->html = $html ; 
    }
    
    public abstract function parse($param) ;
    public abstract function save($param) ;
    


}

?>
