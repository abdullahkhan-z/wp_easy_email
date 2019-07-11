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

    public function __construct($host, $user, $pass, $port = 0, $ssl = false, $tls = false, $directory)
    {
        $this->host=$host;
        $this->user=$user;
        $this->pass=$pass;
        $this->port=$port;
        $this->ssl=$ssl;
        $this->tls=$tls;
        $this->directory=$directory;

    if($ssl=true)
        $ssl="/ssl";
    else
        $ssl="/";
        $this->mailbox = new PhpImap\Mailbox('{'.$host.':'.$port.'/imap'.$ssl.'/novalidate-cert}INBOX',$user, $pass,__DIR__);


    } 

    public function connect()
    {
        $this->mailbox->searchMailbox("1");
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
        
        $all_attachments=array();
        $plaintext=$arr->textPlain;
        $htmltext=$arr->textHtml;
        //var_dump($htmltext);
        $uid=$arr->id;
        $date=strtotime($arr->date);
        $fromName=$arr->fromName;
        $fromEmail=$arr->fromAddress;
        $attachments=$arr->attachments;
        //var_dump($attachments);

        foreach($attachments as $attachment)
        {   if($attachment->disposition!="INLINE")
                array_push($all_attachments,array("filename"=>$attachment->name,"filepath"=>$attachment->filePath));
            else
                {
                    if(stripos($htmltext,$attachment->contentId))
                    {
                        $htmltext=str_replace("cid:".$attachment->contentId,$attachment->filePath,$htmltext);
                        $htmltext=str_replace($attachment->contentId,$attachment->filePath,$htmltext);

                    }
                }
        }
        $output=array();
        array_push($output,array("uid"=>$uid,"plaintext"=>$plaintext,"htmltext"=>$htmltext,"date"=>$date,"fromname"=>$fromName,"fromemail"=>$fromEmail,"attachments"=>$all_attachments));

        return $output;
        
    }
    
}





?>