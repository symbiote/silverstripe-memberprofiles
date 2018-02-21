<?php

namespace Symbiote\MemberProfiles\Model;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

/**
 * A profile section that displays a list of fields that have been marked as
 * public.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage dataobjects
 */
class MemberProfileFieldsSection extends MemberProfileSection {

    private static $table_name = 'MemberProfileFieldsSection';

	public function getDefaultTitle() {
		return _t('MemberProfiles.PROFILEFIELDSLIST', 'Profile Fields List');
	}

	public function forTemplate() {
		return $this->renderWith('MemberProfileFieldsSection');
	}

	public function Fields() {
		$fields = $this->Parent()->Fields()->where('"PublicVisibility" <> \'Hidden\'');
		$public = $this->member->getPublicFields();
		$result = new ArrayList();

		foreach($fields as $field) {
			if($field->PublicVisibility == 'MemberChoice') {
				if(!in_array($field->MemberField, $public)) continue;
			}

			$result->push(new ArrayData(array(
				'Title' => $field->Title,
				'Value' => $this->member->{$field->MemberField}
			)));
		}

		return $result;
	}

	public function ShowTitle() {
		return false;
	}

}
