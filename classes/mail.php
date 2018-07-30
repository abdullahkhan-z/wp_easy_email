<?php



require_once("PhpImap/__autoload.php");



//$mailbox = new PhpImap\Mailbox('{imap.mail.yahoo.com:993/imap/ssl}INBOX', 'dark_knight_iwalkalone@yahoo.com', '@tt@ck0nth3tit@n', __DIR__) or die("Errir");

//$mailbox = new PhpImap\Mailbox('{imap.gmail.com:993/imap/ssl}INBOX', 'abdullah.zafar8881@gmail.com', 'chr0n0assult', __DIR__) or die("Errir");

// Read all messaged into an array:
//$mailsIds = $mailbox->searchMailbox('ALL');
//if(!$mailsIds) {
//	die('Mailbox is empty');
//}

// Get the first message and save its attachment(s) to disk:
//$mail = $mailbox->getMail($mailsIds[0]);



Class GET_IMAP_MAILS

{
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
        $ssl="ssl";
    else
        $ssl="";
        $this->mailbox = new PhpImap\Mailbox('{'.$host.':'.$port.'/imap/'.$ssl.'}INBOX',$user, $pass,__DIR__);


    } 

    
}

/*$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$test=new IMAP("imap.gmail.com","abdullah.zafar8881@gmail.com","chr0n0assult",993,true,true,$actual_link);

//$test->set_max_id();
$arr=$test->get_email(140);
var_dump($arr);*/





?>