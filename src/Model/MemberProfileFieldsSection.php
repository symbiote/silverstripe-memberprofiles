<?php

namespace Symbiote\MemberProfiles\Model;

use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;

/**
 * A profile section that displays a list of fields that have been marked as
 * public.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage dataobjects
 */
class MemberProfileFieldsSection extends MemberProfileSection
{
    private static string $table_name = 'MemberProfileFieldsSection';

    public function getDefaultTitle(): string
    {
        return _t('MemberProfiles.PROFILEFIELDSLIST', 'Profile Fields List');
    }

    public function forTemplate(): string
    {
        return $this->renderWith(MemberProfileFieldsSection::class);
    }

    public function Fields(): ArrayList
    {
        $fields = $this->Parent()->Fields()->where('"PublicVisibility" <> \'Hidden\'');
        $public = $this->getMember()->getPublicFields();
        $result = new ArrayList();

        foreach ($fields as $field) {
            if (($field->PublicVisibility === 'MemberChoice') && !in_array($field->MemberField, $public)) {
                continue;
            }

            $result->push(new ArrayData([
                'Title' => $field->Title,
                'Value' => $this->getMember()->{$field->MemberField}
            ]));
        }

        return $result;
    }

    public function ShowTitle(): bool
    {
        return false;
    }
}
