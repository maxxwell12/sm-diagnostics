<?php
/**
 * SMF Hook Installer
 *
 * @package forum/smf-tools
 * @author Jason Clemons <jason@simplemachines.org>
 * @copyright 2016 Jason Clemons
 * @license MIT
 *
 * @version 1.0.0
 */

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');

// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

// Define the hooks
$hook_functions = array(
	'integrate_admin_include' => 'Sources/Subs-Diagnostics.php',
	'integrate_admin_areas' => 'hookAdminMenu'
);

// Do the deed
foreach ($hook_functions as $hook => $function) {
	if ($context['uninstalling'])
		remove_integration_function($hook, $function);
	else
		add_integration_function($hook, $function);
}
