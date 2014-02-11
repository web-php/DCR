<?php

class DynConfig {

	private $pdo;

	//-------------------------------------------
	public function __construct($pdo){
		$this->pdo = $pdo;
	}

	//-------------------------------------------
	public function write_num_value($param_name, $param_value){
		$sth = $this->pdo->prepare("
			UPDATE	`config`
			SET		`value_num` = ?
			WHERE	`parameter` = ?
		");
		$sth->execute(
				array($param_value, $param_name)
		);
		return $sth->rowCount();
	}
	//-------------------------------------------
	public function read_num_value($param_name){
	
		$sth = $this->pdo->prepare("
			SELECT	`value_num`
			FROM 	`config`
			WHERE	`parameter` = ?
		");
		$sth->execute(array($param_name));
		
		if(!($row = $sth->fetch(PDO::FETCH_ASSOC))){
			return NULL;
		}
		return $row['value_num'];
	}
	//-------------------------------------------
	public function read_text_value($param_name){
	
		$sth = $this->pdo->prepare("
			SELECT	`value_text`
			FROM 	`config`
			WHERE	`parameter` = ?
		");
		$sth->execute(array($param_name));
		
		if(!($row = $sth->fetch(PDO::FETCH_ASSOC))){
			return NULL;
		}
		return $row['value_text'];
	}
	//-------------------------------------------
	public function read_date_value($param_name){
	
		$sth = $this->pdo->prepare("
			SELECT	`value_date`
			FROM 	`config`
			WHERE	`parameter` = ?
		");
		$sth->execute(array($param_name));
		
		if(!($row = $sth->fetch(PDO::FETCH_ASSOC))){
			return NULL;
		}
		return $row['value_date'];
	}
	//-------------------------------------------
	public function read_text_values_by_mask($param_name){
	
		$sth = $this->pdo->prepare("
			SELECT	`value_text`, `desc`
			FROM 	`config`
			WHERE	`parameter`like ?
		");
		$sth->execute(array($param_name));
		
		if(!($rows = $sth->fetchAll(PDO::FETCH_ASSOC))){
			return NULL;
		}
		return $rows;
	}



}

?>