<?php
/**
 * Plugin Name: WP Easy Email
 * Plugin URI: https://zetex-plugins.com
 * Description: SMTP and IMAP/POP client for Wordpress
 * Version: 1
 * Author: europez
 * Author URI: https://zetex-plugins.com
 * Text Domain: Easy Email
 **/

if ( ! class_exists( 'Easy_Email_Inbox' ) ) {
require_once( plugin_dir_path( __FILE__ ) . 'classes/inbox_table.php' );
}

if ( ! class_exists( 'Template_editor' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'classes/create_template.php' );
	}

if ( ! class_exists( 'NEW_EMAIL' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/new_email.php' );
		}

if(! class_exists('Easy_Editor_Settings'))
		{
			require_once plugin_dir_path(__File__) . 'classes/settings.php';
		}

if(! class_exists('EDIT_FORM'))
		{
			require_once plugin_dir_path(__File__) . 'classes/edit_form.php';
		}

if(! class_exists('EDIT_TEMPLATE'))
		{
			require_once plugin_dir_path(__File__) . 'classes/edit_template.php';
		}

register_activation_hook( __file__, 'installer' );
register_deactivation_hook( __file__, 'uninstaller' );


function jp_create_admin_pages() {
    global $submenu;
   
}

function installer()
{
	global $wpdb;

	$cron_jobs = $wpdb->prefix."easy_cronjobs";
	$email_templates=$wpdb->prefix."easy_email_templates";
	$forms=$wpdb->prefix."easy_forms";
	$inbox=$wpdb->prefix."easy_inbox";
	$charset_collate = $wpdb->get_charset_collate();

	$sql1="CREATE TABLE IF NOT EXISTS $cron_jobs (
		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`args` text,
		`user_id` int(11) DEFAULT NULL,
		`type` varchar(11) DEFAULT NULL,
		`status` int(11) DEFAULT '0',
		`pid` int(11) DEFAULT NULL,
		`job_stats` varchar(450),
	PRIMARY KEY (`id`)
  ) $charset_collate;";
  
  
 $sql2="CREATE TABLE IF NOT EXISTS $email_templates (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int(11) DEFAULT NULL,
	`email_template` longtext,
	`design` longtext,
	`name` text,
	PRIMARY KEY (`id`)
  ) $charset_collate;";
  
  
  $sql3="CREATE TABLE IF NOT EXISTS $forms (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int(11) DEFAULT NULL,
	`form_email_to` varchar(450) DEFAULT NULL,
	`form_template` longtext,
	`form_name` text,
	`identifier` bigint(11) DEFAULT NULL,
	`form_json` text,
	`subject` text,
	PRIMARY KEY (`id`)
  ) $charset_collate;";
  
  $sql4="CREATE TABLE IF NOT EXISTS $inbox (
	`user_id` int(11) DEFAULT NULL,
	`uid` int(11) DEFAULT NULL,
	`subject` text,
	`status` int(11) unsigned DEFAULT '0',
	`responded` int(11) DEFAULT '0',
	`previous_box` varchar(450) DEFAULT '',
	`name` varchar(450) DEFAULT '',
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`email_to` varchar(450) DEFAULT '',
	`email_from` varchar(450) DEFAULT '',
	`content` longtext,
	`box` varchar(450) DEFAULT '',
	`attachment` text,
	`added` datetime DEFAULT CURRENT_TIMESTAMP,
	`time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	`allusers` int(11) DEFAULT NULL,
	`allcustomers` int(11) DEFAULT NULL,
	`language` varchar(11) DEFAULT NULL,
	`pdf` int(11) DEFAULT NULL,
	`shortcode` int(11) DEFAULT NULL,
	`gotten_in` int(11) DEFAULT '0',
	PRIMARY KEY (`id`)
  ) $charset_collate;";

  
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql1 );
  dbDelta( $sql2 );
  dbDelta( $sql3 );
  dbDelta( $sql4 );
  

}

function uninstaller()
{	
	global $wpdb;
	$table=$wpdb->prefix."easy_cronjobs";
	$results=$wpdb->get_results("select * from $table");
	require_once (plugin_dir_path(__File__) . '/cron_jobs/cron_stop.php');
	foreach($results as $row)
	{	if($row->type!="real"){
			$args=unserialize($row->args);
			$timestamp = wp_next_scheduled("easy_user_cron_job", $args );
			wp_unschedule_event($timestamp ,"easy_user_cron_job",$args);

		}
		else
		{
			easy_email_stop_cron_job($row->user_id);
		}
	}

	$wpdb->query("TRUNCATE TABLE $table");

}

class Easy_Email_Plugin {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $email_obj;
	public $settings;
	public $new_email;
	public $editor;
	public $form_edit;
	// class constructor
	public function __construct() {
		apply_filters( 'wp_mail_content_type', function()
					{	return 'text/html';});
					
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );

		add_action( "admin_head",[$this,"add_dependencies"] );
		add_action( "wp_head",[$this,"add_dependencies"] );
		add_filter( 'manage_users_columns', [$this,'new_modify_user_table'] );
		add_filter( 'manage_users_custom_column', [$this,'new_modify_user_table_row'], 10, 3 );
		add_action('woocommerce_admin_order_totals_after_tax', [$this,'custom_admin_order_totals_after_tax'], 10, 1 );
		add_filter( 'wp_mail_content_type',[$this,'wpse27856_set_content_type'] );
		$this->create_attachments_folder();
		
		add_action( 'wp_ajax_easy_email_notification', [$this,'easy_email_notification'] );
		add_action( 'wp_ajax_attachment_handler', [$this,'attachment_handler'] );

		$this->editor=new Template_Editor();
		$this->settings=new Easy_Editor_Settings();
		$this->new_email=new NEW_EMAIL();
		add_action('admin_bar_menu', [$this,'add_toolbar_items'], 100);

		//add_menu_page('GEE Admin Settings', 'GEE Admin', 'manage_options', 'gee_admin_settings_page', 'admin_pg_function', '', 3);
	
	}




	public static function set_screen( $status, $option, $value ) {
		return $value;
	}


	public function attachment_handler()
	{
		if ( 0 < $_FILES['file']['error'] ) {
			error_log("error");
		}
		else {
			//error_log(print_r($_FILES['file'],true));
		}
	
		if ($_FILES['file']) {

			global $current_user;
			wp_get_current_user();
			$name=$current_user->user_login;

			$directory=plugin_dir_path(__FILE__)."/attachments/".$name;
			$count=0;


			
			$target=$directory;
			
			//if($FILES)
		
			$temp=$target;
			$tmp=$_FILES["file"]['tmp_name'];
			
			$file=pathinfo($_FILES["file"]['name']);
			$filername=$file['filename'];
			
			$filerextension=$file['extension'];
			$temp=$temp."/".$filername;
			//echo $temp;
			$tempo=$temp;
			$fname=$filername;
			$filer=$fname;
			$file_number=1;
			while (file_exists($temp.".".$filerextension))
				{
					
				$temp=$tempo;
				$fname=$filer;
				$temp=$temp.$file_number;
				$fname=$fname.$file_number;
				$file_number=$file_number+1;
				}
			$temp=$temp.".".$filerextension;
			$fname=$fname.".".$filerextension;
			if(!move_uploaded_file($tmp,$temp))
				{echo "An error occurred while saving attachment(s)";
					wp_die();}
			array_push($attachments,$temp);
			if($attachment_string=="")
			$attachment_string=$fname;
			else
			$attachment_string=$attachment_string.",".$fname;
			//echo $temp;
			//$temp='';
			//$tmp='';
			//$count=$count + 1;
			
			
			

		}
		//error_log(print_r($_POST['item_number'],true));
		$data=array("attachment"=>$attachment_string,"item_number"=>$_POST['item_number']);
		echo json_encode($data);
		wp_die();
	}
	public function add_dependencies()
	{
		$output='
		<link rel="stylesheet" href="'.plugin_dir_url(__FILE__).'assets/css/custom_bootstrap.css" type="text/css"   />
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/codemirror.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/mode/xml/xml.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
		
		<script>
		var easy_email_plugin_dir="'.plugin_dir_url(__FILE__).'";
		var ajaxurl = "'.admin_url('admin-ajax.php').'";
		var plugindir = "'.plugin_dir_url(__FILE__).'";
		var loader_url="'.plugin_dir_url(__FILE__).'classes/email_file_handler.php";

		var notification={
			"action":"easy_email_notification"
		}

		jQuery("document").ready(function(){
			jQuery.post(ajaxurl, notification, function(response) {
				if(response>0)
				{
					jQuery("#new_mails").html(response);
					jQuery("#new_mails").show();
				}
			});

			setInterval(function(){  
				jQuery.post(ajaxurl, notification, function(response) {
					if(response>0)
					{
						jQuery("#new_mails").html(response);
						jQuery("#new_mails").show();
					}
				});
				}, 5000);

	});


		</script>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />
		<style>
		
		.modal
		{
			width:1000px;
		}
		.panel-default-editor {
			border-color: #ddd;
		}
		
		.panel-editor  {
			margin-bottom: 20px;
			background-color: #fff;
			border: 1px solid transparent;
			border-radius: 4px;
			-webkit-box-shadow: 0 1px 1px rgba(0,0,0,.05);
			box-shadow: 0 1px 1px rgba(0,0,0,.05);
		}

		.panel-default-editor >.panel-heading-editor  {
			color: #333;
			background-color: #f5f5f5;
			border-color: #ddd;
		}
		
		.panel-heading-editor  {
			padding: 10px 15px;
			border-bottom: 1px solid transparent;
			border-top-left-radius: 3px;
			border-top-right-radius: 3px;
		}
		.panel-body-editor  {
			padding: 15px;
		}
		
		.modal_on {
			display: none; /* Hidden by default */
			position: fixed; /* Stay in place */
			z-index: 1; /* Sit on top */
			padding-top: 10px;
			padding-bottom: 10px; /* Location of the box */
			left: 0;
			top: 0;
			width: 100%; /* Full width */
			height: 100%; /* Full height */
			overflow: auto; /* Enable scroll if needed */

		}
		
		/* Modal Content */
		.modal-content_on {
			background-color: #fefefe;
			margin: auto;
			padding: 20px;
			border: 1px solid #888;
			width: 80%;
		}
		
		/* The Close Button */
		.close_on {
			color: #aaaaaa;
			float: right;
			font-size: 28px;
			font-weight: bold;
		}
		
		.close_on:hover,
		.close_on:focus {
			color: #000;
			text-decoration: none;
			cursor: pointer;
		}
		
		

		</style>
		

		
		';

		echo $output;

		if($_REQUEST['page']=="all_emails" || $_REQUEST['page']=="easy_inbox" || $_REQUEST['page']=="easy_sent" || $_REQUEST['page']=="easy_draft" ||  $_REQUEST['page']=="easy_deleted" ) 
		
		echo '<script>
		jQuery("#filter_by").change(function(){
			var value=jQuery(this).find(":selected").val();
			jQuery(".filter").hide();
			jQuery("#"+value).show();
		});
		jQuery("document").ready(function(){
		var modal = document.getElementById(\'myModal\');

		// Get the button that opens the modal
		btn = jQuery(".myBtn");

		// Get the <span> element that closes the modal
		var span = document.getElementsByClassName("close_on")[0];

		// When the user clicks the button, open the modal 
		btn.click(function(){
			modal.style.display = "block";
		});

		// When the user clicks on <span> (x), close the modal
		span.onclick = function() {
			modal.style.display = "none";
		}

		// When the user clicks anywhere outside of the modal, close it
		window.onclick = function(event) {
			if (event.target == modal) {
				modal.style.display = "none";
			}
		}
	});
		</script>';
	}


	public function plugin_menu() {
		global $submenu;
		$hook = add_menu_page(
			'All Emails',
			'WP Easy Email',
			'manage_options',
			'all_emails',
			[ $this, 'plugin_settings_page' ],
			plugin_dir_url(__FILE__)."assets/images/wp_icon.png"
		);
		
		//add_menu_page('Members','Members','manage_options','members','jp_handle_admin_members');
		//add_submenu_page('members','Membership Types','Membership Types','manage_options','membership_types','jp_handle_admin_membership_types');
		

		add_action( "load-$hook", [ $this, 'screen_option' ] );
		$new_email=add_submenu_page('all_emails','New Email', 'New Email','manage_options', 'new_email', [ $this, 'new_email' ]);
		$inbox=add_submenu_page('all_emails','Inbox', 'Inbox','manage_options', 'easy_inbox', [ $this, 'plugin_settings_page' ]);
		$sent=add_submenu_page('all_emails','Sent', 'Sent','manage_options', 'easy_sent', [ $this, 'plugin_settings_page' ]);
		$draft=add_submenu_page('all_emails','Draft', 'Draft','manage_options', 'easy_draft', [ $this, 'plugin_settings_page' ]);
		$trash=add_submenu_page('all_emails','Deleted', 'Deleted','manage_options', 'easy_deleted', [ $this, 'plugin_settings_page' ]);
		$settings=add_submenu_page('all_emails','Settings', 'Settings','manage_options', 'easy_settings', [ $this, 'settings' ]);
		$users=add_submenu_page('all_emails','Users', 'All Users','manage_options', 'easy_users', [ $this, 'all_users' ]);
		//$customers=add_submenu_page('all_emails','Customers', 'Customers','manage_options', 'easy_customers', [ $this, 'plugin_settings_page' ]);
		$create_template=add_submenu_page('all_emails','Create Template', 'Create Template','manage_options', 'create_template', [ $this, 'create_template' ]);
		$edit_template=add_submenu_page('all_emails','Edit/Delete Template', 'Edit/Delete Template','manage_options', 'edit_template', [ $this, 'edit_template' ]);
		$form_builder=add_submenu_page('all_emails','Form Builder', 'Form Builder','manage_options', 'easy_form_builder', [ $this, 'form_builder' ]);
		$edit_form=add_submenu_page('all_emails','Edit Forms', 'Edit Forms','manage_options', 'easy_form_editor', [ $this, 'edit_form' ]);

		$submenu['all_emails'][0][0] = 'All Emails';
		add_action( "load-$inbox", [ $this, 'screen_option' ] );
		add_action( "load-$sent", [ $this, 'screen_option' ] );
		add_action( "load-$draft", [ $this, 'screen_option' ] );
		add_action( "load-$trash", [ $this, 'screen_option' ] );
		//add_action( "load-$settings", [ $this, 'screen_option' ] );
		add_action( "load-$users", [ $this, 'screen_option' ] );
		add_action( "load-$customers", [ $this, 'screen_option' ] );
		//add_action( "load-$create_template", [ $this, 'screen_option' ] );
		add_action( "load-$edit_form", [ $this, 'screen_option_form' ] );
		add_action( "load-$edit_template", [ $this, 'screen_option_template' ] );
		
	}
	function wpse27856_set_content_type(){
		return "text/html";
	}


	public function all_users()
	{
		wp_redirect(admin_url()."/users.php");
	}


	public function edit_template()
	{ 
		
		?>
		<div class="wrap">
		<div class="panel-editor  panel-default-editor ">
					<div class="panel-body-editor ">
			<div id="poststuff">
				<div id="post-body" style="position:relative; width:100%;" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<h1>Wp Easy Email - All Templates</h1>
							<form method="GET">
								<?php
								$this->template_edit->prepare_items();
								$this->template_edit->render_search();		
								$this->template_edit->search_box('search', 'search_id');
								?>
								
								<?php
								$this->template_edit->display(); 
								?>

							</form>
							</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	</div>
	</div>

		<?php
	}

	public function edit_form()
	{ 
		
		?>
		<div class="wrap">
		<div class="panel-editor  panel-default-editor ">
					<div class="panel-body-editor ">
			<div id="poststuff">
				<div id="post-body" style="position:relative; width:100%;" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<h1>Wp Easy Email - All Forms</h1>
							<form method="GET">
								<?php
								$this->form_edit->prepare_items();
								$this->form_edit->render_search();		
								$this->form_edit->search_box('search', 'search_id');
								?>
								
								<?php
								$this->form_edit->display(); 
								?>

							</form>
							</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	</div>
	</div>

		<?php
	}


	public function form_builder()
	{
		if ( ! class_exists( 'FORM_BUILDER' ) ) 
			require_once( plugin_dir_path( __FILE__ ) . 'classes/form_editor_class.php' );
		
		$form_builder=new FORM_EDITOR();

		if(isset($_REQUEST['id']) && !empty($_REQUEST['id']))
		$form_builder->reeditor();
		else
		$form_builder->editor();
	}

	public function settings()
	{
		
		$this->settings->display();
		
		
	}

	public function easy_email_notification()
	{
		global $wpdb;
		$table=$wpdb->prefix."easy_inbox";
		$records=$wpdb->get_var("SELECT count(*) FROM $table where date(added)=curdate() and gotten_in=0 and box='inbox' and user_id=".get_current_user_id());

		echo $records;
		wp_die();
	}

	public function new_email()
	{
		if($_REQUEST['easy_action']=='edit')
		{	global $wpdb;
			$id=$_REQUEST['id'];
			$table=$wpdb->prefix."easy_inbox";
			$results = $wpdb->get_results( "SELECT * FROM $table where id=$id");
			
			foreach($results as $row)
			$this->new_email->email_form($id,$id,$row->email_to,false,false,$row->subject,$row->content,false,$row->attachment,null,-1);
			
		}
		
		else if($_REQUEST['easy_action']=='forward')
		{
			global $wpdb;
			$id=$_REQUEST['id'];
			$table=$wpdb->prefix."easy_inbox";
			$results = $wpdb->get_results( "SELECT * FROM $table where id=$id");
			
			foreach($results as $row)
			$this->new_email->email_form($id,$id,null,false,false,$row->subject,$row->content,false,array(),null,-1);
		}

		else if($_REQUEST['easy_action']=='respond')
		{	
			global $wpdb;
			$id=$_REQUEST['id'];
			$table=$wpdb->prefix."easy_inbox";
			$results = $wpdb->get_results( "SELECT * FROM $table where id=$id");
			foreach($results as $row)
			$this->new_email->email_form($id,$id,$row->email_from,false,false,$row->subject,null,false,array(),null,$id);
		}

		else if($_REQUEST['easy_action']=='open')
		{
			if ( ! class_exists( 'EMAIL_UI' ) ) 
				require_once( plugin_dir_path( __FILE__ ) . 'classes/email_ui.php' );

			$email_ui=new EMAIL_UI();
			$email_ui->email_ui();
		}

		else if($_REQUEST['easy_action']=='send_email')
		{
			global $wpdb;
			if(isset($_REQUEST['id']))
			{	$id=$_REQUEST['id'];
				$user_info = get_userdata($id);
				$mailadresje = (string)$user_info->user_email;
				$this->new_email->email_form(-1,-1,$mailadresje,false,false,null,null,false,array(),null,-1); 
			}
			
			else if (isset($_REQUEST['email']))
			{

				$email=$_REQUEST['email'];
				$this->new_email->email_form(-1,-1,$email,false,false,null,null,false,array(),null,-1); 

			}
		}
		else
		$this->new_email->email_form(-1,-1,null,false,false,null,null,false,array(),null,-1);

		

	}

	public function screen_option_form() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Records',
			'default' => 10,
			'option'  => 'forms_per_page'
		];
	
		add_screen_option( $option, $args );
	
		$this->form_edit=new EDIT_FORM();
	}

	public function screen_option_template() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Records',
			'default' => 10,
			'option'  => 'templates_per_page'
		];
	
		add_screen_option( $option, $args );
	
		$this->template_edit=new EDIT_TEMPLATE();
	}

	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Records',
			'default' => 10,
			'option'  => 'emails_per_page'
		];
	
		add_screen_option( $option, $args );
	
		$this->email_obj = new Easy_Email_Inbox();
		
	}
	

	public function override_mce_options($initArray) {
		$opts = '*[*]';
		$initArray['valid_elements'] = $opts;
		$initArray['extended_valid_elements'] = $opts;
		return $initArray;
	}
	



	public function create_template()
	{
				//$this->template_create->display();
				//wp_redirect(plugin_dir_url( __FILE__ )."/editor.php");
				if ( ! class_exists( 'EMAIL_BUILDER' ) ) {
					require_once( plugin_dir_path( __FILE__ ) . 'classes/email_builder_class.php' );
					}

				$email_builder=new EMAIL_BUILDER();

				if($_REQUEST['id'])
				$email_builder->reeditor($_REQUEST['id']);
				else
				$email_builder->editor();

	}

	public function add_toolbar_items($admin_bar){

		$admin_bar->add_menu( array(
			'id'    => 'mails',
			'title' => '<span style="position:relative;"><span>Mails</span><div id="new_mails" style="
			display:none;
			position:relative; float:right;
			top: 4px;
			color: white;
			background-color: red;
			width: 18px;
			height: 18px;
			border-radius: 50%;
			line-height: 18px;
			font-size: 8px;
			text-align: center;
			cursor: pointer;
			z-index: 999;">0 
		  </div>  ',
			'href'  => admin_url().'admin.php?page=easy_inbox',
			'meta'  => array(
				'title' => __('Mails'),            
			),
		));
	}

	public function plugin_settings_page() {
		?>
		<div class="wrap">
		<div class="panel-editor  panel-default-editor ">
			<?php if($_REQUEST['page']=="all_emails") 
		echo '<div class="panel-heading-editor "><h3>Wp Easy Email - All Emails</h3></div>';
		 if($_REQUEST['page']=="easy_inbox")
		{	global $wpdb;
			$table=$wpdb->prefix."easy_inbox";
			$query=$wpdb->prepare("UPDATE $table SET gotten_in = 1 WHERE user_id = %d",get_current_user_id());
			$wpdb->query( $query);

			echo '<div class="panel-heading-editor "><h3>Wp Easy Email - Inbox</h3></div>';

		}
		else if($_REQUEST['page']=="easy_sent") 
		echo '<div class="panel-heading-editor "><h3>Wp Easy Email - Sent</h3></div>';
		 else if($_REQUEST['page']=="easy_draft")
		echo '<div class="panel-heading-editor "><h3>Wp Easy Email - Draft</h3></div>';
		 else if($_REQUEST['page']=="easy_deleted")
		echo '<div class="panel-heading-editor "><h3>Wp Easy Email - Deleted Emails</h3></div>';

		?>
			<div class="panel-body-editor ">
			<div id="poststuff">
				<div id="post-body" style="position:relative; width:100%;" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="GET">
								<?php
								$this->email_obj->prepare_items();
								$this->email_obj->render_search();
														?>
														
								
								
								
								<?php
								$this->email_obj->views();
								?>
								<div id="myModal" style="width:100%; position:relative;" class="modal_on">


							<div class="modal-content_on">
							<span class="close_on">&times;</span>
											
								<div>
									<div id="respond" >
										<label>Responded</label>
										<select  style=" margin-left:5px; margin-top:1px;" name="responded" id="responded">
										<option value="">None</option>
										<option value="1">Yes</option>
										
										<option value="0">No</option>

										</select>
									</div> <br/>
									<div  id="time" > From
										<input type="date" name="start_date" id="start_date"/>
										To
										<input type="date" name="end_date" id="end_date" />
	</div>
	<br/>	

	<div  id="subject" style="margin-left:5px; margin-top:0px;"> 
									<label>Subject</label>	<input type="search"  style=" padding:4px;" name="subject" id="subject" placeholder="Subject contains..."/>
	</div>
	<br/>

									<div  id="content" style="margin-left:5px; margin-top:0px;">
										<label>Content</label><input type="search" style=" padding:4px;"  name="content" id="content" placeholder="Content contains..."/>
	</div>
	<br/>

									<div  id="email_from" style="margin-left:5px; margin-top:0px; "> 
									<label>Email From</label>	<input type="search"  style=" padding:4px;" name="email_from" id="email_from" placeholder="Address"/>
	</div>
	<br/>

										<div  id="email_from" style="margin-left:5px; margin-top:0px; "> 
									<label>Email From (Name)</label>	<input type="search"  style=" padding:4px;" name="email_from_name" id="email_from_name" placeholder="Name"/>
	</div>
	<br/>

									<div  id="email_to" style="margin-left:5px; margin-top:0px;"> 
									<label>Email To</label>	<input type="search"  style=" padding:4px;" name="email_to" id="email_to" placeholder="Address"/>
	</div>
	<br/>
									<input type="submit" id="filter_submit" style="margin-left:2px;"  class="button" value="Filter">
								</div>
</div>

</div>
								<?php
								$this->email_obj->search_box('search', 'search_id');
								?>
								
								<?php
								$this->email_obj->display(); 
								?>

							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	</div>
	</div>
	
	<?php
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
	
		return self::$instance;
	}


	public function create_attachments_folder()
	{	global $current_user;
		wp_get_current_user();
		$name=$current_user->user_login;
		$directory=plugin_dir_path(__FILE__)."attachments/".$name;
		if (!file_exists($directory)) {
			mkdir($directory, 0777, true);
		}
	}

	public function new_contact_methods( $contactmethods ) {
		$contactmethods['phone'] = 'Phone Number';
		return $contactmethods;
	}
	
	
	
	public function new_modify_user_table( $column ) {
		$column['send_email'] = 'Send Email';
		return $column;
	}

	public function new_modify_user_table_row( $val, $column_name, $user_id ) {
		switch ($column_name) {
			case 'send_email' :
				return "<a class='respond' href='admin.php?page=new_email&easy_action=send_email&id=".$user_id."' style='vertical-align:middle;'>Send Email</a>";
				break;
			default:
		}
		return $val;
	}
	

	




	public function custom_admin_order_totals_after_tax( $orderid ) {
	 
		// Here set your data and calculations

	 
		// Output
		?>
		<script>
			
			jQuery("document").ready(function(){
				var email="";
				jQuery("a").each(function() {
						if(jQuery(this).attr("href").indexOf("mailto") !== -1)
						email=jQuery(this).html();
				});

				jQuery("#poststuff").before("<a class='respond' style='margin-bottom:5px; text-decoration:none;' href=admin.php?page=new_email&easy_action=send_email&email="+email+">Send Email to Customer</a>");});
				
			</script>
		<?php
	}
			 

	

}



function ure_add_block_admin_notices_option($items) {
    $item = URE_Role_Additional_Options::create_item('block_admin_notices', esc_html__('Block admin notices', 'user-role-editor'), 'admin_init', 'ure_block_admin_notices');
    $items[$item->id] = $item;
    
    return $items;
}
function ure_block_admin_notices() {
    add_action('admin_print_scripts', 'ure_remove_admin_notices');    
}
function ure_remove_admin_notices() {
    global $wp_filter;
    if (is_user_admin()) {
        if (isset($wp_filter['user_admin_notices'])) {
            unset($wp_filter['user_admin_notices']);
        }
    } elseif (isset($wp_filter['admin_notices'])) {
        unset($wp_filter['admin_notices']);
    }
    if (isset($wp_filter['all_admin_notices'])) {
        unset($wp_filter['all_admin_notices']);
    }
}



add_action( 'plugins_loaded', function () {
	Easy_Email_Plugin::get_instance();
} );

 ?>