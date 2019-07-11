<?php

	require_once '../../../../wp-load.php';

	if(isset($_GET['status']) && !isset($_FILES['image']))
		echo '{"success":true,"data":{"id":0,"name":"Unauthenticated","storage":true,"subscription":{"status":"ACTIVE","entitlements":{"branding":true}},"tools":[]}}';
	else
	{	
	//	echo '{"success":true,"data":{"location":"https://unroll-images-production.s3.amazonaws.com/projects/0/1537980229481-Artboard%201.png","contentType":"image/png","size":2184}}';

		$file_name=$_FILES['image']['name'];
		$content_type=$_FILES['image']['type'];
		$size=$_FILES['image']['size'];
		$error=$_FILES['image']['error'];
		$tmp_name=$_FILES['image']['tmp_name'];
		$file = wp_upload_bits( $file_name, null, @file_get_contents( $tmp_name ) );
		error_log(print_r($file,true));
		if( FALSE === $file['error'])
		{	
			echo '{"success":true,"data":{"location":"'.$file['url'].'","contentType":"'.$content_type.'","size":'.$size.'}}';

		}
		else
		echo '{"success":false}';
	}
?>