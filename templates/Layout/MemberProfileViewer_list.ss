<% require css(memberprofiles/css/MemberProfileViewer.css) %>

<div id="Content" class="typography">
	<h2>$Title</h2>
	<table id="MemberList">
		<thead>
			<tr>
				<% control Members.First %>
					<% control Fields %>
						<% if Sortable %>
							<th><a href="$Top.Link?sort=$Name">$Title</a></th>
						<% else %>
							<th>$Title</th>
						<% end_if %>
					<% end_control %>
				<% end_control %>
			</tr>
		</thead>
		<tbody>
			<% control Members %>
				<tr class="$EvenOdd">
					<% control Fields %>
						<td><a href="$Top.Link/$MemberID">$Value</a></td>
					<% end_control %>
				</tr>
			<% end_control %>
		</tbody>
	</table>

	<% if Members.MoreThanOnePage %>
		<div id="MemberListPagination" class="pagination">
			<% if Members.NotFirstPage %>
				<a class="prev" href="$Members.PrevLink"><% _t('PREV', 'Prev') %></a>
			<% end_if %>
			<span class="pageLinks">
				<% control Members.PaginationSummary(4) %>
					<% if CurrentBool %>
						<span class="current">$PageNum</span>
					<% else %>
							<% if PageNum %>
								<a href="$Link">$PageNum</a>
							<% else %>
								&hellip;
							<% end_if %>
					<% end_if %>
				<% end_control %>
			</span>
			<% if Members.NotLastPage %>
				<a class="next" href="$Members.NextLink"><% _t('NEXT', 'Next') %></a>
			<% end_if %>
		</div>
	<% end_if %>
</div>