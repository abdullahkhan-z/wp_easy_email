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


add_action('init', 'do_output_buffer');
function do_output_buffer() 
{
ob_start();
}

add_action('admin_head','style_script');

function style_script()
{

		$head="<link rel='stylesheet' href='".plugin_dir_url( __FILE__ )."../assets/css/style.css'  type='text/css'>";
		echo $head;
}

class Easy_Email_Inbox extends WP_List_Table {


	public function __construct() {

		parent::__construct( array(
				'singular' => __( 'Email', 'easy_email' ), // singular name of the listed records
				'plural' => __( 'Emails', 'easy_email' ), // plural name of the listed records
				'true' => true, // does this table support ajax?
		) );

		add_action( 'admin_footer', array( &$this, 'script' ) );

	}


	

	public static function get_emails($per_page = 10, $page_number = 10 ) {

		global $wpdb;
		$box="";
		if ($_REQUEST['page']=="easy_inbox")
		$box="where box='inbox'";
		else if  ($_REQUEST['page']=="easy_sent")
		$box="where box='sent'";

		else if ($_REQUEST['page']=="easy_draft")
		$box="where box='draft'";

		else if ($_REQUEST['page']=="easy_deleted")
		$box="where box='deleted'";

		if(!isset($_REQUEST['s']))
		{$sql = "SELECT * FROM {$wpdb->prefix}easy_inbox ".$box;
	  
		if ( ! empty( $_REQUEST['orderby'] ) ) {
		  $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
		  $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : "DESC";
		}
		else
		{
			$sql.=" order BY added DESC";
		}}


		else
		{ 	if($box=="")
				$box="where";
				else 
				$box=$box." and";
			$sql = "SELECT * FROM {$wpdb->prefix}easy_inbox ".$box." name like '%".$_REQUEST['s']."%' or subject like '%".$_REQUEST['s']."%' or content like '%".$_REQUEST['s']."%' or email_from like '%".$_REQUEST['s']."%'";
	  
		if ( ! empty( $_REQUEST['orderby'] ) ) {
		  $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
		  $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : "DESC";
		}
		else
		{
			$sql.=" order BY added DESC";
		}}
		
		
		$sql .= " LIMIT $per_page";
	  
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
	  
	  
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $result;
	  }


	  public static function delete_email( $id,$class ) {
		global $wpdb;
		$tablename = $wpdb->prefix . "easy_inbox";
		//echo $class;
		if($class=="deleted")
		$sql = $wpdb->prepare( "DELETE FROM  $tablename WHERE id=%d",$id );

		else
		$sql=$wpdb->prepare("Update $tablename set box='deleted',previous_box='$class' where id=%d",$id);

		$wpdb->query($sql);
	
	  }


		public static function send_back($id,$prev_class)
		{	global $wpdb;
			$tablename = $wpdb->prefix . "easy_inbox";
			$sql=$wpdb->prepare("Update $tablename set box='$prev_class', previous_box='$prev_class' where id=%d",$id);
			$wpdb->query($sql);
		}


	  public static function record_count() {
		global $wpdb;
		
		$box="";
		if ($_REQUEST['page']=="easy_inbox")
		$box="where box='inbox'";
		else if  ($_REQUEST['page']=="easy_sent")
		$box="where box='sent'";

		else if ($_REQUEST['page']=="easy_draft")
		$box="where box='draft'";

		else if ($_REQUEST['page']=="easy_deleted")
		$box="where box='deleted'";
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}easy_inbox ".$box;
	  
