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
	public $default_name="";
	public $default_email="";
	public $phpmailer=true;
	public function __construct() {
		
		
		//add_action( 'wp_ajax_editor_image_handler', [$this,'editor_image_handler'] );
		//add_action( 'admin_init', 'pw_settings_init' );

		add_action( 'wp_ajax_new_email_call_back', [$this,'new_email_call_back'] );
		
	}




	public function email_form($id,$render_id,$to,$all_users_check,$all_customer_check,$subject,$body,$pdf,$attachments,$language,$respond_id)
	{
		?>

			<div class="wrap">
			<div class="panel-editor  panel-default-editor ">
    <div class="panel-heading-editor "><h3>WP EASY EMAIL</h3></div>
		<div class="panel-body-editor ">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">





<table class="form-table">
<input type="hidden" id="id" name="id" value='<?php echo $id ?>' />
<input type="hidden" id="render" name="id" value='<?php echo $render_id ?>' />
<input type="hidden" id="respond_id" name="respond_id" value='<?php echo $respond_id ?>' />
<tbody><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="email_to">To</label>
					</th>
				<td class="forminp forminp-text">
					<?php if($to!=null)
						echo '<input name="email_to" id="email_to" type="text" style="" value="'.$to.'" class="regular-text">';
						else
						echo '<input name="email_to" id="email_to" type="text" style="" value="" class="regular-text">';
					?>

					
					<br/>
					<br/>
					<label for="all_users">All Users &nbsp;</label>
					<?php if($all_users_check)
					echo '	<input type="checkbox" value="yes" id="all_users" name="all_users" checked/>';
					else
					echo '	<input type="checkbox" value="yes" id="all_users" name="all_users"/>';
					?>

					<label for="all_customers">All Customers &nbsp;</label>

					<?php if($all_customer_check)
					echo '<input type="checkbox" value="yes" id="all_customers" name="all_customers" checked/></td>';
					else
					echo '<input type="checkbox" value="yes" id="all_customers" name="all_customers"/></td>';
					?>


					<td style=" position:relative; style='min-width:320px; vertical-align:top;">


					</td>

				</tr>

		

				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="subject">Subject</label>
					</th>
				<td class="forminp forminp-text">
					<?php if($subject!=null) 
					 echo '<input name="subject" id="subject" type="text" style="" value="'.$subject.'" class="regular-text"> ';
					 else
					 echo '<input name="subject" id="subject" type="text" style="" value="" class="regular-text"> ';
					?>

				  </td>
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

				<tr valign="top">
					<th scope="row" class="titledesc">
					<label>Use Recipient Shortcodes</label>
					</th>
				<td class="forminp forminp-text">
					<input type="checkbox" id="recipient_shortcode" name="recipient_shortcode" value="yes"/> &nbsp; <span>It is required if you are using recipient shortcodes as it tells the email client to compose different emails for each recipient.</span>
				</td>
				<tr/>

				<tr valign="top">
					<th scope="row" class="titledesc">
						<label>Message</label>
					</th>
				<td class="forminp forminp-text">
				<button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#myModal">Shortcodes</button>
				</td>
				<tr/>
	</tbody>
	</table>
	<div>
			<?php 
					if($body!=null)
					//wp_editor(stripslashes($body), 'pw_fed_intro', array('textarea_name' => 'pw_fed_intro', 'media_buttons' => true) ); 
					echo '<div  id="summernote">'.$body.'</div>';
					else
					echo '<div id="summernote"></div>';
					?>    
			</div>
	<table>
	</tbody>

								<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="message"></label>
					</th>
				<td class="forminp forminp-text">
				<!--<label for='template_attachment'>Send Template as PDF (Checking the box will send template as PDF attachment)</label>
				<input  id='template_attachment' name="template_attachment" type="checkbox"/>-->
				
				<?php 
				if($attachments)
				$attached_files=explode(',',$attachments);
				if($attached_files)
				{		$count=0;				
						echo '
								<br/>
								<br/>
									<div style="text-align:left;"><h4>Previously Attached Files</h4>';
					foreach($attached_files as $file)
				{	if($file!="")
					echo '<p>'.$file.'<input type="hidden" name="attachfile'.$count.'"  value="'.$file.'"/>&nbsp;<small class="remover" style="color:red; cursor:pointer;">Remove</small></p>';
					else
					{
						echo '<p style="font-style:italic">No files</p>';

					}
				}
				 echo '	</div>';
				}
		?>
		<br/>
				<br/>
				<div class="row files" id="files1">
				<h2>Attachments</h2>
				<p>(<strong>Note:</strong> The attachments size is limited by the SMTP server. Attachments exceeding the size limit will be rejected by SMTP server. It is advised to check maximum allowed attachment size by your SMTP server. If you are not sure, then keep attachment size within 10 MB to ensure maximum size compatability)
				Your server currently allows <?php echo ini_get('post_max_size') ?>. You can change that by setting "post_max_size" directive in your php.ini file.
				</p>
				<br/>
				<!--<span class="btn-file">
				<button class="btn btn-info" type="button"><label for="file">Select file</label></button>
                    <input type="file" id="file" style="display:none;" name="file" multiple="multiple" />
				</span>-->
				<div>
					<input type="button" id="get_file" style="padding:5px;" value="Add Attachment">
					<input type="file" name="myfile" id="my_file">

				</div>
				<br />
				<ul id="filelist" style="list-style:none; padding-left:0px; width:60%;">
					
			</ul>
                <!--<ul class="fileList"></ul>-->
			</div>
			<br />

				<label for="language">Language:&nbsp; </label>
				<select name='language' id='language'>
					<option value="am">Armenian</option>
					<option value="ar">Arabic</option>
					<option value="az">Azerbaijani</option>

					<option value="ba">Bosnian</option>
					<option value="be">Belarusian</option>
					<option value="bg">Bulgarian</option>
					<option value="pt_br">Brazilian Portugese</option>

					<option value="ca">Catalan</option>
					<option value="ch">Chinese</option>
					<option value="cs">Czech</option>
					<option value="hr">Coratian</option>

					<option value="da">Danish</option>
					<option value="nb">Dutch</option>

					<option value="eo">Esperanto</option>
					<option value="nl">Estonain</option>
					<option value="en" selected>English</option>

					<option value="fi">Finnish</option>
					<option value="fo">Faroese</option>
					<option value="fr">French</option>
					

					<option value="de">German</option>
					<option value="el">Greek</option>
					<option value="gl">Galician</option>
					<option value="ka">Georgian</option>


					<option value="he">Hebrew</option>
					<option value="hi">Hindi</option>
					<option value="hu">Hungarian</option>

					<option value="id">Indonesian</option>
					<option value="it">Italian</option>

					<option value="ja">Japanese</option>

					<option value="ko">Korean</option>

					<option value="lt">Lithuanian</option>
					<option value="lv">Latvian</option>
					
					<option value="ms">Malaysian</option>

					<option value="nb">Norwegian</option>

					
					<option value="pl">Polish</option>
					<option value="pt">Portugese</option>
					<option value="fa">Persian</option>
					
					<option value="ro">Romanian</option>
					<option value="ru">Russian</option>

					<option value="es">Spanish</option>
					<option value="rs">Serbian</option>
					<option value="sk">Slovak</option>
					<option value="sl">Slovene</option>
					<option value="sv">Swedish</option>
					<option value="zh_cn">Simplified Chinese</option>

					<option value="tl">Tagalog</option>
					<option value="tr">Turkish</option>
					<option value="zh">Traditional Chinese</option>

					<option value="uk">Ukranian</option>

					<option value="vi">Vietnamese</option>
					


					
					
					
				</select>
				<br/>
				<br/>
				<br/>
				<br/>
				<button id="send" class="form_button button-primary" type="button" >Send</button>
				<button id="save" class="form_button button-secondary" type="button" >Save as Draft</button>
				<input name="reset" type="button"  class="form_button button-secondary" id="reset" value="Reset">
				<br/>
				<br/>
				<p style="font-style:italic; display:none;" id="notification"></p>
				<p style="font-style:italic; display:none;" id="attachment_notification"></p>
				</tr>
			

			
			</tbody></table>	

</form>


</div></div></div>


  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog">
    
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Shortcodes List</h4>
        </div>
        <div class="modal-body">
		<div style="position:relative;">
					<div class="panel-editor  panel-default-editor ">
					<div class="panel-heading-editor" ><h4 style='text-align:center'>Shortcodes</h4></div>
				<div class="panel-body-editor ">
					<div class="well">
					<span style='text-align:center; display:inline;'>Your First Name:</span>&nbsp;<span >{{first_name}}</span>
					<br/>
					<br/>
					<span  style='text-align:center; display:inline;'>Your Last Name:</span>&nbsp;<span >{{last_name}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>Your Username:</span>&nbsp;<span >{{username}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>Your Email Address:</span>&nbsp;<span >{{email}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>Your Website Url:</span>&nbsp;<span >{{ur_site_url}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>This Website Name:</span>&nbsp;<span >{{site}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>This Website Url:</span>&nbsp;<span >{{site_url}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>Default Body Text:</span>&nbsp;<span >{{default_text}}</span>
					<br/>
					<br/>
					</div>


					<div class="well"><strong>Following are recipient shortcodes, please check "Use Recipient Shortcodes" to enable them.</strong>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>Recipient First Name:</span>&nbsp;<span >{{recv_first_name}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>Recipient Last Name:</span>&nbsp;<span >{{recv_last_name}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>Recipient Email Address:</span>&nbsp;<span >{{recv_email}}</span>
					<br/>
					<br/>
					<span style='text-align:center; display:inline;'>Recipient Username:</span>&nbsp;<span >{{recv_username}}</span>

					</div>
				</div>
					</div>
				</div>

				</div>
        </div>
        <div class="modal-footer">
        
        </div>
      </div>
      
    </div>


<link href="https://netdna.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.css" rel="stylesheet">
 
  <script src="https://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.js"></script> 
  <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.9/summernote.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.9/summernote.js"></script>


  <style>
	  
	  .bar_container
	  {	  
		  height:15px;
		  border-radius:10px;
		  border-style:solid;
		  border-width:1px;
		  border-color:rgba(0,0,0,0.1);
		  padding:0px;
	  }

	  .progressbar
	  {	 
		  height:15px;
		  border-radius:10px;
		  background-color:rgba(0,189,143,0.8);
		  
	  }
	  #my_file {
    display: none;
	}

	#get_file {
		background: #f9f9f9;
		border: 5px solid #88c;
		padding: 15px;
		border-radius: 5px;
		margin: 10px;
		cursor: pointer;
	}

	  .note-btn.btn.btn-default.btn-sm.btn-fullscreen,.note-btn.btn.btn-default.btn-sm.btn-codeview
	  {
		  display:none;
	  }
	  .modal
	  {
		  width:100% !important;
		  max-width:100% !important;
		  border:none !important;
		  background:transparent !important;
	  }
	  .note-editing-area,.note-editable
	  {
		  min-height:500px;
	  }

	  .error
	  {
		  display:none;
	  }

	  .row
	  {
		  margin-left:0px !important;
		  margin-right:0px !important;
	  }
	  </style>
<script>





	</script>

	<script>
		var item=0;
		var files=0;
		var uploading_content=0;
		var xhr_array=new Array();

		jQuery("body").on("click",".attachment_remove",function(){

			var id=jQuery(this).prev().children().eq(0).attr("id");
			jQuery(this).parent().parent().parent().parent().remove();
			jQuery("."+id).remove();
			//console.log(xhr_array);
			item--;
			files--;
			xhr_array["item"+item].abort();
			uploading_content--;
			//XHR.abort();
		});
		
		jQuery("#my_file").change(function(){
			uploading_content++;
			var file=jQuery("#my_file").prop("files");
			//console.log(file[0]);
			jQuery("#filelist").append('\
			<li><div class="row"><div class="col-md-3" style="word-wrap: break-word;">'+file[0].name+' ('+file[0].size+'KB )</div><div class="col-md-9"><div class="row"><div class="col-xs-9 bar_container"><div style="width:0px;" id="item'+item+'" class="progressbar"></div></div><div  class="col-xs-1 attachment_remove glyphicon glyphicon-remove" style="cursor:pointer;"></div></div></div></div></li>\
				');
			
			var item_number="item"+item;
			console.log(item_number);
			item++;

			var fd = new FormData();
			var fileInputElement = document.getElementById("my_file");
			fd.append("item_number",item_number);
			fd.append("file", fileInputElement.files[0]);

		//	fd.append("file", file);
			fd.append("action", "attachment_handler");
			
			var xhr = new XMLHttpRequest();
			var obj={};
			xhr_array[item_number]=xhr;
			//xhr_array.push(obj);
			//console.log(xhr_array);
			xhr.open("POST", ajaxurl, true);
			//xhr.setRequestHeader("Content-Type", "multipart/form-data");
		//	xhr.setRequestHeader("X-File-Name", file[0].name);
			//xhr.setRequestHeader("X-File-Type", file[0].name);

			xhr.upload.onprogress = function(e)
			{
				
			var percentComplete = Math.ceil((e.loaded / e.total) * 100);
			console.log(percentComplete );
			//$("#progress").css("display","");

			//$("#progressText").text((loopGallery+1)+" z "+cList);
			jQuery("#"+item_number).css("width",percentComplete+"%");

			};

			xhr.onload = function(oEvent) {
				if (xhr.status == 200) {
					var data=JSON.parse(xhr.responseText);
					jQuery("#mainform").append("<input type='hidden' name='files"+files+"' value='"+data.attachment+"' class='"+data.item_number+"' /> ");
					files++;
					
				}
				uploading_content--;
			};
			xhr.send(fd);


		});
		document.getElementById('get_file').onclick = function() {
			document.getElementById('my_file').click();
		};

		jQuery(document).ready(function() {
			jQuery('#summernote').summernote();
		
		});

	     jQuery(".remover").click(function(){
			jQuery(this).parent().remove();
		});

	</script>

		<script>

		function addImages(id)
			{


			var files= $("#files").prop("files");
			var file = files[loopGallery];
			var cList= files.length;

			var fd = new FormData();

			fd.append("file", file);
			fd.append("galerie", id);


			var xhr = new XMLHttpRequest();

			xhr.open("POST", "<?php echo plugin_dir_url(__FILE__)?>../attachment_handler.php", true);

			xhr.upload.onprogress = function(e)
			{

			var percentComplete = Math.ceil((e.loaded / e.total) * 100);

			$("#progress").css("display","");

			$("#progressText").text((loopGallery+1)+" z "+cList);
			$("#progressBar").css("width",percentComplete+"%");

			};


			xhr.onload = function()
			{

			if(this.status == 200)
			{
				$("#progressObsah").load("moduls/galerie/showimages.php?ids="+id);

				if((loopGallery+1) == cList)
				{
					loopGallery = 0;

				}
				else
				{
				$("#progressBar").css("width", "0%");   
			loopGallery++;
			addImages(id);
			}

			};

			};

			if(cList < 1)
			{

			}
			else
			{
			xhr.send(fd);
			}


			}
			

	
				jQuery("#save").click(function(e){
 
					e.preventDefault();

					if(uploading_content>0)
					{line="Attachments are being uploaded. Please wait while attachments are uploaded.";
						jQuery("#attachment_notification").html(line);
							jQuery("#attachment_notification").fadeIn().delay(3000).fadeOut();
					 return}

					//jQuery("#pw_fed_intro-html").trigger("click");
					jQuery(this).html("Saving....");
					jQuery(".form_button").attr("disabled","disabled");

					var form = jQuery('#mainform').find("input").not("input[name=myfile]");
					console.log(form);
					var formData = new FormData(form);
					form.each(function(){
						if(!jQuery(this).attr("name") )
							return
						formData.append(jQuery(this).attr("name"),jQuery(this).val());
					});
						
						
					var body=jQuery('div[class=\"note-editable\"]').html();
					formData.append("pw_fed_intro",body);

					formData.append("action","new_email_call_back");
					formData.append("type","save");
					jQuery.ajax({
						url: ajaxurl,
						data: formData	,
						type: 'POST',
						contentType: false, 
						processData: false,
						success:function(response)
						{
							jQuery("#save").html("Save as Draft");
							jQuery(".form_button").removeAttr("disabled");
							var text=response.split("\n");
							var line=text[text.length-1];
							

							jQuery("#notification").html(line);
							jQuery("#notification").fadeIn().delay(3000).fadeOut();


						}

						});

				});

				jQuery("#send").click(function (e) {
					e.preventDefault();

					if(uploading_content>0)
					{	line="Attachments are being uploaded. Please wait while attachments are uploaded.";
						jQuery("#attachment_notification").html(line);
						jQuery("#attachment_notification").fadeIn().delay(3000).fadeOut();
					 return}
				
					//jQuery("#pw_fed_intro-html").trigger("click");
					jQuery(this).html("Sending....");
					jQuery(".form_button").attr("disabled","disabled");

					var form = jQuery('form')[0];

					var formData = new FormData(form);

					var body=jQuery('div[class=\"note-editable\"]').html();
					formData.append("pw_fed_intro",body);



					formData.append("action","new_email_call_back");
					formData.append("type","send");
					jQuery.ajax({
						url: ajaxurl,
						data: formData	,
						type: 'POST',
						timeout: 0,
						contentType: false, 
						processData: false,
						success:function(response)
						{
							jQuery("#send").html("Send");
							jQuery(".form_button").removeAttr("disabled");
							var text=response.split("\n");
							var line=text[text.length-1];
							

							jQuery("#notification").html(line);
							jQuery("#notification").fadeIn().delay(3000).fadeOut();


						}

						});


				});


		jQuery('#reset').click(function(){
			
			window.location.reload();


			});
		</script>

		<?php
	}

	public function new_email_call_back()
	{
		
		if($_POST['type']=="send")
		{	
			global $current_user;
			wp_get_current_user();
			$name=$current_user->user_login;

			$id=-1;
			$render_id=-1;
			$email_to=[];
			$all_users=false;
			$all_customers=false;
			$subject="";
			$body=" ";
			$language="en";
			$attachments=[];
			$attachment_string="";
			if(isset($_POST['email_to']))
			{
				$addresses=$_POST['email_to'];
				$email_to=explode(',',$addresses);


			}

			if(isset($_POST['id']))
			$id=(int)$_POST['id'];
			if(isset($_POST['render_id']))
			$render_id=(int)$_POST['render_id'];

			if(isset($_POST['all_users']) && $_POST['all_users']=="yes")
			$all_users=true;

			if(isset($_POST['all_customers']) && $_POST['all_customers']=="yes")
			$all_customers=true;

			if(isset($_POST['subject']) && $_POST['subject']!="")
			$subject=$_POST['subject'];
			else
			$subject="No Subject";

			if(isset($_POST['pw_fed_intro']) || $_POST['pw_fed_intro']!="")
			$body=$_POST['pw_fed_intro'];
			else
			$body=" ";

			if(isset($_POST['language']))
			$language=$_POST['language'];

			$respond_id=$_POST['respond_id'];

			$email_from=get_user_meta( $current_user->ID, 'easy_email_email_from',true);
			$email_from_name=get_user_meta($current_user->ID, 'easy_email_email_from_name',true);
			$hostname=get_user_meta( $current_user->ID, 'easy_email_hostname',true);
			$port=get_user_meta( $current_user->ID, 'easy_email_port',true);
			$security=get_user_meta( $current_user->ID, 'easy_email_security',true);
			$usernamer=get_user_meta( $current_user->ID, 'easy_email_username',true);
			$password=stripslashes(get_user_meta( $current_user->ID, 'easy_email_password',true));
			
			if($usernamer=="" || $usernamer==null)
				$authentication=false;
			else
				$authentication=true;
			
			if($security=="none")
			{
				$ssl=false;
				$tls=false;
			}
			else if($security=="ssl")
			{
				$ssl=true;
				$tls=false;
			}
			else if($security=="tls")
			{
				$ssl=false;
				$tls=true;
			}
			
			else
			{
				$ssl=false;
				$tls=false;
			}

			if($all_users)
			{	


				$users = get_users( [ 'role__in' => [ 'subscriber','contributer','author','editor','administrator','shop_manager' ] ] );
				
				foreach($users as $user)
				{
					array_push($email_to,$user->user_email);
				}
			}

			if($all_customers)
			{
				$users = get_users( [ 'role__in' => [ 'customer' ] ] );
				
				foreach($users as $user)
				{
					array_push($email_to,$user->user_email);
				}
			}
			/*if ($_FILES['files0']) {

				$directory=plugin_dir_path(__FILE__)."../attachments/".$name;
				$count=0;
				
				while(true)
				{	
					

					if(!$_FILES['files'.$count])
						break;

					
					$target=$directory;
					
					//if($FILES)
				
					$temp=$target;
					$tmp=$_FILES["files".$count]['tmp_name'];
					
					$file=pathinfo($_FILES["files".$count]['name']);
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
					$temp='';
					$tmp='';
					$count=$count + 1;
					
				}
				

			}*/
			

			if ($_POST['files0']) {

				
				$name=$current_user->user_login;
				$directory=plugin_dir_path(__FILE__)."../attachments/".$name;
				$count=0;
				
				while(true)
				{	
					
					
					if(!$_POST['files'.$count])
						break;

					
					$target=$directory;
					
					//if($FILES)
				
					$temp=$target;
					$temp=$temp."/".$_POST['files'.$count];
					array_push($attachments,$temp);
					if($attachment_string=="")
					$attachment_string=$_POST['files'.$count];
					else
					$attachment_string=$attachment_string.",".$_POST['files'.$count];

					$temp='';
					$tmp='';
					$count=$count + 1;
					//error_log(print_r($attachment_string,true));
					
				}

			}
			

			if ($_POST['attachfile0']) {
				$directory=plugin_dir_path(__FILE__)."../attachments/".$name;
				$count=0;
				while(true)
				{
					
					if(!$_POST['attachfile'.$count])
						break;
					
					array_push($attachments,$directory.'/'.$_POST['attachfile'.$count]);

					if($attachment_string=="")
					$attachment_string=$_POST['attachfile'.$count];
					else
					$attachment_string=$attachment_string.",".$_POST['attachfile'.$count];
					
					$count=$count+1;
				}
			}
			if(!$this->phpmailer)
			{
				if(! class_exists('EASY_SMTP'))
				require_once plugin_dir_path(__File__) . '/smtp_class.php';
				if($hostname!="")
				{   
					$smtp=new EASY_SMTP($hostname,(int)$port,$authentication,$usernamer,$password,$ssl,$tls,$email_from,$email_from_name);
					if($language!="en")
					$smtp->SetLanguage($language, plugin_dir_path(__FILE__).'classes/language/');
					
				}
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
				$mail->SMTPSecure = $security;
				$mail->ContentType = 'text/html';
				$mail->Port       = $port;
				$mail->From       = $email_from;
				$mail->FromName   = $email_from_name;
				$mail->SetLanguage($language, plugin_dir_path(__FILE__).'classes/language/');
			}
			

			
			$body=stripslashes($body);
			//$original_body=$body;
			$sitename=get_bloginfo( 'name' );
			$current_email = (string) $current_user->user_email;
			$current_username=$name;
			$current_website_url="<a href='".get_site_url()."'>".get_site_url()."</a>";
			$author_website_url="<a href='".$current_user->user_url."'>".$current_user->user_url."</a>";
			$author_first_name=$current_user->first_name;
			$author_last_name=$current_user->last_name;
			$default_text=get_usermeta(  $current_user->ID, "default_body_text",true );
			
			$string_to=array("{{first_name}}","{{last_name}}","{{username}}","{{email}}","{{site}}","{{ur_site_url}}","{{site_url}}","{{default_text}}");
			$string_with=array($author_first_name,$author_last_name,$current_username,$current_email,$sitename,$author_website_url,$current_website_url,$default_text);

			$rec_to=array("{{recv_first_name}}","{{recv_last_name}}","{{recv_email}}","{{recv_username}}");
			//echo $author_first_name;
			$body=str_replace($string_to,$string_with,$body);
			if($body=="")
			$body=" ";
			$original_body=$body;
			//$this->embed_images($body,$mail);
			$body=$mail->msgHTML($body);
			if($_POST['recipient_shortcode']=="yes")
			{	$total=0;
				$send=0;
				foreach($email_to as $address)
				{
					$total=$total+1;
					$user_data=get_user_by_email( $address );
					
					$user_meta = get_user_meta( $user_data->ID);

					$rec_with=array($user_meta["first_name"][0],$user_meta["last_name"][0],$address,$user_data->user_login);
					$body=str_replace($rec_to,$rec_with,$body);
					if(!$this->phpmailer){
						$smtp->mail->ClearAllRecipients();
						
						$smtp->send_email(array($address),$subject,$body,$attachments);
					}
					else
					{	$mail->clearAllRecipients();
						$mail->AddAddress($address."");
						$mail->Subject=$subject;
						$mail->Body=$body;
					
						foreach($attachments as $attachment)
						{
							$mail->addAttachment($attachment);
						}
						$info = $mail->Send();
						if($info){
							$send=$send+1;
							
						}
						else
						echo $mail->ErrorInfo;
						
					}

				}
				echo $send."/".$total." email(s) were sent.";
			}
			else {
			if($hostname!="")
			{	if(!$this->phpmailer)
					$smtp->send_email($email_to,$subject,$body,$attachments);
				else
					{
						error_log(print_r($attachments,true));

						foreach($email_to as $address){
							$mail->AddAddress($address."");
						}
						$mail->Subject=$subject;
						$mail->Body=$body;
						foreach($attachments as $attachment)
						{
							$mail->addAttachment($attachment);
						}
						$info = $mail->Send();
						if($info){
							echo "Message has been sent.";
							
						}
						else
							echo $mail->ErrorInfo;
					}
			}

			else
			{	$headers=[];
				if($name!="" && $email!="")
				{   $this->default_email=$email;
					$this->default_name=$name;
					add_filter( 'wp_mail_from_name', [$this,'my_mail_from_name']);

					add_filter( 'wp_mail_from', [$this,'my_mail_from']);
					 $headers[] = 'From: '.$name.' <'.$email.'>';
				}
					
					$status=wp_mail($email_to,$subject,$body,$headers,$attachments);
					if($status)
					echo "Message has been sent";
					else
					echo "An error occurred";	
					
					remove_filter( 'wp_mail_from_name', [$this,'my_mail_from_name']);

					remove_filter( 'wp_mail_from', [$this,'my_mail_from']);
			}

		}
			global $wpdb;
			$table=$wpdb->prefix."easy_inbox";
			$wpdb->insert($table, array(
				'user_id' => $current_user->ID,
				'uid' => 0,
				'subject' => $subject,
				'status'=>0,
				'responded'=>-1,
				'previous_box'=>'sent',
				'name'=>$email_from_name,
				'email_to'=>$addresses,
				'email_from'=>$email_from,
				'content'=>$original_body,
				'box'=>'sent',
				'attachment'=>$attachment_string

			));
			

			
			if($respond_id>0)
			{	
				$table=$wpdb->prefix."easy_inbox";
				$query=$wpdb->prepare("UPDATE $table SET responded = 1 WHERE id = %d",$respond_id);
				$wpdb->query( $query);
				
			}
			
			wp_die();

		}

		else if($_POST['type']=="save")
		{	
			global $current_user;
			wp_get_current_user();
			$name=$current_user->user_login;

			$id=-1;
			$render_id=-1;
			$all_users=false;
			$all_customers=false;
			$subject="";
			$body=" ";
			$language="en";
			$attachments=[];
			$addresses ="";
			$attachment_string="";

			if(isset($_POST['email_to']))
			{
				$addresses=$_POST['email_to'];
				


			}

			if(isset($_POST['id']))
			$id=(int)$_POST['id'];

			if(isset($_POST['render_id']))
			$render_id=(int)$_POST['render_id'];

			if(isset($_POST['all_users']) && $_POST['all_users']=="yes")
			$all_users=true;

			if(isset($_POST['all_customers']) && $_POST['all_customers']=="yes")
			$all_customers=true;

			if(isset($_POST['subject']) && $_POST['subject']!="")
			$subject=$_POST['subject'];
			else
			$subject="No Subject";

			if(isset($_POST['pw_fed_intro']) || $_POST['pw_fed_intro']!="")
			$body=$_POST['pw_fed_intro'];
			else
			$body=" ";

			if(isset($_POST['language']))
			$language=$_POST['language'];

			
			if($password=="" || $password==null)
				$authentication=false;
			else
				$authentication=true;
			
			if($security=="none")
			{
				$ssl=false;
				$tls=false;
			}
			else if($security=="ssl")
			{
				$ssl=true;
				$tls=false;
			}
			else if($security=="tls")
			{
				$ssl=false;
				$tls=true;
			}
			
			else
			{
				$ssl=false;
				$tls=false;
			}

			

			if ($_POST['files0']) {

				
				$name=$current_user->user_login;
				$directory=plugin_dir_path(__FILE__)."../attachments/".$name;
				$count=0;
				
				while(true)
				{	
					
					
					if(!$_POST['files'.$count])
						break;

					
					$target=$directory;
					
					//if($FILES)
				
					$temp=$target;
					//$temp=$temp.$_POST['files'.$count];
					if($attachment_string=="")
					$attachment_string=$_POST['files'.$count];
					else
					$attachment_string=$attachment_string.",".$_POST['files'.$count];

					$temp='';
					$tmp='';
					$count=$count + 1;
					//error_log(print_r($attachment_string,true));
					
				}

			}

			

			if ($_POST['attachfile0']) {
				$directory=plugin_dir_path(__FILE__)."../attachments/".$name;
				$count=0;
				while(true)
				{
					
					if(!$_POST['attachfile'.$count])
						break;
					
					array_push($attachments,$directory.'/'.$_POST['attachfile'.$count]);

					if($attachment_string=="")
					$attachment_string=$_POST['attachfile'.$count];
					else
					$attachment_string=$attachment_string.",".$_POST['attachfile'.$count];
					
					
					$count=$count+1;
				}
			}

			global $wpdb;
			if($render_id==-1)
			{	 
				$table=$wpdb->prefix."easy_inbox";
				$wpdb->insert($table, array(
					'user_id' => $current_user->ID,
					'uid' => 0,
					'subject' => $subject,
					'status'=>0,
					'responded'=>-1,
					'previous_box'=>'draft',
					'name'=>get_user_meta( get_current_user_id(), 'easy_email_email_from_name',true),
					'email_to'=>$addresses,
					'email_from'=>get_user_meta( get_current_user_id(), 'easy_email_email_from',true),
					'content'=>$body,
					'box'=>'draft',
					'attachment'=>$attachment_string,

				));
				echo "Saved";
			}

			else
			{	
				$table=$wpdb->prefix."easy_inbox";
				$query=$wpdb->prepare("UPDATE $table SET subject = %s,email_to=%s,content=%s,attachment=%s WHERE id = %d",$subject,$addresses,stripslashes($body),$attachment_string,$id);
				$wpdb->query( $query);
				echo "Saved";
			}

			
			wp_die();
		}




	}

	public function add_headers()
	{
		
		echo $this->script;
	}

	public function get_template_keys()
	{	
		global $wpdb;
		$output=array();
		$table=$wpdb->prefix."easy_email_templates";
		$query="SELECT * from {$table} Where user_id=".get_current_user_id();
		$result = $wpdb->get_results($query);
		$content="<select id='easy_all_templates' name='templates'><option value='none'>None</option>";
		$this->script="<ul style='display:none'>";

		foreach ($result as $row) {
           
			$content.="<option value='".$row->id."'>".$row->name."</option>";
			$this->script.="<li id='easy_template_".$row->id."'>".stripslashes($row->email_template)."</li>";
		
		}
		
			
			$this->script.=" </ul>
			<script>
			jQuery('#easy_all_templates').change(function(){
			    var optionSelected = jQuery('option:selected', this);
					var valueSelected = this.value;
					if(valueSelected!='none')
					{ content=jQuery(\"#easy_template_\"+valueSelected).html();
						jQuery('div[class=\"note-editable\"]').html(content);
					}	
					else
					{
						jQuery('div[class=\"note-editable\"]').html('');
					}
			});




			
			</script>";

			$this->script.='';
			$content.="</select>";
			add_action('admin_footer',[$this,'add_headers']);
		   return $content;
	}

	public function embed_images(&$body,&$mailer){
		// get all img tags
		preg_match_all('/<img[^>]*src="([^"]*)"/i', $body, $matches);
		if (!isset($matches[0])) return;
	
		foreach ($matches[0] as $index=>$img)
		{
			// make cid
			$id = 'img'.$index;
			if(stripos($matches[1][$index],"base64"))
			{	
				$src = $matches[1][$index];
				//error_log(print_r($src,true));
				$arr=explode(",",$src);
				$string=$arr[1];
				$type=explode(":",$arr[0]);
				$attachment=base64_decode($string);
				error_log(print_r($attachment,true));

			
				$mailer->addStringEmbeddedImage($attachment,$id,$id,"base64",$type,"inline");
				
				$body = str_replace($src,'cid:'.$id, $body);
				error_log(print_r($body,true));
			}
		}
	}

	public function my_mail_from_name( $name ) {
		return $this->default_name;
	}

	public function my_mail_from( $email ) {
		return $this->default_email;
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
