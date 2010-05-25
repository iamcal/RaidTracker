<?
	include('init.php');

	$nav = 'overview';

	include('head.txt');
?>


<h2>Recent Raids</h2>

<table border="1">
	<tr>
		<th>Raid</th>
		<th>Duration</th>
		<th>Bosses Killed</th>
		<th>Items Looted</th>
		<th>Raiders</th>
	</tr>

<?
	$result = db_query("SELECT * FROM raids ORDER BY date_start DESC LIMIT 10");
	while ($row = db_fetch_hash($result)){
		list($bosses) = db_fetch_list(db_query("SELECT COUNT(*) FROM bosses WHERE raid_id=$row[id]"));
		list($loots) = db_fetch_list(db_query("SELECT COUNT(*) FROM loots WHERE raid_id=$row[id]"));
		list($players) = db_fetch_list(db_query("SELECT COUNT(*) FROM attendance WHERE raid_id=$row[id]"));
?>
	<tr>
		<td><a href="raid.php?id=<?=$row[id]?>"><?=$row[day]?> - <?=format_zone($row[zone], $row[difficulty])?></a></td>
		<td><?=format_period($row[date_end] - $row[date_start], 1)?></td>
		<td style="text-align: center"><?=$bosses?></td>
		<td style="text-align: center"><?=$loots?></td>
		<td style="text-align: center"><?=$players?></td>
	</tr>
<?
	}
?>
</table>

<p><a href="raids.php">Show all...</a></p>


<h2>Recent Loots</h2>

<table border="1">
	<tr>
		<th>&nbsp;</th>
		<th>Item</th>
		<th>Looted to</th>
		<th>Raid</th>
	</tr>
<?
	$result = db_query("SELECT * FROM loots ORDER BY date_drop DESC LIMIT 10");
	while ($row = db_fetch_hash($result)){
?>
	<tr>
		<td><a href="http://www.wowhead.com/item=<?=$row[item_id]?>"><img src="http://static.wowhead.com/images/wow/icons/small/<?=$row[item_icon]?>.jpg" width="18" height="18" /></a></td>
		<td><a href="item.php?id=<?=$row[item_id]?>"><?=$row[item_name]?></a></td>
<? if ($row[ded] == 0){ ?>
		<td><a href="player.php?name=<?=$row[player_name]?>"><?=$row[player_name]?></a></td>
<? }else if ($row[ded] == 1){ ?>
		<td>DE'd</td>
<? }else if ($row[ded] == 2){ ?>
		<td>Banked</td>
<? }else{ ?>
		<td>ERROR</td>
<? } ?>
		<td><a href="raid.php?id=<?=$row[raid_id]?>"><?=$row[raid_day]?> - <?=format_zone($row[raid_zone], $row[raid_difficulty])?></a></td>
	</tr>
<?
	}
?>
</table>

<p><a href="loots.php">Show all...</a></p>


<?
	include('foot.txt');
?>