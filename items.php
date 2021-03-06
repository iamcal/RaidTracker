<?
	include('init.php');

	$page_title = 'All Items';
	$nav = 'items';

	include('head.txt');
?>

<table width="100%">
	<tr>
		<th>&nbsp;</th>
		<th>Item</th>
		<th>Drops</th>
		<th>Source(s)</th>
	</tr>
<?
	$items = array();

	$result = db_query("SELECT * FROM items ORDER BY name ASC");
	while ($row = db_fetch_hash($result)){

		$row[count] = 0;
		$row[sources] = array();

		$items[$row[id]] = $row;
	}

	$result = db_query("SELECT * FROM loots");
	while ($row = db_fetch_hash($result)){

		$items[$row[item_id]][count]++;
		$items[$row[item_id]][sources][$row[source]] = 1;
	}	


	foreach ($items as $row){
?>
	<tr>
		<td style="padding: 2px;"><a href="item.php?id=<?=$row[id]?>" rel="item=<?=$row[id]?>"><?=insert_icon($row[icon])?></a></td>
		<td><a href="item.php?id=<?=$row[id]?>" rel="item=<?=$row[id]?>" class="q q<?=$row[qual]?>"><?=$row[name]?></a></td>
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