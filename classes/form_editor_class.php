<?php

require_once ABSPATH . 'wp-load.php';


class FORM_EDITOR {

	public function __construct() {

  }

  public function editor()
  {
  ?>
   <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/css/demo.css">
  <link rel="stylesheet" type="text/css" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" media="screen" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.1/jquery.rateyo.min.css">


  <style>
      .icon-file.input-control.input-control-10.ui-sortable-handle
      {
        display:none;
      }
      </style>
 <div style="background-color:white; position:absolute; right:0px; left:0px; top:0px; bottom:0px;"></div>
  <div style="width:100%; background-color:black; color:white; font-size:25px; text-align:center;">WP Easy Email Form Builder</div>
  <div class="content" style="margin-top:0px; padding:20px;">
      
      <div class="header" style="position:relative; z-index:3;">
      <h3 style="margin-top:0px; padding-top:0px; bottom:80px; position:relative;">Wp Easy Email Form Builder</h3>
    <div><label>Form Name</label> <input id="form_name" type="text" style="width:100%" placeholder="Form Name" required="required" name="template_name" /></div><br/>
    <div><label>Email Subject</label> <input id="subject" type="text" style="width:100%" placeholder="Subject" required="required" name="email_subject" /></div><br/>
    <div><label>Email To</label> <input id="email" type="email" style="width:100%" placeholder="Email address to whom form will be emailed" required="required" name="email_to" /></div> <br/>
      </div>

    
    <div id="build-wrap" class="build-wrap"></div>
    <form class="render-wrap" style="display:none;"></form>
    <button id="edit-form">Edit Form</button>
    <div class="action-buttons" style="visibility:hidden;">

      <h2>Set Language</h2>
      <select id="setLanguage">
        <option value="ar-TN" dir="rtl">تونسي</option>
        <option value="de-DE">Deutsch</option>
        <option value="en-US">English</option>
        <option value="es-ES">español</option>
        <option value="fa-IR" dir="rtl">فارسى</option>
        <option value="fr-FR">français</option>
        <option value="nb-NO">norsk</option>
        <option value="nl-NL">Nederlands</option>
        <option value="pt-BR">português</option>
        <option value="ro-RO">român</option>
        <option value="ru-RU">Русский язык</option>
        <option value="tr-TR">Türkçe</option>
        <option value="vi-VN">tiếng việt</option>
        <option value="zh-TW">台語</option>
      </select>
    </div>
  </div>
  <div id="renderer" style="display:none;"></div>
  <script src="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/js/vendor.js"></script>
  <script src="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/js/form-builder.min.js"></script>
  <script src="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/js/form-render.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.1/jquery.rateyo.min.js"></script>
  <script src="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/js/editor.js"></script>
 
  <script>

var fbEditor = document.getElementById('build-wrap');
var formBuilder2;

$(fbEditor).formBuilder().promise.then(formBuilder => {
  formBuilder.actions.setData(formData);
  formBuilder2=formBuilder;
});
var formData='[{\"type\":\"header\",\"subtype\":\"h2\",\"label\":\"My Form\"},{\"type\":\"text\",\"label\":\"First Name\",\"name\":\"text-1536559959591\",\"subtype\":\"text\",\"className\":\"red form-control\"},{\"type\":\"select\",\"label\":\"Profession\",\"className\":\"form-control\",\"name\":\"select-1536559959596\",\"values\":[{\"label\":\"Street Sweeper\",\"value\":\"option-2\"},{\"label\":\"Brain Surgeon\",\"value\":\"option-3\"}]},{\"type\":\"textarea\",\"label\":\"Short Bio:\",\"className\":\"form-control\",\"name\":\"textarea-1536559959620\",\"subtype\":\"textarea\"}]'


    </script>
  <script>

    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    jQuery("document").ready(function()
    {
      jQuery(".btn.btn-primary.save-template").replaceWith('<button type="button" id="save" class="btn btn-primary save-template">Save</button>');

      jQuery("body").on("click","#save",function(){
       
        var form_name=jQuery("#form_name").val();
        var form_email=jQuery("#email").val();
        var subject=jQuery("#subject").val();

        if(form_name=='')
        {
          alert("Please Write Down Form Name.");
          return;
        }

        if(form_email=="")
        {
          alert("Please Write Down Recipient's Email.");
          return;
        }
        
        if(subject=="")
        {
          alert("Please Write Down Email Subject.");
          return;
        }
                var formData = formBuilder2.actions.getData('json');
        $('.render-wrap').formRender({
        formData: formData,

      });

      var form=$('.render-wrap').html();
      var styles="";

      jQuery(".formBuilder-injected-style").each(function()
    {
     styles+="<style>"+jQuery(this).html()+"</style>";
    });
      console.log(form+styles);
        var data={
          "action":"easy_email_form_save_call_back",
          "form":'<div class="wp-easy-email">'+form+"</div>"+styles,
          'form_name':form_name,
          'form_email':form_email,
          'form_json':formData,
          'subject':subject,
        }

        jQuery("#save").html('Saving...');
        jQuery("#save").attr('disabled','disabled');

        jQuery.post(ajaxurl, data, function(response) {
          $data=JSON.parse(response);
          if($data['status']=="Saved")
          {
            jQuery(".content").html("<div class='header' style='position:relative; z-index:3;'><div><strong>Form Saved.</strong></div><br/><div><strong>Use Following Shortcode to display the form</strong> [easy_email_form id="+$data['id']+"]</div></div>");
            jQuery("#save").html('Save');
             jQuery("#save").removeAttr("disabled");
          }
          else
          {
            alert($data['status']);
            jQuery("#save").html('Save');
             jQuery("#save").removeAttr("disabled");
          }
        });




      });

    });
   
    </script>
<?php
  }

