<?
error_reporting(E_ALL);
ini_set("display_errors", 1);

    if (!file_exists('database.config.php'))
    {
        throw new Exception("Missing config file 'database.config.php'. Initialize one based on the .dist version.");
    } else include_once('database.config.php');
    
	function frenchDateToMysqlDate($d)
	{
		return substr($d, 6, 4).'-'.substr($d, 3, 2).'-'.substr($d, 0, 2);
	}
	
	function db_dateFormat($colonne, $fmt)
	{
	
		return "date_format($colonne, '$fmt')";
	}
	
	function selectArray($q)
	{
		$res = array();
		$result = mysql_query($q);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$res[] = $row;
		}
		return $res;
	}
	
	function insert($q)
	{		
		mysql_query($q);
		return mysql_insert_id();
	}
	
	function open_db()
	{
		$link = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Could not connect: ' . mysql_error());
		mysql_select_db(DB_DBASE) or die('Could not select database');	
		return $link;
	}
	
	function close_db($link)
	{
		mysql_close($link);
	}
	
	function export($table)
	{
		$q = "select * from $table";
		$contents = selectArray($q);
		$res = array();
		foreach($contents as $line)
		{
			$q = "insert into $table set ";
			$v = array();
			foreach($line as $field=>$value)
			{
				$tmp = "$field=";
				if (is_null($value))
					$tmp .= 'NULL';
				else
				{
					$tmp .= '"'.mysql_escape_string($value).'"';
				}
				$v[]=$tmp;
			}
			$v = implode($v, ', ');
			$res[] = "$q $v";			
		}
		return $res;
	}
	
	function db_escape_string($str)
	{
		if (function_exists('mysql_real_escape_string'))
			return mysql_real_escape_string($str);
		else
			if (function_exists('mysql_escape_string'))
				return mysql_escape_string($str);
			else
				return addslashes($str);
	}
?>