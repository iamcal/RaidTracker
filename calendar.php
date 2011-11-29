<?
	include('init.php');

	$page_title = 'Calendar';
	$nav = 'calendar';

	include('head.txt');
?>



<?
	$weeks = get_calendar_weeks();

	$all_raids = array();
	$result = db_query("SELECT * FROM raids");
	while ($row = db_fetch_hash($result)){
		$all_raids[$row[day]][$row[id]] = $row;
	}

	#dumper($all_raids);
	#dumper($weeks);
?>
<table class="calendar" width="100%">
	<tr>
		<th width="14%">Tue</th>
		<th width="14%">Wed</th>
		<th width="14%">Thu</th>
		<th width="14%">Fri</th>
		<th width="14%">Sat</th>
		<th width="14%">Sun</th>
		<th width="14%">Mon</th>
	</tr>
<? foreach ($weeks as $week){ ?>
	<tr>
<? foreach ($week as $key => $row){ ?>
		<td>
			<small><?=$key?></small><br />
<?
	if (count($all_raids[$key])){

		foreach ($all_raids[$key] as $info){
?>
			<a href="raid.php?id=<?=$info[id]?>"><?=format_zone($info[zone], $info[difficulty])?></a><br />
<?
		}
	}
?>
		</td>
<? } ?>
	</tr>
<? } ?>
</table>

<?
	include('foot.txt');
?>