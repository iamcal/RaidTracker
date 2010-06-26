<?
	include('init.php');

	$page_title = 'Roster Changes';
	$nav = 'roster';

	include('head.txt');
?>


<table>
	<tr>
		<th>When</th>
		<th>Event</th>
		<th colspan="2">Player</th>
		<th>Class</th>
		<th>Level</th>
	</tr>
<?
	$result = db_query("SELECT * FROM roster_changes ORDER BY date_create ASC");
	while ($row = db_fetch_hash($result)){
		$row[class_id] = StrToLower(str_replace(' ', '', $row['class']));
?>
	<tr>
		<td><?=date('Y-m-d ga', $row[date_create])?></td>
		<td><?=$row['action']?></td>
		<td style="padding: 2px;"><a href="player.php?name=<?=$row[name]?>"><?=insert_icon("class_$row[class_id]")?></a></td>
		<td><a href="player.php?name=<?=$row[name]?>" class="class-<?=$row[class_id]?> class-link"><?=$row[name]?></a></td>
		<td><?=$row['class']?></td>
		<td><?=$row[level]?></td>
	</tr>
<?
	}
?>
</table>


<?
	include('foot.txt');
?>