<?php

//sanitizing the title
function sanitize_title($title) {
	$title = strip_tags($title);
	// Preserve escaped octets.
	$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
	// Remove percent signs that are not part of an octet.
	$title = str_replace('%', '', $title);
	// Restore octets.
	$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
	$title = strtolower($title);
	$title = preg_replace('/&.+?;/', '', $title); // kill entities
	$title = str_replace('.', '-', $title);
	$title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
	$title = preg_replace('/\s+/', '-', $title);
	$title = preg_replace('|-+|', '-', $title);
	$title = trim($title, '-');

	return $title;
}


//uploading images from apple site to the main site

function ftpUpload($file,$url){
global $conn_id;
global $image_dir;
$file=$image_dir.$file;
// upload a file
ftp_put($conn_id, $file, $url,FTP_BINARY);
}


//Function CurlLoad downloads the images from the remote directory to the local directory images/
function curlLoad($url,$file){
$ch = curl_init($url);
$fp = fopen('./images/'.$file, 'wb');
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_exec($ch);
curl_close($ch);
fclose($fp);
}


//function for escaping every ' character
function escape_quote($string)
{
$string=preg_replace('/\'/','\\\'',$string);
return $string;
}


//bad function optimize table first then down't use while inside see the latest example of my app

//function for checking data in database
function in_table($column,$table,$data){
$result = mysql_query("SELECT count(*) FROM $table where $column='$data'") or die(mysql_error());
$row=mysql_fetch_row($result);
if($row[0]>0)return 1;
return;
}

//for checking two columns

function in_table_multiple($table,$feed,$post){	
$query="SELECT count(*) FROM $table where feed_id='$feed' and post_id='$post'";
$result = mysql_query($query) or die(mysql_error());
$row=mysql_fetch_row($result);
if($row[0]>0)return 1;
return 0;
}

?>
