<?php

require_once("../../../wp-load.php");
if ( ! class_exists( 'NEW_EMAIL' ) ) {
  require_once( plugin_dir_path( __FILE__ ) . 'classes/new_email.php' );
  }
  

  $email=new NEW_EMAIL();
  $email->email_form(-1,array(),false,false,null,null,false,array(),null);

?>