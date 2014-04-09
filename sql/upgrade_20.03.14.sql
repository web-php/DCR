# Связь фразами документа и справочником мкту
# база m4_html
CREATE TABLE `doc_mktu_rel` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).',
  `doc_id` int NOT NULL COMMENT 'ИД документа. Ключ на doc_data.id',
  `kf_id` int NOT NULL COMMENT 'ИД фразы. Ключ на dcr_kf511.id', 
  `mktu_catalog_id` int NOT NULL COMMENT 'ИД фразы. Ключ на mktu_catalog.id',
  `sub_class_relevance` float NOT NULL COMMENT 'Процент ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rel` (`doc_id` , `kf_id` , `mktu_catalog_id`) 
) ENGINE=innodb AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

# Связь между справочником мкту и новыми перефразированными подклассаи + однородность 
CREATE TABLE `mktu_catalog_rel` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).',
  `mktu_catalog_id` int NOT NULL COMMENT 'ИД фразы. Ключ на mktu_catalog.id',  
  `mktu_language_id` int NOT NULL COMMENT 'ИД документа. Ключ на mktu_language.id',
  `kf` varchar(255) NOT NULL COMMENT 'Уникальная новая переработанная фраза' ,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rel` (`mktu_catalog_id` , `mktu_language_id` , `kf`) 
) ENGINE=innodb AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
