<p>
	<%t MemberProfiles.APPROVALREQUIRED 'A new member has registered for {siteTitle}, and requires approval before they can log in:' siteTitle=$SiteConfig.Title %>
</p>

<% with Member %>
	<dl>
		<dt><%t MemberProfiles.Name 'Name' %>:</dt>
		<dd>$Name</dd>
		<dt><%t MemberProfiles.Email 'Email' %>:</dt>
		<dd>$Email</dd>
		<dt><%t MemberProfile.Registered 'Registered' %>:</dt>
		<dd>$Created.Date</dd>
	</dl>
<% end_with %>

<p>
	<%t MemberProfiles.APPROVALLINK 'Please visit the link below to confirm this member. Once approved they will be sent a confirmation email if configured. If you do not approve this member then they will not be able to log in.' %>
</p>

<p>
	<a href="$ApproveLink">$ApproveLink</a>
</p>