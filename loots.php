<?
	include('init.php');

	$page_title = 'All Loots';

	include('head.txt');
?>

<table border="1">
	<tr>
		<th>&nbsp;</th>
		<th>Item</th>
		<th>Looted to</th>
		<th>Raid</th>
	</tr>
<?
	$result = db_query("SELECT * FROM loots ORDER BY date_drop DESC");
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


<?
	include('foot.txt');
?>