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
		'Required'               => 'Boolean',
		'Sort'                   => 'Int'
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

	public static $default_sort = 'Sort';
	
	/**
	 * Temporary local cache of form fields - otherwise we can potentially be calling
	 * getMemberFormFields 20 - 30 times per request via getDefaultTitle.
	 * 
	 * It's declared as a static so all instances have access to it after it's
	 * loaded the first time. 
	 *
	 * @var FieldSet
	 */
	protected static $member_fields;

	/**
	 * @return 
	 */
	public function getCMSFields() {
		$fields       = parent::getCMSFields()->findormakeTab("Root.Main")->Children;
		$memberFields = $this->getMemberFields();
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

		$fields->removeByName('Sort');
		if (class_exists('SortableDataObject')){$fields->removeByName('SortOrder');}

		return $fields;
	}
	
	public function onBeforeWrite(){
		parent::onBeforeWrite();
			if (class_exists('SortableDataObject'))
				if(isset($this->owner->Sort) && isset($this->owner->SortOrder)){
					$changed = $this->getChangedFields();
					if(isset($changed['SortOrder']) && $changed['SortOrder']) {
						//Default sort is always used
						$this->owner->Sort=$this->owner->SortOrder;
					}elseif(isset($changed['Sort']) && $changed['Sort']) {
						//User might be migrating
						$this->owner->SortOrder=$this->owner->Sort;
					}
				}
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
		$fields = $this->getMemberFields();
		$field  = $fields->dataFieldByName($this->MemberField);

		return $field->Title() ? $field->Title() : $field->Name();
	}
	
	protected function getMemberFields() {
		if (!self::$member_fields) {
			self::$member_fields = singleton('Member')->getMemberFormFields();
		}
		return self::$member_fields;
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