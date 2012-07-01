<?php
/**
 * A wrapper around a field to add a checkbox to optionally mark it as visible.
 *
 * @package    silverstripe-memberprofiles
 * @subpackage formfields
 */
class CheckableVisibilityField extends FormField {

	protected $child, $checkbox, $alwaysVisible = false;

	/**
	 * @param FormField $child
	 */
	public function __construct($child) {
		parent::__construct($child->getName());

		$this->child    = $child;
		$this->checkbox = new CheckboxField("Visible[{$this->name}]", '');
	}

	/**
	 * @return FormField
	 */
	public function getChild() {
		return $this->child;
	}

	/**
	 * @return CheckboxField
	 */
	public function getCheckbox() {
		return $this->checkbox;
	}

	public function makeAlwaysVisible() {
		$this->alwaysVisible = true;
		$this->checkbox->setValue(true);
		$this->checkbox = $this->checkbox->performDisabledTransformation();
	}

	public function setValue($value, $data = array()) {
		$this->child->setValue($value);

		if ($this->alwaysVisible) {
			$this->checkbox->setValue(true);
		} elseif (is_array($data)) {
			$this->checkbox->setValue((
				isset($data['Visible'][$this->name]) && $data['Visible'][$this->name]
			));
		} else {
			$this->checkbox->setValue(in_array(
				$this->name, $data->getPublicFields()
			));
		}

		return $this;
	}

	public function saveInto(DataObjectInterface $record) {
		$child = clone $this->child;
		$child->setName($this->name);
		$child->saveInto($record);

		$public = $record->getPublicFields();

		if ($this->checkbox->dataValue()) {
			$public = array_merge($public, array($this->name));
		} else {
			$public = array_diff($public, array($this->name));
		}

		$record->setPublicFields($public);
	}

	public function validate($validator) {
		return $this->child->validate($validator);
	}

	public function Value() {
		return $this->child->Value();
	}

	public function dataValue() {
		return $this->child->dataValue();
	}

	public function Field($properties = array()) {
		return $this->child->Field() . ' ' . $this->checkbox->Field();
	}

	public function Title() {
		return $this->child->Title();
	}

}