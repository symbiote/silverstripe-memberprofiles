<?php
/**
 * @package silverstripe-memberprofiles
 */

if(!ClassInfo::exists('Orderaable')) {
	$view = new DebugView();
	$link = 'https://github.com/ajshort/silverstripe-orderable';

	if(!headers_sent()) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
	}

	$view->writeHeader();
	$view->writeInfo('Dependency Error', 'The Member Profiles module requires the Orderable module.');
	$view->writeParagraph("Please install the <a href=\"$link\">Orderable</a> module.");
	$view->writeFooter();

	exit;
}

Director::addRules(20, array(
	'member-approval' => 'MemberApprovalController'
));

Object::add_extension('Member', 'MemberProfileExtension');