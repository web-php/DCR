<?php

/**
 * Фабрика обработчика документов относительно реестров
 * @author Михаил Орехов
 */
class HandlerFactory {

    /** 
     * var array зарегестрированнные в системе обработчики реестров 
     * TODO : Исправить точку входа 
     */
    public static $HandlerName = array(
        "Reestr_6" => "",
        "Reestr_7" => "" ,
        "Reestr_8" => "" ,
        "Reestr_9" => "" ,
        "Reestr_11" => "" ,
        "Reestr_12" => "" , 
        "Reestr_23" => "" , 
        "Reestr_33" => "");

    /**
     * Вернуть в зависимости от условия требуемый экземпляр класса для работы с реестром
     */
    public static function GetInstance( $reestr_id )
    {
        //Проверить существование компонентов
        $Class = "Reestr_{$reestr_id}";
        $PatchClass = __DIR__ . "/ReestrHandler/{$Class}.php";
        if (array_key_exists($Class, self::$HandlerName))
        {
            if (!file_exists($PatchClass))
                throw new Exception(" File : {$PatchClass} not found");
        }
        else
        {
            throw new Exception(" Handler : Reestr_{$reestr_id} is not defined ");
        }
        //Загрузить файл класса , вернуть экземпляр класса
        require_once( $PatchClass );
        $Instance = new $Class();
        return $Instance;
    }

}

?>
