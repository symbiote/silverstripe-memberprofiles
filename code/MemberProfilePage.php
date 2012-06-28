<?php
/**
 * A MemberProfilePage allows the administratior to set up a page with a subset of the
 * fields available on the member object, then allow members to register and edit
 * their profile using these fields.
 *
 * Members who have permission to create new members can also be allowed to
 * create new members via this page.
 *
 * It also supports email validation.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfilePage extends Page implements PermissionProvider {

	public static $db = array (
		'ProfileTitle'             => 'Varchar(255)',
		'RegistrationTitle'        => 'Varchar(255)',
		'AfterRegistrationTitle'   => 'Varchar(255)',
		'ProfileContent'           => 'HTMLText',
		'RegistrationContent'      => 'HTMLText',
		'AfterRegistrationContent' => 'HTMLText',
		'AllowRegistration'        => 'Boolean',
		'AllowProfileViewing'      => 'Boolean',
		'AllowProfileEditing'      => 'Boolean',
		'AllowAdding'              => 'Boolean',
		'RegistrationRedirect'     => 'Boolean',
		'RequireApproval'          => 'Boolean',
		'EmailType'                => 'Enum("Validation, Confirmation, None", "None")',
		'EmailFrom'                => 'Varchar(255)',
		'EmailSubject'             => 'Varchar(255)',
		'EmailTemplate'            => 'Text',
		'ConfirmationTitle'        => 'Varchar(255)',
		'ConfirmationContent'      => 'HTMLText'
	);

	public static $has_one = array(
		'PostRegistrationTarget' => 'SiteTree',
	);

	public static $has_many = array (
		'Fields'   => 'MemberProfileField',
		'Sections' => 'MemberProfileSection'
	);

	public static $many_many = array (
		'Groups'           => 'Group',
		'SelectableGroups' => 'Group',
		'ApprovalGroups'   => 'Group'
	);

	public static $defaults = array (
		'ProfileTitle'             => 'Edit Profile',
		'RegistrationTitle'        => 'Register / Log In',
		'AfterRegistrationTitle'   => 'Registration Successful',
		'AfterRegistrationContent' => '<p>Thank you for registering!</p>',
		'AllowRegistration'        => true,
		'AllowProfileViewing'      => true,
		'AllowProfileEditing'      => true,
		'ConfirmationTitle'        => 'Account Confirmed',
		'ConfirmationContent'      => '<p>Your account is now active, and you have been logged in. Thankyou!</p>'
	);

	/**
	 * An array of default settings for some standard member fields.
	 */
	public static $profile_field_defaults = array(
		'Email' => array(
			'RegistrationVisibility' => 'Edit',
			'ProfileVisibility'      => 'Edit',
			'PublicVisibility'       => 'MemberChoice'),
		'FirstName' => array(
			'RegistrationVisibility' => 'Edit',
			'ProfileVisibility'      => 'Edit',
			'MemberListVisible'      => true,
			'PublicVisibility'       => 'Display'),
		'Surname' => array(
			'RegistrationVisibility'  => 'Edit',
			'ProfileVisibility'       => 'Edit',
			'MemberListVisible'       => true,
			'PublicVisibility'        => 'MemberChoice',
			'PublicVisibilityDefault' => true),
		'Password' => array(
			'RegistrationVisibility' => 'Edit',
			'ProfileVisibility'      => 'Edit')
	);

	public static $description = '';

	public static $icon = 'memberprofiles/images/memberprofilepage.png';

	/**
	 * If profile editing is disabled, but the current user can add members,
	 * just link directly to the add action.
	 *
	 * @param string $action
	 */
	public function Link($action = null) {
		if(
			   !$action
			&& Member::currentUserID()
			&& !$this->AllowProfileEditing
			&& $this->CanAddMembers()
		) {
			$action = 'add';
		}

		return parent::Link($action);
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root', new TabSet('Profile'));
		$fields->addFieldToTab('Root', new Tab('ContentBlocks'));
		$fields->addFieldToTab('Root', new Tab('Email'));
		$fields->fieldByName('Root.Main')->setTitle(_t('MemberProfiles.MAIN', 'Main'));

		$fields->addFieldsToTab('Root.Profile', array(
			new Tab(
				'Fields',
				new GridField(
					'Fields',
					_t('MemberProfiles.PROFILEFIELDS', 'Profile Fields'),
					$this->Fields(),
					$grid = GridFieldConfig_RecordEditor::create()
						->removeComponentsByType('GridFieldDeleteAction')
						->removeComponentsByType('GridFieldAddNewButton')
				)
			),
			new Tab(
				'Groups',
				$groups = new TreeMultiselectField(
					'Groups',
					_t('MemberProfiles.GROUPS', 'Groups'),
					'Group'
				),
				$selectable = new TreeMultiselectField(
					'SelectableGroups',
					_t('MemberProfiles.SELECTABLEGROUPS', 'Selectable Groups'),
					'Group'
				)
			),
			new Tab(
				'PublicProfile',
				new GridField(
					'Sections',
					_t('MemberProfiles.PROFILESECTIONS', 'Profile Sections'),
					$this->Sections(),
					GridFieldConfig_RecordEditor::create()
						->removeComponentsByType('GridFieldAddNewButton')
						->addComponent(new MemberProfilesAddSectionAction())
				)
			)
		));

		$grid->getComponentByType('GridFieldDataColumns')->setFieldFormatting(array(
			'Unique'   => function($val, $obj) { return $obj->dbObject('Unique')->Nice(); },
			'Required' => function($val, $obj) { return $obj->dbObject('Required')->Nice(); }
		));

		if(!$this->AllowProfileViewing) {
			$disabledNote = new LiteralField('PublisProfileDisabledNote', sprintf(
				'<p class="message notice">%s</p>', _t(
					'MemberProfiles.PUBLICPROFILEDISABLED',
					'Public profiles are currently disabled, you can enable them ' .
					'in the "Settings" tab.'
				)
			));
			$fields->insertBefore($disabledNote, 'Sections');
		}

		$groups->setDescription(_t(
			'MemberProfiles.GROUPSNOTE',
			'Any users registering via this page will always be added to ' .
			'these groups (if registration is enabled). Conversely, a member ' .
			'must belong to these groups in order to edit their profile on ' .
			'this page.'
		));

		$selectable->setDescription(_t(
			'MemberProfiles.GROUPSNOTE',
			'Users can choose to belong to these groups, if the  "Groups" field ' .
			'is enabled in the "Fields" tab.'
		));

		$fields->removeByName('Content', true);

		foreach(array('Profile', 'Registration', 'AfterRegistration') as $type) {
			$fields->addFieldToTab("Root.ContentBlocks", new ToggleCompositeField(
				"{$type}Toggle",
				FormField::name_to_label($type),
				array(
					new TextField("{$type}Title", _t('MemberProfiles.TITLE', 'Title')),
					$content = new HtmlEditorField("{$type}Content", _t('MemberProfiles.CONTENT', 'Content'))
				)
			));
			$content->setRows(15);
		}

		$fields->addFieldsToTab('Root.Email', array(
			new OptionsetField(
				'EmailType',
				_t('MemberProfiles.EMAILSETTINGS', 'Email Settings'),
				array(
					'Validation'   => 'Require email validation',
					'Confirmation' => 'Send a confirmation email',
					'None'         => 'None'
				)
			),
			new ToggleCompositeField('EmailContentToggle', 'Email Content', array(
				new TextField('EmailSubject', 'Email subject'),
				new TextField('EmailFrom', 'Email from'),
				new TextareaField('EmailTemplate', 'Email template'),
				new LiteralField('TemplateNote', sprintf(
					'<div class="field">%s</div>', MemberConfirmationEmail::TEMPLATE_NOTE
				))
			)),
			new ToggleCompositeField('ConfirmationContentToggle', 'Confirmation Content', array(
				new TextField('ConfirmationTitle', 'Title'),
				$confContent  = new HtmlEditorField('ConfirmationContent', 'Content')
			))
		));
		$confContent->setRows(15);

		return $fields;
	}

	public function getSettingsFields() {
		$fields = parent::getSettingsFields();

		$fields->addFieldToTab('Root', new Tab('Profile'), 'Settings');
		$fields->addFieldsToTab('Root.Profile', array(
			new CheckboxField(
				'AllowRegistration',
				_t('MemberProfiles.ALLOWREG', 'Allow registration via this page')
			),
			new CheckboxField(
				'AllowProfileEditing',
				_t('MemberProfiles.ALLOWEDITING', 'Allow users to edit their own profile on this page')
			),
			new CheckboxField(
				'AllowAdding',
				_t('MemberProfiles.ALLOWADD', 'Allow adding members via this page')
			),
			new CheckboxField(
				'AllowProfileViewing',
				_t('MemberProfiles.ALLOWPROFILEVIEWING', 'Enable public profiles?')
			),
			new CheckboxField(
				'RequireApproval',
				_t('MemberProfiles.REQUIREREGAPPROVAL', 'Require registration approval by an administrator?')
			),
			$approval = new TreeMultiselectField(
				'ApprovalGroups',
				_t('MemberProfiles.APPROVALGROUPS', 'Approval Groups'),
				'Group'
			),
			new CheckboxField(
				'RegistrationRedirect',
				_t('MemberProfiles.REDIRECTAFTERREG', 'Redirect after registration?')
			),
			new TreeDropdownField(
				'PostRegistrationTargetID',
				_t('MemberProfiles.REDIRECTTOPAGE', 'Redirect To Page'),
				'SiteTree'
			)
		));

		$approval->setDescription(_t(
			'MemberProfiles.NOTIFYTHESEGROUPS',
			'These groups will be notified to approve new registrations'
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
	 * Returns a list of profile field objects after synchronising them with the
	 * Member form fields.
	 *
	 * @return HasManyList
	 */
	public function Fields() {
		$list     = $this->getComponents('Fields');
		$fields   = singleton('Member')->getMemberFormFields()->dataFields();
		$included = array();

		foreach($list as $profileField) {
			if(!array_key_exists($profileField->MemberField, $fields)) {
				$profileField->delete();
			} else {
				$included[] = $profileField->MemberField;
			}
		}

		foreach($fields as $name => $field) {
			if(!in_array($name, $included)) {
				$profileField = new MemberProfileField();
				$profileField->MemberField = $name;

				if(isset(self::$profile_field_defaults[$name])) {
					$profileField->update(self::$profile_field_defaults[$name]);
				}

				$list->add($profileField);
			}
		}

		return $list;
	}

	public function onAfterWrite() {
		if ($this->isChanged('ID')) {
			$section = new MemberProfileFieldsSection();
			$section->ParentID = $this->ID;
			$section->write();
		}

		parent::onAfterWrite();
	}

}

/**
 *
 */
class MemberProfilePage_Controller extends Page_Controller {

	public static $allowed_actions = array (
		'index',
		'RegisterForm',
		'afterregistration',
		'ProfileForm',
		'add',
		'AddForm',
		'confirm',
		'show'
	);

	/**
	 * @uses   MemberProfilePage_Controller::indexRegister
	 * @uses   MemberProfilePage_Controller::indexProfile
	 * @return array
	 */
	public function index() {
		if (isset($_GET['BackURL'])) {
			Session::set('MemberProfile.REDIRECT', $_GET['BackURL']);
		}
		$mode = Member::currentUser() ? 'profile' : 'register';
		$data = Member::currentUser() ? $this->indexProfile() : $this->indexRegister();
		if (is_array($data)) {
			return $this->customise($data)->renderWith(array('MemberProfilePage_'.$mode, 'MemberProfilePage', 'Page'));
		}
		return $data;
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
			'Title'   => $this->obj('RegistrationTitle'),
			'Content' => $this->obj('RegistrationContent'),
			'Form'    => $this->RegisterForm()
		);
	}

	/**
	 * Allows users to edit their profile if they are in at least one of the
	 * groups this page is restricted to, and editing isn't disabled.
	 *
	 * If editing is disabled, but the current user can add users, then they
	 * are redirected to the add user page.
	 *
	 * @return array
	 */
	protected function indexProfile() {
		if(!$this->AllowProfileEditing) {
			if($this->AllowAdding && Injector::inst()->get('Member')->canCreate()) {
				return $this->redirect($this->Link('add'));
			}

			return Security::permissionFailure($this, _t(
				'MemberProfiles.CANNOTEDIT',
				'You cannot edit your profile via this page.'
			));
		}

		$member = Member::currentUser();

		foreach($this->Groups() as $group) {
			if(!$member->inGroup($group)) {
				return Security::permissionFailure($this);
			}
		}

		$form = $this->ProfileForm();
		$form->loadDataFrom($member);

		if($password = $form->Fields()->fieldByName('Password')) {
			$password->setCanBeEmpty(false);
			$password->setValue(null);
			$password->setCanBeEmpty(true);
		}

		return array (
			'Title' => $this->obj('ProfileTitle'),
			'Content' => $this->obj('ProfileContent'),
			'Form'  => $form
		);
	}

	/**
	 * @return MemberProfileViewer
	 */
	public function show() {
		if(!$this->AllowProfileViewing) {
			$this->httpError(404);
		}

		return new MemberProfileViewer($this, 'show');
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
			new FieldList(
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
	public function register($data, Form $form) {
		if($member = $this->addMember($form)) {
			if(!$this->RequireApproval && $this->EmailType != 'Validation' && !$this->AllowAdding) {
				$member->logIn();
			}

			if ($this->RegistrationRedirect) {
				if ($this->PostRegistrationTargetID) {
					$this->redirect($this->PostRegistrationTarget()->Link());
					return;
				}

				if ($sessionTarget = Session::get('MemberProfile.REDIRECT')) {
					Session::clear('MemberProfile.REDIRECT');
					if (Director::is_site_url($sessionTarget)) {
						$this->redirect($sessionTarget);
						return;
					}
				}
			}

			return $this->redirect($this->Link('afterregistration'));
		} else {
			return $this->redirectBack();
		}
	}

	/**
	 * Returns the after registration content to the user.
	 *
	 * @return array
	 */
	public function afterregistration() {
		return array (
			'Title'   => $this->obj('AfterRegistrationTitle'),
			'Content' => $this->obj('AfterRegistrationContent')
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
			new FieldList(
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

		$groupIds = $this->getSettableGroupIdsFrom($form, $member);
		$member->Groups()->setByIDList($groupIds);

		$form->saveInto($member);

		try {
			$member->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return $this->redirectBack();
		}

		$form->sessionMessage (
			_t('MemberProfiles.PROFILEUPDATED', 'Your profile has been updated.'),
			'good'
		);
		return $this->redirectBack();
	}

	/**
	 * Allows members with the appropriate permissions to add/regsiter other
	 * members.
	 */
	public function add($request) {
		if(!$this->AllowAdding || !Injector::inst()->get('Member')->canCreate()) {
			return Security::permissionFailure($this, _t (
				'MemberProfiles.CANNOTADDMEMBERS',
				'You cannot add members via this page.'
			));
		}

		$data = array(
			'Title'   => _t('MemberProfiles.ADDMEMBER', 'Add Member'),
			'Content' => '',
			'Form'    => $this->AddForm()
		);

		return $this->customise($data)->renderWith(array('MemberProfilePage_add', 'MemberProfilePage', 'Page'));
	}

	/**
	 * @return Form
	 */
	public function AddForm() {
		return new Form (
			$this,
			'AddForm',
			$this->getProfileFields('Add'),
			new FieldList(
				new FormAction('doAdd', _t('MemberProfiles.ADD', 'Add'))
			),
			new MemberProfileValidator($this->Fields())
		);
	}

	/**
	 * Saves an add member form submission into a new member object.
	 */
	public function doAdd($data, $form) {
		if($this->addMember($form)) $form->sessionMessage(
			_t('MemberProfiles.MEMBERADDED', 'The new member has been added.'),
			'good'
		);

		return $this->redirectBack();
	}

	public function LoginLink() {
		return Controller::join_links(
			Injector::inst()->get('Security')->Link(),
			'login',
			'?BackURL=' . urlencode($this->Link())
		);
	}

	/**
	 * @return bool
	 */
	public function CanAddMembers() {
		return $this->AllowAdding && singleton('Member')->canCreate();
	}

	/**
	 * Gets the list of groups that can be set after the submission of a particular form
	 *
	 * This works around the problem with the checkboxsetfield which doesn't validate that the
	 * groups that the user has selected are not validated against the list of groups the user is
	 * allowed to choose from.
	 *
	 * @param Form   $form
	 * @param Member $member
	 */
	protected function getSettableGroupIdsFrom(Form $form, Member $member = null) {
		// first off check to see if groups were selected by the user. If so, we want
		// to remove that control from the form list (just in case someone's sent through an
		// ID for a group like, say, the admin's group...). It means we have to handle the setting
		// ourselves, but that's okay
		$groupField = $form->Fields()->dataFieldByName('Groups');
		// The list of selectable groups
		$groupIds = $allowedIds = $this->SelectableGroups()->map('ID', 'ID')->toArray();

		// we need to track the selected groups against the existing user's groups - this is
		// so that we don't accidentally remove them from the list of groups
		// a user might have been placed in via other means
		$existingIds = array();
		if ($member) {
			$existing = $member->Groups();
			if ($existing && $existing->Count() > 0) {
				$existingIds = $existing->map('ID', 'ID')->toArray();
				// remove any that are in the selectable groups map - we only want to
				// worry about those that aren't managed by this form
				foreach ($groupIds as $gid) {
					unset($existingIds[$gid]);
				}
			}
		}

		if ($groupField) {
			$givenIds = $groupField->Value();
			$groupIds = array();
			if ($givenIds) {
				foreach ($givenIds as $givenId) {
					if (isset($allowedIds[$givenId])) {
						$groupIds[] = $givenId;
					}
				}
			}
			$form->Fields()->removeByName('Groups');
		}

		foreach ($this->Groups()->column('ID') as $mustId) {
			$groupIds[] = $mustId;
		}

		foreach ($existingIds as $existingId) {
			if (!in_array($existingId, $groupIds)) {
				$groupIds[] = $existingId;
			}
		}

		return $groupIds;
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
			$this->EmailType != 'Validation'
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
			'Title'   => $this->obj('ConfirmationTitle'),
			'Content' => $this->obj('ConfirmationContent')
		);
	}

	/**
	 * Attempts to save either a registration or add member form submission
	 * into a new member object, returning NULL on validation failure.
	 *
	 * @return Member|null
	 */
	protected function addMember($form) {
		$member   = new Member();
		$groupIds = $this->getSettableGroupIdsFrom($form);

		$form->saveInto($member);

		$member->ProfilePageID   = $this->ID;
		$member->NeedsValidation = ($this->EmailType == 'Validation');
		$member->NeedsApproval   = $this->RequireApproval;

		try {
			$member->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return;
		}

		// set after member is created otherwise the member object does not exist
		$member->Groups()->setByIDList($groupIds);

		// If we require admin approval, send an email to the admin and delay
		// sending an email to the member.
		if ($this->RequireApproval) {
			$groups = $this->ApprovalGroups();
			$emails = array();

			if ($groups) foreach ($groups as $group) {
				foreach ($group->Members() as $_member) {
					if ($member->Email) $emails[] = $_member->Email;
				}
			}

			if ($emails) {
				$email   = new Email();
				$config  = SiteConfig::current_site_config();
				$approve = Controller::join_links(
					Director::baseURL(), 'member-approval', $member->ID, '?token=' . $member->ValidationKey
				);

				$email->setSubject("Registration Approval Requested for $config->Title");
				$email->setBcc(implode(',', array_unique($emails)));
				$email->setTemplate('MemberRequiresApprovalEmail');
				$email->populateTemplate(array(
					'SiteConfig'  => $config,
					'Member'      => $member,
					'ApproveLink' => Director::absoluteURL($approve)
				));

				$email->send();
			}
		} elseif($this->EmailType != 'None') {
			$email = new MemberConfirmationEmail($this, $member);
			$email->send();
		}

		$this->extend('onAddMember', $member);
		return $member;
	}

	/**
	 * @param  string $context
	 * @return FieldSet
	 */
	protected function getProfileFields($context) {
		$profileFields = $this->Fields();
		$fields        = new FieldList();

		// depending on the context, load fields from the current member
		if(Member::currentUser() && $context != 'Add') {
			$memberFields = Member::currentUser()->getMemberFormFields();
		} else {
			$memberFields = singleton('Member')->getMemberFormFields();
		}

		// use the default registration fields for adding members
		if($context == 'Add') {
			$context = 'Registration';
		}

		if ($this->AllowProfileViewing
		    && $profileFields->find('PublicVisibility', 'MemberChoice')
		) {
			$fields->push(new LiteralField('VisibilityNote', '<p>' . _t(
				'MemberProfiles.CHECKVISNOTE',
				'Check fields below to make them visible on your public ' .
				'profile.') . '</p>'));
		}

		foreach($profileFields as $profileField) {
			$visibility  = $profileField->{$context . 'Visibility'};
			$name        = $profileField->MemberField;
			$memberField = $memberFields->dataFieldByName($name);

			// handle the special case of the Groups control so that only allowed groups can be selected
			if ($name == 'Groups') {
				$availableGroups = $this->data()->SelectableGroups();
				$memberField->setSource($availableGroups);
			}

			if(!$memberField || $visibility == 'Hidden') continue;

			$field = clone $memberField;

			if($visibility == 'Readonly') {
				$field = $field->performReadonlyTransformation();
			}

			$field->setTitle($profileField->Title);
			$field->setDescription($profileField->Note);

			if($context == 'Registration' && $profileField->DefaultValue) {
				$field->setValue($profileField->DefaultValue);
			}

			if($profileField->CustomError) {
				$field->setCustomValidationMessage($profileField->CustomError);
			}

			$canSetVisibility = (
				   $this->AllowProfileViewing
				&& $profileField->PublicVisibility != 'Hidden'
			);
			if ($canSetVisibility) {
				$field = new CheckableVisibilityField($field);

				if ($profileField->PublicVisibility == 'Display') {
					$field->makeAlwaysVisible();
				} else {
					$field->getCheckbox()->setValue($profileField->PublicVisibilityDefault);
				}
			}

			$fields->push($field);
		}

		$this->extend('updateProfileFields', $fields);
		return $fields;
	}

}