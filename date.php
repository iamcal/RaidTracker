<?
	include('init.php');

	$page_title = 'Raids on '.HtmlSpecialChars($_GET[d]);

	include('head.txt');
?>

<table width="100%">
	<tr>
		<th>Raid</th>
		<th>Duration</th>
		<th>Start</th>
		<th>End</th>
		<th>Bosses Killed</th>
		<th>Items Looted</th>
		<th>Raiders</th>
	</tr>

<?
	$d_enc = AddSlashes($_GET[d]);

	$result = db_query("SELECT * FROM raids WHERE day='$d_enc' ORDER BY date_start ASC");
	while ($row = db_fetch_hash($result)){
		list($bosses) = db_fetch_list(db_query("SELECT COUNT(*) FROM bosses WHERE raid_id=$row[id]"));
		list($loots) = db_fetch_list(db_query("SELECT COUNT(*) FROM loots WHERE raid_id=$row[id]"));
		list($players) = db_fetch_list(db_query("SELECT COUNT(*) FROM attendance WHERE raid_id=$row[id]"));
?>
	<tr>
		<td><a href="raid.php?id=<?=$row[id]?>"><?=$row[day]?> - <?=format_zone($row[zone], $row[difficulty])?></a></td>
		<td><?=format_period($row[date_end] - $row[date_start], 1)?></td>
		<td><?=format_time_only($row[date_start])?></td>
		<td><?=format_time_only($row[date_end])?></td>
		<td style="text-align: center"><?=$bosses?></td>
		<td style="text-align: center"><?=$loots?></td>
		<td style="text-align: center"><?=$players?></td>
	</tr>
<?
	}
	if (!db_num_rows($result)){
?>
	<tr>
		<td colspan="5" style="text-align: center;"><i>No raids</i></td>
	</tr>
<?
	}
?>
</table>


<?
	include('foot.txt');
?>