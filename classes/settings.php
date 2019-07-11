<?php
/**
 * Email List Table
 * PHP VERSION 5.5
 * 
 * @category Inbox
 * 
 * @package Easy_Email
 * 
 * @author Abdullah Zafar <abdullah.zafar9991@gmail.com>
 * 
 * @license http://google.com Paid
 * 
 * @link http://google.com
 **/



require_once ABSPATH . 'wp-load.php';
require_once plugin_dir_path(__File__) . '/PhpImap/imap.php';

if(! class_exists('EDEN_IMAP'))
	require_once plugin_dir_path(__File__) . '/imap/imap.php';
require_once plugin_dir_path(__File__) . '../cron_jobs/cron_stop.php';

function var_error_log( $object=null ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log( $contents );        // log contents of the result of var_dump( $object )
}

class Easy_Editor_Settings {
	public $phpmailer=true;
	public $script="";
	public $default_email="";
	public $default_name="";
	public $default_email_admin="";
	public $default_name_admin="";
	public $hook="";
	public $hook_eden="";
	public function __construct() {
		

		global $current_user;
		
		$current_username=$current_user->user_login;

		$this->hook="easy_user_cron_job";
		$this->hook_eden="easy_user_cron_job_eden";

		add_action ($this->hook, [$this,'run_service'], 1, 10 );
		add_action ($this->hook_eden, [$this,'run_service_eden'], 1, 10 );

		add_action( 'wp_ajax_easy_email_settings_call_back', [$this,'easy_email_settings_call_back'] );
		add_action( 'wp_ajax_easy_email_form_save_call_back', [$this,'easy_email_form_save_call_back'] );

		add_filter('cron_schedules',[$this,'my_cron_schedules']);
		add_shortcode( 'easy_email_form',[$this,'form_shortcode'] );

		
	}

	public function form_shortcode( $atts ) {
		$a = shortcode_atts( array(
			'id' => '1',
		), $atts );
		
		global $wpdb;
		$table=$wpdb->prefix."easy_forms";
		$results=$wpdb->get_results("SELECT * FROM $table where identifier=".$a['id']);
		foreach($results as $row)
			{	$link=plugin_dir_url(__FILE__);
				$arr=array("https://","http://");
				$link=str_replace($arr,"//",$link);
				return "<form method='post' id='form' action='".$link."/form_builder/form_submission_handler.php'><input type='hidden' name='id' value='".$row->id."'/>".$form_template=stripslashes($row->form_template)."</form>";

			}
	
	}
	
	public function my_mail_from_name( $name ) {
		return $this->default_name;
	}

	public function my_mail_from( $email ) {
		return $this->default_email;
	}
	public function easy_email_form_save_call_back()
	{
		 $form_name="";
		 $form_email="";
		 $form="";
		 if(isset($_POST['form_name']))
			 $form_name=$_POST['form_name'];
		 if(isset($_POST['form_email']))
			 $form_email=$_POST['form_email'];
		 if(isset($_POST['form']))
			 $form=$_POST['form'];
		if(isset($_POST['form_json']))
			 $form_json=$_POST['form_json'];
		if(isset($_POST['subject']))
			$subject=$_POST['subject'];
		
		global $wpdb;
		if(isset($_POST['id']))
		{
			$id=$_POST['id'];
			$table=$wpdb->prefix."easy_forms";
			$query=$wpdb->prepare("UPDATE $table SET form_name = %s,form_email_to=%s,form_template=%s,form_json=%s,subject=%s WHERE id = %d",$form_name,$form_email,$form,$form_json,$subject,$id);
			if($wpdb->query( $query))
			{
				$identifier=$wpdb->get_var("select identifier from $table where id=$id");
				$response=array("status"=>"Saved","id"=>$identifier);
				echo json_encode($response);
			}
			else
			{
				$response=array("status"=>"An Error Occurred While Saving Form. Please Try Again.");
				echo json_encode($response);
			}

			wp_die();

			}

	
		$table=$wpdb->prefix."easy_forms";
		$identifier=time();
		if($wpdb->insert($table, array(
				'user_id' => get_current_user_id(),
				'form_name'=>$form_name,
				'form_email_to'=>$form_email,
				'form_template'=>$form,
				'identifier'=>$identifier,
				'form_json'=>$form_json,
				'subject'=>$subject
			)))
		{
			$response=array("status"=>"Saved","id"=>$identifier);
			echo json_encode($response);

		}
		else
		{
			$response=array("status"=>"An Error Occurred While Saving Form. Please Try Again.");
			echo json_encode($response);
		}

		wp_die();
	}

