<?php

namespace Symbiote\MemberProfiles\Pages;

use PageController;
use Exception;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\SpamProtection\Extension\FormSpamProtectionExtension;
use SilverStripe\View\Requirements;
use SilverStripe\Security\Permission;
use SilverStripe\View\ViewableData_Customised;
use Symbiote\MemberProfiles\Email\MemberConfirmationEmail;
use Symbiote\MemberProfiles\Forms\CheckableVisibilityField;
use Symbiote\MemberProfiles\Forms\MemberProfileValidator;

/**
 * Class MemberProfilePageController
 *
 * @property int $ID
 * @property string $ProfileTitle
 * @property string $RegistrationTitle
 * @property string $AfterRegistrationTitle
 * @property string $ProfileContent
 * @property string $RegistrationContent
 * @property string $AfterRegistrationContent
 * @property bool $AllowRegistration
 * @property bool $AllowProfileViewing
 * @property bool $AllowProfileEditing
 * @property bool $AllowAdding
 * @property bool $RegistrationRedirect
 * @property bool $RequireApproval
 * @property string $EmailType
 * @property string $EmailFrom
 * @property string $EmailSubject
 * @property string $EmailTemplate
 * @property string $ConfirmationTitle
 * @property string $ConfirmationContent
 * @property int $PostRegistrationTargetID
 * @method \SilverStripe\CMS\Model\SiteTree PostRegistrationTarget()
 * @method \SilverStripe\ORM\DataList|\Symbiote\MemberProfiles\Model\MemberProfileField[] Fields()
 * @method \SilverStripe\ORM\DataList|\SilverStripe\Security\Group[] Groups()
 * @method \SilverStripe\ORM\DataList|\SilverStripe\Security\Group[] SelectableGroups()
 * @method \SilverStripe\ORM\DataList|\SilverStripe\Security\Group[] ApprovalGroups()
 */
class MemberProfilePageController extends PageController
{
    private static $allowed_actions = [
        'index',
        'RegisterForm',
        'afterregistration',
        'ProfileForm',
        'add',
        'AddForm',
        'confirm',
        'show',
    ];

    /**
     * @return array|ViewableData_Customised
     */
    public function index(HTTPRequest $request)
    {
        $backURL = $request->getVar('BackURL');
        if ($backURL) {
            $session = $request->getSession();
            $session->set('MemberProfile.REDIRECT', $backURL);
        }

        return Member::currentUser() ? $this->indexProfile() : $this->indexRegister();
    }

    /**
     * Allow users to register if registration is enabled.
     *
     * @return array|ViewableData_Customised
     */
    protected function indexRegister()
    {
        if (!$this->AllowRegistration) {
            return Security::permissionFailure($this, _t(
                'MemberProfiles.CANNOTREGPLEASELOGIN',
                'You cannot register on this profile page. Please login to edit your profile.'
            ));
        }

        $data = array(
            'Type'    => 'Register',
            'Title'   => $this->obj('RegistrationTitle'),
            'Content' => $this->obj('RegistrationContent'),
            'Form'    => $this->RegisterForm()
        );

        return $this->customise($data);
    }

    /**
     * Allows users to edit their profile if they are in at least one of the
     * groups this page is restricted to, and editing isn't disabled.
     *
     * If editing is disabled, but the current user can add users, then they
     * are redirected to the add user page.
     *
     * @return array|ViewableData_Customised
     */
    protected function indexProfile()
    {
        if (!$this->AllowProfileEditing) {
            if ($this->AllowAdding && Injector::inst()->get(Member::class)->canCreate()) {
                return $this->redirect($this->Link('add'));
            }

            return Security::permissionFailure($this, _t(
                'MemberProfiles.CANNOTEDIT',
                'You cannot edit your profile via this page.'
            ));
        }

        $member = Member::currentUser();

        foreach ($this->Groups() as $group) {
            if (!$member->inGroup($group)) {
                return Security::permissionFailure($this);
            }
        }

        $form = $this->ProfileForm();
        $form->loadDataFrom($member);

        if ($password = $form->Fields()->fieldByName('Password')) {
            if ($password->hasMethod('setCanBeEmpty')) {
                $password->setCanBeEmpty(false);
                $password->setValue(null);
                $password->setCanBeEmpty(true);
            } else {
                // If Password field is ReadonlyField or similar
                $password->setValue(null);
            }
        }

        $data = array(
            'Type'    => 'Profile',
            'Title'   => $this->obj('ProfileTitle'),
            'Content' => $this->obj('ProfileContent'),
            'Form'    => $form
        );

        return $this->customise($data);
    }

