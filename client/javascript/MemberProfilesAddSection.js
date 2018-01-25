(function($) {
	$(".memberprofiles-add-section select").entwine({
		onchange: function(e) {
			var link = $(e.target.form).find("a.action");
			var val  = this.val();

			if(val) {
				link.prop("href", val);
			} else {
				link.prop("href", "#");
			}

			this._super(e);
		}
	});
})(jQuery);
