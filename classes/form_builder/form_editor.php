<?php

require_once '../../../../wp-load.php';

?>
<!DOCTYPE html>
<html>

<head>
  <link rel="stylesheet" type="text/css" href="assets/css/demo.css">
  <link rel="stylesheet" type="text/css" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" media="screen" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.1/jquery.rateyo.min.css">

  <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
  <title>WP Easy Email Form Builder</title>
  <style>
      .form-actions.btn-group *:last-child
      {
        display:none;
      }
      .icon-file.input-control 
      {
        display:none;
      }
      .icon-textarea.input-control.input-control-16.ui-sortable-handle,.input-control.input-control-14.ui-sortable-handle
      {
        display:none;
      }
      </style>
</head>

<body>
  <div style="width:100%; background-color:black; color:white; font-size:25px; text-align:center;">WP Easy Email Form Builder</div>
  <div class="content" style="margin-top:0px;">


    <div><label>Form Name</label> <input id="form_name" type="text" style="width:100%" placeholder="Form Name" required="required" name="template_name" /></div><br/>
    <div><label>Email Subject</label> <input id="subject" type="text" style="width:100%" placeholder="Subject" required="required" name="email_subject" /></div><br/>
    <div><label>Email To</label> <input id="email" type="email" style="width:100%" placeholder="Email address to whom form will be emailed" required="required" name="email_to" /></div> <br/>
      <input type="hidden" id="id" name="id" value=""/>

    
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
  <script src="assets/js/vendor.js"></script>
  <script src="assets/js/form-builder.min.js"></script>
  <script src="assets/js/form-render.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.1/jquery.rateyo.min.js"></script>
  <script src="assets/js/editor.js"></script>
  <script>


var fbEditor = document.getElementById('build-wrap');
$(fbEditor).formBuilder().promise.then(formBuilder => {
  formBuilder.actions.setData(formData);
});
var formData = '[{"type":"text","label":"Full Name","subtype":"text","className":"form-control","name":"text-1476748004559"},{"type":"select","label":"Occupation","className":"form-control","name":"select-1476748006618","values":[{"label":"Street Sweeper","value":"option-1","selected":true},{"label":"Moth Man","value":"option-2"},{"label":"Chemist","value":"option-3"}]},{"type":"textarea","label":"Short Bio","rows":"5","className":"form-control","name":"textarea-1476748007461"}]';



 

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
    var id=jQuery("#id").val();
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
    var formData = formBuilder.actions.getData('json');
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
      "form":'<div class="wp-easy-email">'.form.'<div>'+styles,
      'form_name':form_name,
      'form_email':form_email,
      'form_json':formData,
      'subject':subject,
      'id':
    }


    jQuery.post(ajaxurl, data, function(response) {
      $data=JSON.parse(response);
      if($data['status']=="Saved")
      {
        jQuery(".content").html("<div><strong>Form Saved.</strong></div><br/><div><strong>Use Following Shortcode to display the form</strong> [easy_email_form id="+$data['id']+"]</div>");
      }
      else
      {
        alert(data['status']);
      }
    });




  });

});

</script>

</body>

</html>
