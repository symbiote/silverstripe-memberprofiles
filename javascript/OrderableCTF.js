(function($) {
	$('.OrderableCTF').livequery(function() {
		$(this).sortable({
			axis:        'y',
			containment: this,
			cursor:      'move',
			items:       'tbody tr',
			handle:      'a.drag',
			update:      function(event, ui) {
				$(this).sortable('disable');
				$(ui.item).find('a.drag img').attr('src', 'cms/images/network-save.gif');

				var ids = [];

				$(this).find('tbody tr').each(function() {
					var $tr = $(this).attr('id');
					var res = $tr.match(/record-[a-zA-Z0-9_]*-([0-9]+)/);

					if(res) ids.push(res[1]);
				});

				$(this).load(
					$(ui.item).find('a.drag').attr('href'),
					{
						'ids[]': ids
					},
					function(data, status) {
						Behavior.apply();
					}
				);
			}
		});
	});
})(jQuery);