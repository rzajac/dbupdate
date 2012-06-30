<?php defined('SYSPATH') or die('No direct script access.');

define('DBUPDATE', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);
define('DBUPDATE_VERSIONS', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'versions'.DIRECTORY_SEPARATOR);

Route::set('dbupdate', 'dbupdate/update(/<database>)')
	->defaults(array(
		'controller' => 'dbupdate',
		'action' => 'update',
		'database' => NULL));
