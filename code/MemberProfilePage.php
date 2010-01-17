<?php
/**
 * @package silverstripe-memberprofiles
 */
class MemberProfilePage extends Page {

	public static $db = array (
		'AllowRegistration' => 'Boolean',
		'EmailValidation'   => 'Boolean'
	);

	public static $has_many = array (
		'Fields' => 'MemberProfileField'
	);

	public static $many_many = array (
		'Groups' => 'Group'
	);

	public static $defaults = array (
		'AllowRegistration' => true,
		'EmailValidation'   => true
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Content', $profile = new Tab('Profile'), 'Metadata');
		$fields->addFieldToTab('Root.Content', $settings = new Tab('Settings'), 'Metadata');

		$profile->setTitle(_t('MemberProfiles.PROFILE', 'Profile'));
		$settings->setTitle(_t('MemberProfiles.SETTINGS', 'Settings'));

		$profile->push(new HeaderField (
			'FieldsHeader', _t('MemberProfiles.PROFILEREGFIELDS', 'Profile/Registration Fields')
		));
		$profile->push($fieldsTable = new TableField (
			'Fields',
			'MemberProfileField',
			array (
				'DefaultTitle'           => _t('MemberProfiles.TITLE', 'Title'),
				'RegistrationVisibility' => _t('MemberProfiles.REGISTRATIONVIS', ' Registration Visibility'),
				'ProfileVisibility'      => _t('MemberProfiles.PROFILEVIS', 'Profile Visibility'),
				'CustomTitle'            => _t('MemberProfiles.CUSTOMTITLE', 'Custom Title'),
				'Note'                   => _t('MemberProfiles.NOTE', 'Note'),
				'CustomError'            => _t('MemberProfiles.CUSTOMERRMESSAGE', 'Custom Error Message'),
				'Unique'                 => _t('MemberProfiles.UNIQUE', 'Unique'),
				'Required'               => _t('MemberProfiles.REQUIRED', 'Required')
			),
			array (
				'DefaultTitle'           => 'ReadonlyField',
				'RegistrationVisibility' => new DropdownField (
					'RegistrationVisibility',
					'',
					$showOptions = array (
						'Edit'     => _t('MemberProfiles.ALLOWEDIT', 'Allow Editing'),
						'Readonly' => _t('MemberProfiles.READONLY', 'Show readonly'),
						'Hidden'   => _t('MemberProfiles.HIDDEN', 'Do not show')
					)
				),
				'ProfileVisibility' => new DropdownField('ProfileVisibility', '', $showOptions),
				'CustomTitle'       => 'TextField',
				'Note'              => 'TextField',
				'CustomError'       => 'TextField',
				'Unique'            => 'CheckboxField',
				'Required'          => 'CheckboxField'
			),
			'ProfilePageID',
			$this->ID
		));

		$fieldsTable->setPermissions(array('show', 'edit'));
		$fieldsTable->setCustomSourceItems($this->getProfileFields());

		$settings->push(new HeaderField (
			'RegSettingsHeader', _t('MemberProfiles.REGSETTINGS', 'Registration Settings'))
		);
		$settings->push(new CheckboxField (
			'AllowRegistration', _t('MemberProfiles.ALLOWREG', 'Allow registration via this page'))
		);
		$settings->push(new CheckboxField (
			'EmailValidation', _t('MemberProfiles.EMAILVALID', 'Require email validation')
		));

		$settings->push(new HeaderField (
			'GroupSettingsHeader', _t('MemberProfiles.GROUPSETTINGS', 'Group Settings')
		));
		$settings->push(new LiteralField (
			'GroupsNote',
			_t (
				'MemberProfiles.GROUPSNOTE',
				'<p>Any users registering via this page will be added to the below groups (if ' .
				'registration is enabled). Conversely, a member must belong to these groups '   .
				'in order to edit their profile on this page.</p>'
			)
		));
		$settings->push(new CheckboxSetField (
			'Groups', '', DataObject::get('Group')->map()
		));

		return $fields;
	}

	public function getProfileFields() {
		$set        = $this->Fields();
		$fields     = singleton('Member')->getMemberFormFields()->dataFields();
		$setNames   = array_keys($set->map('MemberField'));
		$fieldNames = array_keys($fields);

		foreach($set as $field) {
			if(!in_array($field->MemberField, $fieldNames)) {
				$set->remove($field);
			}
		}

		foreach($fields as $name => $field) {
			if(in_array($name, $setNames)) continue;

			$profileField = new MemberProfileField();
			$profileField->MemberField   = $name;
			$profileField->ProfilePageID = $this->ID;
			$profileField->write();

			$set->add($profileField);
		}

		return $set;
	}

}

/**
 *
 */
class MemberProfilePage_Controller extends Page_Controller {

	public static $allowed_actions = array (
		'index',
		'RegisterForm',
		'ProfileForm'
	);

	public function index() {
	}

	public function RegisterForm() {
		return new Form (
			$this,
			'RegisterForm',
			$this->getProfileFields(),
			new FieldSet (
				new FormAction('register', _t('MemberProfiles.REGISTER', 'Register'))
			)
		);
	}

	public function register($data, $form) {
	}

	public function ProfileForm() {
		return new Form (
			$this,
			'ProfileForm',
			$this->getProfileFields(),
			new FieldSet (
				new FormAction('save', _t('MemberProfiles.SAVE', 'Save'))
			)
		);
	}

	public function save($data, $form) {
	}

	/**
	 * @return FieldSet
	 */
	protected function getProfileFields() {
		$profileFields = $this->Fields();
		$memberFields  = singleton('Member')->getMemberFormFields();
		$fields        = new FieldSet();

		foreach($profileFields as $profileField) {
			$name        = $profileField->MemberField;
			$memberField = $memberFields->dataFieldByName($name);

			$field = clone $memberField;
			$field->setTitle($profileField->Title);
			$field->setRightTitle($profileField->Note);
			$fields->push($field);
		}

		$this->extend('updateProfileFields', $fields);
		return $fields;
	}

}