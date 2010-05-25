<?
#14 is a good test!

	include('init.php');


	$id = intval($_GET[id]);
	$row = db_fetch_hash(db_query("SELECT * FROM reports WHERE id=$id"));

	$xml = new SimpleXMLElement($row[data]);


	#
	# check it's headcount!
	#

	$from = (string) $xml['generatedFrom'];

	if ($from != 'HeadCount'){

		include('head.txt');
?>
	<h1>Error - Bad XML</h1>
	<p>It looks like that XML wasn't in the usual HeadCount format. <a href="import.php">Try again</a>.</p>
<?
		include('foot.txt');
		exit;
	}


	#
	# used later
	#

	$GLOBALS[uid] = 1;
	function get_uid(){
		return $GLOBALS[uid]++;
	}


	#
	# import/update players
	#

	foreach ($xml->players->player as $player){

		$hash = array(
			'name'	=> AddSlashes($player->name),
			'class'	=> AddSlashes($player->class),
			'guild'	=> AddSlashes($player->guild),
			'race'	=> AddSlashes($player->race),
			'sex'	=> AddSlashes($player->sex),
			'level'	=> intval($player->level),
		);

		db_insert_on_dupe('players', $hash, $hash);
	}


	#
	# get raid day
	#

	putenv("TZ=PST8PDT");

	$start	= strtotime((string) $xml->start);

	list($y,$m,$d,$h) = explode('-', date('Y-m-d-H', $start));

	# which day is this raid from?
	# raids that start before 6am count for the day before!
	$day = date('Y-m-d', $start);
	if ($h < 6){
		$ts = mktime(0,0,0,$m,$d-1,$y);
		$day = date('Y-m-d', $ts);
	}


	db_query("DELETE FROM loots WHERE raid_day='$day'");
	db_query("DELETE FROM bosses WHERE raid_day='$day'");
	db_query("DELETE FROM attendance WHERE raid_day='$day'");


	#
	# put boss kills in order
	#

	$times = array();

	$raid_start	= strtotime((string) $xml->start);
	$raid_end	= strtotime((string) $xml->end);

	$times[$raid_start.'_'.get_uid()] = array((string) $xml->zone, (string) $xml->difficulty, "START");

	foreach ($xml->bossKills->boss as $boss){

		$time = strtotime((string) $boss->time);

		$times[$time.'_'.get_uid()] = array((string) $boss->zone, (string) $boss->difficulty, 'boss kill');
	}

	ksort($times);


	#
	# create raids
	#

	$raids = array();
	$prev_zone = null;
	$prev_diff = null;
	$prev_start = null;
	$prev_end = null;

	foreach ($times as $k => $raid){

		list($t, $junk) = explode('_', $k);
		$t = intval($t);

		if ($prev_zone == $raid[0] && $prev_diff == $raid[1]){

			# extend this raid
			$prev_end = $t;

		}else{
			if ($prev_zone){
				$raids[] = array(
					'day'	=> $day,
					'zone'	=> $prev_zone,
					'diff'	=> $prev_diff,
					'start'	=> $prev_start,
					'end'	=> $prev_end,
				);
			}

			# start a new raid
			$prev_zone	= $raid[0];
			$prev_diff	= $raid[1];
			$prev_start	= $prev_end ? $prev_end : $t;
			$prev_end	= $t;
		}
	}

	if ($prev_zone){
		$raids[] = array(
			'day'	=> $day,
			'zone'	=> $prev_zone,
			'diff'	=> $prev_diff,
			'start'	=> $prev_start,
			'end'	=> $raid_end,
		);
	}


	#
	# create the raids
	#

	foreach ($raids as $k => $raid){

		$hash = array(
			'day'		=> AddSlashes($raid[day]),
			'zone'		=> AddSlashes($raid[zone]),
			'difficulty'	=> AddSlashes($raid[diff]),
			'date_start'	=> intval($raid[start]),
			'date_end'	=> intval($raid[end]),
		);

		$id = db_insert_on_dupe('raids', $hash, $hash);

		$raids[$k][id] = $id;
	}


	#
	# import loot events
	#

	function get_raid_id($raids, $time){
		foreach ($raids as $raid){
			if ($raid[start] <= $time && $raid[end] >= $time) return $raid[id];
		}
		return 0;
	}

	foreach ($xml->loot->item as $item){

		$time = strtotime((string) $item->time);

		$hash = array(
			'player_name'		=> AddSlashes($item->looter),
			'raid_id'		=> get_raid_id($raids, $time),
			'raid_day'		=> AddSlashes($day),
			'raid_zone'		=> AddSlashes($item->zone), # not really used
			'item_id'		=> intval($item->id),
			'date_drop'		=> intval($time),
			'source'		=> AddSlashes($item->source),
			'item_name'		=> AddSlashes($item->name),
			'item_icon'		=> AddSlashes(StrToLower(array_pop(explode('\\',$item->texture)))),
		);

		db_insert_on_dupe('loots', $hash, $hash);
	}


	#
	# import boss kills
	#

	foreach ($xml->bossKills->boss as $boss){

		$time = strtotime((string) $boss->time);

		$hash = array(
			'raid_id'		=> get_raid_id($raids, $time),
			'raid_day'		=> AddSlashes($day),
			'name'			=> AddSlashes($boss->name),
			'zone'			=> AddSlashes($boss->zone),
			'difficulty'		=> AddSlashes($boss->difficulty),
			'date_kill'		=> intval($time),
		);

		db_insert_on_dupe('bosses', $hash, $hash);
	}


	#
	# import player attendance
	#

	foreach ($xml->players->player as $player){

		foreach ($raids as $raid){

			# get times for this player in this raid...

			$times = array(
				'raid' => 0,
				'wait' => 0,
				'offl' => 0,
			);
			$matched = 0;

			foreach ($player->attendance->event as $event){

				$e_start = strtotime((string) $event->start);
				$e_end   = strtotime((string) $event->end);
				$e_state = (string) $event->note;

				$key = 'raid';
				if ($e_state == 'Wait list') $key = 'wait';
				if ($e_state == 'Offline') $key = 'offl';


				if ($e_start > $raid[end] || $e_end < $raid[start]){

					# no overlap at all
				}else{

					$a_start = max($e_start, $raid[start]);
					$a_end = min($e_end, $raid[end]);

					$dif = ($a_end - $a_start);

					if ($dif){
						$times[$key] += $dif;
						$matched = 1;
					}
				}

			}

			if ($matched){

				$hash = array(
					'player_name'	=> AddSlashes($player->name),
					'raid_id'	=> $raid[id],
					'raid_day'	=> AddSlashes($day),
					'time_raid'	=> $times[raid],
					'time_wait'	=> $times[wait],
					'time_offline'	=> $times[offl],
				);

				db_insert_on_dupe('attendance', $hash, $hash);
			}
		}

	}



dumper($raids);
exit;




	#
	# done!
	#

	header("location: date.php?d=$day");
	exit;


	echo date('Y-m-d H:i:s', $start)."<br />";
	echo date('Y-m-d H:i:s', $end)."<br />";


echo "<hr />";
dumper($xml);

?>