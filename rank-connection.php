<?php

//connecting to the database
$p=mysql_connect(DB_HOST,DB_USERNAME,DB_PASS);
if (!$p) {
    die('Could not connect: ' . mysql_error());
}
echo 'Connected successfully';


$selected=mysql_select_db(DB_NAME,$p);
if (!$selected) {
    die ('Can\'t use wordpress : ' . mysql_error());
}

/*
//for testing purpose
//mysql_query("DROP TABLE IF EXISTS wp_auto;") or die(mysql_error());
//mysql_query("TRUNCATE TABLE wp_posts;") or die(mysql_error());

*/



//Creating table auto
$sql="CREATE TABLE IF NOT EXISTS wp_auto_valid(
id INT unsigned NOT NULL auto_increment,
post_id int unsigned not null,
links int unsigned NOT NULL,
PRIMARY KEY(id),
key post_id(post_id),
key links(links)
)";

mysql_query($sql) or die(mysql_error());

$sql1="CREATE TABLE IF NOT EXISTS wp_auto_invalid(
id INT NOT NULL auto_increment,
invalid_links int unsigned NOT NULL,
PRIMARY KEY(id),
key invalid_links(invalid_links)
)";

mysql_query($sql1) or die(mysql_error());


$sql2="CREATE TABLE IF NOT EXISTS wp_auto_ranks(
id INT NOT NULL auto_increment,
feed_id tinyint unsigned not null,
post_id INT unsigned NOT NULL ,
ranks text not null,
current_rank smallint NOT NULL default -1,
update_timestamp bigint(20) unsigned NOT NULL,

PRIMARY KEY(id),
key feed_id(feed_id),
key post_id(post_id),
key current_rank(current_rank),
KEY update_timestamp (update_timestamp)
)";

mysql_query($sql2) or die(mysql_error());




//connecting using ftp
//$conn_id = ftp_connect($ftp_server);
//$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
?>
