<?php

namespace Symbiote\MemberProfiles\Extensions;

use Symbiote\MemberProfiles\Pages\MemberProfilePage;
use Symbiote\MemberProfiles\Email\MemberConfirmationEmail;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\DropdownField;
/**
 * Adds validation fields to the Member object, as well as exposing the user's
 * status in the CMS.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfileExtension extends Extension
{
    private static array $db = [
        'ValidationKey' => 'Varchar(40)',
        'NeedsValidation' => 'Boolean',
        'NeedsApproval' => 'Boolean',
        'PublicFieldsRaw' => 'Text'
    ];

    private static array $has_one = [
        'ProfilePage' => MemberProfilePage::class
    ];

    public function getPublicFields(): array
    {
        return (array) unserialize($this->owner->getField('PublicFieldsRaw') ?? '');
    }

    public function setPublicFields($fields): void
    {
        $this->owner->setField('PublicFieldsRaw', serialize($fields));
    }

    public function canLogIn(ValidationResult $result): void
    {
        if ($this->owner->NeedsApproval) {
            $result->addError(_t(
                'MemberProfiles.NEEDSAPPROVALTOLOGIN',
                'An administrator must confirm your account before you can log in.'
            ));
        }

        if (!$this->owner->NeedsValidation) {
            return;
        }

        $result->addError(_t(
            'MemberProfiles.NEEDSVALIDATIONTOLOGIN',
            'You must validate your account before you can log in.'
        ));
    }

    /**
     * Allows admin users to manually confirm a user.
     */
    public function saveManualEmailValidation(string $value) : void
    {
        if ($value === 'confirm') {
            $this->owner->NeedsValidation = false;
        } elseif ($value === 'resend') {
            $email = MemberConfirmationEmail::create($this->owner->ProfilePage(), $this->owner);
            $email->send();
        }
    }

    public function populateDefaults(): void
    {
        $this->owner->ValidationKey = sha1(mt_rand() . mt_rand());
    }

    public function onAfterWrite(): void
    {
        $changed = $this->owner->getChangedFields();

        if (!array_key_exists('NeedsApproval', $changed)) {
            return;
        }

        $before = $changed['NeedsApproval']['before'];
        $after = $changed['NeedsApproval']['after'];
        $page = $this->owner->ProfilePage();
        $email = $page->EmailType;

        if ($before != true || $after !== false || $email === 'None') {
            return;
        }

        $email = MemberConfirmationEmail::create($page, $this->owner);
        $email->send();
    }

    public function updateMemberFormFields(FieldList $fields): void
    {
        $fields->removeByName('ValidationKey');
        $fields->removeByName('NeedsValidation');
        $fields->removeByName('NeedsApproval');
        $fields->removeByName('ProfilePageID');
        $fields->removeByName('PublicFieldsRaw');

        // For now we just pass an empty array as the list of selectable groups -
        // it's up to anything that uses this to populate it appropriately
        $existing = $this->owner->Groups();
        $fields->push(new CheckboxSetField('Groups', 'Groups', [], $existing));
    }

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName('ValidationKey');
        $fields->removeByName('NeedsValidation');
        $fields->removeByName('NeedsApproval');
        $fields->removeByName('ProfilePageID');
        $fields->removeByName('PublicFieldsRaw');

        // Remove member profile fields, as they may have been added by this method being called
        // multiple times.
        $fields->removeByName('ApprovalHeader');
        $fields->removeByName('ApprovalNote');
        $fields->removeByName('ConfirmationHeader');
        $fields->removeByName('ConfirmationNote');

        if ($this->owner->NeedsApproval) {
            $note = _t(
                'MemberProfiles.NOLOGINUNTILAPPROVED',
                'This user has not yet been approved. They cannot log in until their account is approved.'
            );

            $fields->addFieldsToTab('Root.Main', [
                // ApprovalAnchor is used by MemberApprovalController (2017-02-01)
                new LiteralField(
                    'ApprovalAnchor',
                    "<div id=\"MemberProfileRegistrationApproval\"></div>"
                ),
                new HeaderField(
                    'ApprovalHeader',
                    _t('MemberProfiles.REGAPPROVAL', 'Registration Approval')
                ),
                new LiteralField(
                    'ApprovalNote',
                    "<p>$note</p>"
                ),
                new DropdownField(
                    'NeedsApproval',
                    '',
                    [
                        true  => _t('MemberProfiles.DONOTCHANGE', 'Do not change'),
                        false => _t('MemberProfiles.APPROVETHISMEMBER', 'Approve this member')
                    ]
                ),
            ]);
        }

        if (!$this->owner->NeedsValidation) {
            return;
        }

        $fields->addFieldsToTab(
            'Root.Main',
            [
                new HeaderField(
                    'ConfirmationHeader',
                    _t('MemberProfiles.EMAILCONFIRMATION', 'Email Confirmation')
                ),
                new LiteralField(
                    'ConfirmationNote',
                    '<p>' . _t(
                        'MemberProfiles.NOLOGINTILLCONFIRMED',
                        'The member cannot log in until their account is confirmed.'
                    ) . '</p>'
                ),
                new DropdownField(
                    'ManualEmailValidation',
                    '',
                    [
                        'unconfirmed' => _t('MemberProfiles.UNCONFIRMED', 'Unconfirmed'),
                        'resend' => _t('MemberProfiles.RESEND', 'Resend confirmation email'),
                        'confirm' => _t('MemberProfiles.MANUALLYCONFIRM', 'Manually confirm')
                    ]
                )
            ]
        );
    }
}
