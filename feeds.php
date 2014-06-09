<?php
//include "timeLog.php";
//$timeLog = new timeLog();
define("FEED_URL", "http://www.batoto.net/myfollows_rss?secret=");
define("FEED_PARMS", "&l=English");
define("CACHE_DIR", "cache");

$html_basic = <<<EOT
<style>
body { background: #f1f1f1; color: #444; }
.error { 
	background: #FFFFFF;
	color: #B30000;
	font-weight: 700;
	padding: 10px;
}
.tip {
	background: #FFFFFF;
	border: 1px solid #D4D4D4;
	padding: 10px;
}
a { color: #000; }
a:visited { color: #999; }</style>
EOT;
$html_form = <<<EOT
<form method="get">
	<span>secret</span>
	<input type="text" name="secret">
	<input type="submit">
</form>
<div class="tip">
secret - part of RSS Feed link avaiable at <a href="http://www.batoto.net/myfollows">My Follows</a>
</div>
EOT;
$html_error_start = "<div class=\"error\">";
$html_error_end = "</div>";

if ( isset($_GET["secret"]) ) {
	$secret = filter_var($_GET["secret"], FILTER_VALIDATE_REGEXP,
		array(
			"options"=>array(
				"regexp"=>"/[a-z0-9]{32}/"
			)
		)
	);
} else {
	echo $html_basic, $html_form;
	exit;
}
if ( !$secret ) {
	echo $html_basic, $html_form;
	exit;
}
define("FEED_SECRET", $secret);

if ( !file_exists(CACHE_DIR) || !is_dir(CACHE_DIR) ) {
	$old = umask(0); 
	mkdir(CACHE_DIR, 0777); 
	umask($old); 
}

function getData($url, $lifetime = 1800) {
	$hashed = md5($url) . ".tmp";
	if ( file_exists(CACHE_DIR . "/" . $hashed) ) {
		if ( filemtime(CACHE_DIR . "/" . $hashed)+$lifetime < time() ) {
			// expired
			$string = file_get_contents($url);
			file_put_contents(CACHE_DIR . "/" . $hashed, $string);
		} else {
			$string = file_get_contents(CACHE_DIR . "/" . $hashed);
		}
	} else {
		$string = file_get_contents($url);
		file_put_contents(CACHE_DIR . "/" . $hashed, $string);
	}
	return $string;
}

libxml_use_internal_errors(true);
//$timeLog->tick();
$xml = simplexml_load_string(getData(FEED_URL . FEED_SECRET . FEED_PARMS));
if (!$xml) {
	echo $html_basic;
	foreach(libxml_get_errors() as $error) {
        echo $html_error_start, $error->message, $html_error_end;
    }
    libxml_clear_errors();
	exit;
}
$free_items = $xml->channel->item;
$free_items_count = $xml->channel->item->count();
$broken_items = array();
//echo $free_items_count, PHP_EOL;
$counter = 1;
//$timeLog->tick();
foreach($free_items as $key => $free_item) {
	//$timeLog->tick();
	$hashed = md5($free_item->link) . ".ok";
	if ( file_exists(CACHE_DIR . "/" . $hashed) ) {
		$http_links_status = true;
	} else {
		/** cURL is almost ueless there, don't bother **/
		$data = getData($free_item->link, 3600);
		if ( strpos($data, "id=\"comic_page\"") !== false ) {
			$http_links_status = true;
			file_put_contents(CACHE_DIR . "/" . $hashed, "");
		} else {
			$http_links_status = false;
			$broken_items[] = $counter;
			//$free_item->title = "N/A " . $free_item->title;
			//$dom=dom_import_simplexml($free_item);
			//$dom->parentNode->removeChild($dom);
			/* NOPE, breaks loop query */
		}
	}
	$color = ($http_links_status ? "85DD3C" : "B00000");
	//printf("<span style=\"color: #%s\">%s</span><br>\n", $color, $free_item->link);
	++$counter;
}
for($i=0,$size=count($broken_items);$i<$size;++$i) {
	$find = "//channel/item[" . $broken_items[$i] . "]";
	unset($xml->xpath($find)[0][0]);
}
//$timeLog->tick();
//print_r($timeLog->getTimes());
echo $xml->asXML();
?>