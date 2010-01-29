<?php
/**
 * Tests manually confirming users in the admin panel.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage tests
 */
class MemberConfirmationAdminTest extends FunctionalTest {

	public static $fixture_file = 'memberprofiles/tests/MemberConfirmationAdminTest.yml';

	/**
	 * @covers MemberProfileExtension::saveManualEmailValidation
	 * @covers MemberProfileExtension::updateCMSFields
	 */
	public function testManualConfirmation() {
		$member = $this->objFromFixture('Member', 'unconfirmed');
		$admin  = new SecurityAdmin();
		$group  = $this->objFromFixture('Group', 'group');

		Form::disable_all_security_tokens();
		$this->logInWithPermssion('ADMIN');

		$gLink = Controller::join_links($admin->Link(), 'show', $group->ID);
		$mLink = Controller::join_links($admin->Link(), 'EditForm/field/Members/item', $member->ID, 'edit');

		$this->get($gLink);
		$this->get($mLink);

		$this->assertEquals(true, (bool) $member->NeedsValidation);

		$this->submitForm('MemberTableField_Popup_DetailForm', null, array (
			'ManualEmailValidation' => 'confirm'
		));

		$member = DataObject::get_by_id('Member', $member->ID);
		$this->assertEquals(false, (bool) $member->NeedsValidation);
	}

}