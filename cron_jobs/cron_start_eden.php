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
require_once plugin_dir_path(__FILE__)."../classes/imap/imap.php";

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





if($ssl==1)
 	$ssl=true;
else
	$ssl=false;



run_service($protocol,$hostname,$username,$port,$auth,$password,$ssl,$user_id,$current_username,$current_user_email,$interval);





function run_service($protocol,$hostname,$username,$port,$auth,$password,$ssl,$user_id,$current_username,$current_user_email,$interval)
{



	
	global $wpdb;
	$table=$wpdb->prefix."easy_inbox";
	$table2=$wpdb->prefix."easy_cronjobs";
	$directory=plugin_dir_path(__FILE__)."../attachments/".$current_username."/";
	$directory_url=plugin_dir_url(__FILE__)."../attachments/".$current_username."/";
	$number_of_emails=1;
	$password=stripslashes($password);

	$test=new EDEN_IMAP($protocol,$hostname,$username,$password,$port,$ssl,false,$directory,$directory_url);
	
	if($protocol=="imap"){
		$test->connect();
		$test->getmailboxes();
		$test->setInboxactive();
		$test->setmaxuid();
		update_user_meta( $user_id, 'easy_email_max_uid',$test->getmaxuid()); 
		$prev_id=$test->getmaxuid();

		error_log(print_r($prev_id,true));
	}
	else
	{	//$test->connect();
		$id=$test->imap->getEmailTotal();
		update_user_meta( $user_id, 'easy_email_max_uid_pop',$id); 
		$prev_id=$id;
	}	

	//$test->connect();
	while(true) 
	{				
				
		error_log(print_r("inside",true));
					$status=$wpdb->get_var('SELECT status from '.$table2.' where user_id='.$user_id);
					if($status<1 || $status==null || $status=="")
						exit();

						
					if($protocol=="imap") {
						$max_uid=get_user_meta($user_id, 'easy_email_max_uid', true);

					//	error_log(print_r("inside",true));
				//		error_log(print_r($max_uid,true));

						if(($max_uid=="" || $max_uid==null || $max_uid==0))
						{	
							

								$test->setmaxuid();
								update_user_meta( $user_id, 'easy_email_max_uid',$test->getmaxuid()); 
							
						}
					
					

					else
					{	//error_log("this");


						for ($i=0;$i<$number_of_emails;$i++)
					{
	
						$start=$max_uid+1+$i;
						
						$test->setmaxuid();
						$uid=$test->getmaxuid();
						
						
								
						
						
						
						//var_dump($arr);

			
						//error_log("NEW_ID");
						//error_log(print_r($uid,true));

						if($uid>$prev_id && ($uid!=null || $uid!=""))
							{
							$arr=$test->getNewEmails($start,1,true); 
							
						
							$prev_id=$uid;	
							$content=$arr['htmltext'];
							
							$date=date("Y-m-d H:i:s", $arr['date']);
							$fromname=$arr['fromname'];
							$subject=$arr['subject'];
							$fromemail=$arr['fromemail'];
							$attachments=$arr['attachments'];

							$attachment_string=implode(',',$attachments);
							$wpdb->insert($table, array(
								'user_id' => $user_id,
								'uid' => '0',
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
							
							update_user_meta( $user_id, 'easy_email_max_uid', $start); 
							
						}
					}
				
				}
			}
						else if($protocol=="pop"){
							
							$test=new EDEN_IMAP($protocol,$hostname,$username,$password,$port,$ssl,false,$directory,$directory_url);
							
						//	$response=$test->connect();

							$id=$test->imap->getEmailTotal();
							
							


							error_log(print_r($prev_id,true)); 
							error_log("new");
							error_log(print_r($id,true)); 
							
							if($id<=$prev_id)
							    continue;
							if($id=="" || $id==null)
								continue;
							
							$prev_id=$id;

							
							
							$arr=$test->getNewEmails($id,1,true); 
							
							$date=$arr['date'];
							if($date!="" || $date!=null){
							$content=$arr['htmltext'];
							
							$date=date("Y-m-d H:i:s", $arr['date']);
							$fromname=$arr['fromname'];
							$subject=$arr['subject'];
							$fromemail=$arr['fromemail'];
							$attachments=$arr['attachments'];
							//error_log("Attachments");
						
							$attachment_string=implode(',',$attachments);
							//error_log(print_r($attachment_string,true));
							$wpdb->insert($table, array(
								'user_id' => $user_id,
								'uid' => '0',
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

					
						$test->imap->disconnect();
						
					}


				
				sleep($interval);
	}
		
			
			}

 ?>