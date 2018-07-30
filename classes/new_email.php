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

class NEW_EMAIL {
	public $script="";

	public function __construct() {
		
		
		//add_action( 'wp_ajax_editor_image_handler', [$this,'editor_image_handler'] );
		add_action( 'admin_init', 'pw_settings_init' );
		
	}




	public function email_form()
	{
		?>

			<div class="wrap">
			<div class="panel-editor  panel-default-editor ">
    <div class="panel-heading-editor "><h3>WP EASY EMAIL</h3></div>
		<div class="panel-body-editor ">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">





<table class="form-table">

<tbody><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="email_to">To</label>
					</th>
				<td class="forminp forminp-text">
					<input name="email_to" id="email_to" type="text" style="" value="abc@gmail.com" class="regular-text"> 
					<br/>
					<br/>
					<label for="all_users">All Users &nbsp;</label>
					<input type="checkbox" id='all_users' name='all_users'/>
					<label for="all_customers">All Customers &nbsp;</label>
					<input type="checkbox" id='all_customers' name='all_customers'/></td>
				</tr>

		

				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="subject">Subject</label>
					</th>
				<td class="forminp forminp-text">
					<input name="subject" id="subject" type="text" style="" value="subject" class="regular-text">                         </td>
				</tr>



				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="template_select">Select Template (Optional)</label>
					</th>
				<td class="forminp forminp-text">
					<?php echo $this->get_template_keys(); ?>
				
				&nbsp;
				<span><a style='text-decoration:none;' href='admin.php?page=create_template'>Create a New Template</a></span>
				</td>


				</tr>

				
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="message">Message</label>
					</th>
				<td class="forminp forminp-text">
					<?php wp_editor( $options['pw_fed_intro'], 'pw_fed_intro', array('textarea_name' => 'pw_fed_intro', 'media_buttons' => true) ); ?>                      </td>
					
					<td style='min-width:320px; vertical-align:top;'>
					<div class="panel-editor  panel-default-editor ">
					<div class="panel-heading-editor" ><h4 style='text-align:center'>Shortcodes</h4></div>
				<div class="panel-body-editor ">
					<h4 style='text-align:center; display:inline;'>Your First Name:</h4>&nbsp;<span >{{first_name}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Your Last Name:</h4>&nbsp;<span >{{last_name}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Your Username:</h4>&nbsp;<span >{{username}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Your Email Address:</h4>&nbsp;<span >{{email}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Your Website Url:</h4>&nbsp;<span >{{urwebsite}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>This Website Url:</h4>&nbsp;<span >{{website}}</span>
					<br/>
					<br/>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Recipient First Name:</h4>&nbsp;<span >{{recv_first_name}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Recipient Last Name:</h4>&nbsp;<span >{{recv_last_name}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Recipient Email Address:</h4>&nbsp;<span >{{recv_email}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Recipient Username:</h4>&nbsp;<span >{{recv_username}}</span>
					<br/>
					<br/>
					<h4 style='text-align:center; display:inline;'>Recipient Website Url:</h4>&nbsp;<span >{{recv_website}}</span>
					
				</div>
					</div>
				</div>
					</td>
				</tr>

								<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="message"></label>
					</th>
				<td class="forminp forminp-text">
				<label for='template_attachment'>Send Template as PDF (Checking the box will send template as PDF attachment)</label>
				<input  id='template_attachment' name="template_attachment" type="checkbox"/>
				
				<br/>
				<br/>
				
				<div class="row files" id="files1">
				<h2>Attachments</h2>
				<p>(<strong>Note:</strong> The attachments size is limited by the SMTP server. Attachments exceeding the size limit will be rejected by SMTP server. It is advised to check maximum allowable attachment size by your SMTP server. If you are not sure, then keep attachment size within 10 MB to ensure maximum size compatability)</p>
				<br/>
				<span class="btn btn-default btn-file">
                    Upload <input type="file" name="files1" multiple />
                </span>
                <br />
                <ul class="fileList"></ul>
			</div>
				<label for="language">Language:&nbsp; </label>
				<select name='language' id='language'>
					<option value="volvo">Volvo</option>
					<option value="saab">Saab</option>
					<option value="mercedes">Mercedes</option>
					<option value="audi">Audi</option>
					<option value="audi">Audi</option>
				</select>
				<br/>
				<br/>
				<br/>
				<br/>
				<input name="send" class="button-primary" type="submit" value="Send">
				<input name="save" class="button-secondary" type="submit" value="Save as Draft">
				<input name="reset" type="button"  class="button-secondary" id="reset" value="Reset">
				</tr>

			
			</tbody></table>				
</form>


</div></div></div>
		<?php
	}


	public function add_headers()
	{
		
		echo $this->script;
	}

	public function get_template_keys()
	{	
		global $wpdb;
		$output=array();
		$table=$wpdb->prefix."usermeta";
		$query="SELECT meta_key,meta_value from {$table} Where user_id=".get_current_user_id()." and meta_key LIKE 'easy_email_templates%'";
		$result = $wpdb->get_results($query);
		$content="<select id='easy_all_templates' name='templates'><option value='none'>None</option>";
		$this->script="<ul style='display:none'>";
		foreach ($result as $template) {
           
			$output=unserialize($template->meta_value);
			$content.="<option value='".$template->meta_key."'>".$output['name']."</option>";
			$this->script.="<li id='".$template->meta_key."'>".$output['content']."</li>";
		
		}
		
			
			$this->script.=" </ul>
			<script>
			jQuery('#easy_all_templates').change(function(){
			    var optionSelected = $('option:selected', this);
					var valueSelected = this.value;
					if(valueSelected!='none')
					{ content=jQuery(\"#\"+valueSelected);
						//console.log(content.parent().html());
						jQuery('#pw_fed_intro').val(content.html());
						tinyMCE.activeEditor.setContent(content.html());
						//tinymce.activeEditor.execCommand('mceInsertContent', true, content.html());
						//$('#'+tinymce.get('your_editor_id')+'_ifr').attr('src', 'blank.html');
					}	
			});

			jQuery('#reset').click(function(){
				window.location.reload();
			});


			
			</script>";

			$this->script.='	<script>$.fn.fileUploader = function (filesToUpload, sectionIdentifier) {
				var fileIdCounter = 0;
			
				this.closest(".files").change(function (evt) {
					var output = [];
			
					for (var i = 0; i < evt.target.files.length; i++) {
						fileIdCounter++;
						var file = evt.target.files[i];
						var fileId = sectionIdentifier + fileIdCounter;
			
						filesToUpload.push({
							id: fileId,
							file: file
						});
			
						var removeLink = "<a class=\"removeFile\" href=\"#\" data-fileid=\"" + fileId + "\">Remove</a>";
			
						output.push("<li><strong>", escape(file.name), "</strong> - ", file.size, " bytes. &nbsp; &nbsp; ", removeLink, "</li> ");
					};
			
					$(this).children(".fileList")
						.append(output.join(""));
			
					//reset the input to null - nice little chrome bug!
					evt.target.value = null;
				});
			
				$(this).on("click", ".removeFile", function (e) {
					e.preventDefault();
			
					var fileId = $(this).parent().children("a").data("fileid");
			
					// loop through the files array and check if the name of that file matches FileName
					// and get the index of the match
					for (var i = 0; i < filesToUpload.length; ++i) {
						if (filesToUpload[i].id === fileId)
							filesToUpload.splice(i, 1);
					}
			
					$(this).parent().remove();
				});
			
				this.clear = function () {
					for (var i = 0; i < filesToUpload.length; ++i) {
						if (filesToUpload[i].id.indexOf(sectionIdentifier) >= 0)
							filesToUpload.splice(i, 1);
					}
			
					$(this).children(".fileList").empty();
				}
			
				return this;
			};
			
			(function () {
				var filesToUpload = [];
			
				var files1Uploader = $("#files1").fileUploader(filesToUpload, "files1");
				var files2Uploader = $("#files2").fileUploader(filesToUpload, "files2");
				var files3Uploader = $("#files3").fileUploader(filesToUpload, "files3");
			
				$("#uploadBtn").click(function (e) {
					e.preventDefault();
			
					var formData = new FormData();
			
					for (var i = 0, len = filesToUpload.length; i < len; i++) {
						formData.append("files", filesToUpload[i].file);
					}
			
					$.ajax({
						url: "http://requestb.in/1k0dxvs1",
						data: formData,
						processData: false,
						contentType: false,
						type: "POST",
						success: function (data) {
							alert("DONE");
			
							files1Uploader.clear();
							files2Uploader.clear();
							files3Uploader.clear();
						},
						error: function (data) {
							alert("ERROR - " + data.responseText);
						}
					});
				});
			})()</script>';
			$content.="</select>";
			add_action('admin_footer',[$this,'add_headers']);
		   return $content;
	}


	public function get_template()
	{	$template_id=$_REQUEST['template_id'];
		global $wpdb;
		$output=array();
		$table=$wpdb->prefix."usermeta";
		$query="SELECT meta_key,meta_value from {$table} Where user_id=".get_current_user_id()." and meta_key LIKE 'easy_email_templates%'";
		$result = $wpdb->get_results($query);
		$content="<select id='all_templates' name='templates'><option value='none'>None</option>";


	}
	




}
?>