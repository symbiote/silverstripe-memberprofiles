<?php

namespace Symbiote\MemberProfiles\Tests;

use SilverStripe\Security\Member;
use SilverStripe\ORM\DataObject;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Security\Group;
use SilverStripe\Forms\Form;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Dev\FunctionalTest;


/**
 * Tests manually confirming users in the admin panel.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage tests
 */
class MemberConfirmationAdminTest extends FunctionalTest
{
    protected static $fixture_file = 'MemberConfirmationAdminTest.yml';
    protected $usesDatabase = true;

    public function setup(): void
    {
        Email::config()->admin_email = 'james@stark.net';
        parent::setUp();
    }

    public function testManualConfirmation()
    {
        $member = $this->objFromFixture(Member::class, 'unconfirmed');
        $this->assertEquals(true, (bool) $member->NeedsValidation);

        $this->getSecurityAdmin();
        $this->submitForm('Form_ItemEditForm', 'action_doSave', array (
            'ManualEmailValidation' => 'confirm'
        ));
        $member = DataObject::get_by_id(Member::class, $member->ID);
        $this->assertEquals(false, (bool) $member->NeedsValidation);
    }

    public function testResendConfirmationEmail()
    {
        $this->clearEmails();
        $member = $this->objFromFixture(Member::class, 'unconfirmed');
        $this->assertEquals(true, (bool) $member->NeedsValidation);

        $this->getSecurityAdmin();
        $this->submitForm('Form_ItemEditForm', 'action_doSave', array (
            'ManualEmailValidation' => 'resend'
        ));

        $member = DataObject::get_by_id(Member::class, $member->ID);
        $this->assertEquals(true, (bool) $member->NeedsValidation);
        $this->assertEmailSent($member->Email);
    }

    private function getSecurityAdmin()
    {
        $member = $this->objFromFixture(Member::class, 'unconfirmed');
        $admin  = new SecurityAdmin();
        $group  = $this->objFromFixture(Group::class, 'group');

        $this->logInWithPermission('ADMIN');

        $gLink = Controller::join_links($admin->Link(), 'show', $group->ID);
        $mLink = Controller::join_links($admin->Link(), 'EditForm/field/Members/item', $member->ID, 'edit');

        $this->get($gLink);
        $this->get($mLink);
    }
}
