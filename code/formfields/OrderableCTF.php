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
		$items = $this->sourceItems();
		$sort  = Object::get_static($this->sourceClass, 'default_sort');
		$order = $request->postVar('ids');

		// Populate each object with a sort value.
		foreach ($items as $item) if (!$item->$sort) {
			$table = $item->class;
			$query = DB::query("SELECT MAX(\"$sort\") + 1 FROM \"$table\"");

			$item->$sort = $query->value();
			$item->write();
		}

		// Re-order the fields, but only use existing sort values to prevent
		// conflicts with items not in this CTF.
		$values = array_values($items->map('ID', $sort));

		foreach ($order as $key => $id) {
			$item = $items->find('ID', $id);

			$item->$sort = $values[$key];
			$item->write();
		}

		$items->sort($sort);
		return $this->FieldHolder();
	}

	/**
	 * @return string
	 */
	public function FieldHolder() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR   . '/javascript/jquery_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui-1.8rc3.custom.js');
		Requirements::javascript('memberprofiles/javascript/OrderableCTF.js');

		return parent::FieldHolder();
	}

}