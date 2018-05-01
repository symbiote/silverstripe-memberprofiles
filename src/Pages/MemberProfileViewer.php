<?php

namespace Symbiote\MemberProfiles\Pages;

use PageController;

use Exception;
use SilverStripe\Control\RequestHandler;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Security\Member;
use SilverStripe\Control\Controller;
use SilverStripe\View\ViewableData;

/**
 * Handles displaying member's public profiles.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage controllers
 */
class MemberProfileViewer extends PageController
{

    private static $url_handlers = array(
        ''           => 'handleList',
        '$MemberID!' => 'handleView'
    );

    private static $allowed_actions = array(
        'handleList',
        'handleView'
    );

    /**
     * @var MemberProfilePageController
     */
    private $parent;

    /**
     * @var string
     */
    private $name;

    /**
     * @param MemberProfilePageController $parent
     * @param string $name
     */
    public function __construct(MemberProfilePageController $parent, $name)
    {
        $this->parent = $parent;
        $this->name   = $name;

        parent::__construct();
    }

    /**
     * Displays a list of all members on the site that belong to the selected
     * groups.
     *
     * @return ViewableData
     */
    public function handleList($request)
    {
        $parent = $this->getParent();
        $fields  = $parent->Fields()->filter('MemberListVisible', true);

        $groups = $parent->Groups();
        if ($groups->count() > 0) {
            // todo: this ->relation method does not seem to work: no Members are found
            $members = $groups->relation('Members');
            // NOTE(Jake): 2018-05-01
            //
            // PR Comment:
            // https://github.com/symbiote/silverstripe-memberprofiles/pull/138#issuecomment-385571020
            //
            throw new Exception('Not implemented.')
        } else {
            $members = Member::get();
        }
        $members = new PaginatedList($members, $request);

        $list = new ArrayList();
        foreach ($members as $member) {
            $cols   = new ArrayList();
            $public = $member->getPublicFields();
            $link   = $this->Link($member->ID);

            foreach ($fields as $field) {
                if ($field->PublicVisibility == 'MemberChoice'
                    && !in_array($field->MemberField, $public)
                ) {
                    $value =  null;
                } else {
                    $value = $member->{$field->MemberField};
                }

                $cols->push(new ArrayData(array(
                    'Name'     => $field->MemberField,
                    'Title'    => $field->Title,
                    'Value'    => $value,
                    'Sortable' => $member->hasDatabaseField($field->MemberField),
                    'Link'     => $link
                )));
            }

            $list->push($member->customise(array(
                'Fields' => $cols
            )));
        }
        $list = new PaginatedList(new ArrayList(), $request);
        $list->setLimitItems(false);
        $list->setTotalItems($members->getTotalItems());

        $this->data()->Title  = _t('MemberProfiles.MEMBERLIST', 'Member List');
        $this->data()->Parent = $this->getParent();

        $controller = $this->customise(array(
            'Type'    => 'List',
            'Members' => $list
        ));

        return $controller;
    }

    /**
     * Handles viewing an individual user's profile.
     *
     * @return \SilverStripe\View\ViewableData_Customised
     */
    public function handleView($request)
    {
        $id = $request->param('MemberID');

        if (!ctype_digit($id)) {
            $this->httpError(404);
        }

        /**
         * @var Member $member
         */
        $member = Member::get()->byID($id);
        $groups = $this->parent->Groups();

        if ($groups->count() > 0 && !$member->inGroups($groups)) {
            $this->httpError(403);
        }

        $sections     = $this->parent->Sections();
        $sectionsList = new ArrayList();

        foreach ($sections as $section) {
            $sectionsList->push($section);
            $section->setMember($member);
        }

        $this->data()->Title = sprintf(
            _t('MemberProfiles.MEMBERPROFILETITLE', "%s's Profile"),
            $member->getName()
        );
        $this->data()->Parent = $this->parent;

        $controller = $this->customise(array(
            'Type'     => 'View',
            'Member'   => $member,
            'Sections' => $sectionsList,
            'IsSelf'   => $member->ID == Member::currentUserID()
        ));

        return $controller;
    }

    /**
     * @var MemberProfilePageController
     */
    protected function getParent()
    {
        return $this->parent;
    }

    /**
     * @var string
     */
    protected function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getPaginationStart()
    {
        if ($start = $this->request->getVar('start')) {
            if (ctype_digit($start) && (int) $start > 0) {
                return (int) $start;
            }
        }

        return 0;
    }

    /**
     * @return string
     */
    public function Link($action = null)
    {
        return Controller::join_links($this->getParent()->Link(), $this->getName(), $action);
    }
}
