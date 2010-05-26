<?
	include('init.php');

	$name_enc = AddSlashes($_GET[name]);
	$player = db_fetch_hash(db_query("SELECT * FROM players WHERE name='$name_enc'"));

	$player[class_id] = StrToLower(str_replace(' ', '', $player['class']));
	$player[race] = str_replace('NightElf', 'Night Elf', $player[race]);

	$page_title = "Player : $player[name]";
	$title_icon = "http://static.wowhead.com/images/wow/icons/medium/class_{$player[class_id]}.jpg";

	include('head.txt');
?>

<p>
	<b>
		Level <?=$player[level]?> <?=$player[race]?> <?=$player['class']?>,
		&lt;<?=strlen($player[guild])?$player[guild]:'...'?>&gt;
	</b>
	(<a href="http://www.wowarmory.com/character-sheet.xml?r=Hyjal&n=<?=$player[name]?>">armory</a>, <a href="http://www.wowhead.com/profile=us.hyjal.<?=StrToLower($player[name])?>">wowhead</a>)
		
</p>

<h2>ICC 25 Attendance</h2>

<?
	#
	# load raids we went to
	#

	$attendance = array();
	$raids = array();

	$result = db_query("SELECT * FROM attendance WHERE player_name='$name_enc' ORDER BY raid_day ASC");
	while ($row = db_fetch_hash($result)){
		$row[raid] = load_raid($row[raid_id]);
		$row[duration] = $row[raid][date_end] - $row[raid][date_start];
		$percent = min(100, round(100 * ($row[time_raid]+$row[time_wait]+60) / $row[duration]));

		$attendance[$row[raid_id]] = $percent;
		$raids[] = $row;
	}


	#
	# load loots we got
	#

	$loots = array();
	$loots_by_raid = array();

	$result = db_query("SELECT * FROM loots WHERE player_name='$name_enc' AND ded=0 ORDER BY date_drop ASC");
	while ($row = db_fetch_hash($result)){

		$row[raid] = load_raid($row[raid_id]);
		$row[item] = load_item($row[item_id]);

		$loots[] = $row;
		$loots_by_raid[$row[raid_id]]++;
	}


	#
	# build calendar
	#

	$weeks = get_calendar_weeks();

	$all_raids = array();
	$result = db_query("SELECT * FROM raids");
	while ($row = db_fetch_hash($result)){
		$row[duration] = $row[date_end] - $row[date_start];
		$row[attendance] = intval($attendance[$row[id]]);
		$all_raids[$row[day]][$row[id]] = $row;
	}


	#
	# get stats by day/week
	#

	$rev_weeks = array_reverse($weeks);
	array_shift($rev_weeks); # ignore this week

	$days = array();

	foreach ($rev_weeks as $k => $week){
		$week_idx = $k + 1;
		$day_idx = 1;
		foreach (array_keys($week) as $day){

			$days[$day_idx][$week_idx] = array(0, 0, 0);

			if (is_array($all_raids[$day])){
				foreach ($all_raids[$day] as $raid){

					if ($raid[zone] == 'Icecrown Citadel' && $raid[difficulty] == '25 Player'){

						$days[$day_idx][$week_idx] = array($raid[duration], $raid[attendance], intval($loots_by_raid[$raid[id]]));
					}
				}
			}

			$day_idx++;
		}
	}

	#dumper($days);
	#dumper($all_raids);
	#dumper($weeks);


	function display_attendance($day, $weeks){

		$raids		= 0;
		$attended_60	= 0;
		$attended_0	= 0;
		$missed		= 0;
		$loots		= 0;

		$c = 0;
		foreach ($day as $row){

			if ($row[0]){
				$raids++;
				if ($row[1] >= 60){ $attended_60++; }
				else if ($row[1] > 0){ $attended_0++; }
				else { $missed++; }
				$loots += $row[2];
			}

			$c++;
			if ($c == $weeks) break;
		}

		#echo "Raids: $raids<br />";
		echo "Attended:";
			echo " <span class=\"atnd90\">$attended_60</span> / ";
			echo " <span class=\"atnd20\">$attended_0</span> / ";
			echo " <span class=\"atnd0\">$missed</span>";
			echo "<br />";
		echo "Loots: $loots<br />";
	}

?>

<table>
	<tr>
		<th>Day</th>
		<th>Last Week</th>
		<th>Last 4 Weeks</th>
		<th>Last 10 Weeks</th>
		<th>All Time</th>
	</tr>
	<tr>
		<td>Tuesday</td>
		<td><?=display_attendance($days[1], 1)?></td>
		<td><?=display_attendance($days[1], 4)?></td>
		<td><?=display_attendance($days[1], 10)?></td>
		<td><?=display_attendance($days[1], 52)?></td>
	</tr>
	<tr>
		<td>Wednesday</td>
		<td><?=display_attendance($days[2], 1)?></td>
		<td><?=display_attendance($days[2], 4)?></td>
		<td><?=display_attendance($days[2], 10)?></td>
		<td><?=display_attendance($days[2], 52)?></td>
	</tr>
	<tr>
		<td>Sunday</td>
		<td><?=display_attendance($days[6], 1)?></td>
		<td><?=display_attendance($days[6], 4)?></td>
		<td><?=display_attendance($days[6], 10)?></td>
		<td><?=display_attendance($days[6], 52)?></td>
	</tr>
</table>


<h2>Loots</h2>

<table>
	<tr>
		<th>&nbsp;</th>
		<th>Item</th>
		<th>Dropped by</th>
		<th>Raid</th>
	</tr>
<?
	foreach ($loots as $row){
?>
	<tr>
		<td style="padding: 2px;"><a href="item.php?id=<?=$row[item][id]?>" rel="item=<?=$row[item][id]?>"><?=insert_icon($row[item][icon])?></a></td>
		<td><a href="item.php?id=<?=$row[item][id]?>" rel="item=<?=$row[item][id]?>" class="q q<?=$row[item][qual]?>"><?=$row[item][name]?></a></td>
		<td><?=$row[source]?></td>
		<td><a href="raid.php?id=<?=$row[raid_id]?>"><?=$row[raid][day]?> - <?=format_zone($row[raid][zone], $row[raid][difficulty])?></a></td>
	</tr>
<?
	}
	if (!count($loots)){
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
	foreach ($raids as $row){
?>
	<tr>
		<td><a href="raid.php?id=<?=$row[raid_id]?>"><?=$row[raid_day]?> - <?=format_zone($row[raid][zone], $row[raid][difficulty])?></a></td>
		<td><?=format_period($row[time_raid], 1)?></td>
		<td><?=format_period($row[time_wait], 1)?></td>
		<td><?=format_period($row[time_offline], 1)?></td>
	</tr>
<?
	}
?>
</table>


<h2>Calendar</h2>

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