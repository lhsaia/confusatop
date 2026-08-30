<?php

class db_name {
	public $db_name = "confusa_trn";

	public function __construct() {
		if (getenv('DB_NAME')) {
			$this->db_name = getenv('DB_NAME');
		}
	}
}

?>