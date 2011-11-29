<?
	include('init.php');

	$id = intval($_GET[id]);
	$item = load_item($id);

	$page_title = "Item : $item[name]";
	$title_icon = "http://static.wowhead.com/images/wow/icons/medium/{$item[icon]}.jpg";

	include('head.txt');
?>

<p>
	(<a href="http://www.wowarmory.com/item-info.xml?i=<?=$item[id]?>">armory</a>, <a href="http://www.wowhead.com/item=<?=$item[id]?>">wowhead</a>)
		
</p>


<h2>Drops</h2>

<table>
	<tr>
		<th>Looted to</th>
		<th>Dropped by</th>
		<th>Raid</th>
		<th>Time</th>
	</tr>
<?
	$result = db_query("SELECT * FROM loots WHERE item_id=$item[id] ORDER BY date_drop ASC");
	while ($row = db_fetch_hash($result)){

		$raid = load_raid($row[raid_id]);
		$player = load_player($row[player_name]);

?>
	<tr>
<? if ($row[ded] == 0){ ?>
		<td><a href="player.php?name=<?=$row[player_name]?>" class="class-<?=$player[class_id]?> class-link"><?=$row[player_name]?></a></td>
<? }else if ($row[ded] == 1){ ?>
		<td>DE'd</td>
<? }else if ($row[ded] == 2){ ?>
		<td>Banked</td>
<? }else{ ?>
		<td>ERROR</td>
<? } ?>
		<td><?=$row[source]?></td>
		<td><a href="raid.php?id=<?=$row[raid_id]?>"><?=$row[raid_day]?> - <?=format_zone($raid[zone], $raid[difficulty])?></a></td>
		<td><?=format_time($row[date_drop])?></td>
	</tr>
<?
	}
	if (!db_num_rows($result)){
?>
	<tr>
		<td colspan="5" style="text-align: center;"><i>No lootings</i></td>
	</tr>
<?
	}
?>
</table>

<?
	include('foot.txt');
?>