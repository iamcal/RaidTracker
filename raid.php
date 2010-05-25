<?
	include('init.php');

	$id = intval($_GET[id]);
	$raid = db_fetch_hash(db_query("SELECT * FROM raids WHERE id=$id"));

	$date = date('l jS F, Y', strtotime($raid[day]));
	$zone = format_zone($raid[zone], $raid[difficulty]);

	$page_title = "Raid : $zone - $date";

	include('head.txt');
?>

<p>
	<b>Day:</b> <a href="date.php?d=<?=$raid[day]?>"><?=$raid[day]?></a><br />
	<b>Started:</b> <?=date('Y-m-d g:ia', $raid[date_start])?><br />
	<b>Ended:</b> <?=date('Y-m-d g:ia', $raid[date_end])?><br />
	<b>Length:</b> <?=format_period($raid[date_end] - $raid[date_start])?><br />
</p>


<h2>Boss Kills</h2>

<table border="1">
	<tr>
		<th>Boss</th>
		<th>Raid</th>
		<th>Time</th>
	</tr>
<?
	$result = db_query("SELECT * FROM bosses WHERE raid_id=$raid[id] ORDER BY date_kill ASC");
	while ($row = db_fetch_hash($result)){
?>
	<tr>
		<td><?=$row[name]?></td>
		<td><?=format_zone($row[zone], $row[difficulty])?></td>
		<td><?=format_time($row[date_kill])?></td>
	</tr>
<?
	}
	if (!db_num_rows($result)){
?>
	<tr>
		<td colspan="3" style="text-align: center;"><i>No boss kills</i></td>
	</tr>
<?
	}
?>
</table>


<h2>Loots</h2>

<script>

function lootItem(id,state){
	document.getElementById('looter-'+id).innerHTML = '...';
	document.getElementById('links-'+id).innerHTML = '...';

	ajaxify('api_loot.php', {id: id, state: state}, function(o){
		if (o.ok){
			document.getElementById('looter-'+id).innerHTML = o.looter;
			document.getElementById('links-'+id).innerHTML = o.links;
		}
	});

	return false;
}

</script>

<table border="1">
	<tr>
		<th>&nbsp;</th>
		<th>Item</th>
		<th>Dropped by</th>
		<th>Looted to</th>
		<th>Time</th>
		<th>Edit</th>
	</tr>
<?
	$result = db_query("SELECT * FROM loots WHERE raid_id=$raid[id] ORDER BY date_drop ASC");
	while ($row = db_fetch_hash($result)){
?>
	<tr>
		<td><a href="http://www.wowhead.com/item=<?=$row[item_id]?>"><img src="http://static.wowhead.com/images/wow/icons/small/<?=$row[item_icon]?>.jpg" width="18" height="18" /></a></td>
		<td><a href="item.php?id=<?=$row[item_id]?>"><?=$row[item_name]?></a></td>
		<td><?=$row[source]?></td>
<? if ($row[ded] == 0){ ?>
		<td id="looter-<?=$row[id]?>"><a href="player.php?name=<?=$row[player_name]?>"><?=$row[player_name]?></a></td>
<? }else if ($row[ded] == 1){ ?>
		<td id="looter-<?=$row[id]?>">DE'd</td>
<? }else if ($row[ded] == 2){ ?>
		<td id="looter-<?=$row[id]?>">Banked</td>
<? }else{ ?>
		<td id="looter-<?=$row[id]?>">ERROR</td>
<? } ?>
		<td><?=format_time_only($row[date_drop])?></td>
		<td id="links-<?=$row[id]?>">
<? if ($row[ded] != 0){ ?>
			<a href="#" onclick="return lootItem(<?=$row[id]?>,0);">Loot</a>
<? } ?>
<? if ($row[ded] != 1){ ?>
			<a href="#" onclick="return lootItem(<?=$row[id]?>,1);">DE</a>
<? } ?>
<? if ($row[ded] != 2){ ?>
			<a href="#" onclick="return lootItem(<?=$row[id]?>,2);">Bank</a>
<? } ?>
		</td>
	</tr>
<?
	}
	if (!db_num_rows($result)){
?>
	<tr>
		<td colspan="5" style="text-align: center;"><i>No items looted</i></td>
	</tr>
<?
	}
?>
</table>


<h2>Attendance</h2>

<table border="1">
	<tr>
		<th>Player</th>
		<th>In Raid</th>
		<th>On List</th>
		<th>Offline</th>
		<th>Percent</th>
	</tr>
<?
	$duration = $raid[date_end] - $raid[date_start];

	$result = db_query("SELECT * FROM attendance WHERE raid_id=$raid[id] ORDER BY player_name ASC");
	while ($row = db_fetch_hash($result)){

		$percent = min(100, round(100 * ($row[time_raid]+$row[time_wait]+60) / $duration));
?>
	<tr>
		<td><a href="player.php?name=<?=$row[player_name]?>"><?=$row[player_name]?></a></td>
		<td style="text-align: right;"><?=format_period($row[time_raid], 1)?></td>
		<td style="text-align: right;"><?=format_period($row[time_wait], 1)?></td>
		<td style="text-align: right;"><?=format_period($row[time_offline], 1)?></td>
		<td style="text-align: right;"><?=format_percent($percent)?></td>
	</tr>
<?
	}
?>
</table>


<?
	include('foot.txt');
?>