	public function easy_email_settings_call_back()
	{	

		if($_POST['form_type']=="delivery")
		{

			if (isset($_POST['email']) && $_POST['email']!="")
				$email=$_POST['email'];
			else
				$email="";
			
			if (isset($_POST['name']) && $_POST['name']!="")
				$name=$_POST['name'];
			else
				$name="";

			if (isset($_POST['hostname']) && $_POST['hostname']!="")
				$hostname=$_POST['hostname'];
			else
				$hostname="";

			
			if (isset($_POST['port']) && $_POST['port']!="")
				$port=$_POST['port'];
			else
				$port=25;
			
			if (isset($_POST['auth']) && $_POST['auth']!="")
				$auth=$_POST['auth'];
			else
				$auth="none";

			if(isset($_POST['usernamer']) && $_POST['usernamer']!="")
				$usernamer=$_POST['usernamer'];
			else
				$usernamer="";

			if (isset($_POST['password']) && $_POST['password']!="")
				$password=$_POST['password'];
			else
				$password="";

			if(isset($_POST['role']) && $_POST['role']=="admin")
			{
				update_user_meta( get_current_user_id(), 'easy_email_email_from_admin', $email);
				update_user_meta( get_current_user_id(), 'easy_email_email_from_name_admin', $name);
				update_user_meta( get_current_user_id(), 'easy_email_hostname_admin', $hostname);
				update_user_meta( get_current_user_id(), 'easy_email_port_admin', $port);
				update_user_meta( get_current_user_id(), 'easy_email_security_admin', $auth);
				update_user_meta( get_current_user_id(), 'easy_email_password_admin', $password);
				update_user_meta( get_current_user_id(), 'easy_email_username_admin', $usernamer);

				$smtpauth=false;
				if($auth!="none")
				$smtpauth=true;

				if($hostname!="")
				{	

					add_action( 'phpmailer_init', function( $phpmailer ) {
						if ( !is_object( $phpmailer ) ) 
							$phpmailer = (object) $phpmailer;
						
						$phpmailer->Mailer     = 'smtp';
						$phpmailer->Host       = $host;
						$phpmailer->SMTPAuth   = $smtppauth;
						$phpmailer->Port       = $port;
						$phpmailer->Username   = $email;
						$phpmailer->Password   = $pass;
						$phpmailer->SMTPSecure = $auth;
						$phpmailer->From       = $email;
						$phpmailer->FromName   = $name;
					});

				}

				else
				{
					add_action( 'phpmailer_init', function( $phpmailer ) {
						if ( !is_object( $phpmailer ) ) 
							$phpmailer = (object) $phpmailer;
						
							$phpmailer->IsMail();
					});
				}

				echo "Success";
				wp_die();
			}
			update_user_meta( get_current_user_id(), 'easy_email_email_from', $email);
			update_user_meta( get_current_user_id(), 'easy_email_email_from_name', $name);
			update_user_meta( get_current_user_id(), 'easy_email_hostname', $hostname);
			update_user_meta( get_current_user_id(), 'easy_email_port', $port);
			update_user_meta( get_current_user_id(), 'easy_email_security', $auth);
			update_user_meta( get_current_user_id(), 'easy_email_password',$password);
			update_user_meta( get_current_user_id(), 'easy_email_username', $usernamer);

			echo "Success";
			wp_die();
			
		}


		else if($_POST['form_type']=="test")
		{

			if (isset($_POST['email']) && $_POST['email']!="")
				$email=$_POST['email'];
			else
				$email="";
			
			if (isset($_POST['name']) && $_POST['name']!="")
				$name=$_POST['name'];
			else
				$name="";

			if (isset($_POST['hostname']) && $_POST['hostname']!="")
				$hostname=$_POST['hostname'];
			else
				$hostname="";

			
			if (isset($_POST['port']) && $_POST['port']!="")
				$port=$_POST['port'];
			else
				$port=25;
			
			if (isset($_POST['auth']) && $_POST['auth']!="")
				$auth=$_POST['auth'];
			else
				$auth="none";
			
			if(isset($_POST['usernamer']) && $_POST['usernamer']!="")
				{$usernamer=$_POST['usernamer'];
					$authentication=true;}
			else
				{$usernamer="";
					$authentication=false;}

			if (isset($_POST['password']) && $_POST['password']!="")
				$password=stripslashes($_POST['password']);
				 
			else
				$password="";
				 
			global $current_user;
			get_currentuserinfo();
					
			$to_email = (string) $current_user->user_email;

			if($auth=="ssl")
			{	$ssl=true;
				$tls=false;}
			else if($auth=="tls")
			{	$ssl=false;
				$tls=true;}

			if($_POST['hostname']=="")
			{	if($name!="" && $email!="")
				{   $this->default_email=$email;
					$this->default_name=$name;
					add_filter( 'wp_mail_from_name', [$this,'my_mail_from_name']);

					add_filter( 'wp_mail_from', [$this,'my_mail_from']);
				}
				//	$headers[] = 'From: '.$name.' <'.$email.'>';
					$status=wp_mail(array($to_email),"Test Email","This is a test email",$headers);
					if($status)
					echo "Message has been sent";
					else
					echo "An error occurred";	
					
					remove_filter( 'wp_mail_from_name', [$this,'my_mail_from_name']);

					remove_filter( 'wp_mail_from', [$this,'my_mail_from']);
				
			}
			else
			{	if(!$this->phpmailer)
				{
					if(! class_exists('EASY_SMTP'))
					require_once plugin_dir_path(__File__) . '/smtp_class.php';
					$smtp=new EASY_SMTP($hostname,(int)$port,$authentication,$usernamer,$password,$ssl,$tls,$email,$name);
				
					$smtp->send_email(array($to_email),"Test Email","This is a test email",array());
				}
				else{
					
					if(!class_exists('PHPMailer'))
					require_once(ABSPATH . WPINC . '/class-phpmailer.php');

					$mail=new PHPMailer();
					$mail->isSMTP();                                      
					$mail->Host = $hostname;  
					$mail->SMTPAuth = $authentication;                               
					$mail->Username = $usernamer;                            
					$mail->Password = $password;                           
					$mail->SMTPSecure = $auth;
					$mail->ContentType = 'text/html';
					$mail->Port       = $port;
					$mail->From       = $email;
					$mail->FromName   = $name;
					$mail->AddAddress($to_email."");
					$mail->Subject="Test Email";
					$mail->Body="This is a test mail";
					$info = $mail->Send();
					if($info){
						echo "Message has been sent.";
						wp_die();
					}
					else{
						echo $mail->ErrorInfo;
						wp_die();
					}
					
				}

				
				//error_log(print_r($password,true));


				
				

			}

			wp_die();

		}


		else if($_POST['form_type']=="restore")
		{
			$id=get_current_user_id();
			$urlparts = parse_url(site_url());
			$url=$urlparts['host'];
			$domain_name=explode('www.',$url)[1];
			if($domain_name=="")
			$domain_name=$url;

			$email_from="wordpress@".$domain_name;
			update_user_meta($id, 'easy_email_email_from', $email_from);
			update_user_meta($id, 'easy_email_email_from_name', "admin");
			update_user_meta($id, 'easy_email_hostname', "localhost");
			update_user_meta($id, 'easy_email_port', "25");
			update_user_meta($id, 'easy_email_security', "");
			update_user_meta($id, 'easy_email_username', "");
			update_user_meta($id, 'easy_email_password', "");

			echo "Success";
			wp_die();

		}


		else if($_POST['form_type']=='recv')
		{

			if (isset($_POST['protocol']) && $_POST['protocol']!="")
			$protocol=$_POST['protocol'];
			else
			$protocol="";

			if (isset($_POST['hostname']) && $_POST['hostname']!="")
				$hostname=$_POST['hostname'];
			else
				$hostname="";

			if (isset($_POST['username']) && $_POST['username']!="")
				$username=$_POST['username'];
			else
				$username="";

			if (isset($_POST['port']) && $_POST['port']!="")
				$port=$_POST['port'];
			else
				$port=0;
			
			if (isset($_POST['auth']) && $_POST['auth']!="")
				$auth=$_POST['auth'];
			else
				$auth="none";
			
			if (isset($_POST['password']) && $_POST['password']!="")
				$password=$_POST['password'];
			else
				$password="";

			if(isset($_POST['interval']) && $_POST['interval']!="")
				$interval=$_POST['interval'];
			else
				$interval="30";

			if(isset($_POST['cron_type']) && $_POST['cron_type']!="")
				$cron_job=$_POST['cron_type'];
			else
				$cron_job="real";

			if(isset($_POST['engine']) && $_POST['engine']!="")	
				$engine=$_POST['engine'];
			else
				$engine="php";

			update_user_meta( get_current_user_id(), 'easy_email_recv_protocol', $protocol);
			update_user_meta( get_current_user_id(), 'easy_email_recv_hostname', $hostname);
			update_user_meta( get_current_user_id(), 'easy_email_recv_port', $port);
			update_user_meta( get_current_user_id(), 'easy_email_recv_security', $auth);
			update_user_meta( get_current_user_id(), 'easy_email_recv_username', $username);
			update_user_meta( get_current_user_id(), 'easy_email_recv_password', $password);
			update_user_meta( get_current_user_id(), 'easy_email_recv_interval', $interval);
			update_user_meta( get_current_user_id(), 'easy_email_recv_cron_type', $cron_job);
			update_user_meta( get_current_user_id(), 'easy_email_recv_engine', $engine);

			echo "Success";
			wp_die();
		}

		else if($_POST['form_type']=='con_test')
		{
			if (isset($_POST['protocol']) && $_POST['protocol']!="")
			$protocol=$_POST['protocol'];
			else
			$protocol="";

			if (isset($_POST['hostname']) && $_POST['hostname']!="")
				$hostname=$_POST['hostname'];
			else
				$hostname="";

			if (isset($_POST['username']) && $_POST['username']!="")
				$username=$_POST['username'];
			else
				$username="";

			if (isset($_POST['port']) && $_POST['port']!="")
				$port=$_POST['port'];
			else
				$port=0;
			
			if (isset($_POST['auth']) && $_POST['auth']!="")
				$auth=$_POST['auth'];
			else
				$auth="none";
			
			if (isset($_POST['password']) && $_POST['password']!="")
				$password=stripslashes($_POST['password']);
			else
				$password="";

				//error_log(print_r($password,true));

			if(! class_exists('IMAP'))
				require_once plugin_dir_path(__File__) . '/PhpImap/imap.php';
			
			if($auth=="ssl")
			{
				$ssl=true;
			
			}
			else
			{
				$ssl=false;

			}
			
			if(isset($_POST['engine']) && $_POST['engine']!="")	
				$engine=$_POST['engine'];
			else
				$engine="php";

			if($engine=="php")
			{	
				
				$test=new IMAP($protocol,$hostname,$username,$password,$port,$ssl,true,"/","/");
				
				$test->connect();
			}

			else
			{	

				
				$test=new EDEN_IMAP($protocol,$hostname,$username,$password,$port,$ssl,false,"/","/");
				
				$response=$test->connect();
				if($response=="")
				echo "Success";
				else
				echo $response;
				
			}
			wp_die();

		}

		else if($_POST['form_type']=='start_cron')
		{	global $wpdb;
			$table=$wpdb->prefix."easy_cronjobs";
			global $current_user;
			wp_get_current_user();
			$user_id=$current_user->ID;
			$current_username=$current_user->user_login;

			$job=$wpdb->get_var("select user_id from $table where user_id=".$user_id);

			if($job!=null || $job!="")
			{
				echo "refresh";
				wp_die();
			}

			if (isset($_POST['protocol']) && $_POST['protocol']!="")
			$protocol=$_POST['protocol'];
			else
			$protocol="";

			if (isset($_POST['hostname']) && $_POST['hostname']!="")
				$hostname=$_POST['hostname'];
			else
				$hostname="";

			if (isset($_POST['username']) && $_POST['username']!="")
				$username=$_POST['username'];
			else
				$username="";

			if (isset($_POST['port']) && $_POST['port']!="")
				$port=$_POST['port'];
			else
				$port=0;
			
			if (isset($_POST['auth']) && $_POST['auth']!="")
				$auth=$_POST['auth'];
			else
				$auth="none";
			
			if (isset($_POST['password']) && $_POST['password']!="")
				$password=stripslashes($_POST['password']);
			else
				$password="";


			if(isset($_POST['interval']) && $_POST['interval']!="")
				$interval=$_POST['interval'];
			else
				$interval="30sec";
			

			if($auth=="ssl")
			{
				$ssl=true;
			
			}
			else
			{
				$ssl=false;

			}

			if(isset($_POST['engine']) && $_POST['engine']!="")	
				$engine=$_POST['engine'];
			else
				$engine="php";


			if($engine=="php")
			{	
				//$test=new IMAP($protocol,$hostname,$username,$password,$port,$ssl,true,"/","/");
			}
			
			$args=array($protocol,$hostname,$username,$port,$auth,stripslashes($password),$ssl,$user_id,$current_username,$username);
			

			$real_cron_job=true;
			if($_POST['cron_type']=="real")
			$real_cron_job=true;
			else
			$real_cron_job=false;

			if(!$real_cron_job)
			{	
				if($engine=="php") {
					if (!wp_next_scheduled($this->hook)) {
						update_user_meta( $user_id, 'easy_email_max_uid', 0); 
						wp_schedule_event( time(), $interval, $this->hook , $args );
					}
				}
				else
				{
					if (!wp_next_scheduled($this->hook_eden)) {
						update_user_meta( $user_id, 'easy_email_max_uid', 0); 
						wp_schedule_event( time(), $interval, $this->hook_eden , $args );
					}
				}
				
				$this->get_cron_job();

				$args=serialize($args);
				$wpdb->insert($table, array(
					'user_id'=>$user_id,
					'args'=>$args,
					'type'=>$_POST['cron_type'],
					'status'=>1
				));
			}
			else
			{	if($interval="1sec")
				
					$real_interval=1;

	
				else if($interval="5sec")
				
					$real_interval=5;


				else if($interval="10sec")
				
					$real_interval=10;



				else if($interval="30sec")
				
					$real_interval=30;


				else if($interval="5min")
			
					$real_interval=5*60;



				else if($interval="10min")
				
					$real_interval=10*60;
					
				$args=serialize($args);

				$pid=-1;
				if($engine=="php")
					{	
						$url=plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.$password.'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval;
						//error_log(print_r($url,true));
						$curl = curl_init();                
						//$post['test'] = 'examples daata'; // our data todo in received
						curl_setopt($curl, CURLOPT_URL, $url);
						curl_setopt ($curl, CURLOPT_POST, TRUE);
						curl_setopt ($curl, CURLOPT_POSTFIELDS, $post); 
					
						curl_setopt($curl, CURLOPT_USERAGENT, 'api');
					
						curl_setopt($curl, CURLOPT_TIMEOUT, 1); 
						curl_setopt($curl, CURLOPT_HEADER, 0);
						curl_setopt($curl,  CURLOPT_RETURNTRANSFER, false);
						curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
						curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
						curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 10); 
					
						curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
					
						curl_exec($curl);   
					
						curl_close($curl);  
						//exec('curl "'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.$password.'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'" >  /dev/null 2>&1 & ');
						//exec('curl "'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.$password.'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'" > /dev/null 2>/dev/null &');
						//error_log(print_r('curl "'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.$password.'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'" >  /dev/null 2>&1',true));
						//	exec('{ crontab -l; echo "* * * * *  pgrep  curl \"'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.addslashes($password).'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'\" && echo \"Email Client Running\" || curl \"'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.addslashes($password).'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'\" "; } | crontab -');
						//error_log(print_r('curl "'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.$password.'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'"',true));
					}
					else
				{	
					//exec('curl "'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start_eden.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.($password).'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'" >  /dev/null 2>&1 & ');
					
					$url=plugin_dir_url(__FILE__).'../cron_jobs/cron_start_eden.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.($password).'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval;

					$curl = curl_init();                
					//$post['test'] = 'examples daata'; // our data todo in received
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt ($curl, CURLOPT_POST, TRUE);
					curl_setopt ($curl, CURLOPT_POSTFIELDS, $post); 
				
					curl_setopt($curl, CURLOPT_USERAGENT, 'api');
				
					curl_setopt($curl, CURLOPT_TIMEOUT, 1); 
					curl_setopt($curl, CURLOPT_HEADER, 0);
					curl_setopt($curl,  CURLOPT_RETURNTRANSFER, false);
					curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
					curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
					curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 10); 
				
					curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
				
					curl_exec($curl);   
				
					curl_close($curl); 
					
					
					
					//	exec('{ crontab -l; echo "* * * * *  pgrep  curl \"'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start_eden.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.addslashes($password).'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'\" && echo \"Email Client Running\" || curl \"'.plugin_dir_url(__FILE__).'../cron_jobs/cron_start.php?protocol='.$protocol.'&ssl='.$ssl.'&hostname='.$hostname.'&port='.$port.'&username='.$username.'&password='.addslashes($password).'&auth='.$auth.'&user_id='.$user_id.'&current_user_email='.$username.'&current_username='.$current_username.'&interval='.$real_interval.'\" "; } | crontab -');
				}
				$pid = (int)$op[0];
				if($pid=="" || $pid==null)
				$pid=-1;

				$wpdb->insert($table, array(
					'user_id'=>$user_id,
					'args'=>$args,
					'type'=>$_POST['cron_type'],
					'status'=>1,
					'pid'=>-1,
					'job_stats'=>'started'
				));

				//easy_email_verify_cron($id);
				//easy_email_script_verify($protocol,$ssl,$hostname,$port,$username,$password,$current_username,$real_interval,$user_id,$auth,$op);
				easy_email_job_status2($user_id);

			}



			



