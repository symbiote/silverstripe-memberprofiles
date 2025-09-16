<?php

namespace Symbiote\MemberProfiles\Model;

use Symbiote\MemberProfiles\Pages\MemberProfilePage;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use Exception;

/**
 * A section of a public profile page.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage dataobjects
 * @property int $ParentID
 * @property string $CustomTitle
 * @method \Symbiote\MemberProfiles\Pages\MemberProfilePage Parent()
 */
class MemberProfileSection extends DataObject
{
    private static string $table_name = 'MemberProfileSection';

    private static array $db = [
        'CustomTitle' => 'Varchar(100)',
    ];

    private static array $has_one = [
        'Parent' => MemberProfilePage::class,
    ];

    private static array $owned_by = [
        'Parent',
    ];

    private static array $extensions = [
        Versioned::class . "('Stage', 'Live')",
    ];

    private static array $summary_fields = [
        'DefaultTitle' => 'Title',
        'CustomTitle'  => 'Custom Title',
    ];

    /**
     * @var Member
     */
    private ?Member $member;

    /**
     * @return Member
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * @param ?Member $member
     */
    public function setMember(?Member $member): void
    {
        $this->member = $member;
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab(
            'Root.Main',
            [
                new ReadonlyField(
                    'DefaultTitle',
                    _t('MemberProfiles.SECTIONTYPE', 'Section type')
                ),
                new HiddenField(
                    'ClassName',
                    ''
                )
            ],
            'CustomTitle'
        );

        return $fields;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->CustomTitle ?: $this->getDefaultTitle();
    }

    /**
     * Returns the title for this profile section. You must implement this in
     * subclasses.
     *
     * @return string
     */
    public function getDefaultTitle(): string
    {
        throw new Exception("Please implement getDefaultTitle() on {get_class($this)}.");
    }

    /**
     * Controls whether the title is shown in the template.
     *
     * @return bool
     */
    public function ShowTitle(): bool
    {
        return true;
    }

    /**
     * Returns the content to be rendered into the profile template.
     *
     * @return string
     */
    public function forTemplate(): mixed
    {
        throw new Exception("Please implement forTemplate() on {get_class($this)}.");
    }

    public function canEdit($member = null): bool
    {
        return $this->customExtendedCan(__FUNCTION__, $member);
    }

    public function canView($member = null): bool
    {
        return $this->customExtendedCan(__FUNCTION__, $member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return $this->customExtendedCan(__FUNCTION__, $member, $context);
    }

    public function canDelete($member = null): bool
    {
        return $this->customExtendedCan(__FUNCTION__, $member);
    }

    /**
     * @return bool|null
     */
    private function customExtendedCan($methodName, $member, $context = []): ?bool
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        // Standard mechanism for accepting permission changes from extensions
        $extended = $this->extendedCan($methodName, $member, $context);

        if ($extended !== null) {
            return $extended;
        }

        // If has permission to edit profile page, you have permission to edit this field.
        $page = $this->Parent();

        if ($page && $page->exists()) {
            return $page->$methodName($member);
        }

        // Default permissions
        if (Permission::checkMember($member, "SITETREE_EDIT_ALL")) {
            return true;
        }

        // Fallback to default DataObject permissions
        return parent::$methodName($member);
    }
}
