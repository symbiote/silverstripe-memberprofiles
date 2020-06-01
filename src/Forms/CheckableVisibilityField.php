<?php

namespace Symbiote\MemberProfiles\Forms;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxField_Readonly;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Symbiote\MemberProfiles\Extensions\MemberProfileExtension;

/**
 * A wrapper around a field to add a checkbox to optionally mark it as visible.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage formfields
 */
class CheckableVisibilityField extends FormField
{
    /**
     * @var FormField
     */
    private $child;

    /**
     * @var FormField|CheckboxField|CheckboxField_Readonly
     */
    private $checkbox;

    /**
     * @var boolean
     */
    private $alwaysVisible = false;

    /**
     * @param FormField $child
     */
    public function __construct($child)
    {
        parent::__construct($child->getName());

        $this->child    = $child;
        $this->checkbox = CheckboxField::create("Visible[{$this->name}]", '');
    }

    /**
     * @return FormField
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @return FormField|CheckboxField|CheckboxField_Readonly
     */
    public function getCheckbox()
    {
        return $this->checkbox;
    }

    /**
     * @return $this
     */
    public function makeAlwaysVisible()
    {
        $this->alwaysVisible = true;
        $this->getCheckbox()->setValue(true);
        $this->checkbox = $this->getCheckbox()->performDisabledTransformation();
        return $this;
    }

    /**
     * @param mixed $value Either the parent object, or array of source data being loaded
     * @param array|MemberProfileExtension $data {@see Form::loadDataFrom}
     * @return $this
     */
    public function setValue($value, $data = array())
    {
        $this->child->setValue($value);

        if ($this->alwaysVisible) {
            $this->checkbox->setValue(true);
        } elseif (is_array($data)) {
            $this->checkbox->setValue((
                isset($data['Visible'][$this->name]) && $data['Visible'][$this->name]
            ));
        } else {
            $this->checkbox->setValue(in_array(
                $this->name,
                $data->getPublicFields()
            ));
        }

        return $this;
    }

    public function saveInto(DataObjectInterface $record)
    {
        $child = clone $this->child;
        $child->setName($this->name);

        if (!($this->child instanceof ReadonlyField)) {
            $child->saveInto($record);
        }

        if ($record instanceof Member) {
            $public = $record->getPublicFields();

            if ($this->checkbox->dataValue()) {
                $public = array_merge($public, array($this->name));
            } else {
                $public = array_diff($public, array($this->name));
            }

            $record->setPublicFields($public);
        }
    }

    public function validate($validator)
    {
        return $this->child->validate($validator);
    }

    public function Value()
    {
        return $this->child->Value();
    }

    public function dataValue()
    {
        return $this->child->dataValue();
    }

    public function setForm($form)
    {
        $this->child->setForm($form);
        $this->checkbox->setForm($form);

        if ($this->child instanceof FileField) {
            $form->setEncType(Form::ENC_TYPE_MULTIPART);
        }

        return parent::setForm($form);
    }

    public function Field($properties = array())
    {
        return DBHTMLText::create_field(
            'HTMLFragment',
            $this->child->Field() . ' ' . $this->checkbox->Field()
        );
    }

    public function Title()
    {
        return $this->child->Title();
    }
}
