<?php
/**
 * @package silverstripe-memberprofiles
 */
class MemberProfileField extends DataObject {

	public static $db = array (
		'ProfileVisibility'      => 'Enum("Edit, Readonly, Hidden", "Edit")',
		'RegistrationVisibility' => 'Enum("Edit, Readonly, Hidden", "Edit")',
		'MemberField'            => 'Varchar(100)',
		'CustomTitle'            => 'Varchar(100)',
		'DefaultValue'           => 'Text',
		'Note'                   => 'Varchar(255)',
		'CustomError'            => 'Varchar(255)',
		'Unique'                 => 'Boolean',
		'Required'               => 'Boolean'
	);

	public static $has_one = array (
		'ProfilePage' => 'MemberProfilePage'
	);

	/**
	 * @uses   MemberProfileField::getDefaultTitle
	 * @return string
	 */
	public function getTitle() {
		return $this->CustomTitle ? $this->CustomTitle : $this->getDefaultTitle();
	}

	/**
	 * Get the default title for this field, derived from {@link Member::getMemberFormFields}.
	 *
	 * @return string
	 */
	public function getDefaultTitle() {
		$fields = singleton('Member')->getMemberFormFields();
		$field  = $fields->dataFieldByName($this->MemberField);

		return $field->Title() ? $field->Title() : $field->Name();
	}

	/**
	 * @return bool
	 */
	public function isAlwaysRequired() {
		return in_array (
			$this->MemberField,
			array(Member::get_unique_identifier_field(), 'Password')
		);
	}

	/**
	 * @return bool
	 */
	public function isAlwaysUnique() {
		return $this->MemberField == Member::get_unique_identifier_field();
	}

	public function getUnique() {
		return $this->getField('Unique') || $this->isAlwaysUnique();
	}

	public function getRequired() {
		return $this->getField('Required') || $this->isAlwaysRequired();
	}

}