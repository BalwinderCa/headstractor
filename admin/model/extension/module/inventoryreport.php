<?php
class ModelExtensionModuleinventoryreport extends Model {
public function createTable() {

$querystring = "SHOW columns from ".DB_PREFIX ."product where field='sr_costprice'";
$result = $this->db->query($querystring)->rows;

if (count($result)== 0)
{
		$querystring = "ALTER TABLE ".DB_PREFIX ."product ADD sr_costprice decimal(15,4);";
		
		$this->db->query($querystring);
}



$querystring = "SHOW columns from ".DB_PREFIX ."product_option_value where field='sr_costprice'";
$result = $this->db->query($querystring)->rows;

if (count($result)== 0)
{
		$querystring = "ALTER TABLE ".DB_PREFIX ."product_option_value ADD sr_costprice decimal(15,4);";
		
		$this->db->query($querystring);
}

}

public function deleteTable() {

//nothing happens

}












}
?>