Database schema version control module
=================================================

This is very simple Kohana 3.x module to keep your database schema in sync across servers.

It's Kohana specific but it would be really easy to port it to some other PHP framework.

Installation
-------------

1. Clone the repository and move the files into place

	$ git clone https://github.com/rzajac/dbupdate

2. Copy the `dbupdate` folder into `application/classes/modules`

3. Copy `*.php.example` files to `application/config` and rename them so they do not have `.example` suffix

4. Configure your databases for production and development.

The `database.php` configuration file will load configuration for production or development database based on `Kohana::$environment` static variable.

> In my projects I set this variable from the system environment variable passed by Apache to PHP in `bootstrap.php` of my application.

5. Enable the module in the `bootstrap.php`

add this line to module configuration array

	'dbupdate' => APPPATH.'classes/modules/dbupdate'

6. Create `dbmigrations` table in your databases.

```sql
CREATE TABLE `dbmigrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `version` (`version`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
```

You are done!

How to use
----------

The module can be used only from command line to prevent accidental execution by going to some URL. To execute the tool go to the folder where your index.php is and execute the following command:

	$ php index.php --uri=dbupdate/update/dev

You will be presented with a menu simple menu like this one:

	Current DB version is 1341073317. Latest version is 1341073317.

	z) Update to latest version considering applied versions.
	c) See description of new versions.
	m) Mark as applied
	r) Run specific migration (advanced)
	d) Create skeleton version file.
	x) Exit

	Choose option ==>

The tool will inform you what is your current database version and what is the latest version of the schema. In this case both are the same. Notice that we are calling the script with `dev` parameter. That means we want to update `dev` database. The parameters for the script can be: dev, prod, testing. Based on the argument the script will connect to different database.

Creating database migration script
----------------------------------

To create new database migration script in the menu choose `d`. This option will create the migration script and give you the path to it. For example:

	Choose option ==> d
	Version file created: application/classes/modules/dbupdate/versions/1341078532.php

The file is a skeleton class looking like this:

```php
<?php
	/*
	 * Tables created:  none
	 * Tables affected: none
	 */
	class C1341073317
	{
		// Change this to describe database changes
		public $desc = 'Put description here.';

		public $sqls;

		public function __construct()
		{
			$this->setup_sql();
		}

		public function setup_sql()
		{
			$this->sqls[] ="SELECT 1";
		}

		// Write your changes here
		public function execute()
		{
			// Stubs
			foreach($this->sqls as $sql)
			{
				DB::query(NULL, $sql)->execute();
			}
		}
	}
?>
```

Fill out the description of your migration. Put as many SQL statements as you want in `setup_sql` method. Save and you are done.

Now you can commit the file to your repository and check it out on any of your servers. Where all you need to do is run the `dbupdate` script to update the database schema.

License
-------

Licensed under the MIT license
