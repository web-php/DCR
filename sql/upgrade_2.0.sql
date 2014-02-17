ALTER TABLE  `doc_data` ADD `p740` tinytext COMMENT '(740) Патентный поверенный (полное имя, регистрационный номер, местонахождение)' AFTER `moder_status` ;

CREATE TABLE `doc_p732` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doc_id` int(10) NOT NULL COMMENT 'id КЛЮЧ на doc_data.id',
  `date_pub` datetime DEFAULT NULL COMMENT 'дата публикации' , 
  `date_edits` datetime DEFAULT NULL  COMMENT 'p580 дата внесения изменений',
  `type_edits` varchar(500) DEFAULT NULL COMMENT 'тип изменений' ,
  `p732` varchar(500) DEFAULT NULL COMMENT 'p732 правообладатель' ,
  `hash` varchar(32) NOT NULL COMMENT 'хэш всех полей уникальное поле' ,   
  PRIMARY KEY (`id`) ,
  UNIQUE KEY(`hash`),
  KEY `p732` (`p732`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;