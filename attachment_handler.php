<?php


if ( 0 < $_FILES['file']['error'] ) {
	echo 'Error: ' . $_FILES['file']['error'] . '<br>';
}
else {
	error_log(print_r($_FILES['file']['tmp_name'],true));
}


 ?>