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
function docleanup($data)
{
    global $TRINITY20, $queries, $keys;
    set_time_limit(0);
    ignore_user_abort(1);
    $lconf = sql_query('SELECT * FROM lottery_config') or sqlerr(__FILE__, __LINE__);
    while ($aconf = mysqli_fetch_assoc($lconf)) $lottery_config[$aconf['name']] = $aconf['value'];
    if ($lottery_config['enable'] && TIME_NOW > $lottery_config['end_date']) {
        $q = mysqli_query($GLOBALS["___mysqli_ston"], 'SELECT t.user as uid, u.seedbonus, u.modcomment FROM tickets as t LEFT JOIN users as u ON u.id = t.user ORDER BY RAND() ') or sqlerr(__FILE__, __LINE__);
        while ($a = $q->fetch_assoc()) $tickets[] = $a;
        shuffle($tickets);
        $lottery['winners'] = array();
        $lottery['total_tickets'] = count($tickets);
        for ($i = 0; $i < $lottery['total_tickets']; $i++) {
            if (!isset($lottery['winners'][$tickets[$i]['uid']])) $lottery['winners'][$tickets[$i]['uid']] = $tickets[$i];
            if ($lottery_config['total_winners'] == count($lottery['winners'])) break;
        }
        if ($lottery_config['use_prize_fund']) $lottery['total_pot'] = $lottery_config['prize_fund'];
        else $lottery['total_pot'] = $lottery['total_tickets'] * $lottery_config['ticket_amount'];
        $lottery['user_pot'] = round($lottery['total_pot'] / $lottery_config['total_winners'], 2);
        $msg['subject'] = sqlesc('You have won the lottery');
        $msg['body'] = sqlesc('Congratulations, You have won : ' . ($lottery['user_pot']) . '. This has been added to your seedbonus total amount. Thanks for playing Lottery.');
        foreach ($lottery['winners'] as $winner) {
            $_userq[] = '(' . $winner['uid'] . ',' . ($winner['seedbonus'] + $lottery['user_pot']) . ',' . sqlesc("User won the lottery: " . ($lottery['user_pot']) . " at " . get_date(TIME_NOW, 'LONG') . "\n" . $winner['modcomment']) . ')';
            $_pms[] = '(0,' . $winner['uid'] . ',' . $msg['subject'] . ',' . $msg['body'] . ',' . TIME_NOW . ')';
        }
        $lconfig_update = array(
            '(\'enable\',0)',
            '(\'lottery_winners_time\',' . TIME_NOW . ')',
            '(\'lottery_winners_amount\',' . $lottery['user_pot'] . ')',
            '(\'lottery_winners\',\'' . join('|', array_keys($lottery['winners'])) . '\')'
        );
        if (count($_userq)) sql_query('INSERT INTO users(id,seedbonus,modcomment) VALUES ' . join(',', $_userq) . ' ON DUPLICATE KEY UPDATE seedbonus = values(seedbonus), modcomment = values(modcomment)') or die($mysqli->error);
        if (count($_pms)) sql_query('INSERT INTO messages(sender, receiver, subject, msg, added) VALUES ' . join(',', $_pms)) or die($mysqli->error);
        sql_query('INSERT INTO lottery_config(name,value) VALUES ' . join(',', $lconfig_update) . ' ON DUPLICATE KEY UPDATE value=values(value)') or die($mysqli->error);
        sql_query('DELETE FROM tickets') or die($mysqli->error);
    }
    //==End 09 seedbonus lottery by putyn
    if ($queries > 0) write_log("Lottery clean-------------------- lottery Complete using $queries queries --------------------");
    if (false !== mysqli_affected_rows($GLOBALS["___mysqli_ston"])) {
        $data['clean_desc'] = mysqli_affected_rows($GLOBALS["___mysqli_ston"]) . " items deleted";
    }
    if ($data['clean_log']) {
        cleanup_log($data);
    }
}
?>
