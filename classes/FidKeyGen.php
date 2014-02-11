<?php

require_once __DIR__ . "/DynConfig.php";

class FidKeyGen {

	const KEY_STATUS_VALID 		= 1;
	const KEY_STATUS_DONE 		= 2;
	const KEY_STATUS_UNVALID	= 3;

	private $pdo;
	private $DynConfig;
	private $moder_end_date;
	private $max_key_count;
	private $reestr_id_arr = array(0,1,2,3,4,5,6,7,8,9,10,11);

	private $keys_added = 0;
	private $docs_processed = 0;

	//----------------------------------
	public function __construct($pdo){
		$this->pdo = $pdo;
		$this->DynConfig = new DynConfig($pdo);
	}
	//----------------------------------
	public function set_max_key_count($max_key_count){
		$this->max_key_count = $max_key_count;
	}
	//----------------------------------
	public function get_docs_processed(){
		return $this->docs_processed;
	}
	//----------------------------------
	public function get_keys_added(){
		return $this->keys_added;
	}
	//----------------------------------
	public function run(){

		$this->moder_end_date = $this->DynConfig->read_date_value('FID_FKM_MODER_END');

		foreach($this->reestr_id_arr as $reestr_id){
			$this->set_keys_for_reestr($reestr_id);
		}

	}
	//----------------------------------
	private function set_keys_for_reestr($reestr_id){
		do{
			$doc_data_rows = $this->get_docs($reestr_id);
			if($doc_data_rows){

				do{
					$uniq_key = $this->gen_key();
				}while(!($key_id = $this->insert_key($reestr_id, $uniq_key)));
				$this->keys_added++;

				foreach($doc_data_rows as $id => $doc_data_row){
					$this->update_doc_key($doc_data_row['id'], $key_id);
					$this->docs_processed++;
				}
			}
		}while($doc_data_rows);
	}

	//----------------------------------
	private function gen_key(){
		return 'K' . $this->generate_random_string();
	}
	//----------------------------------
	private function generate_random_string($length = 11) {
		return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length); //Можно заменит на регулярку
	}
	//----------------------------------
	private function update_doc_key($doc_id, $key_id){
		$sth = $this->pdo->prepare("
			UPDATE	`doc_data`
			SET		`moder_key_id` = ?
			WHERE	`id` = ?
		");
		$sth->execute(
				array($key_id, $doc_id)
		);
		return $sth->rowCount();
	}
	//----------------------------------
	private function get_docs($reestr_id){

		$sth = $this->pdo->prepare("
			SELECT	doc_data.id
			FROM	`doc_data`
						INNER JOIN `link` ON link.id = doc_data.link_id
						LEFT JOIN `moder_key` ON  doc_data.moder_key_id = moder_key.id

			WHERE	(moder_key.key_status = ? OR `moder_key_id` IS NULL) AND
					`creation_date` < ? AND
					link.reestr_id = ? AND
					(`moder_status` IS NULL OR `moder_status` = 0)
			ORDER BY doc_data.id
			LIMIT " .  $this->max_key_count
		);
		$sth->execute(array(
					self::KEY_STATUS_UNVALID, $this->moder_end_date, $reestr_id
				));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	//----------------------------------
	private function insert_key($reestr_id, $moder_key){

		$sth = $this->pdo->prepare("
			INSERT INTO `moder_key`
				(`moder_key`, `reestr_id`, `key_status`)
			VALUES
				(?, ?, ?)"
		);
		$sth->execute(
				array($moder_key, $reestr_id, self::KEY_STATUS_VALID)
		);
		return $this->pdo->lastInsertId();

	}

}


?>