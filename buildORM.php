<?php
	//copyright fred.trotter@gmail.com
	//Not Only Development, LLC 2012
	//Licensed under the same license as Sequelize
	//https://github.com/sdepold/sequelize/blob/master/LICENSE

	$yaml_file = 'config.yaml';

	if(function_exists('yaml_parse_file')){ //then we are using PECL..
		$config = yaml_parse_file($yaml_file);
	}else{
		echo "use pecl to install php yaml";
		exit();
	}
	$user = $config['user'];
	$password = $config['password'];
	$database = $config['database'];
	mysql_connect($config['host'],$user,$password);
	mysql_select_db($database);

	//Add new code generators here
	require_once('laravelCode.php');
	$lCode = new laravelCode(); 

	if(isset($config['laravel_dir'])){
		$lCode->output_dir = $config['laravel_dir'];
	}else{
		//we put things into the default which is hardcoded into $lCode... etc...
	}

	$allCodeGen = array( $lCode);





	$tables_sql = "SHOW TABLES";

	$result = mysql_query($tables_sql) or die("arrgh... my eye!!! $tables_sql");

	while($row = mysql_fetch_array($result)){		
		$this_table = $row[0];
		echo "found $this_table \n";
		$tables[] = $this_table;
	}

	$other_tables = array(); //lets make sure we have references where we need them...
	
	$object_names = array();
	$object2table = array();


	//first pass handles case
	//BTW fuck plurals... we just take one 's' off of the end of everything if its there
	//Ignore it if its not...
	foreach($tables as $this_table){

		if(substr($this_table, -1, 1) == 's') { //I cannot remeber why this is here???
  			$object_name = substr($this_table, 0, -1);
		}else{
			$object_name = $this_table;
		}
		$object_names[$this_table] = $object_name;
		$object2table[strtolower($object_name)] = $this_table;
	}
	//We have to do this because we need to have all of the object names 
	//to do anything intelligent with modeling...
	//now we can rely on $object_names to be full

	$has_many = array();
	$belongs_to = array();	
	$table_data = array();

	//The second pass over the tables we are looking for valid 
	foreach($tables as $this_table){

		$fields_sql = "SELECT * 
FROM `INFORMATION_SCHEMA`.`COLUMNS` 
WHERE `TABLE_SCHEMA`='$database' 
    AND `TABLE_NAME`='$this_table';";

		$result = mysql_query($fields_sql) or die("doh!\n $fields_sql \n".mysql_error());
		
		$object_name = $object_names[$this_table];

		$object_label = un_camelcase_string($object_name);
		echo "\nWorking on table $this_table -> $object_name -> $object_label \n";

		$all_cols = array();

		//For the sake of sanity keep this model in mind as you read this code..
		// Person ->
		// 	has_many_Editor_Book()
		//	has_many_Author_Book()
		// Book -> 
		//	belongs_to_Author_Person()
		//	belongs_to_Editor_Person()
		//
		// Each entry in the book table has two ids:
		//	 Author_Person_id
		//	 Editor_Person_id
		// All four of the functions in the objects should be modeled correctly by the 
		// contents of the has_many and belongs_to array. so:
		// $has_many['Book']['Author'] = 'Person';
		// $has_many['Book']['Editor'] = 'Person';
		// $belongs_to['Person']['Author'] = 'Book';
		// $belongs_to['Person']['Editor'] = 'Book';

		while($row = mysql_fetch_array($result)){
		//first pass for modeling...
			
		//second pass for creating actual code...
			$foreign_key = false;
		
			//var_export($row);

			$col_name = $row['COLUMN_NAME'];
			$all_cols[] = $col_name;
			$col_label = un_camelcase_string($col_name);

			if(strpos($col_name,'_id') != 0){
	//			echo "\twe have an id tag!!\t";
				//then this is an id... 
				//the col_label already has the id trimmed...
				//lets pretend case is unimportant for now...

				$col_array = explode('_',$col_name);
				$throw_away_the_id = array_pop($col_array); // we don't need _id...
				$other_table_tag = array_pop($col_array);
				$relationship = implode('_',$col_array);
				if(strlen($relationship) == 0){
					$relationship = $other_table_tag;
				}else{

				}

	//			echo "searching for $other_table_tag in object2table to model $relationship\t";
	
				if(isset($object2table[strtolower($other_table_tag)])){
					echo "found it\t";
					//this just doesnt look like a link!! it -is- one.


					$has_many_tmp = array( 
							'prefix' => $relationship,
							'type' => $object_name
							);
					$key = $relationship . '_' . $object_name;
					$has_many[$other_table_tag][$key] = $has_many_tmp;

					$belongs_to_tmp = array( 
							'prefix' => $relationship,
							'type' => $other_table_tag
							);

					$key = $relationship . '_' . $other_table_tag;
					$belongs_to[$object_name][$key] = $belongs_to_tmp;	
   				}
	//			echo "\n";
			}

		}//done dealing with columns...

		$table_data[$this_table] = $all_cols;

	}//moving to the next table
	
	foreach($allCodeGen as $thisCodeGenerator){
		foreach($table_data as $this_table => $table_cols){

		$object_name = $object_names[$this_table];
		$object_label = un_camelcase_string($object_name);
		if(isset($has_many[$object_name])){
			$this_has_many = $has_many[$object_name];
		}else{
			$this_has_many = array();	
		}
		
		if(isset($belongs_to[$object_name])){
			$this_belongs_to = $belongs_to[$object_name];
		}else{
			$this_belongs_to = array();
		}

		$lCode->generate(array(
					'object_name'	=> $object_name,
					'table_name' => $this_table,
					'table_cols' => $table_cols,
					'has_many' => $this_has_many,
					'belongs_to' => $this_belongs_to,
					'database' => $database,
					'object_label' => $object_label ));


		}//moving to the next table
	}//moving to the next code generator

function un_camelcase_string($string){
        $string = preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $string);
        $string = trim($string);
        $string = preg_replace('/_id$/', '', $string);
	return($string);
}
	
?>
