<?php



require_once("__autoload.php");


Class IMAP

{
    public $mailbox;
    public $ssl;
    public $host;
    public $port;
    public $user;
    public $pass;
    public $tls;
    public $max_uid=0;
    public $directory="";
    public $tyle="";

    public function __construct($type,$host, $user, $pass, $port = 0, $ssl = false, $tls = false, $directory,$directory_url)
    {
        $this->host=$host;
        $this->user=$user;
        $this->pass=$pass;
        $this->port=$port;
        $this->ssl=$ssl;
        $this->tls=$tls;
        $this->directory=$directory;
        $this->directory_url=$directory_url;
        $this->type=$type;

    if($ssl==true)
        $ssl="/ssl";
    else
        $ssl="";
        $this->mailbox = new PhpImap\Mailbox('{'.$host.':'.$port.'/'.$this->type.$ssl.'/novalidate-cert}INBOX',$user, $pass,$directory);


    } 

    public function connect()
    {
        $this->mailbox->searchMailbox("ALL");
        echo "Success";
    }
    
    public function set_max_id()
    {
        $uids=$this->mailbox->searchMailbox('ALL'); 
        $count=count($uids);
        $this->max_uid=$uids[$count-1];

    }

    public function get_max_id()
    {
        return $this->max_uid;
    }

    public function get_email($uid)
    {
        $arr=$this->mailbox->getMail($uid,false);
        //return $arr;
        $all_attachments=array();
        $plaintext=$arr->textPlain;
        $htmltext=$arr->textHtml;
        //var_dump($htmltext);
        $uid=$arr->id;
        $date=strtotime($arr->date);
        $subject=$arr->subject;
        $fromName=$arr->fromName;
        $fromEmail=$arr->fromAddress;
        $attachments=$arr->attachments;
        //var_dump($attachments);

        foreach($attachments as $attachment)
        {   if($attachment->disposition!="INLINE")
               { $arr=explode("/",$attachment->filePath);
                array_push($all_attachments,$arr[count($arr)-1]);}
            else
                {
                    if(stripos($htmltext,$attachment->contentId))
                    {   $arr=explode("/",$attachment->filePath);
                        $filename=$arr[count($arr)-1];
                        $htmltext=str_replace("cid:".$attachment->contentId,$this->directory_url.$filename,$htmltext);
                        $htmltext=str_replace($attachment->contentId,$this->directory_url.$filename,$htmltext);

                    }
                }
        }
        $output=array();
        array_push($output,array("uid"=>$uid,"subject"=>$subject,"plaintext"=>$plaintext,"htmltext"=>$htmltext,"date"=>$date,"fromname"=>$fromName,"fromemail"=>$fromEmail,"attachments"=>$all_attachments));

        return $output;
        
    }
    
}







?>