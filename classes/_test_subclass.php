<?php

	error_reporting(E_ALL | E_STRICT);

	$config = require_once __DIR__ . "/../cfg/cfg.php";
	require_once __DIR__ . "/FidSubClass.php";
	
		
	$pdo = new PDO(
		'mysql:host='.$config['FID_HOST'].';dbname='.$config['FID_BASE'],
		$config['FID_USER'],
		$config['FID_PASS'],
		array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		)
	);

	$FidSubClass = new FidSubClass($pdo);

	$html = file_get_contents('/home/phoenix/freelance/data/fips_servlet.html');
	//print $html;
	$FidSubClass->process_subclasses($html, 1);
	
	
	print "\nall done\n";
?>