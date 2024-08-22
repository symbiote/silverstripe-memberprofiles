<?php

namespace Symbiote\MemberProfiles\Pages;

use PageController;

use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\FieldType\DBField;

/**
 * @package silverstripe-memberprofiles
 */
class MemberApprovalController extends PageController
{

    private static $url_handlers = [
        '$ID' => 'index'
    ];

    private static $allowed_actions = [
        'index'
    ];

    /**
     * Redirect the user to the 'admin/Security' member edit page instead
     * of immediately approving after visiting the approve link.
     *
     * @config
     * @var boolean
     */
    private static $redirect_to_admin = false;

    public function index($request)
    {
        $id    = (int)$request->param('ID');
        $token = $request->getVar('token');

        if (!$id) {
            return $this->httpError(404, 'A member ID was not specified.');
        }

        $member = DataObject::get_by_id(Member::class, $id);

        if (!$member) {
            return $this->httpError(404, 'The specified member could not be found.');
        }

        if (!$member->canEdit()) {
            return Security::permissionFailure();
        }

        if ($token != $member->ValidationKey) {
            return $this->httpError(400, 'An invalid token was specified.');
        }

        if (!$member->ValidationKey) {
            return $this->httpError(400, 'Not a MemberProfilePage member.');
        }

        if (!$member->NeedsApproval) {
            $title   = _t('MemberProfiles.ALREADYAPPROVED', 'Already Approved');
            $content = _t('MemberProfiles.ALREADYAPPROVEDNOTE', 'This member has already been approved.');

            return $this->render([
                'Title'   => $title,
                'Content' => DBField::create_field('HTMLFragment', "<p>$content</p>")
            ]);
        }

        if (Config::inst()->get(self::class, 'redirect_to_admin')) {
            $controller = singleton(SecurityAdmin::class);
            if (!$controller->canView()) {
                return Security::permissionFailure();
            }
            $link = $controller->Link('EditForm/field/Members/item/' . $member->ID . '/edit#MemberProfileRegistrationApproval');
            return $this->redirect($link);
        }

        $member->NeedsApproval = false;
        $member->write();

        $title   = _t('MemberProfiles.MEMBERAPPROVED', 'Member Approved');
        $content = _t('MemberProfiles.MEMBERAPPROVEDCONTENT', 'The member "%s" has been approved and can now log in.');
        $content = DBField::create_field('HTMLFragment', '<p>' . sprintf($content, Convert::raw2xml("$member->Name <$member->Email>")) . '</p>');

        return $this->render([
            'Title'   => $title,
            'Content' => $content
        ]);
    }

    public function Link($action = null)
    {
        return Controller::join_links(Director::baseURL(), 'member-approval', $action);
    }
}
