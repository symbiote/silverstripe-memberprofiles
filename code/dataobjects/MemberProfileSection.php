<?php
/**
 * A section of a public profile page.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage dataobjects
 */
class MemberProfileSection extends DataObject {

	public static $db = array(
		'CustomTitle' => 'Varchar(100)'
	);

	public static $has_one = array(
		'Parent' => 'MemberProfilePage'
	);

	public static $summary_fields = array(
		'DefaultTitle' => 'Title',
		'CustomTitle'  => 'Custom Title'
	);

	protected $member;

	/**
	 * @return Member
	 */
	public function getMember() {
		return $this->member;
	}

	/**
	 * @param Member $member
	 */
	public function setMember($member) {
		$this->member = $member;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new HeaderField(
			'ProfileSectionTitle',
			_t('MemberProfiles.PROFILESECTION', 'Profile Section')),
			'CustomTitle');
		$fields->addFieldToTab('Root.Main', new ReadonlyField(
			'DefaultTitle',
			_t('MemberProfiles.SECTIONTYPE', 'Section type')),
			'CustomTitle');

		return $fields;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->CustomTitle ? $this->CustomTitle: $this->getDefaultTitle();
	}

	/**
	 * Returns the title for this profile section. You must implement this in
	 * subclasses.
	 *
	 * @return string
	 */
	public function getDefaultTitle() {
		throw new Exception("Please implement getDefaultTitle() on {$this->class}.");
	}

	/**
	 * Controls whether the title is shown in the template.
	 *
	 * @return bool
	 */
	public function ShowTitle() {
		return true;
	}

	/**
	 * Returns the content to be rendered into the profile template.
	 *
	 * @return string
	 */
	public function forTemplate() {
		throw new Exception("Please implement forTemplate() on {$this->class}.");
	}

}