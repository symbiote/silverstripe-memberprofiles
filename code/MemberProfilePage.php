<?php
/**
 * A MemberProfilePage allows the administratior to set up a page with a subset of the
 * fields available on the member object, then allow members to register and edit
 * their profile using these fields.
 *
 * It also supports email validation.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfilePage extends Page {

	public static $db = array (
		'ProfileTitle'             => 'Varchar(255)',
		'RegistrationTitle'        => 'Varchar(255)',
		'AfterRegistrationTitle'   => 'Varchar(255)',
		'ProfileContent'           => 'HTMLText',
		'RegistrationContent'      => 'HTMLText',
		'AfterRegistrationContent' => 'HTMLText',
		'AllowRegistration'        => 'Boolean',

		'EmailValidation'     => 'Boolean',
		'EmailFrom'           => 'Varchar(255)',
		'EmailSubject'        => 'Varchar(255)',
		'EmailTemplate'       => 'Text',
		'ConfirmationTitle'   => 'Varchar(255)',
		'ConfirmationContent' => 'HTMLText'
	);

	public static $has_many = array (
		'Fields' => 'MemberProfileField'
	);

	public static $many_many = array (
		'Groups' => 'Group'
	);

	public static $defaults = array (
		'ProfileTitle'             => 'Edit Profile',
		'RegistrationTitle'        => 'Register / Log In',
		'AfterRegistrationTitle'   => 'Registration Successful',
		'AfterRegistrationContent' => '<p>Thank you for registering!</p>',
		'AllowRegistration'        => true,
		'EmailValidation'          => true,
		'ConfirmationTitle'        => 'Account Confirmed',
		'ConfirmationContent'      => '<p>Your account is now active, and you have been logged in. Thankyou!</p>'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root', $validation = new Tab('Validation'), 'Behaviour');
		$fields->addFieldToTab('Root.Content', $profileContent = new Tab('Profile'), 'Metadata');
		$fields->addFieldToTab('Root.Content', $regContent = new Tab('Registration'), 'Metadata');
		$fields->addFieldToTab('Root.Content', $afterReg = new Tab('AfterRegistration'), 'Metadata');

		$validation->setTitle(_t('MemberProfiles.VALIDATION', 'Validation'));
		$profileContent->setTitle(_t('MemberProfiles.PROFILE', 'Profile'));
		$regContent->setTitle(_t('MemberProfiles.REGISTRATION', 'Registration'));
		$afterReg->setTitle(_t('MemberProfiles.AFTERRED', 'After Registration'));

		foreach(array('Profile', 'Registration', 'AfterRegistration') as $tab) {
			$fields->addFieldToTab (
				"Root.Content.$tab",
				new TextField("{$tab}Title", _t('MemberProfiles.TITLE', 'Title'))
			);

			$fields->addFieldToTab (
				"Root.Content.$tab",
				new HtmlEditorField("{$tab}Content", _t('MemberProfiles.CONTENT', 'Content'))
			);
		}

		$fields->removeFieldFromTab('Root.Content.Main', 'Title');
		$fields->removeFieldFromTab('Root.Content.Main', 'Content');

		if(!function_exists('member_profile_page_get_tablefields')) {
			/**
			 * Gets the table fields for editing a specific {@link MemberProfileField} instance.
			 *
			 * @param  MemberProfileField $record
			 * @return FieldSet
			 */
			function get_profile_table_fields($record) {
				$fields = new FieldSet (
					new ReadonlyField('DefaultTitle'),
					new DropdownField (
						'RegistrationVisibility',
						'',
						$showOptions = array (
							'Edit'     => _t('MemberProfiles.ALLOWEDIT', 'Allow Editing'),
							'Readonly' => _t('MemberProfiles.READONLY', 'Show readonly'),
							'Hidden'   => _t('MemberProfiles.HIDDEN', 'Do not show')
						)
					),
					new DropdownField('ProfileVisibility', '', $showOptions),
					new TextField('CustomTitle'),
					new TextField('Note'),
					new TextField('CustomError'),
					$unique   = new CheckboxField('Unique'),
					$required = new CheckboxField('Required')
				);

				if($record) {
					if($record->isAlwaysUnique())   $fields->makeFieldReadonly('Unique');
					if($record->isAlwaysRequired()) $fields->makeFieldReadonly('Required');
				}

				return $fields;
			}
		}

		$fields->addFieldsToTab('Root.Content.Main', new HeaderField (
			'FieldsHeader', _t('MemberProfiles.PROFILEREGFIELDS', 'Profile/Registration Fields')
		));
		$fields->addFieldToTab('Root.Content.Main', $fieldsTable = new TableField (
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
			'get_profile_table_fields',
			'ProfilePageID',
			$this->ID
		));

		$fieldsTable->setPermissions(array('show', 'edit'));
		$fieldsTable->setCustomSourceItems($this->getProfileFields());

		$validation->push(new HeaderField (
			'EmailValidHeader', _t('MemberProfiles.EMAILVALIDATION', 'Email Validation')
		));
		$validation->push(new CheckboxField (
			'EmailValidation', _t('MemberProfiles.EMAILVALID', 'Require email validation')
		));
		$validation->push(new TextField (
			'EmailSubject', _t('MemberProfiles.VALIDEMAILSUBJECT', 'Validation email subject')
		));
		$validation->push(new TextField (
			'EmailFrom', _t('MemberProfiles.EMAILFROM', 'Email from')
		));
		$validation->push(new TextareaField (
			'EmailTemplate', _t('MemberProfiles.EMAILTEMPLATE', 'Email template')
		));
		$validation->push(new LiteralField (
			'TemplateNote', MemberConfirmationEmail::TEMPLATE_NOTE
		));
		$validation->push(new HeaderField (
			'ConfirmationContentHeader', _t('MemberProfiles.CONFIRMCONTENT', 'Confirmation Content')
		));
		$validation->push(new LiteralField (
			'ConfirmationNote',
			'<p>' . _t (
				'MemberProfiles.CONFIRMNOTE',
				'This content is displayed when a user confirms their account.'
			) . '</p>'
		));
		$validation->push(new TextField (
			'ConfirmationTitle', _t('MemberProfiles.TITLE', 'Title')
		));
		$validation->push(new HtmlEditorField (
			'ConfirmationContent', _t('MemberProfiles.CONTENT', 'Content')
		));

		$fields->addFieldToTab (
			'Root.Behaviour',
			new HeaderField (
				'RegSettingsHeader', _t('MemberProfiles.REGSETTINGS', 'Registration Settings')
			),
			'ClassName'
		);
		$fields->addFieldToTab (
			'Root.Behaviour',
			new CheckboxField (
				'AllowRegistration', _t('MemberProfiles.ALLOWREG', 'Allow registration via this page')
			),
			'ClassName'
		);
		$fields->addFieldToTab (
			'Root.Behaviour',
			new HeaderField (
				'PageSettingsHeader', _t('MemberProfiles.PAGESETTINGS', 'Page Settings')
			),
			'ClassName'
		);

		$fields->addFieldToTab('Root.Content.Main', new HeaderField (
			'GroupSettingsHeader', _t('MemberProfiles.GROUPSETTINGS', 'Group Settings')
		));
		$fields->addFieldToTab('Root.Content.Main', new LiteralField (
			'GroupsNote',
			_t (
				'MemberProfiles.GROUPSNOTE',
				'<p>Any users registering via this page will be added to the below groups (if ' .
				'registration is enabled). Conversely, a member must belong to these groups '   .
				'in order to edit their profile on this page.</p>'
			)
		));
		$fields->addFieldToTab('Root.Content.Main', new CheckboxSetField (
			'Groups', '', DataObject::get('Group')->map()
		));

		return $fields;
	}

	/**
	 * Get either the default or custom email template.
	 *
	 * @return string
	 */
	public function getEmailTemplate() {
		return ($t = $this->getField('EmailTemplate')) ? $t : MemberConfirmationEmail::DEFAULT_TEMPLATE;
	}

	/**
	 * Get either the default or custom email subject line.
	 *
	 * @return string
	 */
	public function getEmailSubject() {
		return ($s = $this->getField('EmailSubject')) ? $s : MemberConfirmationEmail::DEFAULT_SUBJECT;
	}

	/**
	 * Get a set of all the {@link MemberProfileFields} available, automatically synced with the
	 * state of the Member object.
	 *
	 * @return DataObjectSet
	 */
	public function getProfileFields() {
		$set        = $this->Fields();
		$fields     = singleton('Member')->getMemberFormFields()->dataFields();
		$setNames   = $set->map('ID', 'MemberField');
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
		'ProfileForm',
		'confirm'
	);

	/**
	 * @uses   MemberProfilePage_Controller::indexRegister
	 * @uses   MemberProfilePage_Controller::indexProfile
	 * @return array
	 */
	public function index() {
		return Member::currentUserID() ? $this->indexProfile() : $this->indexRegister();
	}

	/**
	 * Allow users to register if registration is enabled.
	 *
	 * @return array
	 */
	protected function indexRegister() {
		if(!$this->AllowRegistration) return Security::permissionFailure($this, _t (
			'MemberProfiles.CANNOTREGPLEASELOGIN',
			'You cannot register on this profile page. Please login to edit your profile.'
		));

		return array (
			'Title'   => $this->RegisterTitle,
			'Content' => $this->RegisterContent,
			'Form'    => $this->RegisterForm()
		);
	}

	/**
	 * Allows users to edit their profile if they are in the groups this page is
	 * restricted to.
	 *
	 * @return array
	 */
	protected function indexProfile() {
		$member = Member::currentUser();

		foreach($this->Groups() as $group) {
			if(!$member->inGroup($group)) {
				return Security::permissionFailure($this);
			}
		}

		$form = $this->ProfileForm();
		$form->loadDataFrom($member);

		return array (
			'Title'   => $this->ProfileTitle,
			'Content' => $this->ProfileContent,
			'Form'  => $form
		);
	}

	/**
	 * @uses   MemberProfilePage_Controller::getProfileFields
	 * @return Form
	 */
	public function RegisterForm() {
		$form = new Form (
			$this,
			'RegisterForm',
			$this->getProfileFields('Registration'),
			new FieldSet (
				new FormAction('register', _t('MemberProfiles.REGISTER', 'Register'))
			),
			new MemberProfileValidator($this->Fields())
		);

		if(class_exists('SpamProtectorManager')) {
			SpamProtectorManager::update_form($form);
		}

		return $form;
	}

	/**
	 * Handles validation and saving new Member objects, as well as sending out validation emails.
	 */
	public function register($data, $form) {
		$member = new Member();
		$form->saveInto($member);

		try {
			$member->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
		}

		foreach($this->Groups() as $group) $member->Groups()->add($group);

		if($this->EmailValidation) {
			$email = new MemberConfirmationEmail($this, $member);
			$email->send();

			$member->NeedsValidation = true;
			$member->write();
		} else {
			$member->logIn();
		}

		return array (
			'Title'   => $this->AfterRegistrationTitle,
			'Content' => $this->AfterRegistrationContent,
		);
	}

	/**
	 * @uses   MemberProfilePage_Controller::getProfileFields
	 * @return Form
	 */
	public function ProfileForm() {
		return new Form (
			$this,
			'ProfileForm',
			$this->getProfileFields('Profile'),
			new FieldSet (
				new FormAction('save', _t('MemberProfiles.SAVE', 'Save'))
			),
			new MemberProfileValidator($this->Fields(), Member::currentUser())
		);
	}

	/**
	 * Updates an existing Member's profile.
	 */
	public function save(array $data, Form $form) {
		$member = Member::currentUser();
		$form->saveInto($member);

		try {
			$member->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
		}

		$form->sessionMessage (
			_t('MemberProfiles.PROFILEUPDATED', 'Your profile has been updated.'),
			'good'
		);
		return Director::redirectBack();
	}

	/**
	 * Allows the user to confirm their account by clicking on the validation link in
	 * the confirmation email.
	 *
	 * @param  HTTPRequest $request
	 * @return array
	 */
	public function confirm($request) {
		if(Member::currentUser()) {
			return Security::permissionFailure ($this, _t (
				'MemberProfiles.CANNOTCONFIRMLOGGEDIN',
				'You cannot confirm account while you are logged in.'
			));
		}

		if (
			!$this->EmailValidation
			|| (!$id = $request->param('ID')) || (!$key = $request->getVar('key')) || !is_numeric($id)
			|| !$member = DataObject::get_by_id('Member', $id)
		) {
			$this->httpError(404);
		}

		if($member->ValidationKey != $key || !$member->NeedsValidation) {
			$this->httpError(403, 'You cannot validate this member.');
		}

		$member->NeedsValidation = false;
		$member->ValidationKey   = null;
		$member->write();

		$member->logIn();

		return array (
			'Title'   => $this->ConfirmationTitle,
			'Content' => $this->ConfirmationContent
		);
	}

	/**
	 * @param  string $context
	 * @return FieldSet
	 */
	protected function getProfileFields($context) {
		$profileFields = $this->Fields();
		$member        = Member::currentUser() ? Member::currentUser() : singleton('Member');
		$memberFields  = $member->getMemberFormFields();
		$fields        = new FieldSet();

		foreach($profileFields as $profileField) {
			$visibility  = $profileField->{$context . 'Visibility'};
			$name        = $profileField->MemberField;
			$memberField = $memberFields->dataFieldByName($name);

			if(!$memberField || $visibility == 'Hidden') continue;

			$field = clone $memberField;
			$field->setTitle($profileField->Title);
			$field->setRightTitle($profileField->Note);

			if($profileField->CustomError) {
				$field->setCustomValidationMessage($profileField->CustomError);
			}

			if($visibility == 'Readonly') {
				$field = $field->performReadonlyTransformation();
			}

			$fields->push($field);
		}

		$this->extend('updateProfileFields', $fields);
		return $fields;
	}

}