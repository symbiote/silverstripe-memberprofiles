<?php
/**
 * Adds validation fields to the Member object, as well as exposing the user's
 * status in the CMS.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfileExtension extends DataExtension {

	public static $db = array(
		'ValidationKey'   => 'Varchar(40)',
		'NeedsValidation' => 'Boolean',
		'NeedsApproval'   => 'Boolean',
		'PublicFieldsRaw' => 'Text'
	);

	public static $has_one = array(
	'ProfilePage' => 'MemberProfilePage'
	);

	public function getPublicFields() {
		return (array) unserialize($this->owner->getField('PublicFieldsRaw'));
	}

	public function setPublicFields($fields) {
		$this->owner->setField('PublicFieldsRaw', serialize($fields));
	}

	public function canLogIn($result) {
		if($this->owner->NeedsApproval) $result->error(_t (
			'MemberProfiles.NEEDSAPPROVALTOLOGIN',
			'An administrator must confirm your account before you can log in.'
		));

		if($this->owner->NeedsValidation) $result->error(_t (
			'MemberProfiles.NEEDSVALIDATIONTOLOGIN',
			'You must validate your account before you can log in.'
		));
	}

	/**
	 * Allows admin users to manually confirm a user.
	 */
	public function saveManualEmailValidation($value) {
		if($value == 'confirm') {
			$this->owner->NeedsValidation = false;
		} elseif($value == 'resend') {
			$email = new MemberConfirmationEmail($this->owner->ProfilePage(), $this->owner);
			$email->send();
		}
	}

	public function populateDefaults() {
		$this->owner->ValidationKey = sha1(mt_rand() . mt_rand());
	}

	public function onAfterWrite() {
		$changed = $this->owner->getChangedFields();

		if (array_key_exists('NeedsApproval', $changed)) {
			$before = $changed['NeedsApproval']['before'];
			$after  = $changed['NeedsApproval']['after'];
			$page   = $this->owner->ProfilePage();
			$email  = $page->EmailType;

			if ($before == true && $after == false && $email != 'None') {
				$email = new MemberConfirmationEmail($page, $this->owner);
				$email->send();
			}
		}
	}

	public function updateMemberFormFields($fields) {
		$fields->removeByName('ValidationKey');
		$fields->removeByName('NeedsValidation');
		$fields->removeByName('NeedsApproval');
		$fields->removeByName('ProfilePageID');
		$fields->removeByName('PublicFieldsRaw');

		// For now we just pass an empty array as the list of selectable groups -
		// it's up to anything that uses this to populate it appropriately
		$existing = $this->owner->Groups();
		$fields->push(new CheckboxSetField('Groups', 'Groups', array(), $existing));
	}

	public function updateCMSFields(FieldList $fields) {
		$mainFields = $fields->fieldByName("Root")->fieldByName("Main")->Children;

		$fields->removeByName('ValidationKey');
		$fields->removeByName('NeedsValidation');
		$fields->removeByName('NeedsApproval');
		$fields->removeByName('ProfilePageID');
		$fields->removeByName('PublicFieldsRaw');

		if ($this->owner->NeedsApproval) {
			$note = _t(
				'MemberProfiles.NOLOGINUNTILAPPROVED',
				'This user has not yet been approved. They cannot log in until their account is approved.'
			);

			$mainFields->merge(array(
				new HeaderField('ApprovalHheader', _t('MemberProfiles.REGAPPROVAL', 'Registration Approval')),
				new LiteralField('ApprovalNote', "<p>$note</p>"),
				new DropdownField('NeedsApproval', '', array(
					true  => _t('MemberProfiles.DONOTCHANGE', 'Do not change'),
					false => _t('MemberProfiles.APPROVETHISMEMBER', 'Approve this member')
				))
			));
		}

		if($this->owner->NeedsValidation) $mainFields->merge(array(
			new HeaderField(_t('MemberProfiles.EMAILCONFIRMATION', 'Email Confirmation')),
			new LiteralField('ConfirmationNote', '<p>' . _t (
				'MemberProfiles.NOLOGINTILLCONFIRMED',
				'The member cannot log in until their account is confirmed.'
			) . '</p>'),
			new DropdownField('ManualEmailValidation', '', array (
				'unconfirmed' => _t('MemberProfiles.UNCONFIRMED', 'Unconfirmed'),
				'resend'      => _t('MemberProfiles.RESEND', 'Resend confirmation email'),
				'confirm'     => _t('MemberProfiles.MANUALLYCONFIRM', 'Manually confirm')
			))
		));
	}

}