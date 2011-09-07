<?php
/**
 * @package silverstripe-memberprofiles
 */

if (!class_exists('Orderable')) {
	throw new Exception('The Member Profiles module requires the Orderable module.');
}

Director::addRules(20, array(
	'member-approval' => 'MemberApprovalController'
));

Object::add_extension('Member', 'MemberProfileExtension');