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
require_once (INCL_DIR . 'user_functions.php');
require_once (INCL_DIR . 'html_functions.php');
require_once INCL_DIR . 'torrenttable_functions.php';
require_once INCL_DIR . 'pager_functions.php';
require_once (INCL_DIR . 'searchcloud_functions.php');
require_once (CLASS_DIR . 'class_user_options.php');
require_once (CLASS_DIR . 'class_user_options_2.php');
dbconn(false);
loggedinorreturn();
if (isset($_GET['clear_new']) && $_GET['clear_new'] == 1) {
    sql_query("UPDATE users SET last_browse=" . TIME_NOW . " WHERE id=" . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
    $cache->update_row($keys['my_userid'] . $CURUSER['id'], [
        'last_browse' => TIME_NOW
    ], $TRINITY20['expires']['curuser']);
    $cache->update_row('user' . $CURUSER['id'], [
        'last_browse' => TIME_NOW
    ], $TRINITY20['expires']['user_cache']);
    header("Location: {$TRINITY20['baseurl']}/browse.php");
}
$stdfoot = array(
    /* include js **/
    'js' => array(
        'java_klappe',
        'wz_tooltip'
    )
);
$stdhead = array(
    /* include css **/
    'css' => array(
        /*'browse'*/
    )
);
$lang = array_merge(load_language('global') , load_language('browse') , load_language('torrenttable_functions') , load_language('index'));

if (function_exists('parked')) parked();

$HTMLOUT = $searchin = $select_searchin = $where = $addparam = $new_button = $searchstr = $search_help_boolean = '';
$HTMLOUT = "
<script type='text/javascript' src='./scripts/jaxo.suggest.js'></script>
<script type='text/javascript'>
/*<![CDATA[*/
$(document).ready(function(){
$(\"input[placeholder='Search Torrents']\").search(options);
});
/*]]>*/
</script>";
$search_help_boolean = '<div class="callout default">
	<div class="card-heading">
<h2 class="text-center text-info">The boolean search supports the following operators:</h2>
</div>
	<div class="card-body">
 <p><span style="font-weight: bold;">+</span> A leading plus sign indicates that this word must be present.<br /><br />
    <span style="font-weight: bold;">-</span> A leading minus sign indicates that this word must not be present.<br /><br />
        By default (when neither + nor - is specified) the word is optional, but results that contain it are rated higher. <br /><br />
    <span style="font-weight: bold;">*</span> The asterisk serves as the wildcard operator. Unlike the other operators, it should be appended to the word to be affected. Words match if they begin with the word preceding the * operator.<br /><br />
    <span style="font-weight: bold;">> <</span> These two operators are used to change a word\'s contribution to the relevance value that is assigned to a word. The > operator increases the contribution and the < operator decreases it.<br /><br />
    <span style="font-weight: bold;">~</span> A leading tilde acts as a negation operator, causing the word\'s contribution to the words\'s relevance to be negative. A row containing such a word is rated lower than others, but is not excluded altogether, as it would be with the - operator.<br /><br />
    <span style="font-weight: bold;">" "</span> A phrase that is enclosed within double quotes return only results that contain the phrase literally, as it was typed. <br /><br />
    <span style="font-weight: bold;">( )</span> Parentheses group words into subexpressions. Parenthesized groups can be nested.
    </p></div></div>';
$cats = genrelist();
if (isset($_GET["search"])) {
    $searchstr = unesc($_GET["search"]);
    $cleansearchstr = searchfield($searchstr);
    if (empty($cleansearchstr)) unset($cleansearchstr);
}
$selected = (isset($_GET["incldead"])) ? (int)$_GET["incldead"] : 0;
$valid_searchin = array(
    'title' => array(
        'name'
    ) ,
    'descr' => array(
        'descr'
    ) ,
    'genre' => array(
        'newgenre'
    ) ,
    'tags' => array(
        'tags'
    ) ,
    'all' => array(
        'name',
        'newgenre',
        'tags',
        'descr'
    )
);
if (isset($_GET['searchin']) && isset($valid_searchin[$_GET['searchin']])) {
    $searchin = $valid_searchin[$_GET['searchin']];
    $select_searchin = isset($_GET['searchin']) ? $_GET['searchin'] : "name";
    $addparam.= sprintf('search=%s&amp;searchin=%s&amp;', $searchstr, $select_searchin);
} else {
   $searchin = $valid_searchin[ key( $valid_searchin ) ];
    $addparam.= sprintf( 'search=%s&amp;searchin=%s&amp;', $searchstr, key( $valid_searchin ) );
 }
//}
if (isset($_GET['sort']) && isset($_GET['type'])) {
    $column = $ascdesc = '';
    $_valid_sort = array(
        'id',
        'name',
        'numfiles',
        'comments',
        'added',
        'size',
        'times_completed',
        'seeders',
        'leechers',
        'owner'
    );
    $column = isset($_GET['sort']) && isset($_valid_sort[(int)$_GET['sort']]) ? $_valid_sort[(int)$_GET['sort']] : $_valid_sort[0];
    switch (htmlsafechars($_GET['type'])) {
    case 'asc':
        $ascdesc = "ASC";
        $linkascdesc = "asc";
        break;

    case 'desc':
        $ascdesc = "DESC";
        $linkascdesc = "desc";
        break;

    default:
        $ascdesc = "DESC";
        $linkascdesc = "desc";
        break;
    }
    $orderby = "ORDER BY {$column} " . $ascdesc;
    $pagerlink = "sort=" . intval($_GET['sort']) . "&amp;type={$linkascdesc}&amp;";
} else {
    $orderby = "ORDER BY sticky ASC, id DESC";
    $pagerlink = "";
}
$wherea = $wherecatina = array();
if (isset($_GET["incldead"]) && $_GET["incldead"] == 1) {
    $addparam.= "incldead=1&amp;";
    if (!isset($CURUSER) || $CURUSER["class"] < UC_ADMINISTRATOR) $wherea[] = "banned != 'yes'";
} else {
    if (isset($_GET["incldead"]) && $_GET["incldead"] == 2) {
        $addparam.= "incldead=2&amp;";
        $wherea[] = "visible = 'no'";
    } else {
     //$addparam.= "incldead=0&amp;";
     $wherea[] = "visible = 'yes'";
    }
}
//=== added an only free torrents option \\o\o/o//
if (isset($_GET['only_free']) && $_GET['only_free'] == 1) {
    if (XBT_TRACKER == true ? $wherea[] = "freetorrent >= '1'" : $wherea[] = "free >= '1'");
    //$wherea[] = "free >= '1'";
    $addparam.= "only_free=1&amp;";
}
$category = (isset($_GET["cat"])) ? (int)$_GET["cat"] : false;
$all = isset($_GET["all"]) ? $_GET["all"] : false;
    if (!$all) {
        if (!$_GET && $CURUSER["notifs"]) {
            $all = true;
            foreach ($cats as $cat) {
                $all&= $cat['id'];
                if (strpos($CURUSER["notifs"], "[cat" . $cat['id'] . "]") !== false) {
                    if($cat['min_class'] <= $CURUSER['class']){
                        $wherecatina[] = $cat['id'];
                        $addparam.= "c{$cat['id']}=1&amp;";
                    }
                }
            }
        } elseif ($category) {
        $cnum = array_search((int)$category, array_column($cats, 'id'));
        if (!is_valid_id($category) || $cats[$cnum]['min_class'] > $CURUSER['class']) stderr("{$lang['browse_error']}", "{$lang['browse_invalid_cat']}");
            $wherecatina[] = $category;
            $addparam.= "cat=$category&amp;";
        } else {
            $all = true;
            foreach ($cats as $cat) {
                $all&= isset($_GET["c{$cat['id']}"]);
                if (isset($_GET["c{$cat['id']}"])) {
                    if($cat['min_class'] <= $CURUSER['class']){
                        $wherecatina[] = $cat['id'];
                        $addparam.= "c{$cat['id']}=1&amp;";
                    }
                }
            }
        }
    }
    if ($all) {
        foreach ($cats as $cat) {
            if($cat['min_class'] <= $CURUSER['class']){
                $wherecatina[] = $cat['id'];
                $addparam.= "c{$cat['id']}=1&amp;";
            }
        }
        $addparam = "";
    }
    if (count($wherecatina) < 1) {
        foreach ($cats as $cat) {
            if($cat['min_class'] <= $CURUSER['class']){
                $wherecatina2[] = $cat['id'];
            }
        }
        $wherea[] = 'category IN (' . join(', ', $wherecatina2) . ') ';
        $addparam = "";
    }

if (count($wherecatina) > 1) $wherea[] = 'category IN (' . join(', ', $wherecatina) . ') ';
elseif (count($wherecatina) == 1) $wherea[] = 'category =' . $wherecatina[0];
if (isset($cleansearchstr)) {
    //== boolean search by djgrr
    if ($searchstr != '') {
        $addparam.= 'search=' . rawurlencode($searchstr) . '&amp;searchin='.$select_searchin.'&amp;incldead='.intval($selected).'&amp;';
        $searchstring = str_replace(array(
            '_',
            '.',
            '-'
        ) , ' ', $searchstr);
        $s = array(
            '*',
            '?',
            '.',
            '-',
            ' '
        );
        $r = array(
            '%',
            '_',
            '_',
            '_',
            '_'
        );
        if (preg_match('/^\"(.+)\"$/i', $searchstring, $matches)) $wherea[] = '`name` LIKE ' . sqlesc('%' . str_replace($s, $r, $matches[1]) . '%');
        elseif (strpos($searchstr, '*') !== false || strpos($searchstr, '?') !== false) $wherea[] = '`name` LIKE ' . sqlesc(str_replace($s, $r, $searchstr));
        elseif (preg_match('/^[A-Za-z0-9][a-zA-Z0-9()._-]+-[A-Za-z0-9_]*[A-Za-z0-9]$/iD', $searchstr)) $wherea[] = '`name` = ' . sqlesc($searchstr);
        else $wherea[] = 'MATCH (`search_text`, `filename`, `newgenre`, `tags`) AGAINST (' . sqlesc($searchstr) . ' IN BOOLEAN MODE)';
        //......
        $searcha = explode(' ', $cleansearchstr);
        //==Memcache search cloud by putyn
        searchcloud_insert($cleansearchstr);
        //==
        foreach ($searcha as $foo) {
            foreach ($searchin as $boo) $searchincrt[] = sprintf('%s LIKE \'%s\'', $boo, '%' . $foo . '%');
        }
        $wherea[] = '( ' . join(' OR ', $searchincrt) . ' )';
    }
}

$where = count($wherea) ? 'WHERE ' . join(' AND ', $wherea) : '';
$where_key = 'where::' . sha1($where);
if (($count = $cache->get($where_key)) === false) {
    $res = sql_query("SELECT COUNT(id) FROM torrents $where") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_row($res);
    $count = (int)$row[0];
    $cache->set($where_key, $count, $TRINITY20['expires']['browse_where']);
}

$torrentsperpage = ($CURUSER['torrentsperpage'] == 0) ?  15 : (int)$CURUSER['torrentsperpage'];
if ($count) {
    if ($addparam != "") {
        if ($pagerlink != "") {
            if ($addparam [strlen($addparam) - 1 ] != ";") { // & = &amp;
                $addparam .= $addparam . "&" . $pagerlink;
            } else {
                $addparam .= $addparam . $pagerlink;
            }
        }
    } else {
        $addparam .= $pagerlink;
    }

    $pager = pager($torrentsperpage, $count, "browse.php?" . $addparam);
   
    $query = "SELECT id, search_text, category, leechers, seeders, bump, tags, release_group, subs, name, times_completed, size, added, poster, descr, type, free, freetorrent, silver, comments, numfiles, filename, anonymous, sticky, nuked, vip, nukereason, newgenre, description, owner, username, youtube, checked_by, IF(nfo <> '', 1, 0) as nfoav," . "IF(num_ratings < {$TRINITY20['minvotes']}, NULL, ROUND(rating_sum / num_ratings, 1)) AS rating " . "FROM torrents {$where} {$orderby} {$pager['limit']}";
    $res = sql_query($query) or sqlerr(__FILE__, __LINE__);
} else {
    unset($query);
}

if (isset($cleansearchstr)) 
	$title = "{$lang['browse_search']} $searchstr";
else 
	$title = '';
//$HTMLOUT .= navigation_start();
//$HTMLOUT .="<a href='index.php'>" . $TRINITY20["site_name"] . "</a>";
//$HTMLOUT .= navigation_active("Torrents");
//$HTMLOUT .= navigation_end();
//$HTMLOUT.= "<div class='row'><div class='col-md-12 col-md-offset-1'>";
/*
$HTMLOUT.='
<div class="grid-x grid-margin-x">
  <div class="cell large-6 large-offset-3"><div class="orbit" role="region" aria-label="Favorite Space Pictures" data-orbit>
  <div class="orbit-wrapper">
    <div class="orbit-controls">
      <button class="orbit-previous"><span class="show-for-sr">Previous Slide</span>&#9664;&#xFE0E;</button>
      <button class="orbit-next"><span class="show-for-sr">Next Slide</span>&#9654;&#xFE0E;</button>
    </div>
    <ul class="orbit-container">
      <li class="is-active orbit-slide">';
        require_once (BLOCK_DIR . 'browse/top10_torrents_24.php');
        $HTMLOUT.='
      </li>
      <li class="orbit-slide">';
        require_once (BLOCK_DIR . 'browse/top10_movies_all.php');
        $HTMLOUT.='
      </li>
      <li class="orbit-slide">
        <figure class="orbit-figure">
          <img class="orbit-image" src="https://placehold.it/1200x600/777?text=Slide-3" alt="Space">
          <figcaption class="orbit-caption">Encapsulating</figcaption>
        </figure>
      </li>
      <li class="orbit-slide">
        <figure class="orbit-figure">
          <img class="orbit-image" src="https://placehold.it/1200x600/666?text=Slide-4" alt="Space">
          <figcaption class="orbit-caption">Outta This World</figcaption>
        </figure>
      </li>
    </ul>
  </div>
  <nav class="orbit-bullets">
    <button class="is-active" data-slide="0">
      <span class="show-for-sr">First slide details.</span>
      <span class="show-for-sr" data-slide-active-label>Current Slide</span>
    </button>
    <button data-slide="1"><span class="show-for-sr">Second slide details.</span></button>
    <button data-slide="2"><span class="show-for-sr">Third slide details.</span></button>
    <button data-slide="3"><span class="show-for-sr">Fourth slide details.</span></button>
  </nav>
</div></div></div>';
*/
if ($CURUSER['opt1'] & user_options::VIEWSCLOUD) {
    $HTMLOUT.= "<div class='callout float-center text-center' style='width:80%;border:1px solid black;background-color:rgba(121,124,128,0.3);'>";
    //print out the tag cloud
    $HTMLOUT.= cloud() . "
    </div>";
}
$HTMLOUT.= "<form role='form' method='get' action='browse.php'>";
$i = 0;
//Categories
$HTMLOUT.= "<div class='grid-x grid-padding-x'>
  <div class='column large-8 float-center'><ul class='accordion' data-accordion data-allow-all-closed='true'>
  <li class='accordion-item is-closed' data-accordion-item>
    <a href='#' class='accordion-title'>Categories</a>
    <div class='accordion-content' data-tab-content>
      <div class='grid-x grid-padding-x small-up-4 medium-up-6 large-up-8'>";
    foreach ($cats as $cat) {
        if($cat['min_class'] <= $CURUSER['class']){
            $HTMLOUT.= ($i) ? "" : "";
            $HTMLOUT.= "<div class='cell'>
      <input name='c" . (int)$cat['id'] . "'  type='checkbox' " . (in_array($cat['id'], $wherecatina) ? "checked='checked' " : "") . "value='1' >
			 <a href='browse.php?cat=" . (int)$cat['id'] . "'> " . (($CURUSER['opt2'] & user_options_2::BROWSE_ICONS) ? "<img src='{$TRINITY20['pic_base_url']}caticons/{$CURUSER['categorie_icon']}/" . htmlsafechars($cat['image']) . "' alt='" . htmlsafechars($cat['name']) . "' title='" . htmlsafechars($cat['name']) . "' />" : "" . htmlsafechars($cat['name']) . "") . "</a></div>
             ";
            $i++;
        }
    }
//=== Search only free :o)
$only_free =((isset($_GET['only_free'])) ? intval($_GET['only_free']) : '');
//=== checkbox for only free torrents
$HTMLOUT.= '<div class="cell"><input type="checkbox" name="only_free" value="1"'.(isset($_GET['only_free']) ? ' checked="checked"' : '').'>
<img src="'.$TRINITY20['pic_base_url'].'/free.png" height="42" width="42"></div>';
$HTMLOUT.= "</div></div></li></ul></div></div>";
//== clear new tag manually
if ($CURUSER['opt1'] & user_options::CLEAR_NEW_TAG_MANUALLY) {
    $new_button = "<a href='?clear_new=1'><input type='submit' value='clear new tag' class='button' /></a><br />";
} else {
    //== clear new tag automatically
    sql_query("UPDATE users SET last_browse=" . TIME_NOW . " where id=" . $CURUSER['id']);
    $cache->update_row($keys['my_userid'] . $CURUSER['id'], [
        'last_browse' => TIME_NOW
    ], $TRINITY20['expires']['curuser']);
    $cache->update_row('user' . $CURUSER['id'], [
        'last_browse' => TIME_NOW
    ], $TRINITY20['expires']['user_cache']);
}
$deadcheck = "";
$deadcheck.= "<select class='input-group-field' name='incldead'>
    <option value='0'>{$lang['browse_active']}</option>
    <option value='1'" . ($selected == 1 ? " selected='selected'" : "") . ">{$lang['browse_inc_dead']}</option>
    <option value='2'" . ($selected == 2 ? " selected='selected'" : "") . ">{$lang['browse_dead']}</option>
    </select>";
$searchin = '<select class="input-group-field" name="searchin">';
foreach (array(
    'title' => 'Name',
    'descr' => 'Description',
    'genre' => 'Genre',
    'tags'  => 'Tags',
    'all' => 'All'
) as $k => $v) 
$searchin.= '<option value="' . $k . '" ' . ($select_searchin == $k ? 'selected=\'selected\'' : '') . '>' . $v . '</option>';
$searchin.= '</select>';
$HTMLOUT.= '<div class="input-group">
  <span class="input-group-label"><i class="fa fa-search-plus"></i></span>
  <input class="input-group-field" type="text" name="search" value="'.(isset($searchstr) ? htmlsafechars($searchstr, ENT_QUOTES) : "").'" placeholder="Search Torrents">
   <span>
    '.$searchin.'
  </span>
    <span>
    '. $deadcheck .'
  </span>
  <div class="input-group-button">
    <input class="button" type="submit" value="'. $lang['search_search_btn'].'">
  </div>
</div>';
$HTMLOUT.= "
<!--<a href='{$TRINITY20["baseurl"]}/browse_catalogue.php' class='btn btn-default btn-default'>Alternative Browse</a>-->
<!--<a href='{$TRINITY20["baseurl"]}/catalogue.php' class='btn btn-default btn-default'>Search our Catalogue</a>-->
           </form><div class='res'></div>";
$HTMLOUT.= "{$new_button}";
if (isset($cleansearchstr)) {
    $HTMLOUT.= "<div class='row'><div class='col-md-6 col-md-offset-4'><h2>{$lang['browse_search']} " . htmlsafechars($searchstr, ENT_QUOTES) . "</h2></div></div>\n";
}
if ($count) {
    $HTMLOUT.="<!--<br /><div class='row'><div class='col-md-3 col-md-offset-5'><div style='display:inline-block;width:4px';></div><a href='{$TRINITY20["baseurl"]}/catalogue.php' class='btn btn-default btn-default'>Search our Catalogue</a></div></div><br /><br />-->";
    $HTMLOUT.= torrenttable($res);
    $HTMLOUT.= $pager['pagerbottom']."<br />";
} else {
    if (isset($cleansearchstr)) {
        $HTMLOUT.= "<div class='row'><div class='col-md-6 col-md-offset-4'><h2>{$lang['browse_not_found']}</h2>";
        $HTMLOUT.= "{$lang['browse_tryagain']}</div></div>\n";
    } else {
        $HTMLOUT.= "<div class='row'><div class='col-md-6 col-md-offset-5'><h2>{$lang['browse_nothing']}</h2>\n";
        $HTMLOUT.= "{$lang['browse_sorry']}</div></div>\n";
    }
}
$HTMLOUT.= "";
/*
$ip = getip();
//== Start ip logger - Melvinmeow, Mindless, pdq
$no_log_ip = ($CURUSER['perms'] & bt_options::PERMS_NO_IP);
if ($no_log_ip) {
    $ip = '127.0.0.1';
}
if (!$no_log_ip) {
    $userid = (int)$CURUSER['id'];
    $added = TIME_NOW;
    $res = sql_query("SELECT * FROM ips WHERE ip = " . sqlesc($ip) . " AND userid = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
    if (mysqli_num_rows($res) == 0) {
        sql_query("INSERT INTO ips (userid, ip, lastbrowse, type) VALUES (" . sqlesc($userid) . ", " . sqlesc($ip) . ", $added, 'Browse')") or sqlerr(__FILE__, __LINE__);
        $cache->delete('ip_history_' . $userid);
    } else {
        sql_query("UPDATE ips SET lastbrowse = $added WHERE ip=" . sqlesc($ip) . " AND userid = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
        $cache->delete('ip_history_' . $userid);
    }
}
//== End Ip logger
*/
echo stdhead($title, true, $stdhead) . $HTMLOUT . stdfoot($stdfoot);
?>
