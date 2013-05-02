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

	$tables_sql = "SHOW TABLES";

	$result = mysql_query($tables_sql) or die("arrgh... my eye!!! $tables_sql");

	$fields_to_change = array(
		'createdAt' => array( 'to' => 'created_at',
					'type' => 'DATETIME NOT NULL'),
		'updatedAt' => array( 'to' => 'updated_at',
					'type' => 'DATETIME NOT NULL'),
		);

	foreach($fields_to_change as $from => $to_array){
		$to = $to_array['to']; 
		$type = $to_array['type']; 

		while($row = mysql_fetch_array($result)){		
			$this_table = $row[0];

			$sql = "ALTER TABLE  `$this_table` CHANGE  `$from`  `$to` $type";
			echo "$sql \n";
			if(!mysql_query($sql)){ 
				echo "Error with: ".mysql_error()."\n";
			}else{
				echo "$this_table changed\n";
			}


		}//end while

	}//end foreach


	
?>
