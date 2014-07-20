<?php

function getTransactionProps($system, $data)
{
    include_once(SB_CMS_LANG_PATH.'/pl_payment.lng.php');

    if (!isset($GLOBALS[$system]))
       return '';

    $return = '<table>';

    foreach($data as $key => $value)
    {
        if (isset($GLOBALS[$system][$key]) && $value != '')
           $return .= '<tr><td align="right" width="250"><b>'.$GLOBALS[$system][$key].':</b></td><td>'.$value.'</td></tr>';
    }

    $return .= '</table>';
    return $return;
}

function sendEmailOnSuccess($topic, $body, $email, $data, $full_text)
{
	list($sp_id, $sp_name, $sp_secname, $sp_surname, $sp_text, $sp_comment, $sp_summ, $sp_date, $sp_address, $sp_email, $sp_phone) = $data;
    if ($email == '')
       $email = $sp_email;

    if ($email == '')
       return;

    $email_subj = str_replace(
    array('{SP_ID}', '{SP_SURNAME}', '{SP_NAME}', '{SP_SECNAME}', '{SP_EMAIL}', '{SP_PHONE}', '{SP_ADDRESS}', '{SP_TEXT}', '{SP_COMMENT}', '{SP_SUMM}', '{SP_FULL_TEXT}', '{SP_DATE}'),
    array($sp_id, $sp_surname, $sp_name, $sp_secname, $sp_email, $sp_phone, $sp_address, $sp_text, $sp_comment, $sp_summ, $full_text, date('d.m.Y H:i:s', $sp_date)), $topic);

    //чистим код от инъекций
    $email_subj = sb_clean_string($email_subj);

    ob_start();
    eval(' ?>'.$email_subj.'<?php ');
    $email_subj = ob_get_clean();

    $email_text = str_replace(
    array('{SP_ID}', '{SP_SURNAME}', '{SP_NAME}', '{SP_SECNAME}', '{SP_EMAIL}', '{SP_PHONE}', '{SP_ADDRESS}', '{SP_TEXT}', '{SP_COMMENT}', '{SP_SUMM}', '{SP_FULL_TEXT}', '{SP_DATE}'),
    array($sp_id, $sp_surname, $sp_name, $sp_secname, $sp_email, $sp_phone, $sp_address, $sp_text, $sp_comment, $sp_summ,  $full_text, date('d.m.Y H:i:s', $sp_date)), $body);

    //чистим код от инъекций
    $email_text = sb_clean_string($email_text);

    ob_start();
    eval(' ?>'.$email_text.'<?php ');
    $email_text = ob_get_clean();

    if ($email != '' && $email_subj != '' && $email_text != '')
    {
		include_once(SB_CMS_LIB_PATH.'/sbMail.inc.php');

    	$mailer = new sbMail();
    	$mailer->setSubject($email_subj);
        $mailer->setHtml($email_text);
        $mailer->send(array($email));
    }
}

// Обработка платежей по ASSIST
function sb_assist($settings)
{
    $add_str = '';
    if(isset($_GET['order_id']))
    {
        $add_str .= "&SHOPORDERNUMBER=".intval($_GET['order_id']);
    }

    $href = "http://secure.assist.ru/results/results.cfm?SHOP_ID=".$settings['sps_option_id']."&LOGIN=".$settings['sps_option_login']."&PASSWORD=".$settings['sps_option_pass']."&HEADER1=1".$add_str;

	include_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');
    $httpRequest = new sbDownload($href);
    $contents = $httpRequest->download();

    if(!$contents || $contents == '' || stristr($contents, '404 not found'))
    {
        return false;
    }
    else
    {
        $contents = trim($contents);
        $lines = explode("\n", $contents);
        $headers = explode(';', $lines[0]);

		for($i = 1; $i < count($lines); $i++)
        {
			$res = explode(';', $lines[$i]);
            $status = 6;
            switch($res[1])
            {
                case 'AS000':
                    $status = 2;
                    break;

                case 'AS300':
                    $status = 1;
                    break;

                default:
                    $status = 6;
                    break;
            }

            $data = array();
            foreach ($headers as $key => $value)
            {
                if (function_exists('iconv'))
                    $data[strtolower($value)] = SB_CHARSET != 'UTF-8' ? iconv('UTF-8', SB_CHARSET.'//IGNORE', $res[$key]) : $res[$key];
                else
                    $data[strtolower($value)] = $res[$key];
            }
            $ser_data = mysql_escape_string(serialize($data));

            $prev_res = sql_param_query('SELECT sp_status FROM sb_payments WHERE sp_id = ?d', $res[0]);
            if ($prev_res)
            {
                list($prev_status) = $prev_res[0];

                $rows = array();
                $rows['sp_status'] = $status;
                $rows['sp_attr3'] = $ser_data;

                sql_param_query('UPDATE sb_payments SET ?a WHERE sp_id = ?d', $rows, $res[0]);

                if ($prev_status != $status && $status == 2)
                {
					$full_data = getTransactionProps('pl_payment_assist', $data);

                    $res = sql_param_query('SELECT sp_id, sp_name, sp_secname, sp_surname, sp_text, sp_comment, sp_summ, sp_date, sp_address, sp_email, sp_phone FROM sb_payments WHERE sp_id = ?d', $res[0]);
                    if ($res)
                    {
                        eval(str_replace(array('{SP_ID}'), array($res[0][0]), $settings['sps_option_php_code']));

                        sendEmailOnSuccess($settings['sps_option_useremail_topic'], $settings['sps_option_useremail_message'], '', $res[0], $full_data);
                        sendEmailOnSuccess($settings['sps_option_adminemail_topic'], $settings['sps_option_adminemail_message'], $settings['sps_option_adminemail'], $res[0], $full_data);
                    }
                }
            }
        }
    }
}


