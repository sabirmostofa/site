<?php
//required files
require 'config.php' ;
require 'connection.php';
require 'functions.php';

$tagArray = array('race', 'facebook', 'twitter', 'family', 'camera', 'bluetooth', 'library', 'video', 'photo', 
'gallery', 'free', 'car', 'ipad', 'time', 'magazine', 'radio', 'shopping', 'chess', 'tv', 'report', 'spy',
'surf', 'browser', 'chat', 'recorder' , 'converter' , 'flight' , 'tracker' , 'joke', 'card', 'guitar', 'karaoke', 'skype', 'football', 'bible', 'reader', 'people', 'map', 'google', 'speed', 'clock', 'alerm', 'baby', 'ebay', 'horoscope', 'health', 'beauty', 'food', 'flash', 'tutor', 'tips','gps', 'star', 'astronomy'
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


//adding more links

$ult_link = 'http://itunes.apple.com/us/genre/ios/id36?mt=8';
$sep=new DOMDocument();
@$sep->loadHTMLFile($ult_link);
$count=0;
$genre_array= array();
foreach($sep->getElementsByTagName('a')as $node){

if(preg_match('?http://itunes.apple.com/us/genre/ios?', $node->getAttribute('href'))){
if(preg_match('?http://itunes.apple.com/us/genre/ios/id36?', $node->getAttribute('href')))continue;
$genre_array[]=$node->getAttribute('href');
$count++;
}
}

$cat_link_counter=0;
$final_array=$genre_array;
shuffle($final_array);

//The critical part to recursively visit all the itunes pages!
foreach($final_array as $solo_link){
//echo $url_genre.$solo_link.'<br/>'.'first loop'.'<br/>';
$genre_dom=new DOMDocument();
@$genre_dom->loadHTMLFile($solo_link);


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

shuffle($links);







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
$requirement=mysql_real_escape_string($requirement);



//Title
$title=preg_replace('/for+.*+(\n+Store)?/','',$sep->getElementsByTagName('title')->item(0)->nodeValue);
$title=trim($title);
$title = mysql_real_escape_string(mb_convert_encoding($title, 'HTML-ENTITIES', "UTF-8"));

//trimming the link

$link = trim(preg_replace('/mt+.*/','',$link), '?&');
$link = urldecode(urlencode($link));


//Making of Post
$clear='<div style="clear:both;"></div>';
$gap = '';
//$gap='<div style="height:5px;"></div>';
$scr_block='';

$post='<div style="text-align:center;">'.'<img src='.$image_src." alt=$title>".'</div>';
$post.='<div style="text-align:center;font-size:17px;">'.'<h3>Price: '.$price.'</h3></div>';
$post.=$description.$gap;
$post.=$gap.$requirement;
$post.='<div style="text-align:center;font-size:17px;">'.'<a href="'.$link.'" '.'target=_blank>'.'Download '.$title.' from the App Store</a></div>';


//the date format in the wordpress
$date=gmdate('Y-m-d H:i:s');
$user=1;


//inserting into database
$post_name=sanitize_title($title);




//inserting into the wp_posts
$sql="INSERT INTO wp_posts(post_author,post_date,post_date_gmt,post_title,post_content,post_name,post_modified,post_modified_gmt)"."VALUES('$user','$date','$date','$title','$post','$post_name','$date','$date');";
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
$matchedTag='';
foreach($tagArray as $tag)
	if(preg_match("/\b$tag\b/i", $title)):
	$matchedTag=$tag;
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
	
	
	//inserting guid in the wp_posts table
	$guid = SITE_NAME."/?p=$post_id";
	$query="UPDATE wp_posts SET guid='$guid' WHERE ID='$post_id'";
mysql_query($query) or die(mysql_error());

//SEO works
$seo_raw_des = preg_replace('?<h4>+[^<]*+</h4>|<p>|</p>|<br/>?s','',$description);

$seo_keywords='';
if($matchedTag!='')
$seo_keywords.=$matchedTag.' iphone application,';
//making keywords
/*
preg_match_all('/\b[a-z0-9]+\b/i',$title,$ar);

$seo_count=0;
foreach($ar[0] as $ele){

	$seo_keywords.= $ele.' ';
	$seo_count++;
	if($seo_count == 2)break;
	
	
	}
*/	
$seo_keywords=$title;
$seo_title= $title;
$seo_description=trim(substr($seo_raw_des,0,150));

$seo= array(
'title' => $seo_title,
'keywords' => $seo_keywords,
 'description' => $seo_description
 );

foreach($seo as $key=>$value){
	$metakey='_aioseop_'.$key;
$sql="INSERT INTO wp_postmeta(post_id,meta_key,meta_value)"."VALUES('$post_id','$metakey','$value')";
mysql_query($sql) or die(mysql_error());

	
}


//Last Step insterting the link into the database
mysql_query("INSERT IGNORE INTO wp_auto_valid(post_id,links)VALUES('$post_id','$link_id')");
$link_count++;
if($link_count==10)break;
}
}
endforeach;


?>





