<?php

/**
 * Получить файл документа с сервера OPS
 * @author Михаил Орехов
 */
class LoadFile {

    private $config;
    private $server;
    private $path;

    public function __construct($config)
    {
        $this->server = "fips-maksed.ru";
        $this->path = "/";
        $this->config = $config;
    }

    /** Установить путь к файлу */
    private function set_path()
    {
        return "http://" . $this->server . "/" . $this->path . substr($_GET['doc'], 7);
    }

    public function select_html($doc_html_file)
    {

        $html = $this->get_file(
                $this->set_path($doc_html_file));

        return $html;
    }

    /** получить файл с сервера */
    private function get_file($path)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['BASE_AUTH_USER'] . ':' . $this->config['BASE_AUTH_PASS']);
        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] === 200)
            return $content;
        else
            return FALSE;
    }

}

?>
