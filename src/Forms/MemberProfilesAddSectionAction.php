<?php

namespace Symbiote\MemberProfiles\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\DropdownField;
use SilverStripe\View\ArrayData;
use Symbiote\MemberProfiles\Model\MemberProfileSection;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

/**
 * A grid field section that allows one instance of each section subclass to be
 * created.
 *
 * @package silverstripe-memberprofiles
 */
class MemberProfilesAddSectionAction extends GridFieldDetailForm implements GridField_HTMLProvider
{

    public function getURLHandlers($gridField)
    {
        return array(
            'addsection/$ClassName!' => 'handleAddSection'
        );
    }

    public function getHTMLFragments($grid)
    {
        $addable = $this->getAddableSections($grid);
        $base    = $grid->Link('addsection');
        $links   = array();

        if (!$addable) {
            return array();
        }

        foreach ($addable as $class => $title) {
            $class = urlencode($class);
            $links[Controller::join_links($base, $class)] = $title;
        }

        Requirements::javascript('symbiote/silverstripe-memberprofiles: client/javascript/MemberProfilesAddSection.js');
        Requirements::css('symbiote/silverstripe-memberprofiles: client/css/MemberProfilesAddSection.css');

        $select = new DropdownField("{$grid->getName()}[SectionClass]", '', $links);
        $select->setEmptyString(_t('MemberProfiles.SECTIONTYPE', '(Section type)'));
        $select->addExtraClass('no-change-track');

        $data = new ArrayData(array(
            'Title'  => _t('MemberProfiles.ADDSECTION', 'Add Section'),
            'Select' => $select
        ));

        return array(
            'buttons-before-left' => $data->renderWith('Symbiote\\MemberProfiles\\Model\\MemberProfilesAddSectionButton'),
        );
    }

    public function handleAddSection($grid, $request)
    {
        $class = urldecode($request->param('ClassName'));
        if (!is_subclass_of($class, MemberProfileSection::class)) {
            return new HTTPResponse('An invalid section type was specified', 404);
        }

        if (!array_key_exists($class, $this->getAddableSections($grid))) {
            return new HTTPResponse('The section already exists', 400);
        }

        $handler = $this->getItemRequestClass();
        $record  =  $class::create();

        $handler = $handler::create(
            $grid,
            $this,
            $record,
            $grid->getForm()->getController(),
            $this->name
        );
        $handler->setTemplate($this->template);

        return $handler->handleRequest($request);
    }

    protected function getAddableSections($grid)
    {
        $list    = $grid->getList();
        $classes = ClassInfo::subclassesFor(MemberProfileSection::class);
        $result  = array();
        $base    = $grid->Link();

        array_shift($classes);
        $classes = array_diff($classes, $list->column('ClassName'));

        foreach ($classes as $class) {
            $result[$class] = singleton($class)->getTitle();
        }

        return $result;
    }
}
