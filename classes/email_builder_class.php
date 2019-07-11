<?php

require_once ABSPATH . 'wp-load.php';


class EMAIL_BUILDER {

	public function __construct() {

  }

  public function editor()
  {
   ?>

   <style>

     .error
     {
       display:none;
     }
      .sc-bxivhb.eGHHPi > h1
      {
        color:white;
      }
            #editor iframe
      {
        height:135%;
      }

      </style>

  <script>

    </script>
   <div style="background-color:white; position:absolute; right:0px; left:0px; top:0px; bottom:0px;"></div>
   <div style="position:relative; z-index:3;">
  <div id="demo"></div>
    </div>
  <script>!function(r){function e(e){for(var t,p,a=e[0],c=e[1],f=e[2],l=0,s=[];l<a.length;l++)p=a[l],o[p]&&s.push(o[p][0]),o[p]=0;for(t in c)Object.prototype.hasOwnProperty.call(c,t)&&(r[t]=c[t]);for(i&&i(e);s.length;)s.shift()();return u.push.apply(u,f||[]),n()}function n(){for(var r,e=0;e<u.length;e++){for(var n=u[e],t=!0,a=1;a<n.length;a++){var c=n[a];0!==o[c]&&(t=!1)}t&&(u.splice(e--,1),r=p(p.s=n[0]))}return r}var t={},o={0:0},u=[];function p(e){if(t[e])return t[e].exports;var n=t[e]={i:e,l:!1,exports:{}};return r[e].call(n.exports,n,n.exports,p),n.l=!0,n.exports}p.m=r,p.c=t,p.d=function(r,e,n){p.o(r,e)||Object.defineProperty(r,e,{configurable:!1,enumerable:!0,get:n})},p.r=function(r){Object.defineProperty(r,"__esModule",{value:!0})},p.n=function(r){var e=r&&r.__esModule?function(){return r.default}:function(){return r};return p.d(e,"a",e),e},p.o=function(r,e){return Object.prototype.hasOwnProperty.call(r,e)},p.p="";var a=window.webpackJsonp=window.webpackJsonp||[],c=a.push.bind(a);a.push=e,a=a.slice();for(var f=0;f<a.length;f++)e(a[f]);var i=c;n()}([]);</script>
  <script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__)?>email_editor/demo.f7d5a235.js">
  
  
  
  
  </script>

   <?php

  }


  public function reeditor($id)
  {
    global $wpdb;

    $template_name="";
    $design="";
    $table=$wpdb->prefix."easy_email_templates";
    $sql="SELECT design,name from $table where id=$id";

    $design=$wpdb->get_results($sql);
    foreach($design as $row)
    {
      $design=$row->design;
      $template_name=$row->name;
    }
   
   ?>

   <style>
     .error
     {
       display:none;
     }
      .sc-bxivhb.eGHHPi > h1
      {
        color:white;
      }
            #editor iframe
      {
        height:135%;
      }


      </style>

  <script>

    </script>
   <div style="background-color:white; position:absolute; right:0px; left:0px; top:0px; bottom:0px;"></div>
   <div style="position:relative; z-index:3;">
  <div id="demo"></div>
    </div>
  <script>
  var template_id="<?php echo $id;?>";
  var email_template='<?php echo $design; ?>';
  var email_template_name='<?php echo $template_name; ?>';
  </script>
  <script>!function(r){function e(e){for(var t,p,a=e[0],c=e[1],f=e[2],l=0,s=[];l<a.length;l++)p=a[l],o[p]&&s.push(o[p][0]),o[p]=0;for(t in c)Object.prototype.hasOwnProperty.call(c,t)&&(r[t]=c[t]);for(i&&i(e);s.length;)s.shift()();return u.push.apply(u,f||[]),n()}function n(){for(var r,e=0;e<u.length;e++){for(var n=u[e],t=!0,a=1;a<n.length;a++){var c=n[a];0!==o[c]&&(t=!1)}t&&(u.splice(e--,1),r=p(p.s=n[0]))}return r}var t={},o={0:0},u=[];function p(e){if(t[e])return t[e].exports;var n=t[e]={i:e,l:!1,exports:{}};return r[e].call(n.exports,n,n.exports,p),n.l=!0,n.exports}p.m=r,p.c=t,p.d=function(r,e,n){p.o(r,e)||Object.defineProperty(r,e,{configurable:!1,enumerable:!0,get:n})},p.r=function(r){Object.defineProperty(r,"__esModule",{value:!0})},p.n=function(r){var e=r&&r.__esModule?function(){return r.default}:function(){return r};return p.d(e,"a",e),e},p.o=function(r,e){return Object.prototype.hasOwnProperty.call(r,e)},p.p="";var a=window.webpackJsonp=window.webpackJsonp||[],c=a.push.bind(a);a.push=e,a=a.slice();for(var f=0;f<a.length;f++)e(a[f]);var i=c;n()}([]);</script>
  <script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__)?>email_editor/demo2.js">
  
  
  
  
  </script>

   <?php

  }
  
}
?>




