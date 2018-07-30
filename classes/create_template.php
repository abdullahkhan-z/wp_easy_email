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



class Template_editor {


	public function __construct() {

	
		add_action( 'wp_ajax_editor_image_handler', [$this,'editor_image_handler'] );
		add_action( 'wp_ajax_editor_get_images', [$this,'editor_get_images'] );
		add_action( 'wp_ajax_editor_save_template', [$this,'editor_save_template'] );
	}

	public function editor_image_handler()
	{	
		if ( $_FILES ) { 
			require_once(__DIR__."/../../../../wp-admin/includes/image.php");
			require_once(__DIR__."/../../../../wp-admin/includes/file.php");
			require_once(__DIR__."/../../../../wp-admin/includes/media.php");
			

			if ($_FILES) {
				foreach ($_FILES as $file => $array) {
					if ($_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
						echo "Upload error : " . $_FILES[$file]['error'];
						die();
					}
					$attach_id = media_handle_upload($file,0);
				}   
			}
		 
			
			}
			

			$list=array();
			$media_query = new WP_Query(
				array(
					'author'   	=>  get_current_user_id(),
					'orderby'       =>  'post_date',
					'order'         =>  'DESC',
					'post_type' => 'attachment',
					'post_status' => 'inherit',
					'posts_per_page' => -1,
				)
			);
			$list = array();
			foreach ($media_query->posts as $post) {
				array_push($list,array("src"=>wp_get_attachment_url($post->ID),"width"=>300,"height"=>300));
			}
			echo json_encode($list);

			wp_die();
	}


	public function editor_get_images()
	{

		$list=array();
		$media_query = new WP_Query(
			array(
				'author'   	=>  get_current_user_id(),
				'orderby'       =>  'post_date',
				'order'         =>  'DESC',
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'posts_per_page' => -1,
			)
		);
		$list = array();
		foreach ($media_query->posts as $post) {
			array_push($list,array("src"=>wp_get_attachment_url($post->ID),"width"=>300,"height"=>300));
		}
		echo json_encode($list);

		wp_die();
	}


	public function editor_save_template()
	{
		$name=$_POST['template_name']?$_POST['template_name']:time();
		$content=$_POST['content'];
		$add_template=array("name"=>$name,"content"=>$content);
		add_user_meta( get_current_user_id(), 'easy_email_templates'.time(), $add_template);
		echo "Template Saved";
		wp_die();
	}


	public function editor_get_all_templates()
	{
		global $wpdb;
		$table=$wpdb->prefix."usermeta";
		$query="SELECT meta_value from '".$table."' Where user_id=".get_current_user_id()." and meta_key LIKE 'easy_email_templates%'";
		$result = $wpdb->get_results($query);
		
		foreach ($result as $template) {
           

 		  }

	}

	public function editor_get_template()
	{


	}

	

	
}
?>