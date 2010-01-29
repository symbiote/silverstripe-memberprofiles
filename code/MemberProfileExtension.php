<?php
/**
 * Adds validation fields to the Member object, as well as exposing the user's
 * status in the CMS.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfileExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array (
			'db' => array (
				'ValidationKey'   => 'Varchar(40)',
				'NeedsValidation' => 'Boolean'
			),
			'has_one' => array (
				'ProfilePage' => 'MemberProfilePage'
			)
		);
	}

	public function canLogIn($result) {
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

	public function updateMemberFormFields($fields) {
		$fields->removeByName('ValidationKey');
		$fields->removeByName('NeedsValidation');
	}

	public function updateCMSFields($fields) {
		$fields->removeByName('ValidationKey');
		$fields->removeByName('NeedsValidation');

		if($this->owner->NeedsValidation) $fields->addFieldsToTab('Root.Main', array (
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