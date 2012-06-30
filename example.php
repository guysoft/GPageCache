<?php
include_once ("GPageCache.php");

$PATH =  dirname(__FILE__);

$CACHE_FOLDER= joinPaths($PATH,"cache");
$DB_PATH = joinPaths($CACHE_FOLDER,"db.sqlite"); # Move if you dont want it public
$SERVER_URL="http://gnet.homelinux.com/GPageCache/cache/";

//INIT the database
GPageCacheinit($CACHE_FOLDER,$DB_PATH);

//Get the url of the cached page
echo GPageCache($CACHE_FOLDER,$SERVER_URL,$DB_PATH,"http://www.google.com",30);//fetch a page and make sure its not older than 30 seconds
?>