function sb_chronopay($settings)
{
    $status_ok = true;
    $cs2 = '';
    $cs1 = 0;
    extract($_POST);

    if($settings['sps_option_ip'] != $_SERVER['REMOTE_ADDR']) $status_ok = false;
    if(isset($settings['sps_option_login']) && strlen($settings['sps_option_login']) > 0 && $settings['sps_option_login'] != $_POST['username']) $status_ok = false;
    if(isset($settings['sps_option_password']) && strlen($settings['sps_option_password']) > 0 && $settings['sps_option_password'] != $_POST['password']) $status_ok = false;
    if($settings['sps_option_site_id'] != $_POST['site_id']) $status_ok = false;
    if($settings['sps_option_id'] != $_POST['product_id']) $status_ok = false;

    $res = sql_param_query('SELECT ps_id, ps_name, ps_secname, ps_surname, ps_text, ps_comment, ps_summ, ps_date, ps_address, ps_email, ps_phone FROM sb_payments WHERE sp_id = ?d', $cs1);
    if ($res)
    {
        $hash = md5('~'.$res[0][0].$res[0][6].$res[0][1].$res[0][3].$res[0][2].'~');
        if ($hash != $cs2) $status_ok = false;

        $data = array();
        foreach($_POST as $key => $value)
        {
            $data[strtolower($key)] = $value;
        }
        $ser_data = mysql_escape_string(serialize($data));
        $rows = array();

        if($status_ok)
        {
            $rows['sp_status'] = 2;
            $rows['sp_attr3'] = $ser_data;

        	sql_param_query('UPDATE sb_payments SET ?a WHERE sp_id = ?d', $rows, $cs1);

            $full_data = getTransactionProps('pl_payment_chronopay', $data);

            if ($res)
            {
                eval(str_replace(array('{SP_ID}'), array($res[0][0]), $settings['sps_option_php_code']));

                sendEmailOnSuccess($settings['sps_option_useremail_topic'], $settings['sps_option_useremail_message'], '', $res[0], $full_data);
                sendEmailOnSuccess($settings['sps_option_adminemail_topic'], $settings['sps_option_adminemail_message'], $settings['sps_option_adminemail'], $res[0], $full_data);
            }
        }
        else
        {
            $rows['sp_status'] = 3;
            $rows['sp_attr3'] = $data;

            sql_query ('UPDATE sb_payments SET ?a WHERE sp_id=?d', $cs1);
        }
    }
}