    /**
     * @return MemberProfileViewer
     */
    public function show()
    {
        if (!$this->AllowProfileViewing) {
            $this->httpError(404);
        }

        return MemberProfileViewer::create($this, 'show');
    }

    /**
     * @uses   MemberProfilePageController::getProfileFields
     * @return Form
     */
    public function RegisterForm()
    {
        $form = new Form(
            $this,
            'RegisterForm',
            $this->getProfileFields('Registration'),
            new FieldList(
                new FormAction('register', _t('MemberProfiles.REGISTER', 'Register'))
            ),
            new MemberProfileValidator($this->Fields())
        );


        if ($form->hasExtension(FormSpamProtectionExtension::class)) {
            $form->enableSpamProtection();
        }
        $this->extend('updateRegisterForm', $form);
        return $form;
    }

    /**
     * Handles validation and saving new Member objects, as well as sending out validation emails.
     */
    public function register($data, Form $form)
    {
        $member = $this->addMember($form);
        if (!$member) {
            return $this->redirectBack();
        }

        // If they can login, immediately log them in
        if ($member->canLogin()) {
            Injector::inst()->get(IdentityStore::class)->logIn($member);
        }

        if ($this->RegistrationRedirect) {
            if ($this->PostRegistrationTargetID) {
                $this->redirect($this->PostRegistrationTarget()->Link());
                return;
            }

            $session = $this->getRequest()->getSession();
            $sessionTarget = $session->get('MemberProfile.REDIRECT');
            if ($sessionTarget) {
                $session->clear('MemberProfile.REDIRECT');
                if (Director::is_site_url($sessionTarget)) {
                    return $this->redirect($sessionTarget);
                }
            }
        }

        return $this->redirect($this->Link('afterregistration'));
    }

    /**
     * Returns the after registration content to the user.
     *
     * @return array
     */
    public function afterregistration()
    {
        return array (
            'Title'   => $this->obj('AfterRegistrationTitle'),
            'Content' => $this->obj('AfterRegistrationContent')
        );
    }

    /**
     * @uses   MemberProfilePageController::getProfileFields
     * @return Form
     */
    public function ProfileForm()
    {
        $form = new Form(
            $this,
            'ProfileForm',
            $this->getProfileFields('Profile'),
            new FieldList(
                new FormAction('save', _t('MemberProfiles.SAVE', 'Save'))
            ),
            new MemberProfileValidator($this->Fields(), Member::currentUser())
        );
        $this->extend('updateProfileForm', $form);
        return $form;
    }

    /**
     * Updates an existing Member's profile.
     */
    public function save(array $data, Form $form)
    {
        $member = Member::currentUser();

        $groupIds = $this->getSettableGroupIdsFrom($form, $member);
        $member->Groups()->setByIDList($groupIds);

        $form->saveInto($member);

        try {
            $member->write();
        } catch (ValidationException $e) {
            $validationMessages = implode("; ", $e->getResult()->getMessages());
            $form->sessionMessage($validationMessages, 'bad');
            return $this->redirectBack();
        }

        $form->sessionMessage(
            _t('MemberProfiles.PROFILEUPDATED', 'Your profile has been updated.'),
            'good'
        );
        return $this->redirectBack();
    }

    /**
     * Allows members with the appropriate permissions to add/regsiter other
     * members.
     */
    public function add($request)
    {
        if (!$this->AllowAdding || !Injector::inst()->get(Member::class)->canCreate()) {
            return Security::permissionFailure($this, _t(
                'MemberProfiles.CANNOTADDMEMBERS',
                'You cannot add members via this page.'
            ));
        }

        $data = array(
            'Type'    => 'Add',
            'Title'   => _t('MemberProfiles.ADDMEMBER', 'Add Member'),
            'Content' => '',
            'Form'    => $this->AddForm()
        );

        return $this->customise($data);
    }

    /**
     * @return Form
     */
    public function AddForm()
    {
        $form = new Form(
            $this,
            'AddForm',
            $this->getProfileFields('Add'),
            new FieldList(
                new FormAction('doAdd', _t('MemberProfiles.ADD', 'Add'))
            ),
            new MemberProfileValidator($this->Fields())
        );

        $this->extend('updateAddForm', $form);
        return $form;
    }

    /**
     * Saves an add member form submission into a new member object.
     */
    public function doAdd($data, $form)
    {
        if ($this->addMember($form)) {
            $form->sessionMessage(
                _t('MemberProfiles.MEMBERADDED', 'The new member has been added.'),
                'good'
            );
        }

        return $this->redirectBack();
    }

