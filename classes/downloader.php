<?php

require_once '../../../../wp-load.php';

function downloadFile($f,$type){

	if($type="attachment")
	{
	global $current_user;
	wp_get_current_user();
	$name=$current_user->user_login;
	$directory=plugin_dir_path(__FILE__)."../attachments/".$name;
	$link=$directory."/".$f;
	echo $link;
    header('Content-Disposition: attachment; filename=' . urlencode($f));
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Description: File Transfer');
	header('Content-Length: ' . filesize($link));
	ob_clean();
	flush();
	echo readfile($link);
	}
}

downloadFile($_GET["f"],$_GET['type']);
exit;

 ?>