  public function reeditor()
  { 
    global $wpdb;
    $id=$_REQUEST['id'];
    $table=$wpdb->prefix."easy_forms";
    $email_to="";
    $subject="";
    $form_data="";
    $form_name="";

    $result = $wpdb->get_results("SELECT * FROM $table where id=$id");
    foreach($result as $row)
    {
      $email_to=$row->form_email_to;
      $subject=$row->subject;
      $form_data=$row->form_json;
      $form_name=$row->form_name;
    }

    
    ?>
       <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/css/demo.css">
  <link rel="stylesheet" type="text/css" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" media="screen" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.1/jquery.rateyo.min.css">



  <style>
      .icon-file.input-control.input-control-10.ui-sortable-handle
      {
        display:none;
      }
      </style>
 <div style="background-color:white; position:absolute; right:0px; left:0px; top:0px; bottom:0px;"></div>
  <div style="width:100%; background-color:black; color:white; font-size:25px; text-align:center;">WP Easy Email Form Builder</div>
  <div class="content" style="margin-top:0px; padding:20px;">

<div style="position:relative; z-index:3;">
<h3 style="margin-top:0px; padding-top:0px; bottom:80px; position:relative;">Wp Easy Email Form Builder</h3>
    <div><label>Form Name</label> <input id="form_name" type="text" style="width:100%" placeholder="Form Name" required="required" name="template_name" value="<?php echo $form_name; ?>"/></div><br/>
    <div><label>Email Subject</label> <input id="subject" type="text" style="width:100%" placeholder="Subject" required="required" name="email_subject" value="<?php echo $subject; ?>"/></div><br/>
    <div><label>Email To</label> <input id="email" type="email" style="width:100%" placeholder="Email address to whom form will be emailed" required="required" name="email_to" value="<?php echo $email_to; ?>" /></div> <br/>
    </div>

    
    <div id="build-wrap" class="build-wrap"></div>
    <form class="render-wrap" style="display:none;"></form>
    <button id="edit-form">Edit Form</button>
    <div class="action-buttons" style="visibility:hidden;">

      <h2>Set Language</h2>
      <select id="setLanguage">
        <option value="ar-TN" dir="rtl">تونسي</option>
        <option value="de-DE">Deutsch</option>
        <option value="en-US">English</option>
        <option value="es-ES">español</option>
        <option value="fa-IR" dir="rtl">فارسى</option>
        <option value="fr-FR">français</option>
        <option value="nb-NO">norsk</option>
        <option value="nl-NL">Nederlands</option>
        <option value="pt-BR">português</option>
        <option value="ro-RO">român</option>
        <option value="ru-RU">Русский язык</option>
        <option value="tr-TR">Türkçe</option>
        <option value="vi-VN">tiếng việt</option>
        <option value="zh-TW">台語</option>
      </select>
    </div>
  </div>
  <div id="renderer" style="display:none;"></div>
  <script src="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/js/vendor.js"></script>
  <script src="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/js/form-builder.min.js"></script>
  <script src="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/js/form-render.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.1/jquery.rateyo.min.js"></script>
  <script src="<?php echo plugin_dir_url(__FILE__)?>/form_builder/assets/js/editor.js"></script>




    <script>


var fbEditor = document.getElementById('build-wrap');
var formBuilder2;
$(fbEditor).formBuilder().promise.then(formBuilder => {
  formBuilder.actions.setData(formData);
  formBuilder2=formBuilder;
});
var formData = '<?php echo stripslashes($form_data) ?>';



 

    </script>



  <script>

    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    jQuery("document").ready(function()
    {
      jQuery(".btn.btn-primary.save-template").replaceWith('<button type="button" id="save" style="display:block;" class="btn btn-primary save-template">Save</button>');

      jQuery("body").on("click","#save",function(){

        var form_name=jQuery("#form_name").val();
        var form_email=jQuery("#email").val();
        var subject=jQuery("#subject").val();

        if(form_name=='')
        {
          alert("Please Write Down Form Name.");
          return;
        }

        if(form_email=="")
        {
          alert("Please Write Down Recipient's Email.");
          return;
        }
        
        if(subject=="")
        {
          alert("Please Write Down Email Subject.");
          return;
        }
        var formData = formBuilder2.actions.getData('json');
        $('.render-wrap').formRender({
        formData: formData,

      });

      var form=$('.render-wrap').html();
      var styles="";

      jQuery(".formBuilder-injected-style").each(function()
    {
     styles+="<style>"+jQuery(this).html()+"</style>";
    });
      console.log(form+styles);
        var data={
          "action":"easy_email_form_save_call_back",
          "form":'<div class="wp-easy-email">'+form+"</div>"+styles,
          'form_name':form_name,
          'form_email':form_email,
          'form_json':formData,
          'subject':subject,
          'id':'<?php echo $id ?>'
        }
        jQuery("#save").html('Saving...');
        jQuery("#save").attr('disabled','disabled');

        jQuery.post(ajaxurl, data, function(response) {
          $data=JSON.parse(response);
          if($data['status']=="Saved")
          {
            jQuery(".content").html("<div class='header' style='position:relative; z-index:3;'><div><strong>Form Saved.</strong></div><br/><div><strong>Use Following Shortcode to display the form</strong> [easy_email_form id="+$data['id']+"]</div></div>");
            jQuery("#save").html('Save');
             jQuery("#save").removeAttr("disabled");
          }
          else
          {
            alert($data['status']);
            jQuery("#save").html('Save');
             jQuery("#save").removeAttr("disabled");
          }
        });




      });

    });
   
    </script>
    <?php
  }
  
}
?>




