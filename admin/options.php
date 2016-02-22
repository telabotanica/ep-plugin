<?php
	$option = 'option_1';
	$value = $_POST['texte'];
	$autoload = 'yes';
	update_option( $option, $new_value, $autoload );
	echo "ok";
	
?>

