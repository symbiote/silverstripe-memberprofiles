<% require themedCSS('MemberProfileViewer', 'memberprofiles') %>

<div class="content-container typography">
	<h1>$Title</h1>
	
	<div class="content">
		<table id="member-list">
			<thead>
				<tr>
					<% with Members.First %>
						<% loop Fields %>
							<% if Sortable %>
								<th><a href="$Top.Link?sort=$Name">$Title</a></th>
							<% else %>
								<th>$Title</th>
							<% end_if %>
						<% end_loop %>
					<% end_with %>
				</tr>
			</thead>
			<tbody>
				<% loop Members %>
					<tr class="$EvenOdd">
						<% loop Fields %>
							<td><a href="$Link">$Value</a></td>
						<% end_loop %>
					</tr>
				<% end_loop %>
			</tbody>
		</table>

		<% if Members.MoreThanOnePage %>
			<div id="MemberListPagination" class="pagination">
				<% if Members.NotFirstPage %>
					<a class="prev" href="$Members.PrevLink"><%t PREV 'Prev' %></a>
				<% end_if %>
				<span class="pageLinks">
					<% loop Members.PaginationSummary(4) %>
						<% if CurrentBool %>
							<span class="current">$PageNum</span>
						<% else %>
							<% if PageNum %>
								<a href="$Link">$PageNum</a>
							<% else %>
								&hellip;
							<% end_if %>
						<% end_if %>
					<% end_loop %>
				</span>
				<% if Members.NotLastPage %>
					<a class="next" href="$Members.NextLink"><%t NEXT 'Next' %></a>
				<% end_if %>
			</div>
		<% end_if %>
	</div>
</div>