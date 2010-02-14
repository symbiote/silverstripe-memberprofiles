<?php
/**
 * A temporary solution to add drag-and-drop ordering support to ComplexTableField.
 *
 * @package silverstripe-memberprofiles
 */
class OrderableCTF extends ComplexTableField {

	protected $showPagination = false;

	protected $template = 'OrderableCTF';

	/**
	 * Handles saving the updated order of objects.
	 */
	public function order($request) {
		$objects  = $this->sourceItems();
		$sort     = Object::get_static($this->sourceClass, 'default_sort');
		$newIDs   = $request->postVar('ids');
		$sortVals = array_values($objects->map('ID', $sort));

		// populate new ID values
		foreach($objects as $object) if(!$object->$sort) {
			$table = $object->class;
			$query = DB::query("SELECT MAX(\"$sort\") + 1 FROM \"$table\"");

			$object->$sort = $oldIDs[$object->ID] = $query->value();
			$object->write();
		}

		// save the new ID values - but only use existing sort values to prevent
		// conflicts with items not in the table
		foreach($newIDs as $key => $id) {
			$object = $objects->find('ID', $id);

			$object->$sort = $sortVals[$key];
			$object->write();
		}

		$objects->sort($sort);
		return $this->FieldHolder();
	}

	/**
	 * @return string
	 */
	public function FieldHolder() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR   . '/javascript/jquery_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/ui.core.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/ui.sortable.js');
		Requirements::javascript('memberprofiles/javascript/OrderableCTF.js');

		return parent::FieldHolder();
	}

}