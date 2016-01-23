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

function ManageDiagnostics()
{
	global $txt, $sourcedir, $context;

	// Gotta be an Admin to even think about being here!
	isAllowedTo('admin_forum');

	// Load the templates and languages
	loadTemplate('Diagnostics');

	// We need our helper functions
	require_once($sourcedir . '/Subs-Diagnostics.php');

	// Let's use some admin tabs...
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title'       => $txt['diagnostics_title'],
		'description' => $txt['diagnostics_info'],
		'tabs' => array(
			'overview' => array(),
			'phpinfo' => array(),
			'whitespace' => array(),
			'permissions' => array(),
			'connection' => array(),
			'email' => array(),
		),
	);

	// Time for some sub actions.
	$subActions = array(
		'overview' => array(
			'function'   => 'DiagnosticOverview',
			'template'   => 'diagnostic_overview',
			'activities' => array()
		),
		'phpinfo' => array(
			'function'   => 'DiagnosticPhpinfo',
			'template'   => 'diagnostic_phpinfo',
			'activities' => array()
		),
		'whitespace' => array(
			'function'   => 'DiagnosticWhitespace',
			'template'   => 'diagnostic_whitespace',
			'activities' => array()
		),
		'permissions' => array(
			'function'   => 'DiagnosticPermissions',
			'template'   => 'diagnostic_permissions',
			'activities' => array()
		),
		'connection' => array(
			'function'   => 'DiagnosticConnection',
			'template'   => 'diagnostic_connection',
			'activities' => array()
		),
		'email' => array(
			'function'   => 'DiagnosticEmail',
			'template'   => 'diagnostic_email',
			'activities' => array()
		),
		'phpinfo' => array(
			'function'   => 'DiagnosticPhpinfo',
			'template'   => 'diagnostic_phpinfo',
			'activities' => array()
		)
	);

	// Yep, sub-action time!
	if (isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]))
	{
		$subAction = $_REQUEST['sa'];
	}
	else
	{
		$subAction = 'overview';
	}

	// Doing something special?
	if (isset($_REQUEST['activity']) && isset($subActions[$subAction]['activities'][$_REQUEST['activity']]))
	{
		$activity = $_REQUEST['activity'];
	}

	// Set a few things.
	$context['page_title']   = $txt['diagnostics_title'];
	$context['sub_action']   = $subAction;
	$context['sub_template'] = !empty($subActions[$subAction]['template']) ? $subActions[$subAction]['template'] : '';

	// Finally fall through to what we are doing.
	$subActions[$subAction]['function']();

	// Any special activity?
	if (isset($activity))
	{
		$subActions[$subAction]['activities'][$activity]();
	}
}

