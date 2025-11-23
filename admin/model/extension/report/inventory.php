<?php
class ModelExtensionReportInventory extends Model {
	function csv_to_array($filename='', $delimiter=',')
{
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;

    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
        {
            if(!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }
    return $data;
}
	public function putincsv($list){
//print_r($list);exit;
	$heads=array();	
     foreach($list as $key=>$value){
     	foreach($value as $keyval=>$val){

     	 $heads[]=$keyval;
     	}
     	break;
     }
   

     #$fp = fopen('file.csv', 'w');
 $filename = "inventory.csv";

    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename=' . $filename);
    header("Content-Transfer-Encoding: UTF-8");

    $fp = fopen('php://output', 'a');

    fputcsv($fp, $heads);
 
 foreach ($list as $fields) {
    fputcsv($fp, $fields);
 }

fclose($fp);
}
		
	public function getProductinventoryReport($start, $limit, $count, $filter, $sort, $calc, $store) {
		global $keepsort;
		global $keepdir;
	
		$total = 0;

		$product_data = array();
		
		if ($start < 0) {
			$start = 0;
		}
		
		if ($limit < 1) {
			$limit = 20;
		}
		
					
		if ($filter == 0 )
		{
		$query = $this->db->query("SELECT p.product_id, p.quantity AS stock, pd.name, p.model, p.price, p.subtract, p.sr_costprice, p.sku, p.image FROM " . DB_PREFIX . "product p RIGHT JOIN " . DB_PREFIX . "product_to_store pts ON (p.product_id = pts.product_id) , " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id and pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pts.store_id = '". (int)$store ."' AND p.status ='1' ORDER BY p.quantity ASC");
		}
		else
		{
		$query = $this->db->query("SELECT p.product_id, p.quantity AS stock, pd.name, p.model, p.price, p.subtract, p.sr_costprice, p.sku, p.image FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id) right JOIN " . DB_PREFIX . "product_to_store pts ON (p.product_id = pts.product_id), " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id and pd.language_id = '" . (int)$this->config->get('config_language_id') . "'  AND ptc.category_id = '" . (int)$filter . "' AND pts.store_id = '". (int)$store ."' AND p.status ='1' ORDER BY p.quantity ASC");
		
		
		}
		
		foreach ($query->rows as $result) {
			
		$product_stock = $result['stock'];
		$product_id =  $result['product_id'];
		$product_name = $result['name'];

		$product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order");
		
		foreach ($product_option_query->rows as $product_option) {
			if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image'|| $product_option['type'] == 'checkboxQuantity') {
				$product_option_value_data = array();
			
				$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order");
				
				foreach ($product_option_value_query->rows as $product_option_value) {
						
						if ($product_option_value['subtract'] == 1)
							{
							$subtract = 'Y';
							
							}
						else
							{
							$subtract = 'N';
							}
				
				    if ($calc){$product_stock = $product_stock + (int)$product_option_value['quantity'];} // calculate is option is set
					$option_stock =  $product_option_value['quantity'];
					$sellprice = ($product_option_value['price_prefix']== "+" ? floatval( $result['price'] + $product_option_value['price']): floatval( $result['price'] - $product_option_value['price']));
					$product_data[] = array(
						'id'                	=> $product_option_value['product_id'],
						'option_id' 		=> 	$product_option_value['product_option_value_id'],
						'name'              	=> $product_name,
						'model'               => $product_option_value['name'],
						'totalstock' 	=>  0,
						'stock'              	=> $option_stock,
						'pricesell'   		=> $sellprice,
						'pricebuy'  		=> $product_option_value['sr_costprice'],
						'stockval' 		=> $product_option_value['quantity'] * $product_option_value['sr_costprice'],
						'margin'  		=> $sellprice - $product_option_value['sr_costprice'],
						'subtract' 		=> $subtract,
						'image'			=>  $product_option_value['image'],
						'sku'			=>  $result['sku']
					);
					
				}
									
				
			}
		}
		
		if ($result['subtract'] == 1 )
			{
				$subtract = 'Y';
			}
			else
			{
				$subtract = 'N';
			}
			
			$product_data[] = array(
				'id'    => $result['product_id'],
				'option_id' 	=> '0',
				'name'    	=> $result['name'],
				'model'  	=> $result['model'],
				'totalstock' 	=>  $product_stock,
				'stock' 	=>  $result['stock'],
				'pricesell'   	=> $result['price'],
				'pricebuy'   	=> $result['sr_costprice'],
				'stockval'  	=>$result['stock'] *$result['sr_costprice'],
				'margin'  	=> $result['price'] - $result['sr_costprice'],
				'subtract' 	=> $subtract,
				'image'			=>  $result['image'],
				'sku'			=>  $result['sku']
			);
		
		
		}
		
		
		
		
		$count =  count($product_data);
		
 
		$stock = array();
		global $keepsort;
		global $keepdir;

		if ($sort =='')
		{
			$sort = 'name';
			$direction =SORT_ASC;
		}
		else
		{
			$direction =  substr($sort,-1) == 'a' ?  SORT_ASC : SORT_DESC;
			$sort = substr($sort,0,strlen($sort)-1);
			
		
		}
   foreach ($product_data as $product) {
    $stock[] = $product[$sort];
   }
		$array_lowercase = array_map('strtolower', $stock);

		array_multisort($array_lowercase, $direction, $product_data);
		// array_multisort($product_data, SORT_ASC,  'stock');
		$return_data = array_slice($product_data, ($start - 1) * $limit  , $limit);
		
		
		return $return_data;
	}	
	
	public function getTotalProducts()
	{
		$query = $this->db->query("SELECT type,COUNT(*) AS total FROM " . DB_PREFIX . "product_option where product_option.type in ('select','radio','checkbox','image')");
		$temp =$query->row['total'];
		
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product ");
		$temp = $temp + $query->row['total'];
		
		return $temp;
	
	}
	
	function uploadProducts( $products ) {
	
	
			
		
		foreach ($products as $product) {

		
		   $subtract = strtoupper($product['subtract']) == 'Y' ? 1 : 0;
			
			if (	$product['option_id'] != 0)
			{//update item at option level
			
				$query = $this->db->query("SELECT price FROM ".DB_PREFIX."product WHERE product_id=".$product['id'].";");
				$tempprice = $query->row['price'];
				$query = $this->db->query("SELECT price_prefix FROM ".DB_PREFIX."product_option_value WHERE  product_option_value_id=" .$product['option_id'].";");
				$tempprefix =$query->row['price_prefix'];
				
				if ($tempprefix == '+')
				{ $product['pricesell'] = $product['pricesell'] - $tempprice ;}
				elseif ($tempprefix == '-')
				{ $product['pricesell'] = $tempprice - $product['pricesell'] ;}
			  // % en = nog toevoegen om option boost te ondersteunen.	
			  
			  	
									
							
				$this->db->query("UPDATE ".DB_PREFIX."product_option_value SET quantity=" .$product['stock']. ", price=".(float)$product['pricesell'].", sr_costprice='".$product['pricebuy']."', subtract=".$subtract."  WHERE product_option_value_id=" .$product['option_id'].";");
			}
			else
			{//update item at product level
			
			
				$this->db->query("UPDATE ".DB_PREFIX."product SET quantity=".$product['stock'].", price=".(float)$product['pricesell'].", sr_costprice='".$product['pricebuy']."', subtract=".$subtract." WHERE  product_id=".$product['id'].";");
			}
		}
			
		return TRUE;
	
	}
	
	function getCell(&$worksheet,$row,$col,$default_val='') {
		$col -= 1; // we use 1-based, PHPExcel uses 0-based column index
		$row += 1; // we use 0-based, PHPExcel used 1-based row index
		return ($worksheet->cellExistsByColumnAndRow($col,$row)) ? $worksheet->getCellByColumnAndRow($col,$row)->getValue() : $default_val;
	}
	
	
	protected function detect_encoding( $str ) {
		// auto detect the character encoding of a string
		return mb_detect_encoding( $str, 'UTF-8,ISO-8859-15,ISO-8859-1,cp1251,KOI8-R' );
	}
	
	function validateUpload( &$reader )
	{return TRUE;
		if ($reader->getSheetCount() != 1) {
			error_log(date('Y-m-d H:i:s - ', time()).$this->language->get('error_sheet_count')."\n",3,DIR_LOGS."error.txt");
			return FALSE;
		}
		
		return TRUE;
	}
	
	function clearCache() {
		$this->cache->delete('*');
	}
	
		function upload( $filename ) {
		// we use our own error handler
		global $config;
		global $log;
		$config = $this->config;
		$log = $this->log;
	
		$database =& $this->db;

		// use a generous enough configuration, the Import can be quite memory hungry (this needs to be improved)
		// ini_set("memory_limit","512M");
		// ini_set("max_execution_time",180);
		//set_time_limit( 60 );

		// we use the PHPExcel package from http://phpexcel.codeplex.com/
		#$cwd = getcwd();
		#chdir( DIR_SYSTEM.'PHPExcel' );
		#require_once( 'Classes/PHPExcel.php' );
		#chdir( $cwd );

		// parse uploaded spreadsheet file
		#$inputFileType = PHPExcel_IOFactory::identify($filename);
		#$objReader = PHPExcel_IOFactory::createReader($inputFileType);
		#$objReader->setReadDataOnly(true);
		#$reader = $objReader->load($filename);

		// read the various worksheets and load them to the database
		$ok = $this->validateUpload( $filename );
		if (!$ok) {
			return FALSE;
		}
		$this->clearCache();
		#echo $filename;exit;
		$csv_data=$this->csv_to_array($filename);
		
		$ok = $this->uploadProducts( $csv_data );
		if (!$ok) {
			return FALSE;
		}
		
		return TRUE;
	
}

protected function clearSpreadsheetCache() {
		$files = glob(DIR_CACHE . 'Spreadsheet_Excel_Writer' . '*');
		
		if ($files) {
			foreach ($files as $file) {
				if (file_exists($file)) {
					@unlink($file);
					clearstatcache();
				}
			}
		}
	}
	
	
	
	public function createXLS($categoryfilter, $storefilter) {
	
	// we use our own error handler
		global $config;
		global $log;
		$config = $this->config;
		$log = $this->log;
		set_error_handler('error_handler_for_export',E_ALL);
		register_shutdown_function('fatal_error_shutdown_handler_for_export');
		$count=100000;
		$results = $this->model_extension_report_inventory->getProductinventoryReport(1 , 100000, $count, $categoryfilter,'',0,$storefilter);

		
		
		//$results=array_push($results, $heads);
		$this->putincsv($results);
		exit;
	
	    $cwd = getcwd();
		chdir( DIR_SYSTEM.'pear' );
		require_once "Spreadsheet/Excel/Writer.php";
		chdir( $cwd );
		
		// Creating a workbook
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook->setTempDir(DIR_CACHE);
		$workbook->setVersion(8); // Use Excel97/2000 BIFF8 Format
		$priceFormat =& $workbook->addFormat(array('Size' => 10,'Align' => 'right','NumFormat' => '######0.0000'));
		$boxFormat =& $workbook->addFormat(array('Size' => 10,'vAlign' => 'vequal_space' ));
		$weightFormat =& $workbook->addFormat(array('Size' => 10,'Align' => 'right','NumFormat' => '##0.0000'));
		$textFormat =& $workbook->addFormat(array('Size' => 10, 'NumFormat' => "@" ));
		
		// sending HTTP headers
		$workbook->send('opencart_stock_report.xls');
		
		$worksheet =& $workbook->addWorksheet('Products');
		$worksheet->setInputEncoding ( 'UTF-8' );
		//$this->populateProductsWorksheet( $worksheet, $database, $languageId, $priceFormat, $boxFormat, $weightFormat, $textFormat );
		// Set the column widths
		$j = 0;
		$worksheet->setColumn($j,$j++,max(strlen('product_id'),4)+1);
		$worksheet->setColumn($j,$j++,max(strlen('option_id'),4)+1);
		$worksheet->setColumn($j,$j++,max(strlen('name'),30)+1);
		$worksheet->setColumn($j,$j++,max(strlen('model'),30)+1);
		$worksheet->setColumn($j,$j++,max(strlen('subtract'),4)+1);
		$worksheet->setColumn($j,$j++,max(strlen('stock'),12)+1);
		$worksheet->setColumn($j,$j++,max(strlen('pricesell'),10)+1,$priceFormat);
		$worksheet->setColumn($j,$j++,max(strlen('pricebuy'),10)+1,$priceFormat);
		$worksheet->setColumn($j,$j++,max(strlen('stockval'),10)+1,$priceFormat);
		$worksheet->setColumn($j,$j++,max(strlen('margin'),10)+1,$priceFormat);
		$worksheet->setColumn($j,$j++,max(strlen('image'),30)+1);
		$worksheet->setColumn($j,$j++,max(strlen('sku'),30)+1);
		
		// The product headings row
		$i = 0;
		$j = 0;
		$worksheet->writeString( $i, $j++, 'product_id', $boxFormat );
		$worksheet->writeString( $i, $j++, 'option_id', $boxFormat );
		$worksheet->writeString( $i, $j++, 'name', $boxFormat );
		$worksheet->writeString( $i, $j++, 'model', $boxFormat );
		$worksheet->writeString( $i, $j++, 'subtract', $boxFormat );
		$worksheet->writeString( $i, $j++, 'stock', $boxFormat );
		$worksheet->writeString( $i, $j++, 'pricesell', $boxFormat );
		$worksheet->writeString( $i, $j++, 'pricebuy', $boxFormat );
		$worksheet->writeString( $i, $j++, 'stockval', $boxFormat );
		$worksheet->writeString( $i, $j++, 'margin', $boxFormat );
		$worksheet->writeString( $i, $j++, 'image', $boxFormat );
		$worksheet->writeString( $i, $j++, 'sku', $boxFormat );
	exit;
		
		// The actual products data
		$i += 1;
		$j = 0;
		

    	$results = $this->model_report_inventory->getProductinventoryReport(1 , 100000, $count, $categoryfilter,'',0,$storefilter);
        
         $this->load->model('tool/image');
		
			foreach ($results as $result) { 
		
			if ($result['image'] && file_exists(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 100, 100);
			} else {
				$image = $this->model_tool_image->resize('no_image.jpg', 100, 100);
			}
		
			//echo $result['name'].'<Br/>';
			$worksheet->setRow( $i, 26 );
			
		
			$worksheet->write( $i, $j++, $result['id'] );
			$worksheet->write( $i, $j++, $result['option_id'] );
//$worksheet->writeString( $i, $j++, $result['name'],$textFormat );
			$worksheet->writeString($i, $j++, html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'));
//$worksheet->writeString( $i, $j++, $result['model'],$textFormat );
			$worksheet->writeString($i, $j++, html_entity_decode($result['model'], ENT_QUOTES, 'UTF-8'));
			$worksheet->writeString( $i, $j++, $result['subtract'],$textFormat );
			$worksheet->write( $i, $j++, $result['stock']);
			$worksheet->writeString( $i, $j++, number_format($result['pricesell'],4),$priceFormat );
			$worksheet->writeString( $i, $j++, number_format($result['pricebuy'],4),$priceFormat );
			$worksheet->writeString( $i, $j++, number_format($result['stockval'],2),$priceFormat);
			$worksheet->writeString( $i, $j++, number_format($result['margin'],2),$priceFormat);
			$worksheet->writeUrl( $i, $j++, $image,$image,$textFormat );
//$worksheet->writeString( $i, $j++, $result['sku'],$textFormat );
			$worksheet->writeString($i, $j++, html_entity_decode($result['sku'], ENT_QUOTES, 'UTF-8'));
			
			
			$i += 1;
			$j = 0;
		}
		
		
		$worksheet->freezePanes(array(1, 1, 1, 1));
		
			// Let's send the file
		$workbook->close();
		
		// Clear the spreadsheet caches
		$this->clearSpreadsheetCache();
		exit;

	
	
	}
	

	
	
}


?>