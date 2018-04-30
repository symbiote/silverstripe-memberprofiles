<div class="content member-profile <% if IsSelf %>member-profile-self<% end_if %>">
	<% if IsSelf %>
		<p class="message"><%t MemberProfiles.THISISYOURPROFILE 'This is your profile!' %> <a href="$Parent.Link"><%t MemberProfiles.EDITPROFILE 'Edit Profile' %></a></p>
	<% end_if %>
	<% loop Sections %>
		<div id="$ClassName" class="member-profile-section">
			<% if ShowTitle %><h3>$Title</h3><% end_if %>
			$Me
		</div>
	<% end_loop %>
</div>
