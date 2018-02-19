;(function($) {
	$("#Form_EditForm_RequireApproval").livequery(function() {
		$(this)
			.change(function() { $("#ApprovalGroups").toggle(this.checked); })
			.trigger("change");
	});
})(jQuery);