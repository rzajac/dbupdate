<?php defined('SYSPATH') or die('No direct access allowed.');
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
 * The database schema versioning tool.
 *
 * @package    DBUpdate
 * @category   Database
 * @author     Rafal Zajac rzajac<at>gmail<dot>com
 * @copyright  (c) 2007-2012 Rafal Zajac
 */
class Controller_Dbupdate extends Kohana_Controller
{
	/**
	 * Database name
	 *
	 * The database we will be updating
	 *
	 * @var string
	 */
	private $db_name;

	/**
	 * Current database version
	 *
	 * @var int
	 */
	private $current_version = 0;

	/**
	 * Latest migration version
	 *
	 * @var int
	 */
	private $latest_version = 0;

	/**
	 * All migration version numbers and paths to migration scripts
	 *
	 * @var array
	 */
	private $all_versions = array();

	/**
	 * Migration versions that are already applied
	 *
	 * @var array
	 */
	private $applied_versions;

	public function before()
	{
		if( ! Kohana::$is_cli)
		{
			throw new Kohana_HTTP_Exception_404();
		}

		parent::before();
	}

	/**
	 * Default controller action
	 *
	 * return NULL
	 */
	public function action_index()
	{
		$this->response->status(400);
		self::display('Please select action to perform (dbupdate).', 'red');
	}

	/**
	 * Setup DBUpdate class
	 *
	 * @return NULL
	 */
	public function setup()
	{
		$this->all_versions = $this->get_versions();
		$this->latest_version = $this->get_latest_verison();
		$this->current_version = $this->get_current_version();
		$this->applied_versions = $this->get_applied_versions();
	}

	/**
	 * Update selected database
	 *
	 * @throws Exception On unknown database configuration
	 */
	public function action_update()
	{
		$this->db_name = $this->request->param('database', NULL);

		if( ! $this->db_name)
		{
			self::display('Please select the database to perform update on dbupdate/update/[testing|default].', 'red');
			return;
		}

		switch ($this->db_name)
		{
			case 'dev':
				Kohana::$environment = Kohana::DEVELOPMENT;
			break;

			case 'test':
				Kohana::$environment = Kohana::TESTING;
			break;

			case 'prod':
				Kohana::$environment = Kohana::PRODUCTION;
			break;

			default:
				throw new Exception('Unknown database.');
		}

		while (TRUE)
		{
			$this->setup();
			self::display();
			self::display('Current DB version is '.($this->current_version ?: 'NULL').'. Latest version is '.$this->latest_version.".\n");
			self::display('z) Update to latest version considering applied versions.
c) See description of new versions.
m) Mark as applied
r) Run specific migration (advanced)
d) Create skeleton version file.
x) Exit

