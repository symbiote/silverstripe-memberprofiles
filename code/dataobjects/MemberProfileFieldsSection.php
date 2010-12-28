<?php
/**
 * A profile section that displays a list of fields that have been marked as
 * public.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage dataobjects
 */
class MemberProfileFieldsSection extends MemberProfileSection {

	public function getDefaultTitle() {
		return _t('MemberProfiles.PROFILEFIELDSLIST', 'Profile Fields List');
	}

	public function forTemplate() {
		return $this->renderWith('MemberProfileFieldsSection');
	}

	public function Fields() {
		$fields = $this->Parent()->Fields('"PublicVisibility" = \'Display\'');
		$result = new DataObjectSet();

		foreach ($fields as $field) {
			$result->push(new ArrayData(array(
				'Title' => $field->Title,
				'Value' => $this->member->{$field->MemberField}
			)));
		}

		return $result;
	}

}