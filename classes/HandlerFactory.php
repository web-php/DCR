<?php

/**
 * Фабрика обработчика документов относительно реестров
 * @author Михаил Орехов
 */
class HandlerFactory {

    /** var array зарегестрированнные в системе обработчики реестров */
    public static $HandlerName = array("Reestr_6" => "",
        "Reestr_7" => "",
        "Reestr_8" => "",
        "Reestr_9" => "",
        "Reestr_11" => "",
        "Reestr_12" => "");

    /**
     * Вернуть в зависимости от условия требуемый экземпляр класса для работы с реестром реестра
     */
    public static function GetInstance($reestr_id, array $config, array $pdo)
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
        require( $PatchClass );
        $Instance = new $Class( $config, $pdo );
        $Instance->set_log( $reestr_id ); 
        return $Instance;
    }

}

?>
