<?
	include('init.php');

	$page_title = 'All Items';
	$nav = 'items';

	include('head.txt');
?>

<table border="1">
	<tr>
		<th>&nbsp;</th>
		<th>Item</th>
		<th>Drops</th>
		<th>Source(s)</th>
	</tr>
<?
	$items = array();

	$result = db_query("SELECT * FROM loots ORDER BY item_name ASC");
	while ($row = db_fetch_hash($result)){

		if (isset($items[$row[item_id]])){

			$items[$row[item_id]][count]++;
			$items[$row[item_id]][sources][$row[source]] = 1;

		}else{
			$row[count] = 1;
			$row[sources] = array($row[source] => 1);

			$items[$row[item_id]] = $row;
		}
	}


	foreach ($items as $row){
?>
	<tr>
		<td><a href="http://www.wowhead.com/item=<?=$row[item_id]?>"><img src="http://static.wowhead.com/images/wow/icons/small/<?=$row[item_icon]?>.jpg" width="18" height="18" /></a></td>
		<td><a href="item.php?id=<?=$row[item_id]?>"><?=$row[item_name]?></a></td>
		<td style="text-align: center"><?=$row['count']?></td>
		<td>
<? foreach (array_keys($row[sources]) as $source){ ?>
			<?=$source?><br />
<? } ?>
		</td>
	</tr>
<?
	}
?>
</table>


<?
	include('foot.txt');
?>