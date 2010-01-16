<?php
/**
 * @package silverstripe-memberprofiles
 */
class MemberProfileField extends DataObject {

	public static $db = array (
		'Display'      => 'Enum("All, Profile, Registration, Readonly, Hidden", "All")',
		'MemberField'  => 'Varchar(100)',
		'CustomTitle'  => 'Varchar(100)',
		'Note'         => 'Varchar(255)',
		'CustomError'  => 'Varchar(255)',
		'Unique'       => 'Boolean',
		'Required'     => 'Boolean'
	);

	public static $has_one = array (
		'ProfilePage' => 'MemberProfilePage'
	);

}