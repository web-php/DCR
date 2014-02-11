
ALTER TABLE `doc_data` ADD `p220` datetime DEFAULT NULL COMMENT 'поле (220)Дата подачи' AFTER `p210` ;
ALTER TABLE `doc_data` ADD `p151` datetime DEFAULT NULL COMMENT 'поле (151)Дата регистрации' AFTER `p111` ;
