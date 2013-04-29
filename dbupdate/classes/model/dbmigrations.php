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

/**
 * The ORM model class.
 *
 * Stores migrations in the database.
 *
 * It expects dbmigrations table to be present in the database
 *
 * CREATE TABLE dbmigrations
 * (
 * 	id int(11) unsigned NOT NULL AUTO_INCREMENT,
 * 	version varchar(255) DEFAULT NULL,
 * 	PRIMARY KEY (id),
 * 	UNIQUE KEY version (version)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
 *
 * @package    DBUpdate
 * @category   Database
 * @author     Rafal Zajac rzajac<at>gmail<dot>com
 * @copyright  (c) 2007-2012 Rafal Zajac
 */
class Model_Dbmigrations extends ORM
{
	protected $_table_names_plural = FALSE;
	protected $_table_name = 'dbmigrations';
}