    public function LoginLink()
    {
        return Controller::join_links(
            Injector::inst()->get(Security::class)->Link(),
            'login',
            '?BackURL=' . urlencode($this->Link())
        );
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
    protected function getSettableGroupIdsFrom(Form $form, Member $member = null)
    {
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
            if ($existing && $existing->count() > 0) {
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
     * @param HTTPRequest $request
     * @return array|HTTPResponse
     */
    public function confirm(HTTPRequest $request)
    {
        if ($this->EmailType !== 'Confirmation' &&
            $this->EmailType !== 'Validation') {
            return $this->httpError(400, 'No confirmation required.');
        }

        $currentMember = Member::currentUser();
        $id = (int)$request->param('ID');
        $key = $request->getVar('key');

        if ($currentMember) {
            if ($currentMember->ID == $id) {
                return Security::permissionFailure($this, _t(
                    'MemberProfiles.ALREADYCONFIRMED',
                    'Your account is already confirmed.'
                ));
            }
            return Security::permissionFailure($this, _t(
                'MemberProfiles.CANNOTCONFIRMLOGGEDIN',
                'You cannot confirm account while you are logged in.'
            ));
        }

        if (!$id ||
            !$key) {
            return $this->httpError(404);
        }

        $confirmationTitle = $this->data()->dbObject('ConfirmationTitle');

        /**
         * @var Member|null $member
         */
        $member = DataObject::get_by_id(Member::class, $id);
        if (!$member) {
            return $this->invalidRequest('Member #'.$id.' does not exist.');
        }
        if (!$member->NeedsValidation) {
            // NOTE(Jake): 2018-05-03
            //
            // You might be hitting this if you set your MemberProfilePage to use
            // Email Setting 'Confirmation' rather than 'Validation' and you didn't
            // edit the original Email template to not include the copy about confirmation.
            //
            return $this->invalidRequest('Member #'.$id.' does not need validation.');
        }
        if (!$member->ValidationKey) {
            return $this->invalidRequest('Member #'.$id.' does not have a validation key.');
        }
        if ($member->ValidationKey !== $key) {
            return $this->invalidRequest('Validation key does not match.');
        }

        // Allow member to login
        $member->NeedsValidation = false;
        $member->ValidationKey = null;

        $validationResult = $member->validateCanLogin();
        if (!$validationResult->isValid()) {
            $this->getResponse()->setStatusCode(500);
            $validationMessages = $validationResult->getMessages();
            return [
                'Title'   => $confirmationTitle,
                'Content' => $validationMessages ? $validationMessages[0]['message'] : _t('MemberProfiles.ERRORCONFIRMATION', 'An unexpected error occurred.'),
            ];
        }
        $member->write();

        $this->extend('onConfirm', $member);

        if ($member->canLogin()) {
            Injector::inst()->get(IdentityStore::class)->logIn($member);
        } else {
            throw new Exception('Permission issue occurred. Was the "$member->validateCanLogin" check above this code block removed?');
        }

        return [
            'Title'   => $this->data()->dbObject('ConfirmationTitle'),
            'Content' => $this->data()->dbObject('ConfirmationContent')
        ];
    }

    /**
     * @return array
     */
    protected function invalidRequest($debugText)
    {
        $additionalText = '';
        if (Director::isDev()) {
            // NOTE(Jake): 2018-05-02
            //
            // Only expose additional information in 'dev' mode.
            //
            $additionalText .= ' '.$debugText;
        }

        $this->getResponse()->setStatusCode(500);
        return [
            'Title'   => $this->data()->dbObject('ConfirmationTitle'),
            'Content' => _t(
                'MemberProfiles.ERRORCONFIRMATION',
                'An unexpected error occurred.'
            ).$additionalText,
        ];
    }


    /**
     * Attempts to save either a registration or add member form submission
     * into a new member object, returning NULL on validation failure.
     *
     * @return Member|null
     */
    protected function addMember($form)
    {
        $member   = new Member();
        $groupIds = $this->getSettableGroupIdsFrom($form);

        $form->saveInto($member);

        $member->ProfilePageID   = $this->ID;
        $member->NeedsValidation = ($this->EmailType === 'Validation');
        $member->NeedsApproval   = $this->RequireApproval;

        try {
            $member->write();
        } catch (ValidationException $e) {
            $validationMessages = implode("; ", $e->getResult()->getMessages());
            $form->sessionMessage($validationMessages, 'bad');
            return null;
        }

        // set after member is created otherwise the member object does not exist
        $member->Groups()->setByIDList($groupIds);

        // If we require admin approval, send an email to the admin and delay
        // sending an email to the member.
        if ($this->RequireApproval) {
            $groups = $this->ApprovalGroups();
            if (!$groups ||
                $groups->count() == 0) {
                // If nothing is configured, fallback to ADMIN
                $groups = Permission::get_groups_by_permission('ADMIN');
            }

            $emails = [];
            if ($groups) {
                foreach ($groups as $group) {
                    foreach ($group->Members() as $_member) {
                        if ($_member->Email) {
                            $emails[] = $_member->Email;
                        }
                    }
                }
            }

            if ($emails) {
                $emails = array_unique($emails);

                $mail    = Email::create($this->EmailFrom)
                $config  = SiteConfig::current_site_config();
                $approve = Controller::join_links(
                    Director::baseURL(),
                    'member-approval',
                    $member->ID,
                    '?token=' . $member->ValidationKey
                );

                $mail->setSubject("Registration Approval Requested for $config->Title");
                $mail->setHTMLTemplate('Symbiote\\MemberProfiles\\Email\\MemberRequiresApprovalEmail');
                $mail->setData(array(
                    'SiteConfig'  => $config,
                    'Member'      => $member,
                    'ApproveLink' => Director::absoluteURL($approve)
                ));
                
                foreach ($emails as $email) {
                    if (!Email::is_valid_address($email)) {
                        // Ignore invalid email addresses or else we'll get validation errors.
                        // ie. default 'admin' account
                        continue;
                    }
                    
                    $mail->setTo($email);
                    $mail->send();                    
                }
            }
        } else {
            // NOTE(Jake): 2018-05-03
            //
            // Only send the confirmation email immediately after registering if they
            // don't require admin approval.
            //
            switch ($this->EmailType) {
                case 'None':
                    // Does not require anything
                break;

                case 'Confirmation':
                case 'Validation':
                    // Must activate themselves via the confirmation email
                    $email = MemberConfirmationEmail::create($this->data(), $member);
                    $email->send();
                break;
            }
        }

        $this->extend('onAddMember', $member);
        return $member;
    }

    /**
     * @param string $context
     * @return FieldList
     */
    protected function getProfileFields($context)
    {
        $profileFields = $this->Fields();
        $fields        = new FieldList();

        // depending on the context, load fields from the current member
        if (Member::currentUser() && $context != 'Add') {
            $memberFields = Member::currentUser()->getMemberFormFields();
        } else {
            $memberFields = singleton(Member::class)->getMemberFormFields();
        }

        // use the default registration fields for adding members
        if ($context == 'Add') {
            $context = 'Registration';
        }

        if ($this->AllowProfileViewing
            && $profileFields->find('PublicVisibility', 'MemberChoice')
        ) {
            $fields->push(new LiteralField(
                'VisibilityNote',
                '<p>' . _t(
                    'MemberProfiles.CHECKVISNOTE',
                    'Check fields below to make them visible on your public profile.'
                ) . '</p>'
            ));
        }

        foreach ($profileFields as $profileField) {
            $visibility  = $profileField->{$context . 'Visibility'};
            $name        = $profileField->MemberField;
            $memberField = $memberFields->dataFieldByName($name);

            // handle the special case of the Groups control so that only allowed groups can be selected
            if ($name === 'Groups') {
                $availableGroups = $this->data()->SelectableGroups()->map('ID', 'Title');
                $memberField->setSource($availableGroups);
            }

            if (!$memberField || $visibility == 'Hidden') {
                continue;
            }

            $field = clone $memberField;

            if ($visibility === 'Readonly') {
                $field = $field->performReadonlyTransformation();
            }

            if ($name === 'Password') {
                Requirements::javascript("silverstripe/admin: thirdparty/jquery/jquery.js");
                Requirements::javascript("symbiote/silverstripe-memberprofiles: client/javascript/ConfirmedPasswordField.js");
            }

            // The follow two if-conditions were added since the SS4 migration because a Password label disappeared
            $fieldTitle = $profileField->getTitle();
            if ($fieldTitle) {
                $field->setTitle($fieldTitle);
            }
            if ($profileField->Note) {
                $field->setDescription($profileField->Note);
            }

            if ($context === 'Registration' &&
                $profileField->DefaultValue) {
                $field->setValue($profileField->DefaultValue);
            }

            if ($profileField->CustomError) {
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
