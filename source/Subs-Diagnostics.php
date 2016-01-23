<?php
/**
 * SM Diagnostics
 *
 * @package forum/sm-diagnostics
 * @author Jason Clemons <jason@simplemachines.org>
 * @copyright 2016 Jason Clemons
 * @license MIT
 *
 * @version 0.1.0
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/*	This file contains functions necessary to complete certain tasks
	within the SMF Diagnostics Center application.

	string getSqlVersion()
		- get the current version of MySQL/SQLite/PostgreSQL running
		  on this server.

	string getServerLoad()
		- get the current CPU load percentage.

	string whitespaceDirRecurse(string dir)
		- recurse through a given directory and check it for unneeded
		  whitespace.
		- return the path of the file if whitespace is found.

	
*/

function getSqlVersion()
{
	global $smcFunc;

	$query = $smcFunc['db_query']('', '
		SELECT VERSION()
		AS \'version\'',
		array()
	);
	while ($row = $smcFunc['db_fetch_row']($query))
		$result[] = $row[0];
	$smcFunc['db_free_result']($query);

	if (!$result)
	{
		$query = $smcFunc['db_query']('', '
			SHOW VARIABLES LIKE \'version\'',
			array()
		);
		while ($row = $smcFunc['db_fetch_row']($query))
			$result[] = $row[0];
		$smcFunc['db_free_result']($query);

		$true_version = $result[0];
		$tmp          = explode('.', preg_replace('#[^\d\.]#', '\\1', $row[0]));

		$sql_version  = sprintf('%d%02d%02d', $tmp[0], $tmp[1], $tmp[2]);
	}
	else
	{
		$sql_version = $result[0];
	}

	return $sql_version;
}

function getServerLoad()
{
	# @ suppressor stops warning in > 4.3.2 with open_basedir restrictions
	if (@file_exists('/proc/loadavg'))
	{
		if ($fh = @fopen('/proc/loadavg', 'r'))
		{
			$data = @fread($fh, 6);

			@fclose($fh);

			$load_avg   = explode(' ', $data);
			$load_limit = trim($load_avg[0]);
		}
	}
	else if (strpos(strtolower(PHP_OS), 'win') === 0)
	{
		$serverstats = @shell_exec('typeperf "Processor(_Total)\% Processor Time" -sc 1');

		if ($serverstats)
		{
			$server_reply = explode("\n", str_replace("\r", '', $serverstats));
			$serverstats  = array_slice($server_reply, 2, 1);
			$statline     = explode(',', str_replace('"', '', $serverstats[0]));
			$load_limit   = round($statline[1], 4);
		}
	}
	else
	{
		if ($serverstats = @exec('uptime'))
		{
			preg_match('/(?:averages)?\: ([0-9\.]+)(,|)[\s]+([0-9\.]+)(,|)[\s]+([0-9\.]+)/', $serverstats, $load);

			$load_limit = $load[1];
		}
	}

	return $load_limit;
}

function whitespaceDirRecurse($dir)
{
	// Directories that we can skip through because they're not that important :)
	$skip_dirs = array(
		'attachments',
		'avatars',
		'Packages'
	);

	$files = array();

	foreach (new DirectoryIterator($dir) as $directory)
	{
		if ($directory->isDot())
		{
			continue;
		}

		if (strpos($directory->getFilename(), '_') === 0 or strpos($directory->getFilename(), '.') === 0)
		{
			continue;
		}

		$newpath = $dir . '/' . $directory->getFilename();
		$level   = explode('/', $newpath);

		if (is_dir($newpath) && !in_array($directory->getFilename(), $skip_dirs))
		{
			$files = array_merge($files, whitespaceDirRecurse($newpath));
		}
		else
		{
			if (strpos($directory->getFilename(), '.php') !== false && !is_dir($newpath))
			{
				$file           = file_get_contents($newpath);
				$has_whitespace = false;

				if (substr(ltrim($file), 0, 3) == '<?php' and substr($file, 0, 3) == '<?php')
				{
					$has_whitespace = true;
				}
				else if (substr(rtrim($file), -2) == '?>' and substr($file, -2) != '?>')
				{
					if (substr(rtrim($file), -2) == '?>' and substr($file, -3) != "?>\n")
					{
						$has_whitespace = true;
					}
				}

				if ($has_whitespace)
				{
					$files[] = $newpath;
				}
			}
		}
	}

	return $files;
}

/**
 * Create the admin menu hook
 *
 * @param &$menu_buttons array
 * @return void
 */
function hookAdminMenu(&$admin_areas)
{
	global $txt;

	// Call this here... it only makes sense
	loadLanguage('Diagnostics');

	$counter	= array_search('maintenance', array_keys($admin_area)) + 1;
	$admin_area = $admin_areas['maintenance']['areas'];

	$admin_areas['maintenance']['areas'] = array_merge(
		array_slice($admin_area, 0, $counter, true), array('diagnostics' => array(
			'label' => $txt['diagnostics_title'],
			'file' => 'ManageDiagnostics.php',
			'icon' => 'support.gif',
			'function' => 'ManageDiagnostics',
			'subsections' => array(
				'overview' => array($txt['diagnostics_sub_overview'], 'admin_forum'),
				'phpinfo' => array($txt['diagnostics_sub_phpinfo'], 'admin_forum'),
				'whitespace' => array($txt['diagnostics_sub_whitespace'], 'admin_forum'),
				'permissions' => array($txt['diagnostics_sub_permissions'], 'admin_forum'),
				'connection' => array($txt['diagnostics_sub_connection'], 'admin_forum'),
				'email' => array($txt['diagnostics_sub_email'], 'admin_forum')
			),
		)), array_slice($admin_area, $counter, count($admin_area), true)
	);
}

?>