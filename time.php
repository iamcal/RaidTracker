<?
	include('init.php');

	$page_title = 'Total Raid Times';
	$nav = 'time';

	include('head.txt');
?>


<table>
	<tr>
		<th>&nbsp;</th>
		<th>Player</th>
		<th>Class</th>
		<th>Guild</th>
		<th>Loots</th>
		<th>Raids</th>
		<th>Total Raid Time</th>
	</tr>
<?
	$loots = array();
	$result = db_query("SELECT player_name, COUNT(id) AS num FROM loots WHERE ded=0 GROUP BY player_name");
	while ($row = db_fetch_hash($result)){
		$loots[$row[player_name]] = $row[num];
	}

	$details = array();
	$result = db_query("SELECT * FROM players ORDER BY name ASC");
	while ($row = db_fetch_hash($result)){
		$row[class_id] = StrToLower(str_replace(' ', '', $row['class']));
		$details[$row[name]] = $row;
	}

	$raids = array();
	$result = db_query("SELECT player_name, COUNT(raid_id) AS num, SUM(time_raid) AS total_time FROM attendance GROUP BY player_name ORDER BY total_time DESC");
	while ($row = db_fetch_hash($result)){

		$detail = $details[$row[player_name]];
?>
	<tr>
		<td style="padding: 2px;"><a href="player.php?name=<?=$detail[name]?>"><?=insert_icon("class_$detail[class_id]")?></a></td>
		<td><a href="player.php?name=<?=$detail[name]?>" class="class-<?=$detail[class_id]?> class-link"><?=$detail[name]?></a></td>
		<td><?=$detail['class']?></td>
		<td style="text-align: center<?=($detail[guild]=='The Eternal')?'; color: #666;':''?>">&lt;<?=$detail[guild]?$detail[guild]:'...'?>&gt;</td>
		<td style="text-align: center"><?=intval($loots[$detail[name]])?></td>
		<td style="text-align: center"><?=intval($row[num])?></td>
		<td style="text-align: center"><?=format_period($row[total_time], 1)?></td>
	</tr>
<?
	}
?>
</table>



<?
	include('foot.txt');
?>