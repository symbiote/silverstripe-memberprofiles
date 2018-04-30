<div class="content">
	$Content

	<% if $CanAddMembers %>
		<h2><%t MemberProfiles.ADDMEMBER 'Add Member' %></h2>
		<p><%t MemberProfiles.ADDMEMBERLINK 'You can use this page to <a href="{addLink}">add a new member</a>.' addLink=$Link(add) %></p>

		<h2><%t MemberProfiles.YOURPROFILE 'Your Profile' %></h2>
	<% end_if %>

	$Form
</div>
