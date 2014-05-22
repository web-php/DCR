# Таблица символов
CREATE TABLE `dcr_symbol` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `symbol` varchar(100) DEFAULT NULL COMMENT 'Значение символа символ ":" расделитль пример - "№ слова : символ : позиция в слове " 1:A:1 ',
  `hash` varchar(32) NOT NULL COMMENT 'хэш всех полей уникальное поле' ,  
  `count` int NOT NULL DEFAULT '0' COMMENT 'колличество вхождений' ,
  PRIMARY KEY (`id`), 
  KEY i_symbol (symbol) ,
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ;

# Связь между символами и дкоументами 
CREATE TABLE `dcr_symbol_rel` (
  `id` int NOT NULL AUTO_INCREMENT ,
  `doc_id` int NOT NULL COMMENT 'id на doc_data.id',
  `symbol_id` int NOT NULL COMMENT 'id символа 540_symbol.id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rel` (`doc_id`,`symbol_id`)
) ENGINE=innodb AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;