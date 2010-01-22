<?php
/**
 * Adds validation fields to the Member object.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfileExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array('db' => array (
			'ValidationKey'   => 'Varchar(40)',
			'NeedsValidation' => 'Boolean'
		));
	}

	public function populateDefaults() {
		$this->owner->ValidationKey = sha1(mt_rand() . mt_rand());
	}

	public function updateMemberFormFields($fields) {
		$this->updateCMSFields($fields);
	}

	public function updateCMSFields($fields) {
		$fields->removeByName('ValidationKey');
		$fields->removeByName('NeedsValidation');
	}

}