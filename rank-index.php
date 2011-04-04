<?php
require_once('functions.php');
require_once('config.php');
require_once('tags.php');

$time=new DateTime();
$unique_timestamp=$time->getTimeStamp();


$feeds=array('topfreeapplications');


$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"Accept-language: en\r\n"
  )
);
$context = stream_context_create($opts);
// Open the file using the HTTP headers set above
$content = file_get_contents('http://itunes.apple.com/us/rss/topfreeapplications/limit=300/xml', false, $context);


//$content = file_get_contents('test.xml');


preg_match_all('/<link+.[^>]+\/>/',$content,$a);

$linkArray=array();

foreach($a[0] as $single){
	if(preg_match('/href=+.[^>]+\//',$single,$test))
		if(
		preg_match('?http://itunes.apple.com/us/app/|http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?',$test[0]))	
			$linkArray[]='h'.(trim(trim($test[0],'href="/\'')));
            	
	}
	






require_once('rank-connection.php');

//counting posts
$link_count=0;
$post_number=2;

//The Main loop
foreach($linkArray as $current_ran => $link):
	//getting the id from the link
	preg_match('/id+=?+[0-9]*/',$link,$ar);
    $link_id = trim(trim($ar[0],'id'),'=');
	
if(!in_table('links','wp_auto_valid',$link_id) && !in_table('invalid_links','wp_auto_invalid',$link_id))
{	
	
$sep=new DOMDocument();
@$sep->loadHTMLFile($link);
if(!$sep->getElementById('content')){mysql_query("INSERT IGNORE INTO wp_auto_invalid(invalid_links)VALUES('$link_id')");}
else{
	
//getting seo contents

foreach($sep->getElementsByTagName('meta')as $node){
	if($node->getAttribute('name') == 'keywords')
	$seo_ultimate_keywords=$node->getAttribute('content');

	if($node->getAttribute('name') == 'description'){
	 $seo_description = $node->getAttribute('content');
$seo_ultimate_description = preg_replace('/on the iTunes App Store/','',$seo_description);
}
	
	}


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

$alt_title = preg_replace("/'/",'',$title);

$title = mysql_real_escape_string(mb_convert_encoding($title, 'HTML-ENTITIES', "UTF-8"));

$alt_title = mysql_real_escape_string(mb_convert_encoding($alt_title, 'HTML-ENTITIES', "UTF-8"));



//trimming the link

$link = trim(preg_replace('/mt+.*/','',$link), '?&');
$link = urldecode(urlencode($link));


//Making of Post
$clear='<div style="clear:both;"></div>';
$gap = '';
//$gap='<div style="height:5px;"></div>';
$scr_block='';

$post='<div style="text-align:center;">'.'<img src='.$image_src." alt=\'$alt_title\'/>".'</div>';
$post.='<div style="text-align:center;font-size:17px;">'.'<h3>Price: '.$price.'</h3></div>';
$post.=$description.$gap;
$post.=$gap.$requirement;
$post.='<div class="download_link" style="text-align:center;font-size:17px;">'.'<a href="'.$link.'" '.'target=_blank>'.'Download '.$title.' from the App Store</a></div>';


//the date format in the wordpress
$date=gmdate('Y-m-d H:i:s');
$user=rand(1,15);


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
	* */

$seo_keywords.=$title;
$seo_title= $title;
$seo_description=trim(substr($seo_raw_des,0,150));

$seo= array(
'title' => $seo_title,
'keywords' => $seo_ultimate_keywords,
 'description' => $seo_ultimate_description
 );

foreach($seo as $key=>$value){
	$metakey='_aioseop_'.$key;
	$value=mysql_real_escape_string(mb_convert_encoding($value, 'HTML-ENTITIES', "UTF-8"));
	
$sql="INSERT INTO wp_postmeta(post_id,meta_key,meta_value)"."VALUES('$post_id','$metakey','$value')";
mysql_query($sql) or die(mysql_error());

	
}


//Last Step insterting the link into the database
mysql_query("INSERT IGNORE INTO wp_auto_valid(post_id,links)VALUES('$post_id','$link_id')");
$link_count++;

}
}//end of main if


//populating the wp_auto_ranks table
$current_rank=$current_ran+1;
$query="select post_id from wp_auto_valid where links='$link_id'";
 $post_id=mysql_result(mysql_query($query),0);

foreach($feeds as $feedCounter => $feedName):

if(in_table_multiple('wp_auto_ranks',$feedCounter,$post_id)):
 $query="select ranks from wp_auto_ranks where post_id='$post_id' and feed_id='$feedCounter'";
 $result=mysql_query($query) or die(mysql_error()); 
	 if(mysql_num_rows($result)!=0):
	 $ranks=mysql_result($result,0);
	 $tmp_ranks= explode(';',$ranks);
	 
	 if(count($tmp_ranks) >= 20)
		 array_shift($tmp_ranks);
		 
	 $ranks=implode(';',$tmp_ranks);
	 $newRank=$current_rank.','.gmdate('Y-m-d H:i:s');
	 $ranks=$ranks.';'.$newRank;
	 $query="update wp_auto_ranks set ranks='$ranks',current_rank='$current_rank',update_timestamp='$unique_timestamp' where post_id='$post_id' and feed_id='$feedCounter'";
	 mysql_query($query) or die(mysql_error());    
	 endif;
 
else:
$newRank=$current_rank.','.gmdate('Y-m-d H:i:s');
$query="insert into wp_auto_ranks(feed_id,post_id,ranks,current_rank,update_timestamp) values('$feedCounter','$post_id','$newRank','$current_rank','$unique_timestamp')";
mysql_query($query) or die(mysql_error());

endif;

endforeach;


//if($link_count==$post_number)break;

endforeach;//main foreach


$query="update wp_auto_ranks set current_rank=-1 where update_timestamp!='$unique_timestamp'";
mysql_query($query) or die(mysql_error());




