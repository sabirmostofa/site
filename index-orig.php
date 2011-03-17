<?php
//required files
require 'config.php' ;
require 'connection.php';
require 'functions.php';

$tagArray = array('race', 'facebook', 'twitter', 'family', 'camera', 'bluetooth', 'library', 'video', 'photo', 
'gallery', 'free', 'age', 'car', 'ipad', 'time', 'magazine', 'radio', 'shopping', 'chess', 'tv', 'report', 'spy',
'surf', 'browser', 'chat', 'recorder' , 'converter' , 'flight' , 'tracker' , 'joke', 'card', 'guitar', 'karaoke', 'skype', 'football', 'bible', 'reader', 'people', 'map', 'google', 'speed', 'clock', 'alerm', 'baby', 'ebay', 'horoscope', 'health', 'beauty', 'food', 'flash', 'tutor', 'tips','gps'
);





$links = array();
$ult=new DOMDocument();
@$ult->loadHTMLFile('http://www.apple.com/iphone/apps-for-iphone/app-of-the-week/');

  
  //getting the preferred part 
$raw=$ult->saveXML($ult->getElementById('content'));
$ult->loadHTML($raw);


//getting the links from app of the week page
foreach($ult->getElementsByTagName('a') as $link){
if(preg_match('?http://itunes.apple.com/us/app/|http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?', $link->getAttribute('href'))){
$links[] =  $link->getAttribute('href');
}

}

var_dump($links);


//counting posts
$link_count=0;

//The Main loop
foreach($links as $link):
	//getting the id from the link
	preg_match('/id+=?+[0-9]*/',$link,$ar);
    $link_id = trim(trim($ar[0],'id'),'=');
	
if(!in_table('links','wp_auto_valid',$link_id) && !in_table('invalid_links','wp_auto_invalid',$link_id))
{	
	
$sep=new DOMDocument();
@$sep->loadHTMLFile($link);
if(!$sep->getElementById('content')){mysql_query("INSERT IGNORE INTO wp_auto_invalid(invalid_links)VALUES('$link_id')");}
else{


//getting Images
$scr_src=array();
foreach($sep->getElementsByTagName('img')as $node){
if($node->getAttribute('class')=='artwork'&&$node->getAttribute('width')=='175')
{

$image_src=$node->getAttribute('src');

}

/*
//getting screenshots
elseif(preg_match('/Screenshot/',$node->getAttribute('alt'))){
$file_name=basename($node->getAttribute('src'));
$file_url=$node->getAttribute('src');
ftpUpload($file_name,$file_url);
$scr_src[]=$file_name;
}
* */

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


$description = preg_replace('/<div+[^>]+>/','',$raw_description);
$description = preg_replace('?</div>?','',$description);
$description = mysql_real_escape_string(mb_convert_encoding($description, 'HTML-ENTITIES', "UTF-8"));


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

//trimming the link

$link = trim(preg_replace('/mt+.*/','',$link), '?&');



//Making of Post
$clear='<div style="clear:both;"></div>';
$gap = '';
//$gap='<div style="height:5px;"></div>';
$scr_block='';

$post='<div style="text-align:center;">'.'<img src='.$image_src.'>'.'</div>';
$post.='<div style="text-align:center;font-size:17px;">'.'<h3>Price: '.$price.'</h3></div>';
$post.=$description.$gap;
$post.=$gap.$requirement;
$post.='<div style="text-align:center;font-size:17px;">'.'<a href='.$link.' '.'target=_blank>'.'Download '.$title.' from the App Store</a></div>';


//the date format in the wordpress
$date=gmdate('Y-m-d H:i:s');
$user=1;


//inserting into database
$post_name=sanitize_title($title);

$title = mysql_real_escape_string(mb_convert_encoding($title, 'HTML-ENTITIES', "UTF-8"));


//inserting into the wp_posts
$sql="INSERT INTO wp_posts(post_author,post_date,post_title,post_content,post_name)"."VALUES('$user','$date','$title','$post','$post_name');";
mysql_query($sql) or die(mysql_error());

//mysql_query("INSERT INTO wp_auto_valid(post_id,links) VALUES('$link_id')");



//Inserting the category for the post in the database
$result=mysql_query("SELECT ID FROM wp_posts WHERE post_title='$title'") or die(mysql_error());
$post_id=mysql_result($result,0);
$lower_sample=sanitize_title($category);


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


/*
 * 
 * inserting  post tags similar to category
 * 
 * */

foreach($tagArray as $tag)
	if(preg_match("/$tag/i", $title)):
	if(!in_table('name','wp_terms',$tag))mysql_query("INSERT INTO wp_terms(name,slug) VALUES('$tag','$tag')") or die(mysql_error());
	
	$result=mysql_query("SELECT term_id FROM wp_terms WHERE name='$tag'") or die(mysql_error());
$term_id=mysql_result($result,0);



mysql_query("INSERT IGNORE INTO wp_term_taxonomy(term_id,taxonomy)VALUES('$term_id','post_tag')") or die(mysql_error());
$result=mysql_query("SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id='$term_id'") or die(mysql_error());
$taxonomy_id=mysql_result($result,0);



//inserting post count no
$result=mysql_query("SELECT count FROM wp_term_taxonomy WHERE term_id='$term_id'") or die(mysql_error());
$post_count=mysql_result($result,0);
$query="UPDATE wp_term_taxonomy SET count=$post_count+1 WHERE term_id='$term_id'";
mysql_query($query) or die(mysql_error());
mysql_query("INSERT IGNORE INTO wp_term_relationships(object_id,term_taxonomy_id)VALUES('$post_id','$taxonomy_id')") or die(mysql_error());
	
	endif;
	
	
	



//Last Step insterting the link into the database
mysql_query("INSERT IGNORE INTO wp_auto_valid(post_id,links)VALUES('$post_id','$link_id')");
$link_count++;
if($link_count==10)break;
}
}
endforeach;


?>





