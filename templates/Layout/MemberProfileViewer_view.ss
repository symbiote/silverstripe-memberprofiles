<% require css(memberprofiles/css/MemberProfileViewer.css) %>

<div class="content-container typography>">
	<h1>$Title</h1>
	
	<div class="content member-profile <% if IsSelf %>member-profile-self<% end_if %>">
		<% if IsSelf %>
			<p class="message">This is your profile! <a href="$Parent.Link">Edit Profile</a></p>
		<% end_if %>
		<% loop Sections %>
			<div id="$ClassName" class="member-profile-section">
				<% if ShowTitle %><h3>$Title</h3><% end_if %>
				$Me
			</div>
		<% end_loop %>
	</div>
</div>
