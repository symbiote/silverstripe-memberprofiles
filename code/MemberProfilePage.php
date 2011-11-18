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

	public static $icon = 'memberprofiles/images/memberprofilepage';

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
		'Groups'             => 'Group',
		'SelectableGroups'   => 'Group',
		'ApprovalGroups'     => 'Group',
		'RegistrationGroups' => 'Group'
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
			&& $this->AllowAdding
			&& singleton('Member')->canCreate()
		) {
			$action = 'add';
		}

		return parent::Link($action);
	}

	/**
	 * Provide permissions so users can be configured to create accounts for other people via the frontend
	 *
	 * @return array
	 */
	public function providePermissions() {
		return array(
			'CREATE_OTHER_USERS' => array(
				'name' => _t('MemberProfilePage.CREATE_OTHERS', 'Create other users via a member profile page'),
				'category' => _t('MemberProfilePage.MEMBER_PROFILE_CATEGORY', 'Member profile category'),
				'help' => _t('MemberProfilePage.CREATE_OTHERS_HELP', 'Users with this permission can create new
					members via a member profile page that has been configured for creating new users'),
				'sort' => 400
			)
		);
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('memberprofiles/javascript/MemberProfilePageCms.js');

		// Setup tabs
		$fields->addFieldToTab('Root', $profile = new TabSet('Profile'), 'Content');
		$fields->addFieldToTab('Root', $email = new Tab('Email'), 'Behaviour');

		$profile->setTitle(_t('MemberProfiles.PROFILE', 'Profile'));
		$email->setTitle(_t('MemberProfiles.EMAIL', 'Email'));

		$fields->findOrMakeTab(
			'Root.Profile.Fields', _t('MemberProfiles.FIELDS', 'Fields'));
		$fields->findOrMakeTab(
			'Root.Profile.Groups', _t('MemberProfiles.GROUPS', 'Groups'));
		$fields->findOrMakeTab(
			'Root.Profile.PublicProfile',
			_t('MemberProfiles.PUBLICPROFILE', 'Public Profile'));

		// Profile fields
		$fields->addFieldsToTab('Root.Profile.Fields', array(
			new HeaderField(
				'ProfileFieldsHeader',
				_t('MemberProfiles.PROFILEFIELDS', 'Profile Fields')),
			$table = new OrderableComplexTableField(
				$this, 'Fields', 'MemberProfileField')
		));

		$table->setPermissions(array('show', 'edit'));
		$table->setCustomSourceItems($this->getProfileFields());

		// Groups
		$fields->addFieldsToTab('Root.Profile.Groups', array(
			new HeaderField('GroupsHeader',
				_t('MemberProfiles.GROUPASSIGNMENT', 'Group Assignment')),
			new LiteralField('GroupsNote', '<p>' . _t('MemberProfiles.GROUPSNOTE',
				'Any users registering via this page will always be added to '  .
				'the below groups (if registration is enabled). Conversely, a ' .
				'member must belong to these groups in order to edit their '    .
				'profile on this page.'
			) . '</p>'),
			new CheckboxSetField('Groups', '', DataObject::get('Group')->map()),
			new HeaderField('SelectableGroupsHeader',
				_t('MemberProfiles.USERSELECTABLE', 'User Selectable')),
			new LiteralField('SelectableGroupsNote', '<p>' . _t(
				'MemberProfiles.SELECTABLEGROUPSNOTE',
				'Users can choose to belong to the following groups, if the ' .
				'"Groups" field is enabled in the "Fields" tab.'
			) . '</p>'),
			new CheckboxSetField(
				'SelectableGroups', '', DataObject::get('Group')->map()),
			new HeaderField('RegistrationGroupsHeader',
				_t('MemberProfiles.REGISTRATIONGROUPS', 'Registration Groups')),
			new LiteralField('SelectableGroupsNote', '<p>' . _t(
				'MemberProfiles.REGISTRATIONGROUPSNOTE',
				'Upon registration, if the "Groups" field is disabled on the "Fields" tab, ' .
				'users will be placed in these groups.'
			) . '</p>'),
			new CheckboxSetField(
				'RegistrationGroups', '', DataObject::get('Group')->map())
		));

		// Public profile
		$fields->addFieldsToTab('Root.Profile.PublicProfile', array(
			new HeaderField(
				'PublicProfileHeader',
				_t('MemberProfiles.PUBLICPROFILE', 'Public Profile')),
			new CheckboxField(
				'AllowProfileViewing',
				_t('MemberProfiles.ALLOWPROFILEVIEWING', 'Allow people to view user profiles.')),
			new HeaderField(
				'ProfileSectionsHeader',
				_t('MemberProfiles.PROFILESECTIONS', 'Profile Sections')),
			new MemberProfileSectionField(
				$this, 'Sections', 'MemberProfileSection')
		));

		// Email confirmation and validation
		$fields->addFieldsToTab('Root.Email', array(
			new HeaderField('EmailHeader', 'Email Confirmation and Validation'),
			new OptionSetField('EmailType', '', array(
				'Validation'   => 'Require email validation to activate an account',
				'Confirmation' => 'Send a confirmation email after a user registers',
				'None'         => 'Do not send any emails'
			)),
			new ToggleCompositeField('EmailContent', 'Email Content', array(
				new TextField('EmailSubject', 'Email subject'),
				new TextField('EmailFrom', 'Email from'),
				new TextareaField('EmailTemplate', 'Email template'),
				new LiteralField('TemplateNote', MemberConfirmationEmail::TEMPLATE_NOTE)
			)),
			new ToggleCompositeField('ConfirmationContent', 'Confirmation Content', array(
				new LiteralField('ConfirmationNote', '<p>This content is dispayed when
					a user confirms their account.</p>'),
				new TextField('ConfirmationTitle', 'Title'),
				new HtmlEditorField('ConfirmationContent', 'Content')
			))
		));

		// Content
		$fields->removeFieldFromTab('Root.Content.Main', 'Content');

		$fields->addFieldToTab('Root.Content',
			$profileContent = new Tab('Profile'), 'Metadata');
		$fields->addFieldToTab('Root.Content',
			$regContent = new Tab('Registration'), 'Metadata');
		$fields->addFieldToTab('Root.Content',
			$afterReg = new Tab('AfterRegistration'), 'Metadata');

		$profileContent->setTitle(_t('MemberProfiles.PROFILE', 'Profile'));
		$regContent->setTitle(_t('MemberProfiles.REGISTRATION', 'Registration'));
		$afterReg->setTitle(_t('MemberProfiles.AFTERREG', 'After Registration'));

		$tabs = array('Profile', 'Registration', 'AfterRegistration');
		foreach ($tabs as $tab) {
			$fields->addFieldsToTab("Root.Content.$tab", array(
				new TextField("{$tab}Title", _t('MemberProfiles.TITLE', 'Title')),
				new HtmlEditorField("{$tab}Content", _t('MemberProfiles.CONTENT', 'Content'))
			));
		}

		$fields->addFieldToTab(
			'Root.Content.AfterRegistration',
			new CheckboxField('RegistrationRedirect',
				_t('MemberProfiles.REDIRECT_AFTER_REG', 'Redirect after registration?')),
			'AfterRegistrationContent'
		);
		$fields->addFieldToTab(
			'Root.Content.AfterRegistration',
			new TreeDropdownField('PostRegistrationTargetID',
				_t('MemberProfiles.REDIRECT_TARGET', 'Redirect to page'), 'SiteTree'),
			'AfterRegistrationContent'
		);

		// Behaviour
		$fields->addFieldToTab('Root.Behaviour',
			new HeaderField('ProfileBehaviour',
				_t('MemberProfiles.PROFILEBEHAVIOUR', 'Profile Behaviour')),
			'ClassName');
		$fields->addFieldToTab('Root.Behaviour',
			new CheckboxField('AllowRegistration',
			_t('MemberProfiles.ALLOWREG', 'Allow registration via this page')),
			'ClassName');
		$fields->addFieldToTab('Root.Behaviour',
			new CheckboxField('AllowProfileEditing',
			_t('MemberProfiles.ALLOWEDITING', 'Allow users to edit their own profile on this page')),
			'ClassName');
		$fields->addFieldToTab('Root.Behaviour',
			new CheckboxField('AllowAdding',
			_t('MemberProfiles.ALLOWADD',
				'Allow members with member creation permissions to add members via this page')),
			'ClassName');

		$requireApproval = new CheckboxField('RequireApproval', _t(
			'MemberProfiles.REQUIREREGAPPROVAL', 'Require registration approval by an administrator?'
		));
		$fields->addFieldToTab('Root.Behaviour', $requireApproval, 'ClassName');

		$approvalGroups = _t('MemberProfiles.NOTIFYTHESEGROUPS', 'Notify these groups to approve new registrations');
		$approvalGroups = new TreeMultiselectField('ApprovalGroups', $approvalGroups, 'Group');
		$fields->addFieldToTab('Root.Behaviour', $approvalGroups, 'ClassName');

		$pageSettings = new HeaderField('PageSettingsHeader', _t('MemberProfiles.PAGEBEHAVIOUR', 'Page Behaviour'));
		$fields->addFieldToTab('Root.Behaviour', $pageSettings, 'ClassName');

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

			if (array_key_exists($name, self::$profile_field_defaults)) {
				$profileField->update(self::$profile_field_defaults[$name]);
			}

			$profileField->write();
			$set->add($profileField);
		}

		return $set;
	}

	public function onAfterWrite() {
		if ($this->isChanged('ID')) {
			$section = new MemberProfileFieldsSection();
			$section->ParentID = $this->ID;
			$section->write();
		}

		parent::populateDefaults();
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
			'Title' => $this->obj('RegistrationTitle'),
			'Content' => $this->obj('RegistrationContent'),
			'Form' => $this->RegisterForm()
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
			if($this->AllowAdding && Permission::check('CREATE_OTHER_USERS')) {
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
		if(!$this->AllowAdding || !Permission::check('CREATE_OTHER_USERS')) {
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
			new FieldSet (
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
		$groupIds = $allowedIds = $this->SelectableGroups()->map('ID', 'ID');

		// we need to track the selected groups against the existing user's groups - this is
		// so that we don't accidentally remove them from the list of groups
		// a user might have been placed in via other means
		$existingIds = array();
		if ($member) {
			$existing = $member->Groups();
			if ($existing && $existing->Count() > 0) {
				$existingIds = $existing->map('ID', 'ID');
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
						$groupIds[$givenId] = $givenId;
					}
				}
			}
			$form->Fields()->removeByName('Groups');
		}
		elseif( !$member ) {
			/**
			 * We don't have a group field and we don't have a member, so they must be registering so
			 * use the RegistrationGroups as our group ids.
			 * @author Alex Hayes <alex.hayes@dimension27.com>
			 */
			$groupIds = $this->RegistrationGroups()->map('ID', 'ID');
		}

		foreach ($this->Groups()->column('ID') as $mustId) {
			$groupIds[$mustId] = $mustId;
		}

		foreach ($existingIds as $existingId) {
			if (!in_array($existingId, $groupIds)) {
				$groupIds[$existingId] = $existingId;
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
		$fields        = new FieldSet();

		// depending on the context, load fields from the current member
		if(Member::currentUser() && $context != 'Add') {
			$memberFields = Member::currentUser()->getMemberFormFields();
		} else {
			$memberFields = singleton('Member')->getMemberFormFields();
		}

		if($context == 'Registration') {
			$fields->push(new HeaderField (
				'LogInHeader', _t('MemberProfiles.LOGIN_HEADER', 'Log In')
			));

			$fields->push(new LiteralField (
				'LogInNote',
				'<p>' . sprintf (
					_t (
						'MemberProfiles.LOGIN',
						'If you already have an account you can <a href="%s">log in here</a>.'
					),
					Security::Link('login') . '?BackURL=' . $this->Link()
				) . '</p>'
			));

			$fields->push(new HeaderField (
				'RegisterHeader', _t('MemberProfiles.REGISTER', 'Register')
			));
		}

		if(
			$context == 'Profile'
			&& $this->AllowAdding
			&& singleton('Member')->canCreate()
		) {
			$fields->push(new HeaderField(
				'AddHeader', _t('MemberProfiles.ADDUSER', 'Add User')
			));
			$fields->push(new LiteralField (
				'AddMemberNote',
				'<p>' . sprintf(_t(
					'MemberProfiles.ADDMEMBERNOTE',
					'You can use this page to <a href="%s">add a new member</a>.'
				), $this->Link('add')) . '</p>'
			));
			$fields->push(new HeaderField(
				'YourProfileHeader', _t('MemberProfiles.YOURPROFILE', 'Your Profile')
			));
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
			$field->setTitle($profileField->Title);
			$field->setRightTitle($profileField->Note);

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

			if($visibility == 'Readonly') {
				$field->title=$profileField->Title;
				$field = $field->performReadonlyTransformation();
			}

			$fields->push($field);
		}

		$this->extend('updateProfileFields', $fields);
		return $fields;
	}

}