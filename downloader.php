<?php
require_once ABSPATH . 'wp-load.php';

function downloadFile($f,$type){

	if($type="attachment")
	{
	global $current_user;
	wp_get_current_user();
	$name=$current_user->user_login;
	$directory=plugin_dir_path(__FILE__)."../attachments/".$name;
	
	$link=$directory."/".$f;
	error_log(print_r($f,true));
    header('Content-Disposition: attachment; filename=' . urlencode($link));
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Description: File Transfer');
    header('Content-Length: ' . filesize($f));
	echo file_get_contents($f);
	}
}

downloadFile($_GET["f"],$_GET['type']);
exit;

 ?>