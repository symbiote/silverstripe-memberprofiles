<% require css(symbiote/silverstripe-memberprofiles: client/css/MemberProfileViewer.css) %>
<div class="content-container typography">
	<h1>$Title</h1>

	<% if $Type = 'List' %>
		<% include Symbiote/MemberProfiles/Pages/MemberProfileViewer_list %>
	<% else %>
		<% include Symbiote/MemberProfiles/Pages/MemberProfileViewer_view %>
	<% end_if %>
</div>
