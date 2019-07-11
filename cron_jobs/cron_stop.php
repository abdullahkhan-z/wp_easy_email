<?php

ini_set('max_execution_time', 10000);


function easy_email_job_status($pid,$user_id)
{
	if(easy_email_isRunning($pid))
	echo '<input id="cron_type_stop" type="hidden" name="cron_type_stop" value="real"/><button class="form_button2" id="cron_stop" type="button">Stop Cron Job</button>';
	else
	{	global $wpdb;
		$table=$wpdb->prefix."easy_cronjobs";
		$query=$wpdb->prepare("DELETE FROM $table where user_id=%d",$user_id);
		$wpdb->query( $query);
		echo '<button class="form_button2" id="cron" type="button">Start Cron Job</button>';
	}
	
}

function easy_email_job_status2($user_id)
{
	if(get_cron_record($user_id))
	echo '<input id="cron_type_stop" type="hidden" name="cron_type_stop" value="real"/><button class="form_button2" id="cron_stop" type="button">Stop Cron Job</button>';
	else
	{	global $wpdb;
		$table=$wpdb->prefix."easy_cronjobs";
		$query=$wpdb->prepare("DELETE FROM $table where user_id=%d",$user_id);
		$wpdb->query( $query);
		echo '<button class="form_button2" id="cron" type="button">Start Cron Job</button>';
	}
}

function get_cron_record($user_id)
{
	global $wpdb;
	$table=$wpdb->prefix."easy_cronjobs";

	$id=$wpdb->get_var("SELECT id from $table where user_id=$user_id");
	if($id)
	return true;
	else
	return false;
}
function easy_email_isRunning($pid) {
	exec("ps -p $pid", $ProcessState);
	error_log("asdasds");
	error_log(print_r($pid,true));
	return(count($ProcessState) >= 2);
}

function easy_email_script_verify($protocol,$ssl,$hostname,$port,$username,$password,$current_username,$real_interval,$user_id,$auth)
{

	exec('pgrep  curl "'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.$password.'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'" > /dev/null 2>/dev/null & && echo "Running"', $data);
	error_log(print_r('pgrep  curl "'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.$password.'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'" > /dev/null 2>/dev/null & && echo "Running"', true));

	if($data=="Running")
	echo '<input id="cron_type_stop" type="hidden" name="cron_type_stop" value="real"/><button class="form_button2" id="cron_stop" type="button">Stop Cron Job</button>';
	else
	{	global $wpdb;
		$table=$wpdb->prefix."easy_cronjobs";
		$query=$wpdb->prepare("DELETE FROM $table where user_id=%d",$user_id);
		$wpdb->query( $query);
		echo '<button class="form_button2" id="cron" type="button">Start Cron Job</button>';
	}

}
function easy_email_stop_cron_job($id)
{
	$user_id=$id;
	$to_find="user_id=".$user_id;


	
	exec('crontab -l', $data);
	//error_log(print_r($data,true));
	foreach($data as $key=>$row)
	{	
		if(strpos($row,$to_find)!==false)
		{  // error_log(print_r($row,true));
			//error_log(print_r($to_find,true));
			//error_log(print_r($key,true));
			
			unset($data[$key]);
		}	

	}


	exec ('echo \''.implode("\n", $data).'\' | crontab -');

}

function easy_email_verify_cron($id)
{
	$user_id=$id;
	$to_find="user_id=".$user_id;
	
	$exits=false;
	exec('crontab -l', $data);
	
	
	foreach($data as $key=>$row)
	{	
		if(strpos($row,$to_find)!==false)
			{	
				$exits=true;
			}

	}

	if($exits)
	echo '<input id="cron_type_stop" type="hidden" name="cron_type_stop" value="real"/><button class="form_button2" id="cron_stop" type="button">Stop Cron Job</button>';
	else
	{	global $wpdb;
		$table=$wpdb->prefix."easy_cronjobs";
		$query=$wpdb->prepare("DELETE FROM $table where user_id=%d",$id);
		$wpdb->query( $query);
		echo '<button class="form_button2" id="cron" type="button">Start Cron Job</button>';
	}
}	

 ?>