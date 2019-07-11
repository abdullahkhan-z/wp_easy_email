<?php

require_once '../../../../wp-load.php';

function content($id){


	global $wpdb;
	$id=$_REQUEST['id'];
	$table=$wpdb->prefix."easy_inbox";
	$results = $wpdb->get_results( "SELECT * FROM $table where id=$id");
	
	foreach($results as $row)
	{

	  echo	stripslashes($content=$row->content);



	}

wp_die();
}

content($_GET['id']);


 ?>