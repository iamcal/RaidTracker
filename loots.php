<?
	include('init.php');

	$page_title = 'All Loots';

	include('head.txt');
?>

<table>
	<tr>
		<th>&nbsp;</th>
		<th>Item</th>
		<th>Looted to</th>
		<th>Raid</th>
	</tr>
<?
	$result = db_query("SELECT * FROM loots ORDER BY date_drop DESC");
	while ($row = db_fetch_hash($result)){

		$raid = load_raid($row[raid_id]);
		$player = load_player($row[player_name]);
		$item = load_item($row[item_id]);
?>
	<tr>
		<td style="padding: 2px;"><a href="item.php?id=<?=$item[id]?>" rel="item=<?=$item[id]?>"><?=insert_icon($item[icon])?></a></td>
		<td><a href="item.php?id=<?=$item[id]?>" rel="item=<?=$item[id]?>" class="q q<?=$item[qual]?>"><?=$item[name]?></a></td>
<? if ($row[ded] == 0){ ?>
		<td><a href="player.php?name=<?=$row[player_name]?>" class="class-<?=$player[class_id]?> class-link"><?=$row[player_name]?></a></td>
<? }else if ($row[ded] == 1){ ?>
		<td>DE'd</td>
<? }else if ($row[ded] == 2){ ?>
		<td>Banked</td>
<? }else{ ?>
		<td>ERROR</td>
<? } ?>
		<td><a href="raid.php?id=<?=$row[raid_id]?>"><?=$row[raid_day]?> - <?=format_zone($raid[zone], $raid[difficulty])?></a></td>
	</tr>
<?
	}
?>
</table>


<?
	include('foot.txt');
?>