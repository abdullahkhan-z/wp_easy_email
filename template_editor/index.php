
<?php

echo require_once("../../../wp-load.php");

?>

<html>
<head>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.12.17/css/grapes.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet"/>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.12.17/grapes.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/grapesjs-preset-newsletter@0.2.15/dist/grapesjs-preset-newsletter.min.js"></script>

</head>

<body>

<div id="gjs"></div>

<script type="text/javascript">
  var editor = grapesjs.init({
      container : '#gjs',
      plugins: ['gjs-preset-webpage'],
      pluginsOpts: {
        'gjs-preset-webpage': {
          // options
        }
      }
  });
</script>
<!--
<script type="text/javascript">

  var editor = grapesjs.init({

  });
  var editor = grapesjs.init({
      container : '#gjs',
      plugins: ['gjs-preset-newsletter'],
      pluginsOpts: {
        'gjs-preset-newsletter': {
         // modalTitleImport: 'Import template',
          // ... other options
        }
      },
      assetManager: {
  storageType   : '',
      storeOnChange  : true,
      storeAfterUpload  : true,
      upload: 'https://localhost/',        //for temporary storage
      assets      : [ ],
      uploadFile: function(e) {
    var files = e.dataTransfer ? e.dataTransfer.files : e.target.files;
var formData = new FormData();
    for(var i in files){
            formData.append('file-'+i, files[i]) //containing all the selected images from local
    }
$.ajax({
url: '/location to your php page/upload_image.php',
type: 'POST',
      data: formData,
      contentType:false,
  crossDomain: true,
  dataType: 'json',
  mimeType: "multipart/form-data",
  processData:false,
  success: function(result){
            var myJSON = [];
            $.each( result['data'], function( key, value ) {
                  myJSON[key] = value;    
            });
            var images = myJSON;    
          editor.AssetManager.add(images); 
          }
});
},
},
  });
/* editor.CssComposer.getAll().reset();
$.get("saved_resource.html", function( my_var ) {

editor.setComponents(my_var);
});*/


</script>-->
</body>

</html> 