			wp_die();

		}

		else if($_POST['form_type']=='stop_cron')
		{		global $wpdb;
				$table=$wpdb->prefix."easy_cronjobs";
				$id=get_current_user_id();
				

				$query=$wpdb->prepare("DELETE from $table where user_id=%d",$id);
				$wpdb->query($query);

				if(!isset($_POST['cron_type_stop']) || $_POST['cron_type_stop']!="real")
				{
					$this->stop_cron_job();
					
					$this->get_cron_job();
				}
				
				else
				{	
					easy_email_stop_cron_job($id);
					easy_email_verify_cron($id);
				}

				wp_die();

		}

		else if($_POST['form_type']=="default_body")
		{	
			if(isset($_POST['default_body']))
			{	update_user_meta( get_current_user_id(), 'default_body_text', $_POST['default_body']);
				echo "Success";
				wp_die();
			}
			echo "An Error Occurred While Saving.";
			wp_die();
		}
		
		else if($_POST['form_type']=="restore_admin")
		{
			remove_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
			$id=get_current_user_id();
			$urlparts = parse_url(site_url());
			$url=$urlparts['host'];
			$domain_name=explode('www.',$url)[1];
			if($domain_name=="")
			$domain_name=$url;
			$email_from="wordpress@".$domain_name;

			add_action( 'phpmailer_init', function( $phpmailer ) {
				if ( !is_object( $phpmailer ) ) 
					$phpmailer = (object) $phpmailer;
				
					$phpmailer->IsMail();
			});


			update_user_meta($id, 'easy_email_email_from_admin', $email_from);
			update_user_meta($id, 'easy_email_email_from_name_admin', "admin");
			update_user_meta($id, 'easy_email_hostname_admin', "localhost");
			update_user_meta($id, 'easy_email_port_admin', "25");
			update_user_meta($id, 'easy_email_security_admin', "");
			update_user_meta($id, 'easy_email_username_admin', "");
			update_user_meta($id, 'easy_email_password_admin', "");

			echo "Success";
			wp_die();

		}
	}

	public function run_service_eden($protocol,$hostname,$username,$port,$auth,$password,$ssl,$user_id,$current_username,$current_user_email)
	{

		//error_log('asd');
		$tempDir = sys_get_temp_dir() . "/";
		$fp = fopen($tempDir.$current_username, "w+");
		$number_of_emails=1;

			if (flock($fp, LOCK_EX | LOCK_NB)) { 
				//error_log('inside');
				global $wpdb;
				$table=$wpdb->prefix."easy_inbox";
				$directory=plugin_dir_path(__FILE__)."../attachments/".$current_username."/";
				$directory_url=plugin_dir_url(__FILE__)."../attachments/".$current_username."/";
			//	error_log(print_r($directory,true));
				$test=new EDEN_IMAP($protocol,$hostname,$username,$password,$port,$ssl,false,$directory,$directory_url);
				//$test->connect();
				
				if($protocol=="imap")
				$max_uid=get_user_meta($user_id, 'easy_email_max_uid', true);
				else
				$max_uid=get_user_meta($user_id, 'easy_email_max_uid_pop', true);

				$prev_id=$max_uid;
				if(($max_uid=="" || $max_uid==null || $max_uid==0))
				{	
					
					if($protocol=="imap")
						{	$test->connect();
							$test->getmailboxes();
							$test->setInboxactive();
							$test->setmaxuid();
							//var_error_log(var_dump($test->imap->search(array("ALL"),0,1)));

							update_user_meta( $user_id, 'easy_email_max_uid',$test->getmaxuid());
							
						} 
					$test->imap->disconnect();
					//error_log('inside3');
				}

				else
				{	//error_log("this");
					if($protocol=="imap")
					{
						$test->connect();
						$test->getmailboxes();
						$test->setInboxactive();
					for ($i=0;$i<$number_of_emails;$i++)
				{
					
				//	error_log('inside4');
							$start=$max_uid+1+$i;
							//error_log($max_uid);
							$test->setmaxuid();
							$uid=$test->getmaxuid();

							if($uid>$prev_id && ($uid!=null || $uid!=""))
								{
								
								$prev_id=$uid;	
								$arr=$test->getNewEmails($start,1,true);  
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
								
								update_user_meta( $user_id, 'easy_email_max_uid', $uid); 
								
							}
							$test->imap->disconnect();
					}

				}
					else
					{
													
						$id=$test->imap->getEmailTotal();
							
						//error_log(print_r($id,true));
						if($id<=$prev_id)
							{flock($fp, LOCK_UN); 
								fclose($fp);
							return;}
						if($id=="" || $id==null)
							{flock($fp, LOCK_UN); 
								fclose($fp);	
							return;}
						
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
	
						
					$max_uid=update_user_meta($user_id, 'easy_email_max_uid_pop', $prev_id);
					$test->imap->disconnect();
				}
			
			
			}
							flock($fp, LOCK_UN); 
					} else {
						return;
					}

					fclose($fp);

					
				
				}
	
	public function run_service($protocol,$hostname,$username,$port,$auth,$password,$ssl,$user_id,$current_username,$current_user_email)
	{

	//	error_log("this");
		$tempDir = sys_get_temp_dir() . "/";
		$fp = fopen($tempDir.$current_username, "w+");
		$number_of_emails=1;

			if (flock($fp, LOCK_EX | LOCK_NB)) { 

				global $wpdb;
				$table=$wpdb->prefix."easy_inbox";
				$directory=plugin_dir_path(__FILE__)."../attachments/".$current_username."/";
				$directory_url=plugin_dir_url(__FILE__)."../attachments/".$current_username."/";
			//	error_log(print_r($directory,true));
				$test=new IMAP($protocol,$hostname,$username,$password,$port,$ssl,true,$directory,$directory_url);
				//$test->connect();

				if($protocol=="imap")
				$max_uid=get_user_meta($user_id, 'easy_email_max_uid', true);
				else
				$max_uid=get_user_meta($user_id, 'easy_email_max_uid_pop', true);

				if(($max_uid=="" || $max_uid==null || $max_uid==0))
				{	
					
					if($protocol=="pop")
					{		$test->set_max_id();
							update_user_meta( $user_id, 'easy_email_max_uid_pop', $test->max_uid);   
					}
					else
					{	$uid=$test->mailbox->searchMailbox('ALL');
						$real_uid=count($uid);
						update_user_meta( $user_id, 'easy_email_max_uid', $real_uid); 
					}
				}

				else
				{	//error_log("this");
 

					for ($i=0;$i<$number_of_emails;$i++)
				{
					
					if($protocol=="imap")
					{		$start=$max_uid+1+$i;
							$arr=$test->get_email($start);  
					}
					else
					{	
						$uid=$test->mailbox->searchMailbox('ALL');

						$real_uid=count($uid);

						$prev_uid=(int)get_user_meta($user_id, 'easy_email_max_uid_pop', true);

						if(!($real_uid-$prev_id))
							{	
								update_user_meta( $user_id, 'easy_email_max_uid_pop',$real_uid); 
								$arr=$test->get_email($real_uid);

							}
						else
						{
							update_user_meta( $user_id, 'easy_email_max_uid_pop',$real_uid); 
							flock($fp, LOCK_UN); 
							
							exit();
						}
						
					}
				//	error_log(print_r($arr,true));
					$uid=$arr[0]['uid'];
					$fromemail=$arr[0]['fromemail'];
					if($uid!=null &&  $fromemail!=null)
						{$content=$arr[0]['htmltext'];
						
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
			
			
			}
							flock($fp, LOCK_UN); 
					} else {
						return;
					}

					fclose($fp);

					
				
				}

	

	public function display()
	{

		$cur_user_id=get_current_user_id();
?>
	<style>

.tabs {
  left: 50%;
  -webkit-transform: translateX(-50%);
          transform: translateX(-50%);
  position: relative;
  background: white;
  padding: 50px;
  padding-bottom: 80px;
  width: 70%;
  height: 250px;
  box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25), 0 10px 10px rgba(0, 0, 0, 0.22);
  border-radius: 5px;
  min-width: 240px;
}
.tabs input[name="tab-control"] {
  display: none;
}
.tabs .content section h2,
.tabs ul li label {
  font-family: "Montserrat";
  font-weight: bold;
  font-size: 18px;
  color: #428BFF;
}
.tabs ul {
  list-style-type: none;
  padding-left: 0;
  display: flex;
  flex-direction: row;
  margin-bottom: 10px;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
}
.tabs ul li {
  box-sizing: border-box;
  flex: 1;
  width: 25%;
  padding: 0 10px;
  text-align: center;
}
.tabs ul li label {
  transition: all 0.3s ease-in-out;
  color: #929daf;
  padding: 5px auto;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  white-space: nowrap;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}
.tabs ul li label br {
  display: none;
}
.tabs ul li label svg {
  fill: #929daf;
  height: 1.2em;
  vertical-align: bottom;
  margin-right: 0.2em;
  transition: all 0.2s ease-in-out;
}
.tabs ul li label:hover, .tabs ul li label:focus, .tabs ul li label:active {
  outline: 0;
  color: #bec5cf;
}
.tabs ul li label:hover svg, .tabs ul li label:focus svg, .tabs ul li label:active svg {
  fill: #bec5cf;
}
.tabs .slider {
  position: relative;
  width: 25%;
  transition: all 0.33s cubic-bezier(0.38, 0.8, 0.32, 1.07);
}
.tabs .slider .indicator {
  position: relative;
  width: 50px;
  max-width: 100%;
  margin: 0 auto;
  height: 4px;
  background: #428BFF;
  border-radius: 1px;
}
.tabs .content {
  margin-top: 30px;
}
.tabs .content section {
  display: none;
  -webkit-animation-name: content;
          animation-name: content;
  -webkit-animation-direction: normal;
          animation-direction: normal;
  -webkit-animation-duration: 0.3s;
          animation-duration: 0.3s;
  -webkit-animation-timing-function: ease-in-out;
          animation-timing-function: ease-in-out;
  -webkit-animation-iteration-count: 1;
          animation-iteration-count: 1;
  line-height: 1.4;
}
.tabs .content section h2 {
  color: #428BFF;
  display: none;
}
.tabs .content section h2::after {
  content: "";
  position: relative;
  display: block;
  width: 30px;
  height: 3px;
  background: #428BFF;
  margin-top: 5px;
  left: 1px;
}
.tabs input[name="tab-control"]:nth-of-type(1):checked ~ ul > li:nth-child(1) > label {
  cursor: default;
  color: #428BFF;
}
.tabs input[name="tab-control"]:nth-of-type(1):checked ~ ul > li:nth-child(1) > label svg {
  fill: #428BFF;
}
@media (max-width: 600px) {
  .tabs input[name="tab-control"]:nth-of-type(1):checked ~ ul > li:nth-child(1) > label {
    background: rgba(0, 0, 0, 0.08);
  }
}
.tabs input[name="tab-control"]:nth-of-type(1):checked ~ .slider {
  -webkit-transform: translateX(0%);
          transform: translateX(0%);
}
.tabs input[name="tab-control"]:nth-of-type(1):checked ~ .content > section:nth-child(1) {
  display: block;
}
.tabs input[name="tab-control"]:nth-of-type(2):checked ~ ul > li:nth-child(2) > label {
  cursor: default;
  color: #428BFF;
}
.tabs input[name="tab-control"]:nth-of-type(2):checked ~ ul > li:nth-child(2) > label svg {
  fill: #428BFF;
}
@media (max-width: 600px) {
  .tabs input[name="tab-control"]:nth-of-type(2):checked ~ ul > li:nth-child(2) > label {
    background: rgba(0, 0, 0, 0.08);
  }
}
.tabs input[name="tab-control"]:nth-of-type(2):checked ~ .slider {
  -webkit-transform: translateX(100%);
          transform: translateX(100%);
}
.tabs input[name="tab-control"]:nth-of-type(2):checked ~ .content > section:nth-child(2) {
  display: block;
}
.tabs input[name="tab-control"]:nth-of-type(3):checked ~ ul > li:nth-child(3) > label {
  cursor: default;
  color: #428BFF;
}
.tabs input[name="tab-control"]:nth-of-type(3):checked ~ ul > li:nth-child(3) > label svg {
  fill: #428BFF;
}
@media (max-width: 600px) {
  .tabs input[name="tab-control"]:nth-of-type(3):checked ~ ul > li:nth-child(3) > label {
    background: rgba(0, 0, 0, 0.08);
  }
}
.tabs input[name="tab-control"]:nth-of-type(3):checked ~ .slider {
  -webkit-transform: translateX(200%);
          transform: translateX(200%);
}
.tabs input[name="tab-control"]:nth-of-type(3):checked ~ .content > section:nth-child(3) {
  display: block;
}
.tabs input[name="tab-control"]:nth-of-type(4):checked ~ ul > li:nth-child(4) > label {
  cursor: default;
  color: #428BFF;
}
.tabs input[name="tab-control"]:nth-of-type(4):checked ~ ul > li:nth-child(4) > label svg {
  fill: #428BFF;
}
@media (max-width: 600px) {
  .tabs input[name="tab-control"]:nth-of-type(4):checked ~ ul > li:nth-child(4) > label {
    background: rgba(0, 0, 0, 0.08);
  }
}
.tabs input[name="tab-control"]:nth-of-type(4):checked ~ .slider {
  -webkit-transform: translateX(300%);
          transform: translateX(300%);
}
.tabs input[name="tab-control"]:nth-of-type(4):checked ~ .content > section:nth-child(4) {
  display: block;
}
@-webkit-keyframes content {
  from {
    opacity: 0;
    -webkit-transform: translateY(5%);
            transform: translateY(5%);
  }
  to {
    opacity: 1;
    -webkit-transform: translateY(0%);
            transform: translateY(0%);
  }
}
@keyframes content {
  from {
    opacity: 0;
    -webkit-transform: translateY(5%);
            transform: translateY(5%);
  }
  to {
    opacity: 1;
    -webkit-transform: translateY(0%);
            transform: translateY(0%);
  }
}
@media (max-width: 1000px) {
  .tabs ul li label {
    white-space: initial;
  }
  .tabs ul li label br {
    display: initial;
  }
  .tabs ul li label svg {
    height: 1.5em;
  }
}
@media (max-width: 600px) {
  .tabs ul li label {
    padding: 5px;
    border-radius: 5px;
  }
  .tabs ul li label span {
    display: none;
  }
  .tabs .slider {
    display: none;
  }
  .tabs .content {
    margin-top: 20px;
  }
  .tabs .content section h2 {
    display: block;
  }
  #screen-options-link-wrap
  {
	  display:none;
  }
}
		</style>

<div class="tabs" style="margin-top:50px; width:90%; height:auto;">



<h1 style="text-align:center;" >WP Easy Email Settings</h1>
<br/>
  <input type="radio" id="tab1" name="tab-control" checked>
  <input type="radio" id="tab2" name="tab-control">
  <input type="radio" id="tab3" name="tab-control">  
  <input type="radio" id="tab4" name="tab-control">
  <ul>
    <li title="Features"><label for="tab1" role="button"><br><span>Delivery</span></label></li>
    <li title="Delivery Contents"><label for="tab2" role="button"><br><span>IMAP/POP3</span></label></li>
    <li title="Shipping"><label for="tab3" role="button"><br><span>Default Body Text</span></label></li>    <li title="Returns"><label for="tab4" role="button"><svg viewBox="0 0 24 24">
    <path d="M11,9H13V7H11M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,17H13V11H11V17Z" />
</svg><br><span>Other Settings</span></label></li>
  </ul>
  
  <div class="slider"><div class="indicator"></div></div>
  <div class="content">
    <section>
	<div class="panel-editor  panel-default-editor ">
    <div class="panel-heading-editor "><h3>Delivery Settings</h3></div>
		<div class="panel-body-editor ">

	<form id="delivery_form">
	
	<label><strong>Email From:</strong></label><br/><input style="width:100%;" type="email" id="email" name="email" value="<?php echo get_user_meta( get_current_user_id(), 'easy_email_email_from', true); ?>" placeholder="Leave Blank to Use Wordpress Default Value (<?php echo $this->default_email;?>) "></input>
	<br/>
	<br/>
	<label><strong>Email From (Name):</strong></label><br/><input style="width:100%;" type="text" id="name" name="name" value="<?php echo get_user_meta( get_current_user_id(), 'easy_email_email_from_name', true); ?>" placeholder="Leave Blank to Use Wordpress Default Value (<?php echo $this->default_name;?>) "></input>
	<br/>
	<br/>
	<div class="smtp">
	<div class="panel-editor  panel-default-editor"  style="border-radius:100px; " >
    <div class="panel-heading-editor " style="background-color:#129294; "><h3 style="color:white;">SMTP Configuration</h3></div>
		<div class="panel-body-editor ">
	<p><strong>Note:</strong> Some web hosting providers such as GoDaddy do not allow external SMTP server configuration. In such cases, it is advised to use default SMTP settings.</p>
	<label><strong>SMTP Hostname:</strong></label><br/><input style="width:100%;" type="text" id="hostname" name="hostname" value="<?php echo get_user_meta( get_current_user_id(), 'easy_email_hostname', true); ?>" placeholder="Leave Blank to use Wordpress Server's SMTP Hostname (localhost)"></input>
	<br/>
	<br/>
	<label><strong>SMTP Port:</strong></label><br/><input style="width:100%;" type="number" id="port" name="port" value="<?php echo get_user_meta( get_current_user_id(), 'easy_email_port', true); ?>" placeholder="Leave Blank to use Wordpress Server's SMTP Port (25)"></input>
	<br/>
	<br/>
	<label><strong>Security</strong></label><br/>
	<?php $security=get_user_meta($cur_user_id, 'easy_email_security', true); 

	if ($security=="" || $security==null || $security=="none")
	echo '<input type="radio" name="auth" value="none" checked> None<br><input type="radio" name="auth" value="ssl"> SSL<br><input type="radio" name="auth" value="tls"> TLS<br>';
	else if($security=="ssl")
	echo '<input type="radio" name="auth" value="none" > None<br><input type="radio" name="auth" value="ssl" checked> SSL<br><input type="radio" name="auth" value="tls"> TLS<br>';
	else if($security=="tls")
	echo '<input type="radio" name="auth" value="none" > None<br><input type="radio" name="auth" value="ssl" > SSL<br><input type="radio" name="auth" value="tls" checked> TLS<br>';
	else
	echo '<input type="radio" name="auth" value="none" checked> None<br><input type="radio" name="auth" value="ssl"> SSL<br><input type="radio" name="auth" value="tls"> TLS<br>';

	?>



	<br/>
	<br/>
	<!--<label><strong>Username:</strong></label><br/><input style="width:100%;" type="text" id="username" name="username" placeholder="Leave Blank To Use Wordpress"></input> -->
	<br/>
	<br/>
	<label><strong>Username:</strong></label><br/><input style="width:100%;" type="text" id="usernamer" value="<?php echo get_user_meta($cur_user_id, 'easy_email_username', true); ?>" name="usernamer" placeholder="Username For Authentication (Leave Blank if Authentication Does Not Apply)"></input>

	<br/>
	<br/>
	<label><strong>Password:</strong></label><br/><input style="width:100%;" type="password" id="password" value="<?php echo str_replace('"','&quot;',get_user_meta( get_current_user_id(), 'easy_email_password', true)); ?>" name="password" placeholder="Leave Blank If Authentication Does not Apply"></input>
	</div>
	</div>
</div>
	<br/>
	<button class="form_button" type="button" id="test_email">Send a Test Email</button><br/>
	<br/>
	<br/>
	<button class="form_button" id="submit" type="button">Save</button>
	<button class="form_button" id="default" type="button">Restore Default</button>
<br/>
<br/>

<p id="notification" style="display:none; font-style:italic"></p>
	</form>
</div>
</div>


		</section>




   <section>
	<div class="panel-editor  panel-default-editor ">
    <div class="panel-heading-editor "><h3>IMAP/POP3 Settings</h3></div>
		<div class="panel-body-editor ">

	<form id="delivery_form2">
	<?php $protocol=get_user_meta($cur_user_id, 'easy_email_recv_protocol', true); 
	if($protocol=="imap")
	echo '<label><strong>Protocol Type &nbsp;</strong></label><select id="protocol" name="protocol"><option value="imap" selected="selected">IMAP</option><option value="pop">POP</option></select>';
	else if($protocol=="pop")
	echo '<label><strong>Protocol Type &nbsp;</strong></label><select id="protocol" name="protocol"><option value="imap">IMAP</option><option value="pop" selected="selected">POP</option></select>';
	else
	echo '<label><strong>Protocol Type &nbsp;</strong></label><select id="protocol" name="protocol"><option value="imap" >IMAP</option><option value="pop">POP</option></select>';

	?>
		<br/>
	<br/>
	<label><strong>Incoming mail:</strong></label><br/><input style="width:100%" value="<?php echo get_user_meta($cur_user_id, 'easy_email_recv_hostname', true) ?>" type="text" name="hostname" id="hostname2"/>
	<br/>
	<br/>
	<label><strong>Port:</strong></label><br/><input style="width:100%" value="<?php echo get_user_meta($cur_user_id, 'easy_email_recv_port', true) ?>"  type="number" name="port" id="port2"/>
	<br/>
	<br/>
	<label><strong>Username:</strong></label><br/><input style="width:100%" value="<?php echo get_user_meta($cur_user_id, 'easy_email_recv_username', true) ?>" type="text" name="username" id="username2"/>
	<br/>
	<br/>
	<label><strong>Password:</strong></label><br/><input style="width:100%" type="password" value="<?php echo str_replace('"','&quot;',get_user_meta($cur_user_id ,'easy_email_recv_password', true)) ?>" name="password" id="password2"/>
	<br/>
	<br/>
	<label><strong>Security</strong></label><br/>
	<?php $security=get_user_meta($cur_user_id, 'easy_email_recv_security', true); 
	if ($security=="" || $security==null || $security=="none")
	echo '<input type="radio" name="auth" value="none" checked> None<br><input type="radio" name="auth" value="ssl"> SSL<br>';
	else if($security=="ssl")
	echo '<input type="radio" name="auth" value="none" > None<br><input type="radio" name="auth" value="ssl" checked> SSL<br>';
	else
	echo '<input type="radio" name="auth" value="none" checked> None<br><input type="radio" name="auth" value="ssl"> SSL<br>';

	?>




	<br/>
	<br/>
	<label><strong>Time Interval for Cron Job</strong></label><br/>
	<?php $interval=get_user_meta($cur_user_id, 'easy_email_recv_interval', true); 
	if ($interval=="" || $interval==null || $interval=="30sec")
	echo '<input type="radio" name="interval" value="1sec"> 1 Second<br><input type="radio" name="interval" value="5sec"> 5 Seconds<br><input type="radio" name="interval" value="10sec"> 10 Seconds<br><input type="radio" name="interval" value="30sec" checked> 30 Seconds<br><input type="radio" name="interval" value="1min"> 1 Minute<br><input type="radio" name="interval" value="5min"> 5 Minutes<br><input type="radio" name="interval" value="10min"> 10 Minutes<br>';
	else if($interval=="1sec")
	echo '<input type="radio" name="interval" value="1sec" checked> 1 Second<br><input type="radio" name="interval" value="5sec"> 5 Seconds<br><input type="radio" name="interval" value="10sec"> 10 Seconds<br><input type="radio" name="interval" value="30sec"> 30 Seconds<br><input type="radio" name="interval" value="1min"> 1 Minute<br><input type="radio" name="interval" value="5min"> 5 Minutes<br><input type="radio" name="interval" value="10min"> 10 Minutes<br>';
	else if($interval=="5sec")
	echo '<input type="radio" name="interval" value="1sec"> 1 Second<br><input type="radio" name="interval" value="5sec" checked> 5 Seconds<br><input type="radio" name="interval" value="10sec"> 10 Seconds<br><input type="radio" name="interval" value="30sec" > 30 Seconds<br><input type="radio" name="interval" value="1min"> 1 Minute<br><input type="radio" name="interval" value="5min"> 5 Minutes<br><input type="radio" name="interval" value="10min"> 10 Minutes<br>';
	else if($interval=="10sec")
	echo '<input type="radio" name="interval" value="1sec"> 1 Second<br><input type="radio" name="interval" value="5sec"> 5 Seconds<br><input type="radio" name="interval" value="10sec" checked> 10 Seconds<br><input type="radio" name="interval" value="30sec" > 30 Seconds<br><input type="radio" name="interval" value="1min"> 1 Minute<br><input type="radio" name="interval" value="5min"> 5 Minutes<br><input type="radio" name="interval" value="10min"> 10 Minutes<br>';

	else if($interval=="1min")
	echo '<input type="radio" name="interval" value="1sec" checked> 1 Second<br><input type="radio" name="interval" value="5sec"> 5 Seconds<br><input type="radio" name="interval" value="10sec"> 10 Seconds<br><input type="radio" name="interval" value="30sec" > 30 Seconds<br><input type="radio" name="interval" value="1min" checked> 1 Minute<br><input type="radio" name="interval" value="5min"> 5 Minutes<br><input type="radio" name="interval" value="10min"> 10 Minutes<br>';
	else if($interval=="5min")
	echo '<input type="radio" name="interval" value="1sec" checked> 1 Second<br><input type="radio" name="interval" value="5sec"> 5 Seconds<br><input type="radio" name="interval" value="10sec"> 10 Seconds<br><input type="radio" name="interval" value="30sec" > 30 Seconds<br><input type="radio" name="interval" value="1min"> 1 Minute<br><input type="radio" name="interval" value="5min" checked> 5 Minutes<br><input type="radio" name="interval" value="10min"> 10 Minutes<br>';
	else if($interval=="10min")
	echo '<input type="radio" name="interval" value="1sec" checked> 1 Second<br><input type="radio" name="interval" value="5sec"> 5 Seconds<br><input type="radio" name="interval" value="10sec"> 10 Seconds<br><input type="radio" name="interval" value="30sec" > 30 Seconds<br><input type="radio" name="interval" value="1min"> 1 Minute<br><input type="radio" name="interval" value="5min"> 5 Minutes<br><input type="radio" name="interval" value="10min" checked> 10 Minutes<br>';

	?>
	<div style="display:none">
	<br/>
	<br/>
	<label><strong>Cron Job Type</strong><p>(Wordpress cronjobs are not real but simulated cron jobs and run only when website page is active. It is recommended to use real cron job, which runs directly on server.)</p></label><br/>
	<?php $cron_type=get_user_meta($cur_user_id, 'easy_email_recv_cron_type', true); 
	if ($cron_type=="" || $cron_type==null || $cron_type=="real")
	echo '<input type="radio" name="cron_type" value="real"checked>Real Cronjob<br><input type="radio" name="cron_type" value="wp">Wordpress Cronjob<br>';
	else if($cron_type=="wp")
	echo '<input type="radio" name="cron_type" value="real" >Real Cronjob<br><input type="radio" name="cron_type" value="wp" checked>Wordpress Cronjob<br>';
	else
	echo '<input type="radio" name="cron_type" value="real" checked> Real Cronjob<br><input type="radio" name="cron_type" value="wp">Wordpress Cronjob<br>';
	
	?>
	</div>
	<br/>
	<br/>


	
	<label><strong>IMAP/POP Engine</strong><p>(The two engines below perform same functions differently. It is recommended to use Php Imap/Pop module. In case, your web hosting has disabled php-imap extension, use the Eden engine.)</p></label><br/>
	<?php $engine=get_user_meta($cur_user_id, 'easy_email_recv_engine', true); 
	if ($engine=="" || $engine==null || $engine=="php")
	echo '<input type="radio" name="engine" value="php" checked>PHP IMAP/POP<br><input type="radio" name="engine" value="eden">Eden<br>';
	else if($engine=="eden")
	echo '<input type="radio" name="engine" value="php" >PHP IMAP/POP<br><input type="radio" name="engine" value="eden" checked>Eden<br>';
	else
	echo '<input type="radio" name="engine" value="php" checked>PHP IMAP/POP<br><input type="radio" name="engine" value="eden">Eden<br>';

	?>
 	<br/>
	<br/>

	<button class="form_button2" type="button" id="test_connection">Test Connection</button><br/>
	<br/>
	<br/>
	<button class="form_button2" id="submit2" type="button">Save</button>
	<button class="form_button2" id="default2" type="button">Clear All</button>
<br/>
<br/>
<?php
global $wpdb;
$table=$wpdb->prefix."easy_cronjobs";
$val=$wpdb->get_results("SELECT * from $table  where user_id=".$cur_user_id);
if($val[0]->type=="real")
{	
	easy_email_job_status2($cur_user_id);

	//easy_email_verify_cron($cur_user_id);
}
else
$this->get_cron_job();
?>

<br/>
<br/>
<p id="notification2" style="display:none; font-style:italic"></p>
	</form>
</div>
</div>


		</section>








    <section>
	<div class="panel-editor  panel-default-editor ">
    <div class="panel-heading-editor "><h3>Default Body Text </h3></div>
		<div class="panel-body-editor ">

	<form id="delivery_form3">
		<label><strong>Default Body Text</strong></label><br/><br/>
		<textarea style="width:100%;" id="default_body" name="default_body"><?php echo get_usermeta($cur_user_id, 'default_body_text',true); ?></textarea>
		<button class="form_button2" type="button" id="submit3">Save</button>

	</form>
	<p id="notification3" style="display:none; font-style:italic"></p>
		</div>

		</div>

	</section>
	
	
	    <section>
			<?php if($cur_user_id==1) { ?>
	<div class="panel-editor  panel-default-editor ">
    <div class="panel-heading-editor "><h3>System Email Delivery Settings </h3></div>
		<div class="panel-body-editor ">

	<form id="delivery_form4">
	
	<label><strong>Email From:</strong></label><br/><input style="width:100%;" type="email" id="email4" name="email" value="<?php echo get_user_meta($cur_user_id,'easy_email_email_from_admin', true); ?>" placeholder="Leave Blank to Use Wordpress Default Value (<?php echo $this->default_email;?>) "></input>
	<br/>
	<br/>
	<label><strong>Email From (Name):</strong></label><br/><input style="width:100%;" type="text" id="name4" name="name" value="<?php echo get_user_meta( $cur_user_id, 'easy_email_email_from_name_admin', true); ?>" placeholder="Leave Blank to Use Wordpress Default Value (<?php echo $this->default_name;?>) "></input>
	<br/>
	<br/>
	<div class="smtp">
	<div class="panel-editor  panel-default-editor"  style="border-radius:100px; " >
    <div class="panel-heading-editor " style="background-color:#129294; "><h3 style="color:white;">SMTP Configuration</h3></div>
		<div class="panel-body-editor ">
	<p><strong>Note:</strong> Some web hosting providers such as GoDaddy do not allow external SMTP server configuration. In such cases, it is advised to use default SMTP settings.</p>
	<label><strong>SMTP Hostname:</strong></label><br/><input style="width:100%;" type="text" id="hostname4" name="hostname" value="<?php echo get_user_meta($cur_user_id, 'easy_email_hostname_admin', true); ?>" placeholder="Leave Blank to use Wordpress Server's SMTP Hostname (localhost)"></input>
	<br/>
	<br/>
	<label><strong>SMTP Port:</strong></label><br/><input style="width:100%;" type="number" id="port4" name="port" value="<?php echo get_user_meta($cur_user_id, 'easy_email_port_admin', true); ?>" placeholder="Leave Blank to use Wordpress Server's SMTP Port (25)"></input>
	<br/>
	<br/>
	<label><strong>Security</strong></label><br/>
	<?php $security=get_user_meta($cur_user_id, 'easy_email_security_admin', true); 

	if ($security=="" || $security==null || $security=="none")
	echo '<input type="radio" name="auth" value="none" checked> None<br><input type="radio" name="auth" value="ssl"> SSL<br><input type="radio" name="auth" value="tls"> TLS<br>';
	else if($security=="ssl")
	echo '<input type="radio" name="auth" value="none" > None<br><input type="radio" name="auth" value="ssl" checked> SSL<br><input type="radio" name="auth" value="tls"> TLS<br>';
	else if($security=="tls")
	echo '<input type="radio" name="auth" value="none" > None<br><input type="radio" name="auth" value="ssl" > SSL<br><input type="radio" name="auth" value="tls" checked> TLS<br>';
	else
	echo '<input type="radio" name="auth" value="none" checked> None<br><input type="radio" name="auth" value="ssl"> SSL<br><input type="radio" name="auth" value="tls"> TLS<br>';

	?>



	<br/>
	<br/>
	<!--<label><strong>Username:</strong></label><br/><input style="width:100%;" type="text" id="username" name="username" placeholder="Leave Blank To Use Wordpress"></input> -->
	<br/>
	<br/>
	<label><strong>Username:</strong></label><br/><input style="width:100%;" type="text" id="usernamer2" value="<?php echo get_user_meta($cur_user_id, 'easy_email_username_admin', true); ?>" name="usernamer" placeholder="Username For Authentication (Leave Blank if Authentication Does Not Apply)"></input>

	<br/>
	<br/>
	<label><strong>Password:</strong></label><br/><input style="width:100%;" type="password" id="password4" value="<?php echo str_replace('"','&quot;',get_user_meta($cur_user_id, 'easy_email_password_admin', true)); ?>" name="password" placeholder="Leave Blank If Authentication Does Not Apply"></input>
	</div>
	</div>
</div>
	<br/>
	<button class="form_button4" type="button" id="test_email4">Send a Test Email</button><br/>
	<br/>
	<br/>
	<button class="form_button4" id="submit4" type="button">Save</button>
	<button class="form_button4" id="default4" type="button">Restore Default</button>
<br/>
<br/>

<p id="notification4" style="display:none; font-style:italic"></p>
	</form>
</div>
</div>
			<?php } 
			else
			echo "<strong>These settings can be only configured by admin.</strong>";
			?>


		</section>
  </div>
</div>

<script>
	 var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
				

		 jQuery("#default2").click(function()
				{
					jQuery("#hostname2").val('');
					jQuery("#port2").val('');
					jQuery("#username2").val('');
					jQuery("#password2").val('');
				
				}
			);
			

		jQuery("#submit3").click(function(){
		jQuery(this).html("Saving...");
		
		jQuery(".form_button3").attr("disabled","disabled");
		var data=jQuery("#delivery_form3").serialize();
		
		data=data+"&form_type=default_body&action=easy_email_settings_call_back";

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#submit3").html("Save");
			jQuery(".form_button3").removeAttr("disabled");
			var text=response.split("\n");
			var line=text[text.length-1];
			

			jQuery("#notification3").html(response.split("\n")[0]);
			jQuery("#notification3").fadeIn().delay(3000).fadeOut();

		});
		
	});

		jQuery("#submit2").click(function(){
		jQuery(this).html("Saving...");
		
		jQuery(".form_button2").attr("disabled","disabled");
		var data=jQuery("#delivery_form2").serialize();
		
		data=data+"&form_type=recv&action=easy_email_settings_call_back";

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#submit2").html("Save");
			jQuery(".form_button2").removeAttr("disabled");
			var text=response.split("\n");
			var line=text[text.length-1];
			

			jQuery("#notification2").html(response.split("\n")[0]);
			jQuery("#notification2").fadeIn().delay(3000).fadeOut();

		});
		
	});

	jQuery("#submit4").click(function(){
		jQuery(this).html("Saving...");
		
		jQuery(".form_button4").attr("disabled","disabled");
		var data=jQuery("#delivery_form4").serialize();
		
		data=data+"&form_type=delivery&action=easy_email_settings_call_back&role=admin";

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#submit4").html("Save");
			jQuery(".form_button4").removeAttr("disabled");
			var text=response.split("\n");
			var line=text[text.length-1];
			

			jQuery("#notification4").html(response.split("\n")[0]);
			jQuery("#notification4").fadeIn().delay(3000).fadeOut();

		});
		
	});


	jQuery("#default4").click(function(){
		jQuery(this).html("Restoring Default...");
		jQuery(".form_button4").attr("disabled","disabled");

		var data="form_type=restore_admin&action=easy_email_settings_call_back&role=admin";

		jQuery.post(ajaxurl, data, function(response) {
				jQuery("#default4").html("Restore Default");
				jQuery(".form_button4").removeAttr("disabled");
				location.reload();
				jQuery("#notification4").html(response.split("\n")[0]);
				jQuery("#notification4").fadeIn().delay(3000).fadeOut();
				
			});
		
	});

	jQuery("#test_email4").click(function(){
		jQuery(this).html("Sending Email.....");
		jQuery(".form_button4").attr("disabled","disabled");
		var data=jQuery("#delivery_form4").serialize();
		data=data+"&form_type=test&action=easy_email_settings_call_back";

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#test_email4").html("Send a Test Email");
			jQuery(".form_button4").removeAttr("disabled");

			var text=response.split("\n");
			var line=text[text.length-1];
			console.log(line);

			jQuery("#notification4").html(line);
	

			jQuery("#notification4").fadeIn().delay(3000).fadeOut();

		});

	});


	jQuery("#submit").click(function(){
		jQuery(this).html("Saving...");
		
		jQuery(".form_button").attr("disabled","disabled");
		var data=jQuery("#delivery_form").serialize();
		
		data=data+"&form_type=delivery&action=easy_email_settings_call_back";

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#submit").html("Save");
			jQuery(".form_button").removeAttr("disabled");
			var text=response.split("\n");
			var line=text[text.length-1];
			

			jQuery("#notification").html(response.split("\n")[0]);
			jQuery("#notification").fadeIn().delay(3000).fadeOut();

		});
		
	});


	jQuery("#default").click(function(){
		jQuery(this).html("Restoring Default...");
		jQuery(".form_button").attr("disabled","disabled");

		var data="form_type=restore&action=easy_email_settings_call_back";

		jQuery.post(ajaxurl, data, function(response) {
				jQuery("#default").html("Restore Default");
				jQuery(".form_button").removeAttr("disabled");
				location.reload();
				jQuery("#notification").html(response.split("\n")[0]);
				jQuery("#notification").fadeIn().delay(3000).fadeOut();
				
			});
		
	});

	jQuery("#test_email").click(function(){
		jQuery(this).html("Sending Email.....");
		jQuery(".form_button").attr("disabled","disabled");
		var data=jQuery("#delivery_form").serialize();
		data=data+"&form_type=test&action=easy_email_settings_call_back";

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#test_email").html("Send a Test Email");
			jQuery(".form_button").removeAttr("disabled");

			var text=response.split("\n");
			var line=text[text.length-1];
			console.log(line);

			jQuery("#notification").html(line);
	

			jQuery("#notification").fadeIn().delay(3000).fadeOut();

		});

	});


	jQuery("#test_connection").click(function(){
		jQuery(this).html("Testing.....");
		jQuery(".form_button2").attr("disabled","disabled");
		var data=jQuery("#delivery_form2").serialize();
		data=data+"&form_type=con_test&action=easy_email_settings_call_back";

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#test_connection").html("Test Connection");
			jQuery(".form_button2").removeAttr("disabled");

			var text=response.split("\n");
			var line=text[0];
			console.log(line);

			jQuery("#notification2").html(line);
	

			jQuery("#notification2").fadeIn().delay(3000).fadeOut();

		});

	});


		jQuery("body").on("click","#cron",function(){
			jQuery(this).html("Starting.....");
			jQuery(".form_button2").attr("disabled","disabled");
			var data=jQuery("#delivery_form2").serialize();
			data=data+"&form_type=start_cron&action=easy_email_settings_call_back";

			jQuery.post(ajaxurl, data, function(response) {
				if(response=="refresh")
					location.reload();
				else
				{

					jQuery("#cron").html("Start Cron Job");
					jQuery(".form_button2").removeAttr("disabled");
					jQuery("#cron").replaceWith(response);
				}

			});

	});
	
	
	jQuery("body").on("click","#cron_stop",function(){

		jQuery(this).html("Stopping.....");
		jQuery(".form_button2").attr("disabled","disabled");
	
		data="form_type=stop_cron&action=easy_email_settings_call_back&cron_type_stop="+jQuery("#cron_type_stop").val()+"&cron_key="+jQuery("#cron_key").val();

		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#cron_stop").html("Start Cron Job");
			jQuery(".form_button2").removeAttr("disabled");
			jQuery("#cron_stop").replaceWith(response);


		});

	});
	
	</script>


