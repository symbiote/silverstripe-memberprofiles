<% require css(memberprofiles/css/MemberProfileViewer.css) %>

<div id="Content" class="typography">
	<div id="MemberProfile"<% if IsSelf %> class="self"<% end_if %>>
		<div id="MemberProfileTitle">
			<% if IsSelf %>
				<p class="memberProfileSelf right">
					This is your profile! <a href="$Parent.Link">Edit Profile</a>
				</p>
			<% end_if %>
			<h2>$Member.Name&apos;s User Profile</h2>
		</div>
		<% control Sections %>
			<div id="$ClassName" class="memberProfileSection">
				<% if ShowTitle %><h3>$Title</h3><% end_if %>
				$Me
			</div>
		<% end_control %>
	</div>
</div>