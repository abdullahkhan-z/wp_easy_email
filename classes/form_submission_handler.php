<?php

require_once '../../../../../wp-load.php';


class FORM_SUB_HANDLER
{
    public $default_email="";
    public $default_name="";
    public function __construct() {
                        if(isset($_POST['id']))
                        $id=$_POST['id'];
                        else
                        exit();

                        global $wpdb;
                        $table=$wpdb->prefix."easy_forms";
                        $results=$wpdb->get_results("SELECT * FROM $table where id=$id");
                        $json=array();
                        $email_to=array();
                        $email_subject="No Subject";
                        $html="<div>";
                        $addresses="";
                        foreach($results as $row)
                        {  
                            $json=json_decode(stripslashes($row->form_json));
                            $addresses=$row->form_email_to;
                            $email_subject=$row->subject;
                        // var_dump($json);
                        
                        }

                        $email_to=explode(',',$addresses);
                        foreach($json as $field)
                        {    
                            if($field->name!=null)
                            {
                                if(isset($_POST[$field->name]))
                                {   if($_POST[$field->name]!=null)
                                    $html.="<div><div style='background-color:black; color:white; width:100%; padding:5px;'>".$field->label."</div><p>".$_POST[$field->name]."</p></div>";
                                }
                            }

                        }

                        if(! class_exists('EASY_SMTP'))
                        require_once plugin_dir_path(__File__) . '../smtp_class.php';

                        $email_from=get_user_meta( 1, 'easy_email_email_from_admin',true);
                        $email_from_name=get_user_meta( 1, 'easy_email_email_from_name_admin',true);
                        $hostname=get_user_meta( 1, 'easy_email_hostname_admin',true);
                        $port=get_user_meta( 1, 'easy_email_port_admin',true);
                        $security=get_user_meta( 1, 'easy_email_security_admin',true);
                        $usernamer=get_user_meta( 1, 'easy_email_username_admin',true);
                        $password=trim(get_user_meta(1, 'easy_email_password_admin',true));
                                    
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


                        



                        get_header(); ?>




                                    <?php


                                        echo "<div style='display:none'>";
                                        if($hostname!="")
                                        {    
                                            
                                            $smtp=new EASY_SMTP($hostname,(int)$port,$authentication,$usernamer,$password,$ssl,$tls,$email_from,$email_from_name);
                                            $val=$smtp->send_email($email_to,$email_subject,$html,array());
                                        }
                                        else
                                        {   echo "</div>";
											$headers=[];
                                            if($email_from_name!="" && $email_from!="")
                                            {   $this->default_email=$email_from;
                                                $this->default_name=$email_from_name;
                                                add_filter( 'wp_mail_from_name', [$this,'my_mail_from_name']);
                            
                                                add_filter( 'wp_mail_from', [$this,'my_mail_from']);
                                                $headers[] = 'From: '.$email_from_name.' <'.$email_from.'>';
                                            }
                                            $status=wp_mail($email_to,$email_subject,$html,$headers,$attachments);

                                            if($status)
                                            $message="Your form has been submitted";
                                            else
                                            $message="An error occurred while submitting your form.";

                                            echo "<div class='notification'><center>
                                            ".$message."
                                            </center>
                                            <br/>
                                            <br/>
                                            </div>";
                                            
                                            remove_filter( 'wp_mail_from_name', [$this,'my_mail_from_name']);
                        
                                            remove_filter( 'wp_mail_from', [$this,'my_mail_from']);
											
                                            return;
                                        }

                                        echo "</div>";

                                        if($val)

                                        $message="Your form has been submitted";
                                        else
                                        $message="An error occurred while submitting your form.";

                                        echo "<div class='notification'><center>
                                        ".$message."<br><br>
                                        </center></div>";
                                        ?>



                        <?php get_footer(); ?>

                      <?php  


                }


    public function my_mail_from_name( $name ) {
		return $this->default_name;
	}

	public function my_mail_from( $email ) {
		return $this->default_email;
	}           


 }

$response=new FORM_SUB_HANDLER();

?>