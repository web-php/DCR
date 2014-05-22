ALTER TABLE `doc_data`
ADD UNIQUE INDEX `unique` (`link_id`, `reestr_id`) USING HASH ; 
# исправит тип под OGRN 13 символов 
ALTER TABLE `doc_data`
MODIFY COLUMN `doc_number`  bigint UNSIGNED NULL DEFAULT NULL COMMENT 'Номер документа (публикации)' AFTER `p732_init`,
# Добавить новое поле , с 15,05,2014 стали появлятся в заявках фипса
ALTER TABLE `doc_data`
ADD COLUMN `ogrn`  bigint NULL DEFAULT NULL COMMENT 'ОГРН организации' AFTER `doc_number` ; 
