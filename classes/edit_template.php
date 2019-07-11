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


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}



class EDIT_TEMPLATE extends WP_List_Table {



	public function __construct() {

		parent::__construct( array(
				'singular' => __( 'Template', 'easy_email' ), // singular name of the listed records
				'plural' => __( 'Templates', 'easy_email' ), // plural name of the listed records
				'true' => true, // does this table support ajax?
		) );

	

	}


	

	public static function get_templates($per_page = 10, $page_number = 10 ) {

		global $wpdb;
		
			
		if(empty($_REQUEST['s']))
		{$sql = "SELECT * FROM {$wpdb->prefix}easy_email_templates where user_id=".get_current_user_id();
		
		if ( ! empty( $_REQUEST['orderby'] ) ) {
		  $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
		  $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : "DESC";
		}

		}


		else
		{
			$sql = "SELECT * FROM {$wpdb->prefix}easy_email_templates where user_id=".get_current_user_id()." ( name like '%".$_REQUEST['s']."%'";
		
		if ( ! empty( $_REQUEST['orderby'] ) ) {
		  $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
		  $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : "DESC";
		}

	}
		
		$sql .= " LIMIT $per_page";
	  
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		
		
	  
	
	

		
	  
	  	$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		  
	  return $result;
	}

	  public static function delete_template( $id ) {
		global $wpdb;
		$tablename = $wpdb->prefix . "easy_email_templates";
		
		$sql = $wpdb->prepare( "DELETE FROM  $tablename WHERE id=%d",$id );


		$wpdb->query($sql);
	
	  }




	  public static function record_count() {
		global $wpdb;

		if(!empty($_REQUEST['s']))
		{
			$sql = "SELECT count(*) FROM {$wpdb->prefix}easy_email_templates where user_id=".get_current_user_id()." ( name like '%".$_REQUEST['s']."%'";		}
		else
		{
			$sql = "SELECT count(*) FROM {$wpdb->prefix}easy_email_templates where user_id=".get_current_user_id();

		}
		
		return $wpdb->get_var($sql);
	  }
	


	  public function no_items() {
		_e( '	No Templates.', 'easy_email' );
	  }






		public function count_boxes($box)

		{	global $wpdb;

			$sql="SELECT COUNT(*) FROM {$wpdb->prefix}easy_forms where user_id=".get_current_user_id();

			return $wpdb->get_var( $sql );
		}


		function get_columns() {
			$columns = [
				'cb'      => '<input type="checkbox" />',
				'name'    => 'Template Name',
	
	
			];
		
			return $columns;
		  }



		





		
		function column_name($item)
		{

			$delete_nonce = wp_create_nonce( 'delete_template' );

						
						$name=wp_strip_all_tags($item['name']);
						if($name==null || $name=="")
							$name="No Name";
						$name=stripslashes($name);
						$title = "<a href='admin.php?page=create_template&id=".$item['id']."' style='text-decoration:none;'><span style='display:inline; font-weight:bold; color:#2c7c93'>".mb_strimwidth($name, 0,50, '...')."</span> </a>";
						
						$actions = [
							'delete' => sprintf( '<a href="?&page=%s&action=%s&id=%s&_wpnonce=%s">Delete</a>',esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce),
													];
					return $title . $this->row_actions( $actions );
					

		}



	  function column_cb( $item ) {
		return sprintf(
		  '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	  }



	  
	  public function get_sortable_columns() {
		$sortable_columns = array(
			'name' =>array( 'name', true ),

		);
	  
		return $sortable_columns;
	  }



	  public function get_bulk_actions() {

		$actions = [
			'bulk-delete' => 'Delete'
		];
	
		return $actions;
		}
		






	  public function prepare_items() {
		
		$this->_column_headers = $this->get_column_info();
		
		/** Process bulk action */
		$this->process_bulk_action();
	  
		$per_page     = $this->get_items_per_page( 'template_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();
	  
		$this->set_pagination_args( [
		  'total_items' => $total_items, //WE have to calculate the total number of items
		  'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );
	  
	  
		$this->items = self::get_templates( $per_page, $current_page );
	
	  }






		public function render_search()
		{	
			echo '<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
		}



	  public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
	  
		  // In our file that handles the request, verify the nonce.
		  $nonce = esc_attr( $_REQUEST['_wpnonce'] );
	  
		  if ( ! wp_verify_nonce( $nonce, 'delete_template' ) ) {
			die( 'Error While Deleting....' );
		  }
		  else {
			self::delete_template( absint( $_GET['id']));
				
			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		  }
	  
		}

		
		// If the delete bulk action is triggered
		if ( ( isset( $_GET['action'] ) && $_GET['action'] == 'bulk-delete' )
			 || ( isset( $_GET['action2'] ) && $_GET['action2'] == 'bulk-delete' )
		) {
			
		  $delete_ids = esc_sql( $_GET['bulk-delete'] );
	  
		  // loop over the array of record IDs and delete them
		  foreach ( $delete_ids as $id ) {
			
			self::delete_template( $id );
	  
		  }
	  
		  wp_redirect( esc_url( add_query_arg() ) );
		
		  exit;
		}		  


	  }



}

?>