function DiagnosticOverview()
{
	global $txt, $modSettings, $scripturl, $context, $options, $settings, $forum_version, $db_type;

	// Server Stuff
	$sql_version       = getSqlVersion();
	$php_version       = phpversion() . ' (' . @php_sapi_name() . ')';
	$server_software   = @php_uname();
	$load_limit        = getServerLoad();
	$total_memory      = '--';
	$avail_memory      = '--';
	$_disabled         = @ini_get('disable_functions') ? explode(',', @ini_get('disable_functions')) : array();
	$_shellExecAvail   = in_array('shell_exec', $_disabled) ? false : true;

	// Check Memory
	if (strpos(strtolower(PHP_OS), 'win') === 0)
	{
		// Make sure we have shell access
		$mem = $_shellExecAvail ? @shell_exec('systeminfo') : null;

		if ($mem)
		{
			$server_reply = explode("\n", str_replace("\r", '', $mem));

			if (count($server_reply))
			{
				foreach ($server_reply as $info)
				{
					if (strstr($info, $txt['diagnostics_totalmem']))
					{
						$total_memory = trim(str_replace(':', '', strrchr($info, ':')));
					}

					if (strstr($info, $txt['diagnostics_availmem']))
					{
						$avail_memory = trim(str_replace(':', '', strrchr($info, ':')));
					}
				}
			}
		}
	}
	else
	{
		// Again, no shell, no tell
		$mem = $_shellExecAvail ? @shell_exec('free -m') : null;

		if ($mem)
		{
			$server_reply = explode("\n", str_replace("\r", '', $mem));
			$mem          = array_slice($server_reply, 1, 1);
			$mem          = preg_split('#\s+#', $mem[0]);

			$total_memory = ($mem[1]) ? $mem[1] . ' MB' : '--';
			$avail_memory = ($mem[3]) ? $mem[3] . ' MB' : '--';
		}
		else
		{
			$total_memory = '--';
			$avail_memory = '--';
		}
	}

	$disabled_functions = (is_array($_disabled) && count($_disabled)) ? implode(', ', $_disabled) : $txt['diagnostics_noinfo'];
	$extensions         = get_loaded_extensions();
	$extensions         = array_combine($extensions, $extensions);

	sort($extensions, SORT_STRING);

	// Arrange the data and pass it to the template
	$data = array(
		'version_forum' => 'v' . str_replace('SMF ', '', $forum_version),
		'version_sql'   => $sql_version,
		'driver_type'   => strtoupper($db_type),
		'version_php'   => $php_version,
		'disabled'      => $disabled_functions,
		'extensions'    => str_replace('suhosin', '<strong>suhosin</strong>', implode(', ', $extensions)),
		'safe_mode'     => ini_get('safe_mode') ? '<span style="color:red;font-weight:bold;">' . $txt['diagnostics_fon'] . '</span>' : '<span style="color:green;font-weight:bold;">' . $txt['diagnostics_foff'] . '</span>',
		'server'        => $server_software,
		'load'          => $load_limit,
		'total_memory'  => $total_memory,
		'avail_memory'  => $avail_memory
	);

	if ($_shellExecAvail)
	{
		if (strpos(strtolower(PHP_OS), 'win') === 0)
		{
			$tasks = @shell_exec('tasklist');
			$tasks = str_replace(' ', '&nbsp;', $tasks);
		}
		else if (strtolower(PHP_OS) == 'darwin')
		{
			$tasks = @shell_exec('top -1 1');
			$tasks = str_replace(' ', '&nbsp;', $tasks);
		}
		else
		{
			$tasks = @shell_exec('top -b -n 1');
			$tasks = str_replace(' ', '&nbsp;', $tasks);
		}
	}
	else
	{
		$tasks = '';
	}

	if (!$tasks)
	{
		$tasks = $txt['diagnostics_unable'];
	}
	else
	{
		$tasks = '<pre>' . $tasks . '</pre>';
	}

	$data['tasks'] = $tasks;
	$context['diagnostics'] = $data;
}

// Show our awesome phpinfo()!
function DiagnosticPhpinfo()
{
	global $context;

	// Since we're stripping the head from the original
	// we need to put it back...
	$context['html_headers'] = '
	<style type="text/css">
		.center {text-align: center;}
		.center table {margin-left: auto; margin-right: auto; text-align: left;}
		.center th {text-align: center;}
		h1 {font-size: 150%;}
		h2 {font-size: 125%;}
		.p {text-align: left;}
		.e {background-color: #ccccff; font-weight: bold;}
		.h {background-color: #9999cc; font-weight: bold;}
		.v {background-color: #cccccc; white-space: normal;}
	</style>';

	// Start the output buffer
	@ob_start();
	phpinfo();
	$parsed = @ob_get_contents();
	@ob_end_clean();

	// We only want the meat & potatoes of it
	preg_match('#<body>(.*)</body>#is' , $parsed, $match1);

	$php_body = $match1[1];

	// The following lines prevent certain data, such as cookies,
	// from wrapping and throwing things off. Just a tad bit of
	// housekeeping, if you will.
	$php_body = str_replace('; ', ';<br />', $php_body);
	$php_body = str_replace('%3B', '<br />', $php_body);
	$php_body = str_replace(';i:', ';<br />i:', $php_body);
	$php_body = str_replace(':*.', '<br />:*.', $php_body);
	$php_body = str_replace('bin:/', 'bin<br />:/', $php_body);
	$php_body = str_replace('%2C', '%2C<br />', $php_body);
	$php_body = preg_replace('#,(\d+),#', ',<br />\\1,', $php_body);

	// Send the data out to the template!
	$context['php_body'] = $php_body;
}

// Check for whitespace in our .php files
function DiagnosticWhitespace()
{
	global $context, $boarddir;

	// The data is all done in Subs-ManageDiagnostics.php, we just
	// need to send it to the template.
	$context['diagnostics_whitespace_files'] = whitespaceDirRecurse($boarddir);
}

// Check to make sure all vital files have the proper permissions
function DiagnosticPermissions()
{

}

// Check to make sure that the server can make outbound connections
function DiagnosticConnection()
{

}

// Send test emails to make sure PHP Mail/SMTP are working
function DiagnosticEmail()
{

}

?>