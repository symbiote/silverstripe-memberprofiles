
<p><strong><%t MemberProfiles.APPROVALREQUIRED 'A new member has registered for {siteTitle}' siteTitle=$SiteConfig.Title %></strong></p>

<table>
   	<tbody>
		<% with Member %>
		<tr>
            <td><strong><%t MemberProfiles.Name 'Name' %></strong></td>
            <td>$Name</td>
        </tr>
        <tr>
            <td><strong><%t MemberProfiles.Email 'Email' %></strong></td>
            <td>$Email</td>
        </tr>
        <tr>
            <td><strong><%t MemberProfile.Registered 'Registered' %></strong></td>
            <td>$Created.Date</td>
        </tr>
		<% end_with %>
    </tbody>
</table>
