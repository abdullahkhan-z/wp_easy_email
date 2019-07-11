<?php
/**
 * Plugin Name: WP Easy Email
 * Plugin URI: https://zetex-plugins.com
 * Description: SMTP and IMAP/POP client for Wordpress
 * Version: 1
 * Author: Abdullah Zafar
 * Author URI: https://zetex-plugins.com
 * Text Domain: Easy Email
 *
 * 
 */

ini_set('max_execution_time', 10000000000);

require_once '../../../../wp-load.php';
require_once plugin_dir_path(__FILE__)."../classes/PhpImap/imap.php";

//error_log("started");
$protocol=$_GET['protocol'];
$hostname=$_GET['hostname'];
$username=$_GET['username'];
$password=$_GET['password'];
$port=$_GET['port'];
$auth=$_GET['auth'];
$ssl=$_GET['ssl'];
$user_id=$_GET['user_id'];
$current_username=$_GET['current_username'];
$current_user_email=$_GET['current_user_email'];
$interval=$_GET['interval'];
$counter=0;


run_service($protocol,$hostname,$username,$port,$auth,$password,$ssl,$user_id,$current_username,$current_user_email,$interval);





function run_service($protocol,$hostname,$username,$port,$auth,$password,$ssl,$user_id,$current_username,$current_user_email,$interval)
{


	error_log("Service Running....");

	global $wpdb;
	$table=$wpdb->prefix."easy_inbox";
	$table2=$wpdb->prefix."easy_cronjobs";
	$wp_user_meta=$wpdb->prefix."usermeta";
	$directory=plugin_dir_path(__FILE__)."../attachments/".$current_username."/";
	$directory_url=plugin_dir_url(__FILE__)."../attachments/".$current_username."/";
	$number_of_emails=1;
//	error_log(print_r($directory,true));
	
	$password=stripslashes($password);
	//error_log('here');
	//$sql=$wpdb->prepare("UPDATE $table2 set job_stats='starting', pid=%d where user_id=%d",$counterx,$user_id);
	//$wpdb->query($sql);

	$test=new IMAP($protocol,$hostname,$username,$password,$port,$ssl,true,$directory,$directory_url);


	if($protocol=="imap")
		{ 
		$test->set_max_id();
		update_user_meta( $user_id, 'easy_email_max_uid', $test->max_uid); 
		$prev_uid=$test->max_uid;
		}
	else {
		$uid=$test->mailbox->searchMailbox('ALL');
		$real_uid=count($uid);
		update_user_meta( $user_id, 'easy_email_max_uid_pop', $real_uid); 
	}
	//$test->connect();
	//$counterx=0;

	while(true) 
	{				
					error_log("Inside Loop....");
					


					$status=$wpdb->get_var('SELECT status from '.$table2.' where user_id='.$user_id);
					

				//	$sql=$wpdb->prepare("UPDATE $table2 set job_stats='running', pid=%d where user_id=%d",$counterx,$user_id);
				//	$wpdb->query($sql);
					
					//error_log(print_r($sql,true));
				//	$counterx++;
					
					if($status<1 || $status==null || $status=="")
						exit();

					if($protocol=="imap")
					$max_uid=$wpdb->get_var('SELECT meta_value from '.$wp_user_meta.' where meta_key="easy_email_max_uid" and user_id='.$user_id);
					else
					$max_uid=$wpdb->get_var('SELECT meta_value from '.$wp_user_meta.' where meta_key="easy_email_max_uid_pop" and user_id='.$user_id);

					error_log(print_r($max_uid,true));
					error_log("----");
					if(($max_uid=="" || $max_uid==null || $max_uid==0))
					{	
						$test->set_max_id();
						if($protocol=="pop")
						update_user_meta( $user_id, 'easy_email_max_uid_pop', $test->max_uid);   
						else
						update_user_meta( $user_id, 'easy_email_max_uid', $test->max_uid); 
					}

					else
					{	//error_log("this");


						for ($i=0;$i<$number_of_emails;$i++)
					{
						
						if($protocol=="imap")
						{		$start=$max_uid+1+$i;
								$arr=$test->get_email($start);  

								//var_dump($arr);

								$uid=$arr[0]['uid'];
								$fromemail=$arr[0]['fromemail'];
								if($uid!=null &&  $fromemail!=null && $uid>$prev_uid)
									{$content=$arr[0]['htmltext'];
									$prev_uid=$uid;
									$date=date("Y-m-d H:i:s", $arr[0]['date']);
									$fromname=$arr[0]['fromname'];
									$subject=$arr[0]['subject'];
									$fromemail=$arr[0]['fromemail'];
									$attachments=$arr[0]['attachments'];
									$attachment_string=implode(',',$attachments);
									$wpdb->insert($table, array(
										'user_id' => $user_id,
										'uid' => $uid,
										'subject' => $subject,
										'status'=>0,
										'responded'=>0,
										'previous_box'=>'inbox',
										'name'=>$fromname,
										'email_to'=>$current_user_email,
										'email_from'=>$fromemail,
										'content'=>$content,
										'box'=>'inbox',
										'attachment'=>$attachment_string,
										'time'=>$date
									));
									if($protocol=="imap")
									update_user_meta( $user_id, 'easy_email_max_uid', $uid); 
									//$test->mailbox->disconnect();
								}
								
						}

						else if($protocol=="pop")
						
						{	$uid=$test->mailbox->searchMailbox('ALL');
							$real_uid=count($uid);
							$max_uid=0;

							$max_uid=$wpdb->get_var('SELECT meta_value from '.$wp_user_meta.' where meta_key="easy_email_max_uid_pop" and user_id='.$user_id);
							//$max_uid=get_user_meta($user_id, 'easy_email_max_uid_pop', true);
							update_user_meta( $user_id, 'easy_email_max_uid_pop', $real_uid);
							if($real_uid>$max_uid)
							{$arr=$test->get_email($real_uid); 
							$uid=$arr[0]['uid'];
							$fromemail=$arr[0]['fromemail'];
							if($uid!=null &&  $fromemail!=null)
								{$content=$arr[0]['htmltext'];
								$prev_uid=$uid;
								$date=date("Y-m-d H:i:s", $arr[0]['date']);
								$fromname=$arr[0]['fromname'];
								$subject=$arr[0]['subject'];
								$fromemail=$arr[0]['fromemail'];
								$attachments=$arr[0]['attachments'];
								$attachment_string=implode(',',$attachments);
								$wpdb->insert($table, array(
									'user_id' => $user_id,
									'uid' => $uid,
									'subject' => $subject,
									'status'=>0,
									'responded'=>0,
									'previous_box'=>'inbox',
									'name'=>$fromname,
									'email_to'=>$current_user_email,
									'email_from'=>$fromemail,
									'content'=>$content,
									'box'=>'inbox',
									'attachment'=>$attachment_string,
									'time'=>$date
								));
								
							
							}
							}
							$test->mailbox->disconnect();

						}
						
					}


				}
				sleep($interval);
	}
		
			
			}

 ?>