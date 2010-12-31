<?php
/**
 * @package silverstripe-memberprofiles
 */

if (!class_exists('Orderable')) {
	throw new Exception('The Member Profiles module requires the Orderable module.');
}

Object::add_extension('Member', 'MemberProfileExtension');