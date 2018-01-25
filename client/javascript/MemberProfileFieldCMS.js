;(function($) {
	$("#Form_ItemEditForm_PublicVisibility").entwine({
		onmatch: function() {
			this._toggleDefault();
			this._super();
		},
		onchange: function(e) {
			this._toggleDefault();
			this._super(e);
		},
		_toggleDefault: function() {
			$("#PublicVisibilityDefault").toggle($(this).val() == "MemberChoice");
		}
	});
})(jQuery);
