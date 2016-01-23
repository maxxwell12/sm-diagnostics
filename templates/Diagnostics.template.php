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

 function template_diagnostic_overview()
 {
	global $txt, $context;

	echo '
	<div id="maintain_overview">
		<div class="cat_bar">
			<h3 class="catbg">', $txt['diagnostics_cat_overview'], '</h3>
		</div>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div class="content">
				<table border="0" cellpadding="3">';

	// Loop through all the server data and display it accordingly.
	// The $alternate variable allows for alternating table colors.
	$alternate = false;
	foreach ($context['diagnostics'] as $k => $v) {
		if ($k == 'tasks')
			continue;
		if ($k == 'driver_type')
			continue;

		echo '
					<tr class="windowbg', $alternate ? '' : '2', '">
						<td width="15%" align="right"><strong>', ($k == 'version_sql') ? sprintf($txt['diagnostics_version_sql'], $context['diagnostics']['driver_type']) : $txt['diagnostics_' . $k], '</strong></td>
						<td>' . $v . '</td>
					</tr>';

		$alternate = !$alternate;
	}

	echo '
				</table>
			</div>
			<span class="botslice"><span></span></span>
		</div>';

	// Here's where we display our server processes. 'Tis beautiful :)
	echo '
		<br />
		<div class="cat_bar">
			<h3 class="catbg">', $txt['diagnostics_cat_processes'], '</h3>
		</div>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div class="content">';

	echo '
				<code>
					', $context['diagnostics']['tasks'], '
				</code>';

	echo '
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>';
}

function template_diagnostic_phpinfo()
{
	global $context, $txt;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">', $txt['diagnostics_phpinfo'], '</h3>
	</div>
	<div class="windowbg">
		<span class="topslice"><span></span></span>
		<div class="center">';

	// Nice and simple. phpinfo() did all the work for us!
	echo $context['php_body'];

	echo '
		</div>
		<span class="botslice"><span></span></span>
	</div>';
}

function template_diagnostic_whitespace()
{
	global $context, $txt;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">', $txt['diagnostics_sub_whitespace'], '</h3>
	</div>
	<div class="windowbg">
		<span class="topslice"><span></span></span>
		<div class="content">';

	// If we found any files with whitespace, loop through and display them
	if (count($context['diagnostics_whitespace_files']) && is_array($context['diagnostics_whitespace_files'])) {
		$alternate = false;

		echo '
			<table border="0" cellpadding="3">';

		foreach ($context['diagnostics_whitespace_files'] as $file) {
			echo '
				<tr class="windowbg', $alternate ? '2' : '', '">
					<td>', $file . ' ' . $txt['diagnostics_whitespace_found'], '</td>
				</tr>';

			$alternate = !$alternate;
		}

		echo '
			</table>';
	} else {
		// No files, no problem!
		echo '
			<div id="task_completed">
				', $txt['diagnostics_whitespace_notfound'], '
			</div>';
	}

	echo '
		</div>
		<span class="botslice"><span></span></span>
	</div>';
}

function template_diagnostic_permissions()
{

}

function template_diagnostic_connection()
{

}

function template_diagnostic_email()
{

}

?>