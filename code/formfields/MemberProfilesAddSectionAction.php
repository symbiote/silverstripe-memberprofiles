<?php
/**
 * A grid field section that allows one instance of each section subclass to be
 * created.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfilesAddSectionAction extends GridFieldDetailForm implements GridField_HTMLProvider {

	public function getURLHandlers($gridField) {
		return array(
			'addsection/$ClassName!' => 'handleAddSection'
		);
	}

	public function getHTMLFragments($grid) {
		$addable = $this->getAddableSections($grid);
		$base    = $grid->Link('addsection');
		$links   = array();

		if(!$addable) {
			return array();
		}

		foreach($addable as $class => $title) {
			$links[Controller::join_links($base, $class)] = $title;
		}

		Requirements::javascript('memberprofiles/javascript/MemberProfilesAddSection.js');
		Requirements::css('memberprofiles/css/MemberProfilesAddSection.css');

		$select = new DropdownField("{$grid->getName()}[SectionClass]", '', $links);
		$select->setEmptyString(_t('MemberProfiles.SECTIONTYPE', '(Section type)'));
		$select->addExtraClass('no-change-track');

		$data = new ArrayData(array(
			'Title'  => _t('MemberProfiles.ADDSECTION', 'Add Section'),
			'Select' => $select
		));

		return array(
			'buttons-before-left' => $data->renderWith('MemberProfilesAddSectionButton'),
		);
	}

	public function handleAddSection($grid, $request) {
		$class = $request->param('ClassName');

		if(!is_subclass_of($class, 'MemberProfileSection')) {
			return new SS_HTTPResponse('An invalid section type was specified', 404);
		}

		if(!array_key_exists($class, $this->getAddableSections($grid))) {
			return new SS_HTTPResponse('The section already exists', 400);
		}

		$handler = $this->getItemRequestClass();
		$record  =  $class::create();

		$handler = $handler::create(
			$grid,
			$this,
			$record,
			$grid->getForm()->Controller(),
			$this->name
		);
		$handler->setTemplate($this->template);

		return $handler->handleRequest($request, DataModel::inst());
	}

	protected function getAddableSections($grid) {
		$list    = $grid->getList();
		$classes = ClassInfo::subclassesFor('MemberProfileSection');
		$result  = array();
		$base    = $grid->Link();

		array_shift($classes);
		$classes = array_diff($classes, $list->column('ClassName'));

		foreach($classes as $class) {
			$result[$class] = singleton($class)->getTitle();
		}

		return $result;
	}

}

class MemberProfilesAddSectionAction_ItemRequest extends GridFieldDetailForm_ItemRequest {

	public function Link($action = null) {
		if($this->record->ID) {
			return parent::Link($action);
		} else {
			return Controller::join_links(
				$this->gridField->Link(), 'addsection', get_class($this->record)
			);
		}
	}

}
