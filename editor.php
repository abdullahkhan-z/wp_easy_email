<?php

require_once("../../../wp-load.php");

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>GrapesJS Demo - Free Open Source Newsletter Editor</title>
    <meta content="Best Free Open Source Responsive Newsletter Builder" name="description">
    <link rel="stylesheet" href="html_editor/stylesheets/grapes.min-v0.14.23.css">
    <link rel="stylesheet" href="html_editor/stylesheets/material.css">
    <link rel="stylesheet" href="html_editor/stylesheets/tooltip.css">
    <link rel="stylesheet" href="html_editor/stylesheets/toastr.min.css">
    <link rel="stylesheet" href="html_editor/stylesheets/grapesjs-preset-newsletter-v=0.2.15.css">
    <link rel="stylesheet" href="html_editor/stylesheets/demos.css">

    <script src="html_editor/js/aviary.js"></script>
    <script src="html_editor/js/grapes.min-v0.14.23.js"></script>
    <script src="html_editor/js/ckeditor/ckeditor.js"></script>
    <script src="html_editor/js/grapesjs-plugin-ckeditor.min.js"></script>
    <script src="html_editor/js/grapesjs-preset-newsletter.min-v=0.2.15.js"></script>
    <script src="html_editor/js/grapesjs-aviary.min.js"></script>
    <script src="html_editor/js/toastr.min.js"></script>
    <script src="html_editor/js/ajaxable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  </head>
  <style>
    .nl-link {
      color: inherit;
    }

    .gjs-clm-tag {
      color: white;
    }

    .gjs-four-color {
      color: #35d7bb;
    }

    .gjs-logo-version {
      background-color: #5a606d;
    }

      .container-cell > img
    { display: none;
  }

.container-cell
{
 display: none;
}
  .cell > img
  {
    display:none;
  }
  </style>
  <body>


    <div id="gjs" style="height:0px; overflow:hidden">


      <table class="main-body">
  <tr class="row">
    <td class="main-body-cell">
      <table class="container">
        <tr>
          <td class="container-cell">          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>


