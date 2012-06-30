=== Database version control module for Kohana 3.x

This is very simple module to keep your database schema in sync

=== Installation 

1. Clone the repository and move the files into place

$ git clone https://github.com/rzajac/dbupdate

2. Copy the dbupdate folder into application/classes/modules

3. Copy *.php.example files to application/config and rename them so they do not have .example suffix 

4. Configure your databases for production and development.

The database.php configuration file will load configuration for production or development database based on Kohana::$environment static variable.

In my projects I set this variable from system environment variable passed by Apache to PHP in bootstrap.php of my application.

5. Enable the module in the bootstrap.php

add this line to module configuration array

'dbupdate' => APPPATH.'classes/modules/dbupdate'

You are done!

The module can be used only from command line. 

For example to run the tool for development database use:

$ php index.php --uri=dbupdate/update/dev

You will be presented with a menu:

Current DB version is 1341073317. Latest version is 1341073317.

z) Update to latest version considering applied versions.
c) See description of new versions.
m) Mark as applied
r) Run specific migration (advanced)
d) Create skeleton version file.
x) Exit

Choose option ==> 

The tool will tell you that your current database schema version is 1341073317 and the latest version is 1341073317. In this case both are the same.

=== Creating database migration script

To create new database migration script in the menu choose d. This option will create the migration script and give you the path to it. For example: 

Choose option ==> d
Version file created: application/classes/modules/dbupdate/versions/1341078532.php

The file is a skeleton class looking like this:

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

Fill out the description of your migration. Put as many as you want SQL statements in setup_sql method. Save and you are done.

Commit your code -> check it out on a different server -> run dbupdate -> select z from options -> all of your migrations are applied to new server.



