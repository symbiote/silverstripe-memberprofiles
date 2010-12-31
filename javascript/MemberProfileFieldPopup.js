;(function($) {
	var visibility = $('#ComplexTableField_Popup_DetailForm_PublicVisibility');
	var visDefault = $('#PublicVisibilityDefault');

	visibility.change(function() {
		visDefault.toggle($(this).val() == 'MemberChoice');
	});
	visibility.trigger('change');
})(jQuery);