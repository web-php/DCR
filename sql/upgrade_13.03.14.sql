# база m4_fp
CREATE TABLE `dcr_error` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `link_id` int(32) NOT NULL COMMENT 'ключ на m4_fp.link.link.id' ,
  `error` int(32) NOT NULL COMMENT 'код ошибки error.id' ,
  `attempt` int(2) NOT NULL COMMENT 'колличество попыток' ,
  `parsed_date` datetime DEFAULT NULL  COMMENT 'Дата парсинга' , 
  `state` int(1) NOT NULL COMMENT '0 или 1 . 0 - действующая ошибка требуется повторная проверка , 1 - точно известно что документа нет' ,   
  PRIMARY KEY (`id`) ,
  UNIQUE KEY(`link_id` , `error`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ;

CREATE TABLE `error` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `error` varchar(255) DEFAULT NULL COMMENT 'текстовое обозначение ошибки' ,
  PRIMARY KEY (`id`) ,
  UNIQUE KEY(`error`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ;