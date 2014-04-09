
# база m4_html

/******************** Связи **********************/

# Связь между фразами и документами
CREATE TABLE `dcr_fraza_rel` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).',
  `class_id` int(10) NOT NULL COMMENT 'Класс фразы',   
  `doc_id` int(10) NOT NULL COMMENT 'ИД документа. Ключ на doc_data.id',
  `fraza_id` int(20) NOT NULL COMMENT 'ИД фразы. Ключ на dcr_fraza.id',
  INDEX (`class_id`) ,
  INDEX (`doc_id`) ,
  INDEX (`fraza_id`) ,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rel` (`class_id` , `doc_id` , `fraza_id`)
) ENGINE=innodb AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

# Таблица исходных фраз мкту разделитель " ; "
CREATE TABLE `dcr_fraza` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).',
  `fraza` text NOT NULL COMMENT 'Уникальная исходная фраза',
  `hash` varchar(32) NOT NULL COMMENT 'хэш всех полей уникальное поле' ,  
  PRIMARY KEY (`id`), 
  UNIQUE KEY `hash` (`hash`)
) ENGINE=innodb DEFAULT CHARSET=utf8;



# Связь между фразами и документами
CREATE TABLE `dcr_kf511_rel` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).' ,  
  `fraza_id` int(10) NOT NULL COMMENT 'ИД фразы. Ключ на dcr_fraza.id' ,
  `kf_id` int(20) NOT NULL COMMENT 'id на dcr_kf511.id' ,
  UNIQUE KEY `rel` (`fraza_id` ,`kf_id`) ,
  PRIMARY KEY (`id`)
) ENGINE=innodb AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

# Связь между словами и фразами
CREATE TABLE `dcr_words_rel` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).',
  `kf_id` int(20) NOT NULL COMMENT 'id на dcr_kf511.id',
  `words_id` int(20) NOT NULL COMMENT 'id слова dcr_words.id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rel` (`kf_id`,`words_id`)
) ENGINE=innodb AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

/******************** Слова **********************/

# Слова из которых состоят кф разделитель " "
CREATE TABLE `dcr_words` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).' ,
  `words` varchar(255) NOT NULL COMMENT 'Уникальное исходное слово' ,
  PRIMARY KEY ( `id` ),
  UNIQUE KEY `words` ( `words` )
  `hash` varchar(32) NOT NULL COMMENT 'хэш всех полей уникальное поле' ,  
) ENGINE=innodb DEFAULT CHARSET=utf8;

# Ключевые фразы из которых состоят кф разделитель " , "
CREATE TABLE `dcr_kf511` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).' ,
  `kf511` text NOT NULL COMMENT 'Уникальная исходная фраза' ,
  `hash` varchar(32) NOT NULL COMMENT 'хэш всех полей уникальное поле' ,  
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=innodb DEFAULT CHARSET=utf8;

# Таблица исходных фраз мкту разделитель " ; "
CREATE TABLE `dcr_fraza` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).',
  `fraza` text NOT NULL COMMENT 'Уникальная исходная фраза',
  `hash` varchar(32) NOT NULL COMMENT 'хэш всех полей уникальное поле' ,  
  PRIMARY KEY (`id`), 
  UNIQUE KEY `hash` (`hash`)
) ENGINE=innodb DEFAULT CHARSET=utf8;



# Индекс по справочнику МКТУ

# Ключевые фразы из которых состоят кф разделитель " , "
CREATE TABLE `mktu_word` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).' ,
  `mktu_word` VARCHAR(255) NOT NULL COMMENT 'Уникальная исходная фраза' ,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mktu_word` (`mktu_word`)
) ENGINE=innodb DEFAULT CHARSET=utf8;

# Связь между словами и фразами
CREATE TABLE `mktu_word_rel` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Внутренний ИД. Назначается программно (autoincrement).',
  `mktu_catalog_id` int(20) NOT NULL COMMENT 'id на mktu_catalog.id',
  `mktu_word_id` int(20) NOT NULL COMMENT 'id слова mktu_word.id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rel` (`mktu_catalog_id`,`mktu_word_id`)
) ENGINE=innodb AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
