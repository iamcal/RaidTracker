<?
	include('init.php');

	$name_enc = AddSlashes($_GET[name]);
	$player = db_fetch_hash(db_query("SELECT * FROM players WHERE name='$name_enc'"));

	$page_title = "Player : $player[name]";

	include('head.txt');
?>

<p>
	<b>
		Level <?=$player[level]?> <?=$player[race]?> <?=$player['class']?>,
		&lt;<?=$player[guild]?>&gt;
	</b>
	(<a href="http://www.wowarmory.com/character-sheet.xml?r=Hyjal&n=<?=$player[name]?>">armory</a>, <a href="http://www.wowhead.com/profile=us.hyjal.<?=StrToLower($player[name])?>">wowhead</a>)
		
</p>


<h2>Loots</h2>

<table border="1">
	<tr>
		<th>&nbsp;</th>
		<th>Item</th>
		<th>Dropped by</th>
		<th>Raid</th>
	</tr>
<?
	$result = db_query("SELECT * FROM loots WHERE player_name='$name_enc' AND ded=0 ORDER BY date_drop ASC");
	while ($row = db_fetch_hash($result)){
?>
	<tr>
		<td><a href="http://www.wowhead.com/item=<?=$row[item_id]?>"><img src="http://static.wowhead.com/images/wow/icons/small/<?=$row[item_icon]?>.jpg" width="18" height="18" /></a></td>
		<td><a href="item.php?id=<?=$row[item_id]?>"><?=$row[item_name]?></a></td>
		<td><?=$row[source]?></td>
		<td><a href="raid.php?id=<?=$row[raid_id]?>"><?=$row[raid_day]?> - <?=format_zone($row[raid_zone], $row[raid_difficulty])?></a></td>
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



<h2>Raids</h2>

<table border="1">
	<tr>
		<th>Raid</th>
		<th>In Raid</th>
		<th>On List</th>
		<th>Offline</th>
	</tr>
<?
	$attendance = array();

	$result = db_query("SELECT * FROM attendance WHERE player_name='$name_enc' ORDER BY raid_day ASC");
	while ($row = db_fetch_hash($result)){
		$raid = db_fetch_hash(db_query("SELECT * FROM raids WHERE id=$row[raid_id]"));
		$duration = $raid[date_end] - $raid[date_start];
		$percent = min(100, round(100 * ($row[time_raid]+$row[time_wait]+600) / $duration));
		$attendance[$row[raid_id]] = $percent;
?>
	<tr>
		<td><a href="raid.php?id=<?=$row[raid_id]?>"><?=$row[raid_day]?> - <?=format_zone($row[raid_zone], $row[raid_difficulty])?></a></td>
		<td><?=format_period($row[time_raid], 1)?></td>
		<td><?=format_period($row[time_wait], 1)?></td>
		<td><?=format_period($row[time_offline], 1)?></td>
	</tr>
<?
	}
?>
</table>


<h2>Attendance</h2>

<?
	$weeks = get_calendar_weeks();

	$all_raids = array();
	$result = db_query("SELECT * FROM raids");
	while ($row = db_fetch_hash($result)){
		$row[attendance] = intval($attendance[$row[id]]);
		$all_raids[$row[day]][$row[id]] = $row;
	}

	#dumper($all_raids);
	#dumper($weeks);
?>
<table border="1" class="calendar" width="100%">
	<tr>
		<th width="12%">Tue</th>
		<th width="12%">Wed</th>
		<th width="12%">Thu</th>
		<th width="12%">Fri</th>
		<th width="12%">Sat</th>
		<th width="12%">Sun</th>
		<th width="12%">Mon</th>
		<th width="12%">Week</th>
	</tr>
<? foreach ($weeks as $week){ ?>
	<tr>
<?
	$raids = 0;
	$attended = 0;
?>
<? foreach ($week as $key => $row){ ?>
		<td>
			<small><?=$key?></small><br />
<?
	if (count($all_raids[$key])){

		foreach ($all_raids[$key] as $info){
?>
			<a href="raid.php?id=<?=$info[id]?>"><?=format_zone($info[zone], $info[difficulty])?></a> - <?=format_percent($info[attendance])?><br />
<?
			$raids++;
			if ($info[attendance]) $attended++;
		}
	}else{
		echo "-";
	}
?>
		</td>
<? } ?>
		<td><?=$attended?> / <?=$raids?></td>
	</tr>
<? } ?>
</table>

<?
	include('foot.txt');
?>