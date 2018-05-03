<?php

namespace Symbiote\MemberProfiles\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

class MemberProfilesAddSectionAction_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    public function Link($action = null)
    {
        if ($this->record->ID) {
            return parent::Link($action);
        } else {
            return Controller::join_links(
                $this->gridField->Link(),
                'addsection',
                urlencode(get_class($this->record))
            );
        }
    }
}
