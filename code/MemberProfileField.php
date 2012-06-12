<?php
/**
 * @package silverstripe-memberprofiles
 */
class MemberProfileField extends DataObject {

	public static $db = array (
		'ProfileVisibility'       => 'Enum("Edit, Readonly, Hidden", "Hidden")',
		'RegistrationVisibility'  => 'Enum("Edit, Readonly, Hidden", "Hidden")',
		'MemberListVisible'       => 'Boolean',
		'PublicVisibility'        => 'Enum("Display, MemberChoice, Hidden", "Hidden")',
		'PublicVisibilityDefault' => 'Boolean',
		'MemberField'             => 'Varchar(100)',
		'CustomTitle'             => 'Varchar(100)',
		'DefaultValue'            => 'Text',
		'Note'                    => 'Varchar(255)',
		'CustomError'             => 'Varchar(255)',
		'Unique'                  => 'Boolean',
		'Required'                => 'Boolean'
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

	public static $extensions = array(
		// 'Orderable'
	);

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
		$fields       = parent::getCMSFields();
		$memberFields = $this->getMemberFields();
		$memberField  = $memberFields->dataFieldByName($this->MemberField);

		$fields->insertBefore (
			new ReadonlyField('MemberField', $this->fieldLabel('MemberField')),
			'ProfileVisibility'
		);

		if ($memberField instanceof DropdownField) {
			$fields->replaceField('DefaultValue', new DropdownField (
				'DefaultValue',
				$this->fieldLabel('DefaultValue'),
				$memberField->getSource(),
				null, null, true
			));
		} elseif ($memberField instanceof TextField) {
			$fields->replaceField('DefaultValue', new TextField (
				'DefaultValue', $this->fieldLabel('DefaultValue')
			));
		} else {
			$fields->removeByName('DefaultValue');
		}

		$publicVisibility = array(
			'Display'      => _t('MemberProfiles.ALWAYSDISPLAY', 'Always display'),
			'MemberChoice' => _t('MemberProfiles.MEMBERCHOICE', 'Allow the member to choose'),
			'Hidden'       => _t('MemberProfiles.DONTDISPLAY', 'Do not display')
		);

		$fields->addFieldsToTab('Root.Visibility', array(
			$fields->dataFieldByName('ProfileVisibility'),
			$fields->dataFieldByName('RegistrationVisibility'),
			$fields->dataFieldByName('MemberListVisible'),
			new DropdownField(
				'PublicVisibility', $this->fieldLabel('PublicVisibility'),
				$publicVisibility),
			new CheckboxField(
				'PublicVisibilityDefault',
				_t('MemberProfiles.DEFAULTPUBLIC', 'Mark as public by default'))
		));

		if ($this->isNeverPublic()) {
			$fields->makeFieldReadonly('MemberListVisible');
			$fields->makeFieldReadonly('PublicVisibility');
		}

		$fields->addFieldsToTab('Root.Validation', array(
			$fields->dataFieldByName('CustomError'),
			$fields->dataFieldByName('Unique'),
			$fields->dataFieldByName('Required'),
		));

		if($this->isAlwaysUnique())   $fields->makeFieldReadonly('Unique');
		if($this->isAlwaysRequired()) $fields->makeFieldReadonly('Required');

		return $fields;
	}

	public function getRequirementsForPopup() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('memberprofiles/javascript/MemberProfileFieldPopup.js');
	}

	/**
	 * @return array
	 */
	public function fieldLabels($relations = true) {
		return array_merge(parent::fieldLabels($relations), array (
			'MemberField'       => _t('MemberProfiles.MEMBERFIELD', 'Member Field'),
			'DefaultValue'      => _t('MemberProfiles.DEFAULTVALUE', 'Default Value'),
			'MemberListVisible' => _t('MemberProfiles.VISIBLEMEMLIST', 'Visible on member list'),
			'PublicVisibility'  => _t('MemberProfiles.PUBLICVISIBILITY', 'Public Profile Visiblity')
		));
	}

	/**
	 * @uses   MemberProfileField::getDefaultTitle
	 * @return string
	 */
	public function getTitle() {
		if ($this->CustomTitle) {
			return $this->CustomTitle;
		} else {
			return $this->getDefaultTitle(false);
		}
	}

	/**
	 * Get the default title for this field from the form field.
	 *
	 * @param  bool $force Force a non-empty title to be returned.
	 * @return string
	 */
	public function getDefaultTitle($force = true) {
		$fields = $this->getMemberFields();
		$field  = $fields->dataFieldByName($this->MemberField);
		$title  = $field->Title();

		if (!$title && $force) {
			$title = $field->Name();
		}

		return $title;
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

	/**
	 * @return bool
	 */
	public function isNeverPublic() {
		return $this->MemberField == 'Password';
	}

	public function getUnique() {
		return $this->getField('Unique') || $this->isAlwaysUnique();
	}

	public function getRequired() {
		return $this->getField('Required') || $this->isAlwaysRequired();
	}

	/**
	 * @return string
	 */
	public function getPublicVisibility() {
		if ($this->isNeverPublic()) {
			return 'Hidden';
		} else {
			return $this->getField('PublicVisibility');
		}
	}

	/**
	 * @return bool
	 */
	public function getMemberListVisible() {
		return $this->getField('MemberListVisible') && !$this->isNeverPublic();
	}

}