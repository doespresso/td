<?php

require_once(dirname(__FILE__).'/prog/header.inc.php');

if (!isset($_GET['id']))
	exit(0);

$id = intval($_GET['id']);

$res = sql_query('SELECT e_text FROM sb_editor WHERE e_id=?d', $id);
if ($res)
{
	list($html) = $res[0];
	sql_query('DELETE FROM sb_editor WHERE e_id=?d', $id);

	//чистим код от инъекций
	$html = sb_clean_string($html);


	eval(' ?>'.$html.'<?php ');
}

?>