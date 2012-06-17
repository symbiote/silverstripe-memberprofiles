<?php
/**
 * Handles displaying member's public profiles.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage controllers
 */
class MemberProfileViewer extends Page_Controller {

	public static $url_handlers = array(
		''           => 'handleList',
		'$MemberID!' => 'handleView'
	);

	protected $parent, $name;

	/**
	 * @param RequestHandler $parent
	 * @param string $name
	 */
	public function __construct($parent, $name) {
		$this->parent = $parent;
		$this->name   = $name;

		parent::__construct();
	}

	/**
	 * Displays a list of all members on the site that belong to the selected
	 * groups.
	 *
	 * @return string
	 */
	public function handleList($request) {
		$fields  = $this->parent->Fields()->filter('MemberListVisible', true);
		$members = $this->parent->Groups()->relation('Members');
		$members = new PaginatedList($members, $request);

		$list = new PaginatedList(new ArrayList(), $request);
		$list->setLimitItems(false);
		$list->setTotalItems($members->getTotalItems());

		foreach($members as $member) {
			$cols   = new ArrayList();
			$public = $member->getPublicFields();
			$link   = $this->Link($member->ID);

			foreach($fields as $field) {
				if(
					   $field->PublicVisibility == 'MemberChoice'
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

		$this->data()->Title  = _t('MemberProfiles.MEMBERLIST', 'Member List');
		$this->data()->Parent = $this->parent;

		$controller = $this->customise(array(
			'Members' => $list
		));
		return $controller->renderWith(array(
			'MemberProfileViewer_list', 'MemberProfileViewer', 'Page'
		));
	}

	/**
	 * Handles viewing an individual user's profile.
	 *
	 * @return string
	 */
	public function handleView($request) {
		$id = $request->param('MemberID');

		if(!ctype_digit($id)) {
			$this->httpError(404);
		}

		$member = Member::get()->byID($id);
		$groups = $this->parent->Groups();

		if(!$member->inGroups($groups)) {
			$this->httpError(403);
		}

		$sections     = $this->parent->Sections();
		$sectionsList = new ArrayList();

		foreach($sections as $section) {
			$sectionsList->push($section);
			$section->setMember($member);
		}

		$this->data()->Title = sprintf(
			_t('MemberProfiles.MEMBERPROFILETITLE', "%s's Profile"),
			$member->getName()
		);
		$this->data()->Parent = $this->parent;

		$controller = $this->customise(array(
			'Member'   => $member,
			'Sections' => $sectionsList,
			'IsSelf'   => $member->ID == Member::currentUserID()
		));
		return $controller->renderWith(array(
			'MemberProfileViewer_view', 'MemberProfileViewer', 'Page'
		));
	}

	/**
	 * @return int
	 */
	public function getPaginationStart() {
		if ($start = $this->request->getVar('start')) {
			if (ctype_digit($start) && (int) $start > 0) return (int) $start;
		}

		return 0;
	}

	/**
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links($this->parent->Link(), $this->name, $action);
	}

}