Choose option ==> ', FALSE);

			$input = trim(fgets(STDIN));
			switch ( $input )
			{
				case 'x':
					return;
				break;

				case 'z':
					// Update to a version considering applied migrations
					$this->apply_migrations();
					return;
				break;

				case 'c':
					self::display();

					$not_applied = $this->get_not_applied_versions();
					foreach ($not_applied as $version => $file)
					{
						require_once($file);
						$class = 'C'.$version;
						$c = new $class();
						self::display('Version '.$version.': '.$c->desc."\n");

						foreach($c->sqls as $sql)
						{
							self::display(trim($sql));
						}
						self::display();
						self::display('-------------------------------------------------');
					}
				break;

					// Create skeleton file for next version
				case 'd':
					$resp = Request::factory('/dbupdate/skel')->execute();
					self::display($resp.'');
				break;

				case 'm':
					$this->mark_applied();
				break;

				case 'r':
					self::display("\nApply migration (ENTER to exit) ==> ", FALSE);

					$input = trim(fgets(STDIN));

					if($input !== '')
					{
						$versions = $this->get_versions();

						if(array_key_exists($input, $versions))
						{
							$this->apply_migrations(array($input => $versions[$input]));
						}
						else
						{
							self::display("\nMigration: ".$input.' does not exist!', 'red');
						}
					}
				break;
			}
		}
	}

	/**
	 * Create skeleton migration file
	 *
	 * @return NULL
	 */
	public function action_skel()
	{
		$file_desc = $this->get_new_file();

		$skeleton_class = <<<EOF
<?php
/*
 * Tables created:  none
 * Tables affected: none
 */
class C{$file_desc['timestamp']}
{
	// Change this to describe database changes
	public \$desc = 'Put description here.';

	public \$sqls;

	public function __construct()
	{
		\$this->setup_sql();
	}

	public function setup_sql()
	{
		\$this->sqls[] ="SELECT 1";
	}

	// Write your changes here
	public function execute()
	{
		// Stubs
		foreach(\$this->sqls as \$sql)
		{
			DB::query(NULL, \$sql)->execute();
		}
	}
}
EOF;
		if(file_exists($file_desc['file_name']))
		{
			self::display('Generated file name already exists!');
			return;
		}

		file_put_contents($file_desc['file_name'], $skeleton_class);

		self::display('Version file created: '.$file_desc['file_name']);
	}

	/**
	 * Generate new migration file name
	 *
	 * @return array
	 */
	private function get_new_file()
	{
		$timestamp = time();
		return array
		(
			'timestamp' => $timestamp,
			'file_name' => DBUPDATE.'versions/'.$timestamp.'.php'
		);
	}

	/**
	 * Reads versions directory and returns all migration versions already sorted
	 *
	 * @return array List of version files sorted in ascending order
	 */
	private function get_versions()
	{
		$versions = array();
		if ( $handle = opendir(DBUPDATE_VERSIONS) )
		{
			while ( FALSE !== ($file = readdir($handle)) )
			{
				$matches = NULL;
				if ( preg_match('/^([0-9]+)\.php$/', $file, $matches) )
				{
					$version = $matches[1];
					$versions[$version] = DBUPDATE_VERSIONS.$file;
				}
			}
		}

		ksort($versions);
		return $versions;
	}

	/**
	 * Get latest migration version
	 *
	 * @return int
	 */
	private function get_latest_verison()
	{
		$keys = array_keys($this->all_versions);
		return array_pop($keys);
	}

	/**
	 * Get current DB version
	 *
	 * Gets the migration version that database is currently in.
	 *
	 * @return int
	 */
	private function get_current_version()
	{
		return DB::query(Database::SELECT, 'SELECT MAX(version) AS version FROM dbmigrations')
			->execute()
			->get('version');
	}

	/**
	 * Get already applied versions for selected database
	 *
	 * @return array
	 */
	private function get_applied_versions()
	{
		$versions = array();
		$rows = DB::query(Database::SELECT, 'SELECT * FROM dbmigrations ORDER BY version ASC')
			->execute();

		foreach($rows as $v)
		{
			$versions[] = $v['version'];
		}

		return $versions;
	}

	/**
	 * Get not applied version numbers
	 *
	 * @return array
	 */
	private function get_not_applied_versions()
	{
		$not_applied = array();
		foreach ($this->all_versions as $version => $file)
		{
			if(in_array($version, $this->applied_versions)) continue;
			$not_applied[$version] = $file;
		}

		return $not_applied;
	}

	/**
	 * Apply migrations to selected database
	 *
	 * @param array $not_applied
	 */
	private function apply_migrations(array $not_applied = array())
	{
		if(empty($not_applied))
		{
			$not_applied = $this->get_not_applied_versions();
		}

		if(count($not_applied) == 0)
		{
			self::display("\nAll versions applied.", 'blue');
			return;
		}

		foreach ($not_applied as $version => $file)
		{
			require_once($file);
			$class = 'C'.$version;
			$c = new $class();
			self::display('Applying '.$version.': '.$c->desc);
			$c->execute();

			$this->mark_as_applied($version);
		}
	}

	/**
	 * Marks migration version as applied
	 *
	 * @return NULL
	 */
	private function mark_applied()
	{
		$not_applied = $this->get_not_applied_versions();

		if(count($not_applied) == 0)
		{
			self::display("\nAll versions applied.", 'blue');
			return;
		}

		self::display("\nNot applied versions:");

		foreach($not_applied as $version => $file)
		{
			require_once($file);
			$class = 'C'.$version;
			$c = new $class();

			self::display($version.' - '.$c->desc);
		}

		self::display("\nMark as applied (ENTER to exit) ==> ", FALSE);

		$input = trim(fgets(STDIN));

		if(array_key_exists($input, $not_applied))
		{
			$this->mark_as_applied($input);
		}
		else
		{
			if($input !== '') self::display("There is no vession: $input!", 'red');
		}
	}

	/**
	 * Marks migration version as applied in the selected database
	 *
	 * @param int $version
	 * @return NULL
	 */
	private function mark_as_applied($version)
	{
		$sql = "INSERT dbmigrations SET version = :version";
		DB::query(Database::INSERT, $sql)->param(':version', $version)->execute();
	}

	/**
	 * Display message on CLI
	 *
	 * @param string $msg Message
	 * @param string $newLine_fcolor Insert new line / color of the message
	 * @param string $fcolor Message color
	 * @param string $bcolor Message background color
	 * @return string The message
	 */
	public static function display($msg = '', $newLine_fcolor = TRUE, $fcolor = NULL, $bcolor = NULL)
	{
		if( ! is_bool($newLine_fcolor))
		{
			$fcolor = $newLine_fcolor;
			$newLine_fcolor = TRUE;
		}

		$colored_string = "";

		// Check if given foreground color found
		if (isset(self::$foreground_colors[$fcolor]))
		{
			$colored_string .= "\033[" . self::$foreground_colors[$fcolor] . "m";
		}

		// Check if given background color found
		if (isset(self::$background_colors[$bcolor]))
		{
			$colored_string .= "\033[" . self::$background_colors[$bcolor] . "m";
		}

		// Add string and end coloring
		$colored_string .=  $msg . "\033[0m";

		$colored_string = $newLine_fcolor ? $colored_string . "\n" : $colored_string;

		if(Kohana::$is_cli)
		{
			echo $colored_string;
			ob_get_level() && ob_end_flush();
		}

		return $colored_string;
	}

	/**
	 * Available foreground colors
	 *
	 * @var array
	 */
	protected static $foreground_colors = array
	(
			'black' => '0;30',
			'dark_gray' => '1;30',
			'blue' => '0;34',
			'light_blue' => '1;34',
			'green' => '0;32',
			'light_green' => '1;32',
			'cyan' => '0;36',
			'light_cyan' => '1;36',
			'red' => '0;31',
			'light_red' => '1;31',
			'purple' => '0;35',
			'light_purple' => '1;35',
			'brown' => '0;33',
			'yellow' => '1;33',
			'light_gray' => '0;37',
			'white' => '1;37',
	);

	/**
	 * Available background colors
	 *
	 * @var array
	 */
	protected static $background_colors = array
	(
			'black' => '40',
			'red' => '41',
			'green' => '42',
			'yellow' => '43',
			'blue' => '44',
			'magenta' => '45',
			'cyan' => '46',
			'light_gray' => '47'
	);
}
