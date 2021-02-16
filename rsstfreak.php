<?php

/**
 * -------   U-232 Codename Trinity   ----------*
 * ---------------------------------------------*
 * --------  @authors U-232 Team  --------------*
 * ---------------------------------------------*
 * -----  @site https://u-232.duckdns.org/  ----*
 * ---------------------------------------------*
 * -----  @copyright 2020 U-232 Team  ----------*
 * ---------------------------------------------*
 * ------------  @version V6  ------------------*
 */
require_once (__DIR__ . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php');
require_once (INCL_DIR . 'html_functions.php');
require_once (INCL_DIR . 'user_functions.php');
dbconn();
loggedinorreturn();
$html = '';
$lang = load_language('global');
$use_limit = true;
$limit = 15;
$html.= "<div class='container-fluid' style='width:90%;border:2px solid black;margin:0 auto;'><div class='row-fluid'>";
$xml = file_get_contents('http://feed.torrentfreak.com/Torrentfreak/');
$icount = 1;
$doc = new DOMDocument();
@$doc->loadXML($xml);
$items = $doc->getElementsByTagName('item');
foreach ($items as $item) {
    $html.= "<div class='col-md-12' style='padding:5px;'><h2 class='text-center'>" . $item->getElementsByTagName('title')->item(0)->nodeValue . "</h2><hr/>" . preg_replace("/<p>Source\:(.*?)width=\"1\"\/>/is", "", $item->getElementsByTagName('encoded')->item(0)->nodeValue) . "<hr/></div>";
    if ($use_limit && $icount == $limit) break;

    
    $icount++;
}
$html = str_replace(['“', '”', "’", "‘", "‘"], ['"', '"', "'", "'", "'"], $html);
$html = str_replace(["–", '="/images/'], ["-", '="http://torrentfreak.com/images/'], $html);

$html .= "</div></div>";
echo (stdhead('Torrent freak news') . $html . stdfoot());
?>