// Обработка pre-request RUpay
function sb_rupay_prerequest($settings)
{
    $payment_ok = true;
    $rupay_action = $rupay_name_service = $rupay_user = $rupay_email = $rupay_hash = '';
    $rupay_site_id = $rupay_order_id = $rupay_id = $rupay_sum = $rupay_data = 0;

    extract($_POST);
    // Проверяем ID магазина
    if($rupay_site_id != $settings['sps_option_rupay_id']) $payment_ok = false;

    // Проверяем сумму платежа
    $res = sql_param_query('SELECT sp_summ FROM sb_payments WHERE sp_id=?d', $rupay_order_id);

    if(count($res) == 0)
    {
        $payment_ok = false;
    }
    else
    {
        list($sp_summ) = $res[0];
        if(round($sp_summ, 2) != round($rupay_sum, 2))
        {
            echo "incorrect summ";
            $payment_ok = false;
        }
    }

    // Проверяем контрольную подпись
    $string = $rupay_action.'::'.$rupay_site_id.'::'.$rupay_order_id.'::'.$rupay_name_service.'::'.$rupay_id.'::'.$rupay_sum.'::'.
                $rupay_user.'::'.$rupay_email.'::'.$rupay_data.'::'.$settings['sps_option_rupay_skey'];

    if(strtoupper($rupay_hash) != strtoupper(md5($string))) $payment_ok = false;

    if($payment_ok)
    {
        $rows = array();
        $rows['sp_status'] = 1;
        $rows['sp_attr1'] = $rupay_user;
        $rows['sp_sttr2'] = $rupay_email;
        $rows['sp_sttr3'] = $rupay_hash;

        sql_param_query('UPDATE sb_payments SET ?a WHERE sp_id = ?d', $rows, $rupay_order_id);
        echo "YES";
    }
}

// Обработка формы оповещения о платеже RUpay
function sb_rupay_confirmation($settings)
{
    $payment_ok = true;

    $rupay_action = $rupay_email = $rupay_hash = '';
    $rupay_site_id = $rupay_order_id = $rupay_sum = $rupay_id = $rupay_data = $rupay_status = $cs1 = 0;

    extract($_POST);
    // Проверяем ID магазина
    if($_POST['rupay_site_id'] != $settings['sps_option_rupay_id']) $payment_ok = false;

    // Проверяем контрольную подпись
    $string = $rupay_action.'::'.$rupay_site_id.'::'.$rupay_order_id.'::'.$rupay_sum.'::'.$rupay_id.'::'.$rupay_data.'::'.
                $rupay_status.'::'.$settings['sps_option_rupay_skey'];

    if($rupay_hash != md5($string)) $payment_ok = false;

    if($payment_ok)
    {
        switch($rupay_status)
        {
            case 2:
                $status = 4;
                break;

            case 3:
                $status = 2;
                break;

            case 4:
                $status = 5;
                break;

            case 6:
                $status = 6;
                break;
        }

        $rows = array();
        $rows['sp_status'] = $status;

        sql_param_query('UPDATE sb_payments SET ?a WHERE sp_id=?d', $rows, $rupay_order_id);

        $res = sql_param_query('SELECT sp_id, sp_name, sp_secname, sp_surname, sp_text, sp_summ, FROM sb_payments WHERE sp_id = ?d', $cs1);
        if ($res)
        {
            eval(str_replace(array('{SP_ID}'), array($res[0][0]), $settings['sps_option_php_code']));

            sendEmailOnSuccess($settings['sps_option_useremail_topic'], $settings['sps_option_useremail_message'], $rupay_email, $res[0]);
            sendEmailOnSuccess($settings['sps_option_adminemail_topic'], $settings['sps_option_adminemail_message'], $settings['sps_option_adminemail'], $res[0]);
        }
    }
    else
    {
	    sql_param_query('UPDATE sb_payments SET sp_status = 1 WHERE sp_id = ?d', $rupay_order_id);
    }
}

// Проверяем процессинговый центр
$res = sql_query('SELECT sps_ident, sps_name, sps_value FROM sb_payments_settings');

$settings = array();
for($i = 0; $i < count($res); $i++)
{
    list($sps_ident, $sps_name, $sps_value) = $res[$i];

    if (!is_array($settings[$sps_ident]))
       $settings[$sps_ident] = array();

    $settings[$sps_ident][$sps_name] = $sps_value;
}



if(isset($_GET['check_assist']))
{
    sb_assist($settings['pl_payment_assist']);
}

if(isset($_POST['cs1']))
{
    sb_chronopay($settings['pl_payment_chronopay']);
}

if(isset($_POST['rupay_action']) && $_POST['rupay_action'] == 'add')
{
    sb_rupay_prerequest($settings['pl_payment_rupay']);
}
// Тест на оповещение о платеже RUpay, если это то что нам надо вызываем обработчик
elseif(isset($_POST['rupay_action']) && $_POST['rupay_action'] == 'update')
{
    sb_rupay_confirmation($settings['pl_payment_rupay']);
}

?>