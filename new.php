<?php

/*THIS IS THE APP WHICH COVERS ALL LINKS, NOT ONLY the populer ones
Almost 70,000 posts will be generated
each time it will publish 10 posts you can change that by changing $post_count
If new apps are added in the apple directory they will be included automatically

THIS APP EXCLUDES THE APPS IN OTHER LANGUAGES;
********************
if you increase the $cat_link_counter increase the set_time_limit() too 

*********************



*/


//required files
require 'config.php' ;
require 'connection.php';
require 'functions.php';

//set time limit for the huge list to scrape
set_time_limit(30*60);

$url_genre='http://itunes.apple.com/us/genre/mobile-software-applications/id';


//building the category links

$array_first=range(6000,6013);
$array_games=range(7000,7019);
$array_second=range(6015,6018);
$final_array=array_merge($array_first,$array_games,$array_second);
$final_array[]=6020;
$links=array();
shuffle($final_array);
$cat_link_counter=0;


//The critical part to recursively visit all the itunes pages!
foreach($final_array as $solo_link){
//echo $url_genre.$solo_link.'<br/>'.'first loop'.'<br/>';
$genre_dom=new DOMDocument();
@$genre_dom->loadHTMLFile($url_genre.$solo_link);


 foreach($genre_dom->getElementsByTagName('a') as $link_one){
 if(preg_match('?http://itunes.apple.com/us/genre/+.+letter=[A-Z*]?',$link_one->getAttribute('href'))){
 //echo $link_one->getAttribute('href').'second loop'.'<br/>';
 $alpha_dom=new DOMDocument();
 @$alpha_dom->loadHTMLFile($link_one->getAttribute('href'));
    
	$inner_array=array();
	
	
	foreach($alpha_dom->getElementsByTagName('a') as $link_inner){
	$load_main=1;
	if(!in_array($link_inner->getAttribute('href'),$inner_array))
	if(preg_match('?http://itunes.apple.com/us/genre/+.+letter=[A-Z*]+.+page=[0-9]*?',$link_inner->getAttribute('href')))
	{
	$load_main=0;
	$num_dom=new DOMDocument();
	@$num_dom->loadHTMLFile($link_inner->getAttribute('href'));
		
		
		foreach($num_dom->getElementsByTagName('a') as $link_innermost){
		if(preg_match('?http://itunes.apple.com/us/app/?',$link_innermost->getAttribute('href'))){
		if(!in_array(preg_replace('/\?+.*/','',$link_innermost->getAttribute('href')),$links))
		if(!preg_match('?[^A-Za-z0-9\'"; $%^&*()<>_\-+=`~/\]\\\|.,/@#!\?\[:]?',$link_innermost->nodeValue))
		$links[]=preg_replace('/\?+.*/','',$link_innermost->getAttribute('href'));
		}
		}
		$inner_array[]=$link_inner->getAttribute('href');		
		}
		
		//if the number indexing not exists load from the general links
		if($load_main)		
		if(preg_match('?http://itunes.apple.com/us/app/?',$link_inner->getAttribute('href'))){
		if(!in_array(preg_replace('/\?+.*/','',$link_inner->getAttribute('href')),$links))
		if(!preg_match('?[^A-Za-z0-9\'"; $%^&*()<>_\-+=`~/\]\\\|.,/@#!\?\[:]?',$link_inner->nodeValue))
		$links[]=preg_replace('/\?+.*/','',$link_inner->getAttribute('href'));
		}
		}
		
	
 
 }
 }
 //this will get almost 3000 links so one is enough as chosen randomly
 if(++$cat_link_counter==1)break;
}


//echo 'total links scraped from one random category is'.count($links);
//shuffling the links for random apps
shuffle($links);

//counting posts
$link_count=0;

