<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Reestr_9
 *
 * @author Михаил Орехов
 */
class Reestr_9 {
    //put your code here

    
    // Воспроизведение знака, 9-ый реестр
    protected function get_p540_txt($html)
    {
        if (preg_match("#<B>\(540\)</B>\s*Воспроизведение знака</TD>.*?<B>(.*?)</B>#i", $html, $p540_res))
        {
            return html_entity_decode(rtrim(ltrim($p540_res[1])), 0, 'UTF-8');
        }
        return FALSE;
    }
}

?>