<style>
  .link {
    color: rgb(217, 131, 166);
  }
  .row{
    vertical-align:top;
  }
  .main-body{
    min-height:150px;
    padding: 5px;
    width:100%;
    height:100%;
    background-color:rgb(234, 236, 237);
  }
  .c926{
    color:rgb(158, 83, 129);
    width:100%;
    font-size:50px;
  }
  .cell.c849{
    width:11%;
  }
  .c1144{
    padding: 10px;
    font-size:17px;
    font-weight: 300;
  }


  .card{
    min-height:150px;
    padding: 5px;
    margin-bottom:20px;
    height:0px;
  }
  .card-cell{
    background-color:rgb(255, 255, 255);
    overflow:hidden;
    border-radius: 3px;
    padding: 0;
    text-align:center;
  }
  .card.sector{
    background-color:rgb(255, 255, 255);
    border-radius: 3px;
    border-collapse:separate;
  }
  .c1271{
    width:100%;
    margin: 0 0 15px 0;
    font-size:50px;
    color:rgb(120, 197, 214);
    line-height:250px;
    text-align:center;
  }
  .table100{
    width:100%;
  }
  .c1357{
    min-height:150px;
    padding: 5px;
    margin: auto;
    height:0px;
  }
  .darkerfont{
    color:rgb(65, 69, 72);
  }
  .button{
    font-size:12px;
    padding: 10px 20px;
    background-color:rgb(217, 131, 166);
    color:rgb(255, 255, 255);
    text-align:center;
    border-radius: 3px;
    font-weight:300;
  }
  .table100.c1437{
    text-align:left;
  }
  .cell.cell-bottom{
    text-align:center;
    height:51px;
  }
  .card-title{
    font-size:25px;
    font-weight:300;
    color:rgb(68, 68, 68);
  }
  .card-content{
    font-size:13px;
    line-height:20px;
    color:rgb(111, 119, 125);
    padding: 10px 20px 0 20px;
    vertical-align:top;
  }
  .container{
    font-family: Helvetica, serif;
    min-height:150px;
    padding: 5px;
    margin:auto;
    height:0px;
    width:90%;
    max-width:550px;
  }
  .cell.c856{
    vertical-align:middle;
  }
  .container-cell{
    vertical-align:top;
    font-size:medium;
    padding-bottom:50px;
  }
  .c1790{
    min-height:150px;
    padding: 5px;
    margin:auto;
    height:0px;
  }
  .table100.c1790{
    min-height:30px;
    border-collapse:separate;
    margin: 0 0 10px 0;
  }
  .browser-link{
    font-size:12px;
  }
  .top-cell{
    text-align:right;
    color:rgb(152, 156, 165);
  }
  .table100.c1357{
    margin: 0;
    border-collapse:collapse;
  }
  .c1769{
    width:30%;
  }
  .c1776{
    width:70%;
  }
  .c1766{
    margin: 0 auto 10px 0;
    padding: 5px;
    width:100%;
    min-height:30px;
  }
  .cell.c1769{
    width:11%;
  }
  .cell.c1776{
    vertical-align:middle;
  }
  .c1542{
    margin: 0 auto 10px auto;
    padding:5px;
    width:100%;
  }
  .card-footer{
    padding: 20px 0;
    text-align:center;
  }
  .c2280{
    height:150px;
    margin:0 auto 10px auto;
    padding:5px 5px 5px 5px;
    width:100%;
  }
  .c2421{
    padding:10px;
  }
  .c2577{
    padding:10px;
  }
  .footer{
    margin-top: 50px;
    color:rgb(152, 156, 165);
    text-align:center;
    font-size:11px;
    padding: 5px;
  }
  .quote {
    font-style: italic;
  }

  .list-item{
    height:auto;
    width:100%;
    margin: 0 auto 10px auto;
    padding: 5px;
  }
  .list-item-cell{
    background-color:rgb(255, 255, 255);
    border-radius: 3px;
    overflow: hidden;
    padding: 0;
  }
  .list-cell-left{
    width:30%;
    padding: 0;
  }
  .list-cell-right{
    width:70%;
    color:rgb(111, 119, 125);
    font-size:13px;
    line-height:20px;
    padding: 10px 20px 0px 20px;
  }
  .list-item-content{
    border-collapse: collapse;
    margin: 0 auto;
    padding: 5px;
    height:150px;
    width:100%;
  }
  .list-item-image{
    color:rgb(217, 131, 166);
    font-size:45px;
    width: 100%;
  }

  .grid-item-image{
    line-height:150px;
    font-size:50px;
    color:rgb(120, 197, 214);
    margin-bottom:15px;
    width:100%;
  }
  .grid-item-row {
    margin: 0 auto 10px;
    padding: 5px 0;
    width: 100%;
  }
  .grid-item-card {
    width:100%;
    padding: 5px 0;
    margin-bottom: 10px;
  }
  .grid-item-card-cell{
    background-color:rgb(255, 255, 255);
    overflow: hidden;
    border-radius: 3px;
    text-align:center;
    padding: 0;
  }
  .grid-item-card-content{
    font-size:13px;
    color:rgb(111, 119, 125);
    padding: 0 10px 20px 10px;
    width:100%;
    line-height:20px;
  }
  .grid-item-cell2-l{
    vertical-align:top;
    padding-right:10px;
    width:50%;
  }
  .grid-item-cell2-r{
    vertical-align:top;
    padding-left:10px;
    width:50%;
  }
