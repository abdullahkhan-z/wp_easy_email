<?php


require_once("eden.php");




Class EDEN_IMAP
{   

    public $host="";
    public $user="";
    public $pass="";
    public $port=0;
    public $ssl=false;
    public $tls=false;
    public $imap="";
    public $mailboxes="";
    public $inbox_name="Inbox";
    public $max_uid=0;
    public $protocol;
    public $directory;
    public $directory_url;
    public function __construct($protocol,$host, $user, $pass, $port = 0, $ssl = false, $tls = false,$directory,$directory_url)
    {   
        $this->directory=$directory;
        $this->directory_url=$directory_url;
        $this->protocol=$protocol;
        $mail = new Eden_Mail();
        if($protocol=="pop")
        {$this->imap=$mail->pop3($host, 
                    $user, 
                    $pass, 
                    $port, 
                    $ssl,
                    $tls);}
        else
          {     
               $this->imap=$mail->imap($host, 
                    $user, 
                    $pass, 
                    $port, 
                    $ssl,
                    $tls);
          }
    }   
    

    public function connect()
    {   if($this->protocol=="imap")
            {
                $this->mailboxes = $this->imap->getMailboxes(); 
             
            } 
        else
           $this->imap->connect();

    }

    public function getmailboxes()
    {
        foreach($this->mailboxes as $box)
        { if(stripos($box,"INBOX")!==false)
            {$this->inbox_name=$box;
            }
        }
    }

    public function setInboxactive()
    {
        $this->imap->setActiveMailboxExamine($this->inbox_name);



    }

    public function getactivebox()
    {
        return $this->imap->getActiveMailbox();
    }

    public function setmaxuid()
    {
        $this->max_uid=(int)$this->imap->search(array("ALL"),0,1)['uid'];
     
    }

    public function getEmails($number)
    {
        return $this->max_uid=$this->imap->search(array("ALL"),0,$number);
    }

    public function getmaxuid()
    {
        return $this->max_uid;
    }

  /*  public function getNewEmails($uid,$number,$body)
    {       $uid=$uid+1;
            //var_dump($uid);
            $value=(string)$uid.":*";
        return $this->imap->uid_search(array($value),0,$number,false,$body);
    }
*/

        public function is_multi2($a) {
            foreach ($a as $v) {
                if (is_array($v)) return true;
            }
            return false;
        }

        public function getNewEmails($uid,$number,$body)
    {       //$uid=$uid+1;
            //var_dump($uid);
            //$all_mails=array();
            if($this->protocol=="imap")
            {    $value=(string)$uid.":*";
                $emails=$this->imap->uid_search(array($value),0,$number,false,$body);
                
              
            }
            else
            {   
               $emails=$this->imap->getOne($uid,1);

            }
            if((isset($emails[0]['from']['email']) && $emails[0]['from']['email']!="") || (isset($emails['from']['email']) && $emails['from']['email']!=""))
            {   
                //var_dump($this->directory);
                if(isset($emails['from']['email']))
                $email=$emails;
                else
                $email=$emails[0];

                //var_dump($email);
                $uid=$email['uid'];
                $subject=$email['subject'];
                $from_name=$email['from']['name'];
                $from_email=$email['from']['email'];
                $subject=$email['subject'];
                $date=$email['date'];
                if(isset($email['text/html']))
                $body=$email['text/html'];
                else if(isset($email['text/plain']))
                $body=$email['text/plain'];
                else if(isset($email['body']["text/html"]))
                $body=$email['body']['text/html'];
                else
                $body=$email['body']['text/plain'];


                $attachments=array();
                //exit();
                foreach($email['attachment'] as $attachment)
                {   $counter=1;
                    
                    $name=$attachment["name"][0];
                    $desposition=$attachment["deposition"];
                    $info = pathinfo($name);
                    $extension=$info['extension'];
                    $filename=$info['filename'];
                    $directory = join(DIRECTORY_SEPARATOR, array($this->directory, $name));
                    $attachment_name=$filename.".".$extension;
                    while(file_exists($directory))
                    {


                         
                        $directory = join(DIRECTORY_SEPARATOR, array($this->directory, $filename.$counter.".".$extension));
                        $attachment_name=$filename.$counter.".".$extension;
                        $counter++;
                        

                    }
                    
                    $file = fopen($directory, "w");
                    fwrite($file,$attachment['content']);
                    fclose($file);

                    if($desposition!="inline")
                    array_push($attachments, $attachment_name);
                     else
                     {  
                         if(stripos($body,$attachment['attachmentid']))
                         {   
                             $body=str_replace("cid:".$attachment['attachmentid'],$this->directory_url.$attachment_name,$body);
                             $body=str_replace($attachment['attachmentid'],$this->directory_url.$attachment_name,$body);
     
                         }
                     }
                   
                    
                }

               
                return array("uid"=>$uid,"subject"=>$subject,"fromname"=>$from_name,"fromemail"=>$from_email,"htmltext"=>$body,"attachments"=>$attachments,"date"=>$date);

            }
    }
}
    
// $eden=new EDEN_IMAP("imap","imap.gmail.com","abdullah.zafar8881@gmail.com","chr0n0assult",993,true,false,"\\","\\");


// $eden->connect();
// $eden->getmailboxes();
// $eden->setInboxactive();
// $eden->setmaxuid();
// $max_uid=$eden->getmaxuid();

// var_dump($max_uid);
// $emails = $eden->imap->getEmails($max_uid-1, $max_uid); 
// $structure=$eden->bodypeek(max_uid);
// var_dump($structure)


?>