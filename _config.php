<?php
/**
 * @package silverstripe-memberprofiles
 */

Object::add_extension('Member', 'MemberProfileExtension');

if (class_exists('SortableDataObject')){
	SortableDataObject::add_sortable_class('MemberProfileField');
}
