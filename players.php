<?
	include('init.php');

	$page_title = 'All Players';
	$nav = 'players';

	include('head.txt');
?>


<h2>Guild Roster</h2>

<table border="1">
	<tr>
		<th>Player</th>
		<th>Class</th>
		<th>Loots</th>
		<th>Raids</th>
	</tr>
<?
	$loots = array();
	$result = db_query("SELECT player_name, COUNT(id) AS num FROM loots WHERE ded=0 GROUP BY player_name");
	while ($row = db_fetch_hash($result)){
		$loots[$row[player_name]] = $row[num];
	}

	$raids = array();
	$result = db_query("SELECT player_name, COUNT(raid_id) AS num FROM attendance GROUP BY player_name");
	while ($row = db_fetch_hash($result)){
		$raids[$row[player_name]] = $row[num];
	}

	$result = db_query("SELECT * FROM players WHERE guild='The Eternal' ORDER BY name ASC");
	while ($row = db_fetch_hash($result)){
?>
	<tr>
		<td><a href="player.php?name=<?=$row[name]?>"><?=$row[name]?></a></td>
		<td><?=$row['class']?></td>
		<td style="text-align: center"><?=intval($loots[$row[name]])?></td>
		<td style="text-align: center"><?=intval($raids[$row[name]])?></td>
	</tr>
<?
	}
?>
</table>

<?
	include('foot.txt');
?>