</style>


    </div>



    <form id="test-form" class="test-form" action="//grapes.16mb.com/s" method="POST" style="display:none">
      <div class="putsmail-c">
        <a href="https://putsmail.com/" target="_blank">
          <img src="img/putsmail.png" style="opacity:0.85;" />
        </a>
        <div class="gjs-sm-property" style="font-size: 10px">
          Test delivering offered by <a class="nl-link" href="https://litmus.com/" target="_blank">Litmus</a> with <a class="nl-link" href="https://putsmail.com/" target="_blank">Putsmail</a>
          <span class="form-status" style="opacity: 0">
            <i class="fa fa-refresh anim-spin" aria-hidden="true"></i>
          </span>
        </div>
      </div>
      <div class="gjs-sm-property">
        <div class="gjs-field">
        	<span id="gjs-sm-input-holder">
            <input  type="email" name="email" placeholder="Email" required>
          </span>
        </div>
      </div>

      <div class="gjs-sm-property">
        <div class="gjs-field">
        	<span id="gjs-sm-input-holder">
            <input type="text" name="subject" placeholder="Subject" required>
          </span>
        </div>
      </div>
      <input type="hidden" name="body">
      <button class="gjs-btn-prim gjs-btn-import" style="width: 100%">SEND</button>
    </form>


    <div id="info-panel" style="display:none">
      <br/>
      <svg class="info-panel-logo" xmlns="//www.w3.org/2000/svg" version="1"><g id="gjs-logo"><path d="M40 5l-12.9 7.4 -12.9 7.4c-1.4 0.8-2.7 2.3-3.7 3.9 -0.9 1.6-1.5 3.5-1.5 5.1v14.9 14.9c0 1.7 0.6 3.5 1.5 5.1 0.9 1.6 2.2 3.1 3.7 3.9l12.9 7.4 12.9 7.4c1.4 0.8 3.3 1.2 5.2 1.2 1.9 0 3.8-0.4 5.2-1.2l12.9-7.4 12.9-7.4c1.4-0.8 2.7-2.2 3.7-3.9 0.9-1.6 1.5-3.5 1.5-5.1v-14.9 -12.7c0-4.6-3.8-6-6.8-4.2l-28 16.2" style="fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-width:10;stroke:#fff"/></g></svg>
      <br/>
      <div class="info-panel-label">
        <b>GrapesJS Newsletter Builder</b> is a showcase of what/how is possible to build an editor using the
        <a class="info-panel-link gjs-four-color" target="_blank" href="https://artf.github.io/grapesjs/">GrapesJS</a>
        core library
        <br/><br/>
        For any tip about this demo (or newsletters construction in general) check the
        <a class="info-panel-link gjs-four-color" target="_blank" href="https://github.com/artf/grapesjs-preset-newsletter">Newsletter Preset repository</a>
        and open an issue. For any problem with the builder itself, open an issue on the main
        <a class="info-panel-link gjs-four-color" target="_blank" href="https://github.com/artf/grapesjs">GrapesJS repository</a>
        <br/><br/>
        Being a free and open source project contributors and supporters are extremely welcome.
        If you like the project support it with a donation of your choice or become a backer/sponsor via
        <a class="info-panel-link gjs-four-color" target="_blank" href="https://opencollective.com/grapesjs">Open Collective</a>
      </div>
    </div>

    <div style="display: none">
      <div class="gjs-logo-cont">
        <input style="margin-bottom:10px; border-radius:5px; padding-left:2px; width:200px;" id="template_name" name="template_name" type="text" placeholder="Template Name" />
        <a ><img class="gjs-logo"></a>
        <div class="gjs-logo-version"></div>
      </div>
    </div>




    <script type="text/javascript">
    
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
   
      var images = [

      ];

      // Set up GrapesJS editor with the Newsletter plugin
      $("document").ready(function(){
      var editor = grapesjs.init({
        protectedCss: 'img {position:relative;max-width:100%;}',
        clearOnRender: true,
        height: '100%',
        storageManager: {
          id: 'gjs-nl-',
        },
         
        assetManager: {
                        storageType  	: '',
                        storeOnChange  : true,
                        storeAfterUpload  : true,
                        upload: 'https://localhost/assets/upload',        //for temporary storage
                        assets    	: [ ],
                        uploadFile: function(e) {
                        var files = e.dataTransfer ? e.dataTransfer.files : e.target.files;
                        var formData = new FormData();
                              for(var i in files){
                                      formData.append('file-'+i, files[i]) //containing all the selected images from local
                              }
                  $.ajax({
                        url: ajaxurl+"?action=editor_image_handler",
                        type: 'POST',
                        data: formData,
                        contentType:false,
                        crossDomain: true,
                        timeout: 10000000,
                        mimeType: "multipart/form-data",
                        processData:false,
                        success: function(result){
                          alert(result);
                             var obj=editor.AssetManager.getAll();
                             assets=obj.models;
                             alert("Uploaded");
                             for(i=0;i<assets.length;i++)
                             { editor.AssetManager.remove(assets[i].attributes.src);
                               
                             }
                             editor.AssetManager.add(JSON.parse(result));
                             

                            }
                  });
                  },
                  },
     
        container : '#gjs',
        fromElement: true,
        plugins: ['gjs-preset-newsletter', 'gjs-plugin-ckeditor'],
        pluginsOpts: {
          'gjs-preset-newsletter': {
            modalLabelImport: 'Paste all your code here below and click import',
            modalLabelExport: 'Copy the code and use it wherever you want',
            codeViewerTheme: 'material',
            //defaultTemplate: templateImport,
            importPlaceholder: '<table class="table"><tr><td class="cell">Hello world!</td></tr></table>',
            cellStyle: {
              'font-size': '12px',
              'font-weight': 300,
              'vertical-align': 'top',
              color: 'rgb(111, 119, 125)',
              margin: 0,
              padding: 0,
            }
          },
          'gjs-plugin-ckeditor': {
            position: 'center',
            options: {
              startupFocus: true,
              extraAllowedContent: '*(*);*{*}', // Allows any class and any inline style
              allowedContent: true, // Disable auto-formatting, class removing, etc.
              enterMode: CKEDITOR.ENTER_BR,
              extraPlugins: 'sharedspace,justify,colorbutton,panelbutton,font',
              toolbar: [
                { name: 'styles', items: ['Font', 'FontSize' ] },
                ['Bold', 'Italic', 'Underline', 'Strike'],
                {name: 'paragraph', items : [ 'NumberedList', 'BulletedList']},
                {name: 'links', items: ['Link', 'Unlink']},
                {name: 'colors', items: [ 'TextColor', 'BGColor' ]},
              ],
            }
          }
        }
      });


      var mdlClass = 'gjs-mdl-dialog-sm';
      var pnm = editor.Panels;
      var cmdm = editor.Commands;
      var md = editor.Modal;

      var infoContainer = document.getElementById("info-panel");
      cmdm.add('open-info', {
        run: function(editor, sender) {
          sender.set('active', 0);
          var mdlDialog = document.querySelector('.gjs-mdl-dialog');
          mdlDialog.className += ' ' + mdlClass;
          infoContainer.style.display = 'block';
          md.setTitle('About this demo');
          md.setContent('');
          md.setContent(infoContainer);
          md.open();
          md.getModel().once('change:open', function() {
            mdlDialog.className = mdlDialog.className.replace(mdlClass, '');
          })
        }
      });
      pnm.addButton('options', [{
        id: 'undo',
        className: 'fa fa-undo',
        attributes: {title: 'Undo'},
        command: function(){ editor.runCommand('core:undo') }
      },{
        id: 'redo',
        className: 'fa fa-repeat',
        attributes: {title: 'Redo'},
        command: function(){ editor.runCommand('core:redo') }
      },{
        id: 'clear-all',
        className: 'fa fa-trash icon-blank',
        attributes: {title: 'Clear canvas'},
        command: {
          run: function(editor, sender) {
            sender && sender.set('active', false);
            if(confirm('Are you sure to clean the canvas?')){
                    editor.CssComposer.getAll().reset();
                      $.get("templates/default.html", function( my_var ) {

                      editor.setComponents(my_var);
                      });
              setTimeout(function(){
                localStorage.clear()
              },0)
            }
          }
        }
      },{
        id: 'view-info',
        className: 'fa fa-question-circle',
        command: 'open-info',
        attributes: {
          'title': 'About',
          'data-tooltip-pos': 'bottom',
        },
      }]);

      // Simple warn notifier
      var origWarn = console.warn;
      toastr.options = {
        closeButton: true,
        preventDuplicates: true,
        showDuration: 250,
        hideDuration: 150
      };
      console.warn = function (msg) {
        toastr.warning(msg);
        origWarn(msg);
      };

      // Beautify tooltips
      var titles = document.querySelectorAll('*[title]');
      for (var i = 0; i < titles.length; i++) {
        var el = titles[i];
        var title = el.getAttribute('title');
        title = title ? title.trim(): '';
        if(!title)
          break;
        el.setAttribute('data-tooltip', title);
        el.setAttribute('title', '');
      }


      // Do stuff on load
      editor.on('load', function() {
        var $ = grapesjs.$;

        // Show logo with the version
        var logoCont = document.querySelector('.gjs-logo-cont');
       
        var logoPanel = document.querySelector('.gjs-pn-commands');
        logoPanel.appendChild(logoCont);

        
      });
               
      
      editor.Panels.addButton('options', [{ 
          id: 'save-db', 
          className: 'fa fa-floppy-o', 
          command: 'save-db',
          attributes: {title: 'Save DB'} 
        }]);

     editor.Commands.add('save-db', {
        run: function(editor, sender) {
          sender && sender.set('active', 0); // turn off the button
          var InnerHtml =  this.frameEl.contentDocument.activeElement.innerHTML;
          var InnerHtml =   window.grapesjs.editors[0].runCommand('gjs-get-inlined-html');
          var template_name=$("#template_name").val();
          if(template_name=="" || template_name==null)
          {
            alert("Please write a name for the template.");
            
          }
          else{
          data={"template_name":template_name,"content":InnerHtml,"action":"editor_save_template"}
          jQuery.post(ajaxurl, data, function(response) {
                    console.log(response);
	                	alert("Saved");
            	});
          }
        }
      });



      $.ajax({
            url: ajaxurl+"?action=editor_get_images",
            type: 'GET',
            success: function(result){

                  var obj=editor.AssetManager.getAll();
                  assets=obj.models;
                  console.log(obj);
                  for(i=0;i<assets.length;i++)
                  { editor.AssetManager.remove(assets[i].attributes.src);
                    
                  }
                  editor.AssetManager.add(JSON.parse(result));
                  

                }
      });


      });
    </script>

  </body>
</html>
