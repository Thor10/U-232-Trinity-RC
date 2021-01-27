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
$categorie = genrelist();
foreach ($categorie as $key => $value) $change[$value['id']] = array(
    'id' => $value['id'],
    'name' => $value['name'],
    'image' => $value['image']
);
if (($top10torrents24 = $cache->get('top10_tor_24_')) === false) {
    $tortime24 = $_SERVER['REQUEST_TIME'] - 86400;
    $res_top10_24 = sql_query("SELECT id, times_completed, seeders, poster, leechers, name, category from torrents WHERE last_action >= {$tortime24} ORDER BY seeders + leechers DESC LIMIT {$TRINITY20['latest_torrents_limit']}") or sqlerr(__FILE__, __LINE__);
    while ($top10torrent24 = mysqli_fetch_assoc($res_top10_24)) 
		$top10torrents24[] = $top10torrent24;
    $cache->set('top10_tor_24_', $top10torrents24);
}
if (!empty($top10torrents24)) {
    $HTMLOUT.= "<table class='top10'>
            <tr>
            <th><b>*</b></th>
            <th><b>Top 10 torrents in past 24 hours</b></th>
			<th><i class='fas fa-check'></i></th>
            <th><i class='fas fa-arrow-up'></i></th>
            <th><i class='fas fa-arrow-down'></i></th></tr>";
	if ($top10torrents24) {
		$counter = 1;
        foreach ($top10torrents24 as $top10torrent24arr) {
            $top10torrent24arr['cat_name'] = htmlsafechars($change[$top10torrent24arr['category']]['name']);
	    $top10torrent24arr['cat_pic'] = htmlsafechars($change[$top10torrent24arr['category']]['image']);
            $torrname = htmlsafechars($top10torrent24arr['name']);
            if (strlen($torrname) > 50) 
				$torrname = substr($torrname, 0, 50) . "...";
            $HTMLOUT.= "
            <tr>
            <td>". $counter++ ."</td>
            <td><a class ='float-left' href=\"{$TRINITY20['baseurl']}/details.php?id=" . (int)$top10torrent24arr['id'] . "&amp;hit=1\">{$torrname}</a></td>
			<td>" . (int)$top10torrent24arr['times_completed'] . "</td>
            <td>" . (int)$top10torrent24arr['seeders'] . "</td>
            <td>" . (int)$top10torrent24arr['leechers'] . "</td>     
	 </tr>";
        }
    } else {
        //== If there are no torrents
        if (empty($top10torrents24)) 
            $HTMLOUT.= "<tr><td>{$lang['top5torrents_no_torrents']}</td></tr>";
    }
}
$HTMLOUT.= "</table>";
//==End	
// End Class
// End File
