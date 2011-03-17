<?php


$link = 'id330595774.html';
$link1 = 'id7001.html';
$link2 = 'Apple - iPhone - Apps of the Week.html';
$link3 = 'http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?id=329384702&mt=8&ign-mpt=uo%3D4';

$sep=new DOMDocument();
@$sep->loadHTMLFile($link);

$i=0;
foreach($sep->getElementsByTagName('div')as $node){
if($node->getAttribute('class')=='product-review'){
	
	//$sepa=new DOMDocument();
//@$sepa->loadHTML($link);

$raw_description=$sep->saveXML($node);
$raw_description=preg_replace('/style/','',$raw_description);
$i++;
if($i==1)break;
}
}

//$raw_description = utf8_decode($raw_description);
$raw_description = mb_convert_encoding($raw_description, 'HTML-ENTITIES', "UTF-8");
//echo $raw_description;

@$sep->loadHTMLFile($link1);

foreach($sep->getElementsByTagName('a')as $node){

if(preg_match('?http://itunes.apple.com/us/genre/ios?', $node->getAttribute('href'))){
//echo $node->getAttribute('href');
//echo '<br/>';
}
}


@$sep->loadHTMLFile($link2);

$raw=$sep->saveXML($sep->getElementById('content'));
@$sep->loadHTML($raw);
$count = 0;
foreach($sep->getElementsByTagName('a')as $node){

if(preg_match('?http://itunes.apple.com/us/app/|http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?', $node->getAttribute('href'))){
	$count++;
echo $node->getAttribute('href');
echo '<br/>';
}
}
echo $count;


$testl = 'http://itunes.apple.com/us/app/abc-music/id420949855?mt=8&uo=4';
$testl1 = 'http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?id=284910350&mt=8&uo=4';

preg_match('/id+=?+[0-9]*/',$testl,$ar);
var_dump($ar);
preg_match('/id+=?+[0-9]*/',$testl1,$ar);
var_dump($ar);

echo trim(trim($ar[0],'id'),'=');

$tagArray = array('race', 'facebook', 'twitter', 'family', 'camera', 'bluetooth', 'library', 'video', 'photo', 
'gallery', 'free', 'age', 'car', 'ipad', 'time', 'magazine', 'radio', 'shopping', 'chess', 'tv', 'report', 'spy',
'surf', 'browser', 'chat', 'recorder' , 'converter' , 'flight' , 'tracker' , 'joke', 'card', 'guitar', 'karaoke', 'skype', 'football', 'bible', 'reader', 'people', 'map', 'google', 'speed', 'clock', 'alerm', 'baby', 'ebay', 'horoscope', 'health', 'beauty', 'food', 'flash', 'tutor', 'tips'
);


foreach($tagArray as $tag)
{
if(preg_match("/$tag/i",'Race'))echo 'matched';
}

//@$sep->loadHTMLFile($link3);
//echo $sep -> saveHTML();

//'http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware'
/*
//$raw_description = file_get_contents($link);
$raw_description = mb_convert_encoding($raw_description, 'HTML-ENTITIES', "UTF-8");


$ar = stripos($raw_description,'<div more-text="More" class="product-review"');

echo $first = substr($raw_description,$ar);

var_dump($ar);
*/
