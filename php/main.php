<?php
	require_once 'SintegraSpider.php';

	$spider = new SintegraSpider();
	$result = $spider->search('31.804.115-0002-43');
	print_r($result);

?>