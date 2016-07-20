<?php
/**
 * This validator provides the unique and required functionality for {@link MemberProfileField}s.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfileValidator extends RequiredFields {

	protected $fields, $member, $unique = array();

	/**
	 * @param MemberProfileField[] $fields
	 * @param Member $member
	 */
	public function __construct($fields, $member = null) {
		parent::__construct();

		$this->fields = $fields;
		$this->member = $member;

		foreach($this->fields as $field) {
			if($field->Required) {
				if($field->ProfileVisibility !== 'Readonly') {
					$this->addRequiredField($field->MemberField);
				}
			}
			if($field->Unique)   $this->unique[] = $field->MemberField;
		}

		if($member && $member->ID && $member->Password) {
			$this->removeRequiredField('Password');
		}
	}

	/**
	 * JavaScript validation is disabled on profile forms.
	 */
	public function javascript() {
		return null;
	}

	public function php($data) {
		$member = $this->member;
		$valid  = true;

		foreach($this->unique as $field) {
			$other = DataObject::get_one (
				'Member',
				sprintf('"%s" = \'%s\'', Convert::raw2sql($field), Convert::raw2sql($data[$field]))
			);

			if ($other && (!$this->member || !$this->member->exists() || $other->ID != $this->member->ID)) {
				$fieldInstance = $this->form->Fields()->dataFieldByName($field);

				if($fieldInstance->getCustomValidationMessage()) {
					$message = $fieldInstance->getCustomValidationMessage();
				} else {
					$message = sprintf (
						_t('MemberProfiles.MEMBERWITHSAME', 'There is already a member with the same %s.'),
						$field
					);
				}

				$valid = false;
				$this->validationError($field, $message, 'required');
			}
		}
		
		// Create a dummy member as this is required for custom password validators
		if(isset($data['Password']) && $data['Password'] !== "") {
			if(is_null($member)) $member = Member::create();

			if($validator = $member::password_validator()) {
				$results = $validator->validate($data['Password'], $member);

				if(!$results->valid()) {
					$valid = false;
					foreach($results->messageList() as $key => $value) {
						$this->validationError('Password', $value, 'required');
					}
				}
			}
		}

		return $valid && parent::php($data);
	}

}
