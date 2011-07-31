<p>
	A new member has registered for $SiteConfig.Title, and requires approval
	before they can log in:
</p>

<% control Member %>
	<dl>
		<dt>Name:</dt>
		<dd>$Name</dd>
		<dt>Email:</dt>
		<dd>$Email</dd>
		<dt>Registered:</dt>
		<dd>$Created.Date</dd>
	</dl>
<% end_control %>

<p>
	Please visit the link below to confirm this member. Once approved they will
	be sent a confirmation email if configured. If you do not approve this member
	then they will not be able to log in.
</p>

<p>
	<a href="$ApproveLink">$ApproveLink</a>
</p>