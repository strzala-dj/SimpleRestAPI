<?php
/*
 * In root folder for this project only.
 */

namespace SimpleRestAPI;

$access['db'] = array(
	'hostname' => 'localhost',
	'username' => '',
	'password' => '',
	'database' => 'products'
);


$access['PromoProductsAPI'] = array(
	'X-token' => 'sababa'
);


$access['SimpleRestAPI'] = array(
	'X-token' => 'sababa'
);

return (object) $access;