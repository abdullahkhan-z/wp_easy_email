<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

class EASY_SMTP
{   public $mail; 
    public $host="localhost";
    public $authentication=false;
    public $port="25";
    public $username;
    public $password;
    public $ssl=false;
    public $tls=false;
    public $from;
    public $from_name;
    public $to;

    public function __construct($host="localhost",$port="25",$authentication=false,$username,$password,$ssl=false,$tls=false,$from,$from_name) 
    {
    $this->mail=new PHPMailer(true);
    $this->host=$host;    
    $this->authentication=$authentication;   
    $this->port=$port;    
    $this->username=$username;
    $this->password=$password;
    $this->ssl=$ssl;
    $this->tls=$tls;
    $this->from=$from;
    $this->from_name=$from_name;

    try {
    
    $this->mail->SMTPDebug = 2;                                
    $this->mail->isSMTP();                                      
    $this->mail->Host = $this->host; 

    if($authentication)
    {
        $this->mail->SMTPAuth = true;                              
        $this->mail->Username = $username;                 
        $this->mail->Password = $password;
    }     
    else
    {
        $this->mail->SMTPAuth = false;                              
        $this->mail->Username = $username;                 
    }                   
    
    if($ssl)
    {
        $this->mail->SMTPSecure = 'ssl';  
    }

    else if($tls)
    {
        $this->mail->SMTPSecure = 'tls';  
    }
    $this->mail->Port = $port;                                    

    //Recipients
    $this->mail->setFrom($from, $from_name);
    }
    catch (Exception $e) {
        echo 'An Exception Occurred ', $mail->ErrorInfo;
        exit();
    }

}

public function send_email($to,$subject,$body,$attachments)
{

    try 
    {
    foreach($to as $user)
    { 
        $this->mail->addAddress($user);     

    }

    //$mail->addReplyTo('info@example.com', 'Information');
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    foreach($attachments as $attachment)
     { 
         $this->mail->addAttachment($attachment);  

     }
    

   
    $this->mail->isHTML(true);                                  
    $this->mail->Subject = $subject;
    $this->mail->Body    = $body;
    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $this->mail->send();
    echo 'Message has been sent';
    
    return true;
    
    }

    catch (Exception $e) {
        echo 'An Exception Occurred ', $mail->ErrorInfo;
        return false;
        exit();
    }


    }





}




