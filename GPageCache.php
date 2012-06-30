<?php
/**
* Simple Page Cacher
* Written by Guy Sheffer <guysoft at gmail dot com>
* Released under GPL V 2
* Usage example:
* GPageCacheinit($CACHE_FOLDER,$DB_PATH); //INIT the database
* GPageCache($CACHE_FOLDER,$SERVER_URL,$DB_PATH,"http://www.google.com",30);//fetch a page and make sure its not older than 30 seconds
**/

/**
* Joins filesystem paths, takes as many args as you like
*/
function joinPaths() {
  $paths = array_filter(func_get_args());
  return preg_replace('#/{2,}#', '/', implode('/', $paths));
}


$PATH =  dirname(__FILE__);

$CACHE_FOLDER= joinPaths($PATH,"cache");
$DB_PATH = joinPaths($CACHE_FOLDER,"db.sqlite"); # Move if you dont want it public
$SERVER_URL="http://gnet.homelinux.com/GPageCache/cache/";

/**
* Init the cache database and make sure we have a folder
* @param $CACHE_FOLDER: The cache folder
* @param $DB_PATH: The path to the database (currently created in the db folder)
**/
function GPageCacheinit($CACHE_FOLDER,$DB_PATH){
  #Make sure we have a folder
  if (! is_dir($CACHE_FOLDER)){
    if (! mkdir($CACHE_FOLDER)){
      echo "Can't create cache folder, please run: mdkir ". $CACHE_FOLDER;
    }
  }
  
  if (! file_exists($DB_PATH)){
    try{
      //create or open the database
      $database = new PDO('sqlite:'. $DB_PATH);
      
      //add Cache table to database
      $query = 'CREATE TABLE Cache ' .
	  '(id INTEGER PRIMARY KEY AUTOINCREMENT, URL TEXT, Last_Access TEXT)';
      if(!$database->query($query)){
	die($exception->getMessage());
      }
      $database= null;//close db
      }
    catch(PDOException $exception){
	die($exception->getMessage());
      }
    }
}

/**
* Get the cached URL of a given URL
* @param $SERVER_URL : The URL of the cache folder on the server
**/
function getCachePath($SERVER_URL,$URL){
  return joinPaths($SERVER_URL,urlencode($URL));
}

/**
* Write a webpage to cache
* @param $CACHE_FOLDER : The cache folder
* @param $SERVER_URL : The URL of the cache folder on the server
* @param $URL : The URL
* @returns The cached url
**/
function writeFile($CACHE_FOLDER,$SERVER_URL,$URL){
  $path = joinPaths($CACHE_FOLDER,urlencode($URL));
  $fh = fopen($path, 'w') or die("can't open file for caching");
  $stringData = file_get_contents($URL);
  fwrite($fh, $stringData);
  fclose($fh);
  return getCachePath($SERVER_URL,$URL);
}

/**
* The cache function
* @param $CACHE_FOLDER : The cache folder on the server
* @param $SERVER_URL : The URL of the cache folder on the server
* @param $URL : The url we want to pull
* @param $delt a: The page we get will be at most $delta seconds old
* @param $DB_PATH : The path to the database (currently created in the db folder)
* @returns url on our webserver where the cache exists
**/
function GPageCache($CACHE_FOLDER,$SERVER_URL,$DB_PATH,$URL,$delta=10){
  try{
    //create or open the database
    $database = new PDO('sqlite:'. $DB_PATH);
    
    $date = new DateTime();
    
    //prepare the SQL statement
    $stmt = $database->prepare("SELECT * FROM Cache WHERE URL = :URL_str");
    //bind the paramaters
    $stmt->bindParam(':URL_str', $URL, PDO::PARAM_STR);
    //execute the prepared statement
    $stmt->execute();
    //fetch the results
    $result = $stmt->fetchAll();
    
    $found=false;
    foreach($result as $row){
      $found=true;
      $id= $row['id'];
      $lastAccess= (int) $row['Last_Access'];
      break;
      }
      
      if ($found){
	if($date->getTimestamp() - $lastAccess< $delta){
	  return getCachePath($SERVER_URL,$URL);
	}
	else{ //found But we need to update
	  $returnValue =  writeFile($CACHE_FOLDER,$SERVER_URL,$URL);
	  $count = $database->exec("UPDATE Cache SET Last_Access=".$date->getTimestamp()." WHERE id=". $id ."");
	}
      }
      else{//not found
	$returnValue =  writeFile($CACHE_FOLDER,$SERVER_URL,$URL);
	//Insert in to DB
	$count = $database->exec("INSERT INTO Cache(id,URL,Last_Access) VALUES(NULL,'$URL'," . $date->getTimestamp() .")");
	return $returnValue;
      }
      
    $database= null;//close db
    }
  catch(PDOException $exception){
      die($exception->getMessage());
    }
}

?>