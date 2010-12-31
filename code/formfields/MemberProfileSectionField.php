<?php
/**
 * An extension to {@link OrderableComplexTableField} that allows you to create
 * and manage one of each {@link MemberProfileSection}.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage formfields
 */
class MemberProfileSectionField extends OrderableComplexTableField {

	/**
	 * @return array
	 */
	public function getAddableSections() {
		$sections = ClassInfo::subclassesFor('MemberProfileSection');
		$items    = $this->sourceItems();
		$result   = array();

		array_shift($sections);
		if ($items) {
			$sections = array_diff($sections, $items->map('ClassName', 'ClassName'));
		}

		foreach ($sections as $section) {
			$result[$section] = singleton($section)->getTitle();
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function Can($mode) {
		if ($mode == 'add') {
			return parent::Can($mode) && $this->getAddableSections();
		} else {
			return parent::Can($mode);
		}
	}

	/**
	 * @return Form
	 */
	public function AddForm() {
		return new $this->popupClass(
			$this,
			'AddForm',
			new FieldSet(new TabSet('Root', new Tab('Main',
				new LiteralField('AddSectionNote',
					'<p>' . _t('MemberProfiles.ADDSECTIONNOTE',
					'Please select a section type below to add:') . '</p>'),
				new DropdownField(
					'ClassName', '', $this->getAddableSections(),
					null, null, true)
			))),
			new RequiredFields('ClassName'),
			false,
			new MemberProfileSection()
		);
	}

	public function saveComplexTableField($data, $form, $params) {
		$child = new $data['ClassName']();
		$child->ParentID = $this->controller->ID;
		$child->write();

		$link = SecurityToken::inst()->addToUrl(Controller::join_links(
			$this->Link(), 'item', $child->ID, 'edit'
		));

		Session::set('FormInfo.ComplexTableField_Popup_DetailForm.formError', array(
			'message' => _t('MemberProfiles.SECTIONADDED',
				'Profile section added, please edit it below.'),
			'type' => 'good'
		));

		return Director::redirect($link);
	}

}