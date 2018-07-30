<?php
/**
 * Plugin Name: Easy Email
 * Plugin URI: https://zetex-plugins.com
 * Description: Plugin to Modify archive.php
 * Version: 1
 * Author: Abdullah Zafar
 * Author URI: https://zetex-plugins.com
 * Text Domain: Easy Email
 *
 * 
 */

if ( ! class_exists( 'Easy_Email_Inbox' ) ) {
require_once( plugin_dir_path( __FILE__ ) . 'classes/inbox_table.php' );
}

if ( ! class_exists( 'Template_editor' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'classes/create_template.php' );
	}

if ( ! class_exists( 'NEW_EMAIL' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/new_email.php' );
		}


function jp_create_admin_pages() {
    global $submenu;
   
}

class Easy_Email_Plugin {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $email_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
		
		add_action( "admin_head",[$this,"add_dependencies"] );
		
	
		$editor=new Template_Editor();
		
		//add_menu_page('GEE Admin Settings', 'GEE Admin', 'manage_options', 'gee_admin_settings_page', 'admin_pg_function', '', 3);
	
	}




	public static function set_screen( $status, $option, $value ) {
		return $value;
	}


	public function add_dependencies()
	{
		$output='
		
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/codemirror.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.3.0/mode/xml/xml.min.js"></script>
  
		<style>
		
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
		</style>';

		echo $output;
	}


	public function plugin_menu() {
		global $submenu;
		$hook = add_menu_page(
			'All Emails',
			'Easy WP Email',
			'manage_options',
			'all_emails',
			[ $this, 'plugin_settings_page' ]
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
		$users=add_submenu_page('all_emails','Users', 'Users','manage_options', 'easy_users', [ $this, 'plugin_settings_page' ]);
		$customers=add_submenu_page('all_emails','Customers', 'Customers','manage_options', 'easy_customers', [ $this, 'plugin_settings_page' ]);
		$create_template=add_submenu_page('all_emails','Create Template', 'Create Template','manage_options', 'create_template', [ $this, 'create_template' ]);
		$edit_template=add_submenu_page('all_emails','Edit/Delete Template', 'Edit/Delete Template','manage_options', 'edit_template', [ $this, 'edit_template' ]);
		$submenu['all_emails'][0][0] = 'All Emails';
		add_action( "load-$inbox", [ $this, 'screen_option' ] );
		add_action( "load-$sent", [ $this, 'screen_option' ] );
		add_action( "load-$draft", [ $this, 'screen_option' ] );
		add_action( "load-$trash", [ $this, 'screen_option' ] );
		add_action( "load-$settings", [ $this, 'screen_option' ] );
		add_action( "load-$users", [ $this, 'screen_option' ] );
		add_action( "load-$customers", [ $this, 'screen_option' ] );
		add_action( "load-$create_template", [ $this, 'screen_option' ] );
		
	}


	public function edit_template()
	{
		
	}

	public function settings()
	{

		
	}

	public function new_email()
	{
		$new_email=new NEW_EMAIL();
		$new_email->email_form();

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
				wp_redirect(plugin_dir_url( __FILE__ )."/editor.php");
	}

	public function plugin_settings_page() {
		?>
		<div class="wrap">
		<div class="panel-editor  panel-default-editor ">
			<?php if($_REQUEST['page']=="all_emails") 
		echo '<div class="panel-heading-editor "><h3>All Emails</h3></div>';
		 if($_REQUEST['page']=="easy_inbox")
		echo '<div class="panel-heading-editor "><h3>Inbox</h3></div>';
		else if($_REQUEST['page']=="easy_sent") 
		echo '<div class="panel-heading-editor "><h3>Sent</h3></div>';
		 else if($_REQUEST['page']=="easy_draft")
		echo '<div class="panel-heading-editor "><h3>Draft</h3></div>';
		 else if($_REQUEST['page']=="easy_deleted")
		echo '<div class="panel-heading-editor "><h3>Deleted Emails</h3></div>';

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


	



}




add_action( 'plugins_loaded', function () {
	Easy_Email_Plugin::get_instance();
} );

 ?>