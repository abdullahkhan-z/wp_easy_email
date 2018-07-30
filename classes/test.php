<?php

require_once('includes/eden.php');

$smtp=new Eden_Mail_Smtp('smtp.gmail.com','abdullah.zafar8881@gmail.com','chr0n0assult',587,false,true);

$smtp->setSubject('Welcome!');
 $smtp ->setBody('<p>Hello you!</p>', false);
  $smtp ->setBody('Hello you!');
  $smtp ->addTo('abdullah.zafar9991@gmail.com');
 $smtp->send();
$smtp->disconnect(); 
?>