//The Main loop
foreach($links as $link)
if(!in_table('links','wp_auto',$link)&&!in_table('invalid_links','wp_auto',$link))
{
$sep=new DOMDocument();
@$sep->loadHTMLFile($link);
if(!$sep->getElementById('content')){mysql_query("INSERT IGNORE INTO wp_auto(invalid_links)VALUES('$link')");}
else{


//getting Images
$scr_src=array();
foreach($sep->getElementsByTagName('img')as $node){
if($node->getAttribute('class')=='artwork'&&$node->getAttribute('width')=='175')
{
$file_name=basename($node->getAttribute('src'));
$file_url=$node->getAttribute('src');
ftpUpload($file_name,$file_url);
$icon=$file_name;
}


//getting screenshots
elseif(preg_match('/Screenshot/',$node->getAttribute('alt'))){
$file_name=basename($node->getAttribute('src'));
$file_url=$node->getAttribute('src');
ftpUpload($file_name,$file_url);
$scr_src[]=$file_name;
}

}

//descriptions
$i=0;
foreach($sep->getElementsByTagName('div')as $node){
if($node->getAttribute('class')=='product-review'){
$raw_description=$sep->saveXML($node);
$raw_description=preg_replace('/style/','',$raw_description);
$i++;
if($i==1)break;
}
}
$description=preg_replace('/...More/','',$raw_description);
$description=preg_replace('/<br>+\n+<br>/','<br\>',$description);
$description=escape_quote($description);
 $description=utf8_decode($description);
$description=str_replace('?','',$description);


//price
foreach($sep->getElementsByTagName('span')as $node){
if($node->getAttribute('class')=='price')$price=$node->nodeValue;
}


//price may be in div
foreach($sep->getElementsByTagName('div')as $node){
if($node->getAttribute('class')=='price'){
$price=$node->nodeValue;
}
}


//category
$i=0;
foreach($sep->getElementsByTagName('a')as $node){
if(preg_match('?http://itunes.apple.com/us/genre?',$node->getAttribute('href')))
{
$category=$node->nodeValue;
if(++$i==1)break;
}
}


//requirement
foreach($sep->getElementsByTagName('p')as $node)if(!$node->hasAttribute('style'))if(preg_match('/Requirements:/',$node->nodeValue))$requirement=$node->nodeValue;
$requirement=escape_quote($requirement);



//Title
$title=preg_replace('/for+.*+(\n+Store)?/','',$sep->getElementsByTagName('title')->item(0)->nodeValue);
$title=trim(escape_quote($title));



//Making of Post
$clear='<div style="clear:both;"></div>';
$gap='<div style="height:5px;"></div>';
$scr_block='';


foreach($scr_src as $screen){
$source=SITE_NAME.'/images_applestore1/'.$screen;
$scr_block.='<img src='.$source.' '.'style="float:left;margin-left:5px;margin-top:4px;">';
}
$icon_url=SITE_NAME.'/images_applestore1/'.$icon;
$post='<div style="text-align:center;">'.'<img src='.$icon_url.'>'.'</div>';
$post.='<div style="text-align:center;font-size:17px;">'.'<h3>Price: '.$price.'</h3></div>';
$post.=$description.$gap;
$post.='<h3>Here are a few <b>Screenshots<b>, courtesy of <a href="http://iphone4topapps.com">iPhone 4 Top Apps:</a></h3>'.$clear;
$post.=$scr_block.$clear.$gap;
$post.=$gap.$requirement;
$post.='<div style="text-align:center;font-size:17px;">'.'<a href='.$link.' '.'target=_blank>'.'Download '.$title.' from the App Store</a></div>';


//the date format in the wordpress
$date=gmdate('Y-m-d H:i:s');
$user=1;


//inserting into database
$post_name=sanitize_title($title);


//inserting into the wp_posts
$sql="INSERT INTO wp_posts(post_author,post_date,post_title,post_content,post_name)"."VALUES('$user','$date','$title','$post','$post_name');";
mysql_query($sql) or die(mysql_error());
mysql_query("INSERT INTO wp_auto(links)VALUES('$single_link')");



//Inserting the category for the post in the database
$result=mysql_query("SELECT ID FROM wp_posts WHERE post_title='$title'") or die(mysql_error());
$post_id=mysql_result($result,0);
$lower_sample=strtolower($category);


if(!in_table('name','wp_terms',$category))mysql_query("INSERT INTO wp_terms(name,slug) VALUES('$category','$lower_sample')") or die(mysql_error());

$result=mysql_query("SELECT term_id FROM wp_terms WHERE name='$category'") or die(mysql_error());
$term_id=mysql_result($result,0);
mysql_query("INSERT IGNORE INTO wp_term_taxonomy(term_id,taxonomy)VALUES('$term_id','category')") or die(mysql_error());
$result=mysql_query("SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id='$term_id'") or die(mysql_error());
$taxonomy_id=mysql_result($result,0);



//inserting post count no
$result=mysql_query("SELECT count FROM wp_term_taxonomy WHERE term_id='$term_id'") or die(mysql_error());
$post_count=mysql_result($result,0);
$query="UPDATE wp_term_taxonomy SET count=$post_count+1 WHERE term_id='$term_id'";
mysql_query($query) or die(mysql_error());
mysql_query("INSERT IGNORE INTO wp_term_relationships(object_id,term_taxonomy_id)VALUES('$post_id','$taxonomy_id')") or die(mysql_error());



//Last Step insterting the link into the database
mysql_query("INSERT IGNORE INTO wp_auto(links)VALUES('$link')");
$link_count++;
if($link_count==10)break;
}
}
ftp_close($conn_id);

?>