<div class="content-container typography>">
	<h1>$Title</h1>
	
	<div class="content">
		$Content

		<% if $CanAddMembers %>
			<h2>Add Member</h2>
			<p>You can use this page to <a href="$Link(add)">add a new member</a>.</p>

			<h2>Your Profile</h2>
			$Form
		<% else %>
			$Form
		<% end_if %>
	</div>
</div>
