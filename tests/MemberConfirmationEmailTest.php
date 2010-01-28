<?php
/**
 * Tests for {@link MemberConfirmationEmail}.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage tests
 */
class MemberConfirmationEmailTest extends SapphireTest {

	protected $usesDatabase = true;

	/**
	 * @covers MemberConfirmationEmail::get_parsed_string
	 */
	public function testGetParsedString() {
		$page   = new MemberProfilePage();
		$member = new Member();

		$member->Email     = 'Test Email';
		$member->FirstName = 'Test';
		$member->LastName  = 'User';
		$member->write();

		$raw = '<ul>
			<li>Cost: $10</li>
			<li>Site Name: $SiteName</li>
			<li>Login Link: $LoginLink</li>
			<li>Member:
				<ul>
					<li>Since: $Member.Created</li>
					<li>Email: $Member.Email</li>
					<li>Name: $Member.Name</li>
					<li>Surname: $Member.Surname</li>
				</ul>
			</li>
		</ul>';

		$expected = "<ul>
			<li>Cost: $10</li>
			<li>Site Name: " . SiteConfig::current_site_config()->Title . "</li>
			<li>Login Link: " . Director::absoluteURL(Security::Link('login')) . "</li>
			<li>Member:
				<ul>
					<li>Since: " . $member->obj('Created')->Nice() . "</li>
					<li>Email: {$member->Email}</li>
					<li>Name: {$member->Name}</li>
					<li>Surname: {$member->Surname}</li>
				</ul>
			</li>
		</ul>";

		$this->assertEquals (
			$expected,
			MemberConfirmationEmail::get_parsed_string($raw, $member, $page),
			'All allowed variables are parsed into the string.'
		);
	}

}