<?php



	}





public function my_cron_schedules($schedules){

		if(!isset($schedules["1sec"]))
		{
			$schedules["1sec"] = array(
				'interval' => 1	,
				'display' => __('Once every 1 second'));

		}

		if(!isset($schedules["5sec"]))
		{
			$schedules["5sec"] = array(
				'interval' => 5	,
				'display' => __('Once every 5 seconds'));

		}

		if(!isset($schedules["10sec"]))
		{
			$schedules["10sec"] = array(
				'interval' => 10,
				'display' => __('Once every 10 seconds'));

		}

		if(!isset($schedules["30sec"]))
		{
			$schedules["30sec"] = array(
				'interval' => 30,
				'display' => __('Once every 30 seconds'));

		}
		if(!isset($schedules["1min"])){
			$schedules["1min"] = array(
				'interval' => 60,
				'display' => __('Once every 1 minute'));
		}
		if(!isset($schedules["5min"])){
			$schedules["5min"] = array(
				'interval' => 5*60,
				'display' => __('Once every 5 minutes'));
		}
		if(!isset($schedules["10min"])){
			$schedules["10min"] = array(
				'interval' => 10*60,
				'display' => __('Once every 10 minutes'));
		}
		return $schedules;
	}

	
	 

	public function get_cron_job()
{
	$arr1=_get_cron_array();
$cron=false;
foreach($arr1 as $key=>$val)
{	//print_r($val);
	if($val['easy_user_cron_job'])
	{	
		$easy_user_cron_job=$val['easy_user_cron_job'];
		foreach($easy_user_cron_job as $key2=>$inner)
		{	
			if($inner['args'][7]==get_current_user_id())
			{
				$cron=true;
			}
		}
	}
	else if($val['easy_user_cron_job_eden'])
	{	
		$easy_user_cron_job=$val['easy_user_cron_job_eden'];
		foreach($easy_user_cron_job as $key2=>$inner)
		{	
			if($inner['args'][7]==get_current_user_id())
			{
				$cron=true;
			}
		}
	}
}
//echo(json_encode(_get_cron_array())); 
if($cron)
echo '<button class="form_button2" id="cron_stop" type="button">Stop Cron Job</button>';
else
echo '<button class="form_button2" id="cron" type="button">Start Cron Job</button>';

}
	


public function stop_cron_job()
{
	$arr1=_get_cron_array();
	$args=array();
	foreach($arr1 as $key=>$val)
	{	//print_r($val);
		if($val['easy_user_cron_job'])
		{	
			$easy_user_cron_job=$val['easy_user_cron_job'];
			foreach($easy_user_cron_job as $key2=>$inner)
			{	
				if($inner['args'][7]==get_current_user_id())
				{
					$args=$inner['args'];
					//error_log(print_r($args,true));
					$timestamp = wp_next_scheduled("easy_user_cron_job", $args );
					wp_unschedule_event($timestamp ,"easy_user_cron_job",$args);
				}
			}
		}
		else if($val['easy_user_cron_job_eden'])
		{	
			$easy_user_cron_job=$val['easy_user_cron_job_eden'];
			foreach($easy_user_cron_job as $key2=>$inner)
			{	
				if($inner['args'][7]==get_current_user_id())
				{
					$args=$inner['args'];
					//error_log(print_r($args,true));
					$timestamp = wp_next_scheduled("easy_user_cron_job_eden", $args );
					wp_unschedule_event($timestamp ,"easy_user_cron_job_eden",$args);
				}
			}
		}
	}
}



}




?>