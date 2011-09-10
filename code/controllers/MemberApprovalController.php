<?php
/**
 * @package silverstripe-memberprofiles
 */
class MemberApprovalController extends Page_Controller {

	public static $url_handlers = array(
		'$ID' => 'index'
	);

	public static $allowed_actions = array(
		'index'
	);

	public function index($request) {
		$id    = $request->param('ID');
		$token = $request->getVar('token');

		if (!$id || !ctype_digit($id)) {
			$this->httpError(404, 'A member ID was not specified.');
		}

		$member = DataObject::get_by_id('Member', $id);

		if (!$member) {
			$this->httpError(404, 'The specified member could not be found.');
		}

		if (!$member->canEdit()) {
			return Security::permissionFailure();
		}

		if ($token != $member->ValidationKey) {
			$this->httpError(400, 'An invalid token was specified.');
		}

		if (!$member->NeedsApproval) {
			$title   = _t('MemberProfiles.ALREADYAPPROVED', 'Already Approved');
			$content = _t('MemberProfiles.ALREADYAPPROVEDNOTE', 'This member has already been approved');

			return $this->render(array(
				'Title'   => $title,
				'Content' => "<p>$content</p>"
			));
		}

		$member->NeedsApproval = false;
		$member->write();

		$title   = _t('MemberProfiles.MEMBERAPPROVED', 'Member Approved');
		$content = _t('MemberProfiles.MEMBERAPPROVED', 'The member "%s" has been approved and can now log in.');
		$content = sprintf($content, Convert::raw2xml("$member->Name <$member->Email>"));

		return $this->render(array(
			'Title'   => $title,
			'Content' => $content
		));
	}

	public function Link($action = null) {
		return Controller::join_links(Director::baseURL(), 'member-approval', $action);
	}

}