# база m4_fp
ALTER TABLE  `doc_data` ADD `p740` tinytext COMMENT '(740) Патентный поверенный (полное имя, регистрационный номер, местонахождение)' AFTER `moder_status` ;

CREATE TABLE `doc_modification` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doc_id` int(10) NOT NULL COMMENT 'id КЛЮЧ на doc_data.id',
  `date_pub` datetime DEFAULT NULL  COMMENT 'Опубликовано:' , 
  `type_edits` int(10) NOT NULL COMMENT 'тип изменений ключ на type_edits.id' ,
  `designation` varchar(500) DEFAULT NULL COMMENT 'Указание об изменениях:' ,  
  `p580` datetime DEFAULT NULL  COMMENT 'p580 дата внесения изменений',
  `p732` varchar(500) DEFAULT NULL COMMENT 'p732 правообладатель' ,
  `p791` varchar(500) DEFAULT NULL COMMENT 'p791 лицензиат' , 
  `p793` varchar(500) DEFAULT NULL COMMENT 'p793 Указание условий и/или ограничений лицензии: ' , 
  `p740` varchar(500) DEFAULT NULL COMMENT 'p740 Имя и адрес представителя 9реестр (патентный поверенный 6-7реестр)' ,
  `p750` varchar(500) DEFAULT NULL COMMENT 'p750 Адрес для переписки:' ,  
  `p770` varchar(500) DEFAULT NULL COMMENT 'p770 Прежний владелец' ,
  `p771` varchar(500) DEFAULT NULL COMMENT 'p771 Прежнее наименование/имя правообладателя: ' ,
  `hash` varchar(32) NOT NULL COMMENT 'хэш всех полей уникальное поле' ,   
  PRIMARY KEY (`id`) ,
  UNIQUE KEY(`hash`) ,
  KEY `p732` (`p732`) , 
  KEY `p791` (`p791`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ;

CREATE TABLE `type_edits` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reestr_id` int NOT NULL COMMENT 'id реестра ключ на reestr.id',  
  `type_edits` varchar(500) DEFAULT NULL COMMENT 'тип изменений' ,
  `hash` varchar(32) NOT NULL COMMENT 'хэш всех полей уникальное поле' ,   
  PRIMARY KEY (`id`) ,
  UNIQUE KEY(`hash`),
  KEY `reestr_id` (`reestr_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ;
