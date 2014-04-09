# Связь между справочником мкту и типами однородностей
CREATE TABLE `uniformity_rel` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).',
  `mktu_catalog_id` int NOT NULL COMMENT 'ИД фразы. Ключ на mktu_catalog.id',  
  `consumer_id` int NOT NULL COMMENT 'Ид потребителя uniformity_value.id' ,
  `type_id` int ,
  `shop_id` int ,
  `branch_id` int ,
  `showcase_id` int ,
  PRIMARY KEY (`id`) ,
  UNIQUE KEY `rel` (`mktu_catalog_id` , `consumer_id` , `type_id` , `shop_id` , `branch_id` , `showcase_id`) 
) ENGINE=innodb AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

# Значение однородностей
CREATE TABLE `uniformity_value` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` int NOT NULL,
  `value` varchar(255) DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE KEY(`type` , `value`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ;

# Добовление нового дата
ALTER TABLE  `dcr_fraza` ADD `count` int NOT NULL;
ALTER TABLE  `dcr_kf511` ADD `update` datetime DEFAULT NULL COMMENT 'Дата-время создания записи роботом' AFTER `hash` ;
ALTER TABLE  `dcr_words` ADD `update` datetime DEFAULT NULL COMMENT 'Дата-время создания записи роботом' AFTER `hash` ;

datetime DEFAULT NULL COMMENT 'Дата-время создания записи роботом',