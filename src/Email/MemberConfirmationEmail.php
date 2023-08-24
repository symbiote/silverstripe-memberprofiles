<?php

namespace Symbiote\MemberProfiles\Email;

use Symbiote\MemberProfiles\Pages\MemberProfilePage;
use SilverStripe\Security\Member;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;

/**
 * An email sent to the user with a link to validate and activate their account.
 *
 * @package silverstripe-memberprofiles
 */
class MemberConfirmationEmail extends Email
{
    /**
     * @var Member|null
     */
    private $member = null;

    /**
     * @var MemberProfilePage
     */
    private $page = null;

    /**
     * The default confirmation email subject if none is provided.
     *
     * @var string
     */
    const DEFAULT_SUBJECT = '$SiteName Member Activation';

    /**
     * The default email template to use if none is provided.
     *
     * @var string
     */
    const DEFAULT_TEMPLATE = '
<p>
	Dear $Member.Email,
</p>

<p>
	Thank you for registering at $SiteName! In order to use your account you must first confirm your
	email address by clicking on the link below. Until you do this you will not be able to log in.
</p>

<h3>Account Confirmation</h3>

<p>
	Please <a href="$ConfirmLink">confirm your email</a>, or copy and paste the following URL into
	your browser to confirm this is your real email address:
</p>

<pre>$ConfirmLink</pre>

<p>
	If you were not the person who signed up using this email address, please ignore this email. The
	user account will not become active.
</p>

<h3>Log-In Details</h3>

<p>
	Once your account has been activated you will automatically be logged in. You can also log in
	<a href="$LoginLink">here</a>. If you have lost your password you can generate a new one
	on the <a href="$LostPasswordLink">lost password</a> page.
</p>
';

    /**
     * A HTML note about what variables will be replaced in the subject and body fields.
     *
     * @var string
     */
    const TEMPLATE_NOTE = '
<p>
	The following special variables will be replaced in the email template and subject line:
</p>

<ul>
	<li>$SiteName: The name of the site from the default site configuration.</li>
	<li>$ConfirmLink: The link to confirm the user account.</li>
	<li>$LoginLink: The link to log in with.</li>
	<li>$LostPasswordLink: A link to the forgot password page.</li>
	<li>
		$Member.(Field): Various fields to do with the registered member. The available fields are
		Name, FirstName, Surname, Email, and Created.
	</li>
</ul>
';

    /**
     * @param MemberProfilePage $page
     * @param Member $member
     */
    public function __construct(MemberProfilePage $page, Member $member)
    {
        parent::__construct();

        $this->page = $page;
        $this->member = $member;

        $emailFrom = $page->EmailFrom;
        if (!$emailFrom) {
            $emailFrom = Email::config()->get('admin_email');
        }
        $this->setFrom($emailFrom);
        $this->setTo($member->Email);
        $this->setSubject($this->getParsedString($page->EmailSubject));
        $this->setBody($this->getParsedString($page->EmailTemplate));
    }

    /**
     * Deprecated. For backwards compatibility.
     * NOTE(Jake): 2018-05-01, Removed for SS4 upgrade. Also removed $isSingleton param from __construct()
     *
     * @param string $string
     * @param Member $member
     * @param SiteTree $page
     * @return string
     */
    /*public static function get_parsed_string($string, Member $member, $page)
    {
        $class = get_called_class();
        $inst = new $class($page, $member, true);
        return $inst->getParsedString($string);
    }*/

    /**
     * Replaces variables inside an email template according to {@link TEMPLATE_NOTE}.
     *
     * @param string $string
     * @return string
     */
    public function getParsedString($string)
    {
        $member = $this->getMember();
        $page = $this->getPage();

        /**
         * @var \SilverStripe\ORM\FieldType\DBDatetime $createdDateObj
         */
        $createdDateObj = $member->obj('Created');

        $absoluteBaseURL = $this->BaseURL();
        $variables = [
            '$SiteName'       => SiteConfig::current_site_config()->Title,
            '$LoginLink'      => Controller::join_links(
                $absoluteBaseURL,
                singleton(Security::class)->Link('login')
            ),
            '$ConfirmLink'    => Controller::join_links(
                $this->page->AbsoluteLink('confirm'),
                $member->ID,
                "?key={$member->ValidationKey}"
            ),
            '$LostPasswordLink' => Controller::join_links(
                $absoluteBaseURL,
                singleton(Security::class)->Link('lostpassword')
            ),
            '$Member.Created' => $createdDateObj->Nice()
        ];
        foreach (['Name', 'FirstName', 'Surname', 'Email'] as $field) {
            $variables["\$Member.$field"] = $member->$field;
        }
        $this->extend('updateEmailVariables', $variables);

        return str_replace(array_keys($variables), array_values($variables), $string);
    }

    public function BaseURL()
    {
        $absoluteBaseURL = parent::BaseURL();
        $this->extend('updateBaseURL', $absoluteBaseURL);
        return $absoluteBaseURL;
    }

    /**
     * @return MemberProfilePage
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }
}
