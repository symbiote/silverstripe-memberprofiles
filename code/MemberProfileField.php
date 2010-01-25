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

	public static $summary_fields = array (
		'DefaultTitle'           => 'Field',
		'ProfileVisibility'      => 'Profile Visibility',
		'RegistrationVisibility' => 'Registration Visibility',
		'CustomTitle'            => 'Custom Title',
		'Unique'                 => 'Unique',
		'Required'               => 'Required'
	);

	/**
	 * @return 
	 */
	public function getCMSFields() {
		$fields       = parent::getCMSFields();
		$memberFields = singleton('Member')->getMemberFormFields();
		$memberField  = $memberFields->dataFieldByName($this->MemberField);

		$fields->insertBefore (
			new HeaderField('FieldOptions', $this->fieldLabel('FieldOptions')),
			'ProfileVisibility'
		);
		$fields->insertBefore (
			new ReadonlyField('MemberField', $this->fieldLabel('MemberField')),
			'ProfileVisibility'
		);

		$fields->insertBefore (
			new HeaderField('ValidationHeader', $this->fieldLabel('ValidationOptions')),
			'CustomError'
		);

		if($memberField instanceof DropdownField) {
			$fields->replaceField('DefaultValue', new DropdownField (
				'DefaultValue',
				$this->fieldLabel('DefaultValue'),
				$memberField->getSource(),
				null,
				null,
				true
			));
		} elseif($memberField instanceof TextField) {
			$fields->replaceField('DefaultValue', new TextField (
				'DefaultValue', $this->fieldLabel('DefaultValue')
			));
		} else {
			$fields->removeByName('DefaultValue');
		}

		if($this->isAlwaysUnique())   $fields->makeFieldReadonly('Unique');
		if($this->isAlwaysRequired()) $fields->makeFieldReadonly('Required');

		return $fields;
	}

	/**
	 * @return array
	 */
	public function fieldLabels() {
		return array_merge(parent::fieldLabels(), array (
			'FieldOptions'      => _t('MemberProfiles.FIELDOPTIONS', 'Field Options'),
			'MemberField'       => _t('MemberProfiles.MEMBERFIELD', 'Member Field'),
			'ValidationOptions' => _t('MemberProfiles.VALIDOPTIONS', 'Validation Options'),
			'DefaultValue'      => _t('MemberProfiles.DEFAULTVALUE', 'Default Value')
		));
	}

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