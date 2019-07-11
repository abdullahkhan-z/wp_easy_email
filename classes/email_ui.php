<?php
/**
 * Email List Table
 * PHP VERSION 5.5
 * 
 * @category Inbox
 * 
 * @package Easy_Email
 * 
 * @author Abdullah Zafar <abdullah.zafar99-1,
 * 
 * @license http://google.com Paid
 * 
 * @link http://google.com
 **/


require_once ABSPATH . 'wp-load.php';

class EMAIL_UI {
	public $script="";

	public function __construct() {
		
		
		//add_action( 'wp_ajax_editor_image_handler', [$this,'editor_image_handler'] );
		add_action( 'admin_init', 'pw_settings_init' );

		add_action( 'wp_ajax_new_email_call_back', [$this,'new_email_call_back'] );
		
	}




	public function email_ui()
	{	


		
		$from="";
		$to="";
		$name="";
		$subject="";
		$time="";
		$message="";
		$attachments="";


		if(isset($_REQUEST['id']))
		{	

			global $wpdb;
			$id=$_REQUEST['id'];
			$table=$wpdb->prefix."easy_inbox";
			if(isset($_REQUEST['second_action']) && $_REQUEST['second_action']=="delete")
			{	
				if($_REQUEST['box']!="deleted")
				{
					$query=$wpdb->prepare("update $table set box='deleted',previous_box=%s WHERE id = %d",$_REQUEST['box'],$_REQUEST['id']);
					$wpdb->query( $query);
	
					echo "The email has been moved to deleted emails section";
					return;
				}
				else
				{

			
					$query=$wpdb->prepare("DELETE FROM $table  WHERE id = %d",$_REQUEST['id']);
					$wpdb->query( $query);

					echo "The email has been Deleted";
					return;
				}

			}
			$query=$wpdb->prepare("UPDATE $table SET status = 1 WHERE id = %d",$_REQUEST['id']);
			$results = $wpdb->get_results( "SELECT * FROM $table where id=$id");
			$wpdb->query( $query);
			
			foreach($results as $row)
			{
				$from=$row->email_from;
				$name=$row->name;
				$to=$row->email_to;
				$subject=$row->subject;
				if($subject=="")
				{
					$subject="No Subject";
				}
				$time=$row->time;
				$content=$row->content;
				$attachments=$row->attachment;


			}
		}
		else
		{
			echo "This email does not exist.";
			return;
		}
		?>
		<style>
			.error
			{
				display:none;
			}
			</style>
		<script>
				function calcHeight(iframeElement){
					var the_height=  iframeElement.contentWindow.document.body.scrollHeight;
					iframeElement.height=  the_height+10;
				}
			</script>
			<div class="wrap">
			<div class="panel-editor  panel-default-editor ">
    <div class="panel-heading-editor "><h3>WP EASY EMAIL</h3></div>
		<div class="panel-body-editor ">


<div class="row" style="float:right"><a href='admin.php?page=new_email&easy_action=respond&id=<?php echo $id ?>'><button style="background-color:dodgerblue; border-radius:10px; margin:2px; cursor:pointer; color:white">Reply</button></a><a href='admin.php?page=new_email&easy_action=forward&id=<?php echo $id ?>'><button style="background-color:darkcyan; border-radius:10px; margin:2px; cursor:pointer; color:white" >Forward</button></a><a href='admin.php?page=new_email&box=<?php echo $_REQUEST['box'];?>&easy_action=open&id=<?php echo $id ?>&second_action=delete'><button style="background-color:crimson; border-radius:10px; margin:2px; cursor:pointer; color:white"><?php if($_REQUEST['box']=="deleted") echo "Delete Permanently"; else echo "Delete";?></button></a></div>

<table class="form-table">
<input type="hidden" id="id" name="id" value='<?php echo $id ?>' />
<input type="hidden" id="respond_id" name="respond_id" value='<?php echo $respond_id ?>' />
<tbody><tr valign="top">
					<th scope="row" class="titledesc">
						<label>From</label>
					</th>
				<td class="forminp forminp-text">
					<p style="font-style:italic;"><strong><?php echo $name; ?></strong><<small style="font-style:italic;"><?php echo $from ?></small>> <small style="font-style:italic;"><?php echo " on ".$time; ?></small></p>
					</td>
				</tr>

		

				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>To</label>
					</th>
				<td class="forminp forminp-text">

					<p><small style="font-style:italic;"><?php echo $to; ?></small></p>

				  </td>
				</tr>



				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>Subject</label>
					</th>
				<td class="forminp forminp-text">

					<p style="font-style:italic;"><?php echo $subject; ?></p>

				  </td>
				</tr>

				
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="message">Message</label>
					</th>
				<td class="forminp forminp-text">
					<?php echo '<iframe style="width:100%;" onload="calcHeight(this);"  src="'.plugin_dir_url(__FILE__).'email_content.php?id='.$_REQUEST['id'].'"></iframe>'
					//	echo ' <object src="'.plugin_dir_url(__FILE__).'email_content.php?id='.$_REQUEST['id'].'" onload="calHeight(this);" ></object>';
					?>
					</td>
				</tr>

								<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="message"></label>
					</th>
				<td class="forminp forminp-text">

				
				
				<h2>Attachments</h2>
				<?php if($attachments=="" || $attachments==null) 
				echo '<p style="italic">No Attachments</p>';
				
				else
				{ $attachment=explode(',',$attachments);

					
					foreach($attachment as $attach)
					{	
						echo '<p style="italic"><a href="'.plugin_dir_url(__FILE__).'downloader.php?f='.$attach.'&type=attachment">'.$attach.'</a></p>';

					}
				}
			
				?>
				
			
	</td>
				</tr>
			

			
			</tbody></table>				
	</div>
	</div>
	</div>



<?php

			}




}
?>