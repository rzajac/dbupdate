<?php defined('SYSPATH') or die('No direct script access.');
/*!
 * DBUpdate
 *
 * This file is part of very simple Kohana 3.x module
 * to keep your database schema in sync across servers
 *
 * Copyright 2007-2013 Rafal Zajac rzajac<at>gmail<dot>com. All rights reserved.
 * http://github.com/rzajac/dbupdate
 *
 * Licensed under the MIT license
 */

define('DBUPDATE', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);
define('DBUPDATE_VERSIONS', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'versions'.DIRECTORY_SEPARATOR);

Route::set('dbupdate', 'dbupdate/update(/<database>)')
	->defaults(array(
		'controller' => 'dbupdate',
		'action' => 'update',
		'database' => NULL));