		return $wpdb->get_var( $sql );
	  }
	


	  public function no_items() {
		_e( '	No Emails.', 'easy_email' );
	  }






		public function count_boxes($box)
		{	global $wpdb;
			if($box=="" || $box==null)
			$sql="SELECT COUNT(*) FROM {$wpdb->prefix}easy_inbox";
			else
			$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}easy_inbox where box='".$box."'";
			return $wpdb->get_var( $sql );
		}



		function column_time( $item ) {


			
			//date('j M ', $date);
			//$title = '' . date('m/d/Y H:i:s',$item['time'] ). '';
			$date = date('j M Y',$item['time'] );
			$elapsed=time_elapsed_string("@".$item['time']);
			$title="<p style='font-style:italic'>".$date."</p><small style='font-style:italic'>".$elapsed."</small>";
			return $title. $this->row_actions( $actions );
			}

		
		function column_content( $item ) {

	
		
			$string=$item['content'];
			$length=20;
			$title = mb_strimwidth(wp_strip_all_tags($item['content']), 0,100, '...');
			

			return $title . $this->row_actions( $actions );
			}



		function column_responded( $item ) {

				$responded=$item['responded'];
				if($responded==0)
				$title ="<p><a href='' style='text-decoration:none;'><button class='respond'>Respond</button></a><p>";
				else
				$title ="<p><a href='' style='text-decoration:none;'><button class='responsed'>Responded</button></a><p>";
				
	
				return $title;
				}


		function column_email_from( $item ) {

					//$responded=$item['responded'];
					//if($responded==0)
					
					$title  ="<p style='font-weight:bold;'>".$item['name']."</p><small style='font-style:italic'>".$item['email_from']."<small>";
					
		
					return $title . $this->row_actions( $actions );
					}
	
		
		function column_subject($item)
		{

			$delete_nonce = wp_create_nonce( 'delete_email' );
			$send_back_nonce=wp_create_nonce('send_back');
						$img="";
						if($item['box']=="inbox")
						$img="<img width=20 height=20  style='display:inline;' src='".plugin_dir_url( __FILE__ )."../assets/images/inbox.png' alt='' />";

						else if($item['box']=="sent")
						$img="<img width=15 height=15  style='display:inline;' src='".plugin_dir_url( __FILE__ )."../assets/images/sent.png' alt='' />";

						else if($item['box']=="deleted")
						$img="<img width=15 height=15  style='display:inline;' src='".plugin_dir_url( __FILE__ )."../assets/images/deleted.png' alt='' />";
 
						else if($item['box']=="draft")
						$img="<img width=25 height=25  style='display:inline;' src='".plugin_dir_url( __FILE__ )."../assets/images/draft.png' alt='' />";
 
						

						$title = "<a href='' style='text-decoration:none;'><span style='display:inline; font-weight:bold; color:#2c7c93'>".mb_strimwidth(wp_strip_all_tags($item['subject']), 0,50, '...')."</span> <span style='margin-top0px; margin-bottom:0px;'>".$img."</span></a>";
						if($item['box']=="deleted")
						$actions = [
							'delete' => sprintf( '<a href="?&page=%s&action=%s&id=%s&_wpnonce=%s&class=%s">Delete</a>',esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce,$item['box'] ),
							'sendback' => sprintf( '<a href="?&page=%s&action=%s&id=%s&_wpnonce=%s&previous_class=%s">Send Back</a>',esc_attr( $_REQUEST['page'] ), 'sendback', absint( $item['id'] ), $send_back_nonce,$item['previous_box'] ),
						];
						
						else
						$actions = [
							'delete' => sprintf( '<a href="?&page=%s&action=%s&id=%s&_wpnonce=%s&class=%s">Delete</a>',esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce,$item['box'] )];
		
					return $title . $this->row_actions( $actions );

		}



	  function column_cb( $item ) {
		return sprintf(
		  '<input type="checkbox" name="bulk-delete[]" value="%s&%s&%s" />', $item['id'],$item["box"],$item["previous_box"]
		);
	  }


	  function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'subject'    => __( 'Subject', 'easy_email' ),
		  'email_from' => __( 'Email From', 'easy_email' ),
			'content'	=> __( 'Content', 'easy_email' ),
			'time' =>__( 'Time', 'easy_email' ),
			'responded'=>__( 'Action', 'easy_email' )

		];
	  
		return $columns;
	  }

	  
	  public function get_sortable_columns() {
		$sortable_columns = array(
			'subject' =>array( 'name', true ),
		  'name' => array( 'name', true ),
		  'email_from' => array( 'email_from', false ),
		  'time' => array( 'time', false ),
		  'responded'=>array('responded',true)
		);
	  
		return $sortable_columns;
	  }



	  public function get_bulk_actions() {
			if($_REQUEST['page']=="all_emails" || $_REQUEST['page']=="easy_deleted")
		{$actions = [
			'bulk-delete' => 'Delete',
			'bulk-sendback'=>'Send Back'
		];
	}
		else
{
		$actions = [
			'bulk-delete' => 'Delete'
		];
	}
		return $actions;
	  }

		function get_views() { 
			$status_links = array(
					"all"       => __("<a href='admin.php?page=all_emails'>All(".$this->count_boxes("").")</a>",'my-plugin-slug'),
					"inbox" => __("<a href='admin.php?page=easy_inbox'>Inbox(".$this->count_boxes("inbox").")</a>",'my-plugin-slug'),
					"sent"   => __("<a href='admin.php?page=easy_sent'>Sent(".$this->count_boxes("sent").")</a>",'my-plugin-slug'),
					"Draft"   => __("<a href='admin.php?page=easy_draft'>Draft(".$this->count_boxes("draft").")</a>",'my-plugin-slug'),
					"Deleted" => __("<a href='admin.php?page=easy_deleted'>Deleted(".$this->count_boxes("deleted").")</a>",'my-plugin-slug'),
			);
			return $status_links;
		}



	  public function prepare_items() {

		$this->_column_headers = $this->get_column_info();
	  $customvar = ( isset($_REQUEST['customvar']) ? $_REQUEST['customvar'] : 'all');

		/** Process bulk action */
		$this->process_bulk_action();
	  
		$per_page     = $this->get_items_per_page( 'emails_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();
	  
		$this->set_pagination_args( [
		  'total_items' => $total_items, //WE have to calculate the total number of items
		  'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );
	  
	  
		$this->items = self::get_emails( $per_page, $current_page );
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
	  
		  if ( ! wp_verify_nonce( $nonce, 'delete_email' ) ) {
			die( 'Error While Deleting....' );
		  }
		  else {
			self::delete_email( absint( $_GET['id']),$_GET['class'] );
				
			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		  }
	  
		}
		else if('sendback' === $this->current_action())
		{
     		  // In our file that handles the request, verify the nonce.
		  $nonce = esc_attr( $_REQUEST['_wpnonce'] );
	  
		  if ( ! wp_verify_nonce( $nonce, 'send_back' ) ) {
			die( 'Error While Sending Back Email....' );
		  }
		  else {
			self::send_back( absint( $_GET['id']),$_GET['previous_class'] );
				
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
		  foreach ( $delete_ids as $ids ) {
				$arr=explode("&",$ids);
				$id=$arr[0];
				$class=$arr[1];
			//	$prev_class=$arr3[0]?$class:$arr3[0];
			self::delete_email( $id,$class );
	  
		  }
	  
		  wp_redirect( esc_url( add_query_arg() ) );
		
		  exit;
		}


		if ( ( isset( $_GET['action'] ) && $_GET['action'] == 'bulk-sendback' )
		|| ( isset( $_GET['action2'] ) && $_GET['action2'] == 'bulk-sendback' )
 ) {
	 
	 $delete_ids = esc_sql( $_GET['bulk-delete'] );
 
	 // loop over the array of record IDs and delete them
	 foreach ( $delete_ids as $ids ) {
		 $arr=explode("&",$ids);
		 $id=$arr[0];
		 $class=$arr[2];
	 //	$prev_class=$arr3[0]?$class:$arr3[0];
	 self::send_back( $id,$class );
 
	 }
 
	 wp_redirect( esc_url( add_query_arg() ) );
 
	 exit;
 }


			  


	  }



}


function time_elapsed_string($datetime, $full = false) {
	$now = new DateTime;
	$ago = new DateTime($datetime);
	$diff = $now->diff($ago);

	$diff->w = floor($diff->d / 7);
	$diff->d -= $diff->w * 7;

	$string = array(
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
	);
	foreach ($string as $k => &$v) {
			if ($diff->$k) {
					$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			} else {
					unset($string[$k]);
			}
	}

	if (!$full) $string = array_slice($string, 0, 1);
	return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>