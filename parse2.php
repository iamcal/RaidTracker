<?
	include('init.php');


	#
	# get day
	#

	$day = $_GET[d];
	if (!preg_match('!^\d\d\d\d-\d\d-\d\d$!', $day)){
		die('bad day: '.$day);
	}


	#
	# load & parse all reports
	#

	$data = array(
		'events'	=> array(),
		'items'		=> array(),
		'loots'		=> array(),
	);

	$result = db_query("SELECT * FROM reports WHERE raid_day='$day'");
	while ($row = db_fetch_hash($result)){

		parse_raid_data($data, $row[data]);
	}


	#
	# parse stuff
	#

	$raids = parse_raids($day, $data);

	dumper($raids);

	parse_items($data); 

	parse_loots($day, $raids, $data);


	########################################################################################

	function parse_items($data){

		foreach ($data[items] as $row){

			$hash = array(
				'id'	=> intval($row[id]),
				'name'	=> AddSlashes($row[name]),
				'qual'	=> intval($row[qual]),
				'level'	=> intval($row[level]),
				'icon'	=> AddSlashes($row[icon]),
			);

			db_insert_on_dupe('items', $hash, $hash);
		}

	}

	########################################################################################

	function parse_loots($day, $raids, $data){

		#
		# loots
		#

		$loots = array();


		#
		# de-dupe loot rows
		#

		foreach ($data[loots] as $row){
			$key = "$row[item]_$row[who]";
			if (!isset($loots[$key])){
				$loots[$key] = $row;
			}
		}


		#
		# fetch DB rows
		#

		$db_loots = array();

		$result = db_query("SELECT * FROM loots WHERE raid_day='$day'");
		while ($row = db_fetch_hash($result)){

			$db_loots[$row[id]] = $row;
		}


		#
		# for each loot, try and match it against a db row
		#

		foreach ($loots as $k => $loot){

			$matched = 0;

			foreach ($db_loots as $row){
				if ($row[player_name] == $loot[who] && $row[item_id] == $loot[item]){

					db_update('loots', array(
						'raid_id'	=> get_raid_id($raids, $loot[when]),
						'date_drop'	=> intval($loot[when]),
						'source'	=> AddSlashes($loot[src]),
					), "id=$row[id]");

					$loots[$k][id] = $row[id];
					$matched = 1;
					unset($db_loots[$row[id]]);
					break;
				}
			}

			if (!$matched){

				$loots[$k][id] = db_insert('loots', array(
					'player_name'	=> AddSlashes($loot[who]),
					'raid_id'	=> get_raid_id($raids, $loot[when]),
					'raid_day'	=> $day,
					'item_id'	=> intval($loot[item]),
					'date_drop'	=> intval($loot[when]),
					'source'	=> AddSlashes($loot[src]),
				));

				$loots[$k][INSERTED] = 1;
			}
		}


		#
		# deal with un-matched DB loots
		#

		foreach ($db_loots as $row){

			db_query("DELETE FROM loots WHERE id=$row[id]");
		}

	}

	########################################################################################

	function parse_raid_data(&$data, $raw_xml){

		$xml = new SimpleXMLElement($raw_xml);

		$events = array();

		#
		# start event
		#

		$events[] = array(
			'type'	=> 'start',
			'when'	=> strtotime((string) $xml->start),
			'whenx'	=> (string) $xml->start,
			'zone'	=> (string) $xml->zone,
			'diff'	=> (string) $xml->difficulty,
		);


		#
		# boss kills
		#

		foreach ($xml->bossKills->boss as $boss){

			$events[] = array(
				'type'	=> 'boss',
				'when'	=> strtotime((string) $boss->time),
				'whenx'	=> (string) $boss->time,
				'zone'	=> (string) $boss->zone,
				'diff'	=> (string) $boss->difficulty,
			);
		}


		#
		# loots
		#

		foreach ($xml->loot->item as $item){

			$time = strtotime((string) $item->time);
			$zone = (string) $item->zone;
			$diff = find_most_recent_zone_diff($events, $time, $zone);

			if ($diff){
				$events[] = array(
					'type'	=> 'loot',
					'when'	=> $time,
					'whenx'	=> (string) $item->time,
					'zone'	=> $zone,
					'diff'	=> $diff,
				);
			}
		}


		#
		# raid end
		#

		usort($events, 'sort_events');

		$last = $events[count($events)-1];

		$events[] = array(
			'type'	=> 'end',
			'when'	=> strtotime((string) $xml->end),
			'whenx'	=> (string) $xml->end,
			'zone'	=> $last[zone],
			'diff'	=> $last[diff],
		);


		#
		# merge with global events list
		#

		foreach ($events as $row){
			$data[events][] = $row;
		}

		usort($data[events], 'sort_events');


		#
		# loot events
		#

		foreach ($xml->loot->item as $item){

			$id = (string) $item->id;

			$data[items][$id] = array(
				'id'	=> $id,
				'name'	=> (string) $item->name,
				'qual'	=> (string) $item->rarity,
				'level'	=> (string) $item->level,
				'icon'	=> StrToLower(array_pop(explode('\\', (string) $item->texture))),
			);

			$data[loots][] = array(
				'item'	=> $id,
				'who'	=> (string) $item->looter,
				'src'	=> (string) $item->source,
				'when'	=> strtotime((string) $item->time),
			);
		}
	}

	########################################################################################

	function find_most_recent_zone_diff($events, $time, $zone){

		$match = array(
			'when'	=> 0,
			'zone'	=> null,
			'diff'	=> null,
		);

		foreach ($events as $row){
			if ($row[when] < $time){
				if ($match[when] < $row[when]){
					$match = $row;
				}
			}
		}

		return ($match[zone] == $zone) ? $match[diff] : null;

	}

	########################################################################################

	function sort_events($a, $b){
		return $a[when] == $b[when] ? 0 : $a[when] > $b[when] ? 1 : -1;
	}

	########################################################################################

	function extract_raids($events){

		$raids = array();
		$prev = array(
			'zone'	=> null,
			'diff'	=> null,
			'start'	=> null,
			'end'	=> null,
		);

		foreach ($events as $row){

			$t = $row[when];
			$raid = $row;

			if ($prev[zone] == $row[zone] && $prev[diff] == $row[diff]){

				# extend this raid
				$prev[end]	= $row[when];
				$prev[hard_end]	= $row[when];

			}else{
				if ($prev[zone]){

					#
					# the border should be 10 mins before the first event
					# in the new raid, unless there's not enough time, then
					# it's the last event in the previous raid
					#

					$start_of_new = $row[when] - (10 * 60);
					if ($start_of_new < $prev[end]) $start_of_new = $prev[end];
					$prev[end] = $start_of_new;

					$raids[] = $prev;
				}

				# start a new raid
				$prev[zone]		= $row[zone];
				$prev[diff]		= $row[diff];
				$prev[start]		= $prev[end] ? $prev[end] : $row[when];
				$prev[end]		= $row[when];
				$prev[hard_start]	= $row[when];
				$prev[hard_end]		= $row[when];
			}
		}

		if ($prev[zone]){
			$raids[] = $prev;
		}


		foreach ($raids as $k => $row){
			$raids[$k][duration] = format_period($row[end]-$row[start], 1);
			$raids[$k][hard_duration] = format_period($row[hard_end]-$row[hard_start], 1);
		}

		return $raids;
	}

	########################################################################################

	function parse_raids($day, $data){

		#
		# find raids
		#

		$raids = extract_raids($data[events]);


		#
		# grab raid rows from db
		#

		$db_raids = array();

		$result = db_query("SELECT * FROM raids WHERE day='$day'");
		while ($row = db_fetch_hash($result)){

			$db_raids[$row[id]] = $row;
		}


		#
		# for each raid we find, try and match it against a db row
		#

		foreach ($raids as $k => $raid){

			$matched = 0;

			foreach ($db_raids as $row){
				if ($row[zone] == $raid[zone] && $row[difficulty] == $raid[diff]){

					db_update('raids', array(
						'date_start'	=> intval($raid[start]),
						'date_end'	=> intval($raid[end]),
					), "id=$row[id]");

					$raids[$k][id] = $row[id];

					$matched = 1;
					unset($db_raids[$row[id]]);
					break;
				}
			}

			if (!$matched){

				$raids[$k][id] = db_insert('raids', array(
					'day'		=> $day,
					'zone'		=> AddSlashes($raid[zone]),
					'difficulty'	=> AddSlashes($raid[diff]),
					'date_start'	=> intval($raid[start]),
					'date_end'	=> intval($raid[end]),
				));
			}
		}


		#
		# deal with un-matched DB raids
		#

		foreach ($db_raids as $row){

			db_query("DELETE FROM raids WHERE id=$row[id]");
		}


		return $raids;
	}

	########################################################################################
	########################################################################################




	function parse_report2($id, $clear_day=0){

		$row = db_fetch_hash(db_query("SELECT * FROM reports WHERE id=$id"));

		$xml = new SimpleXMLElement($row[data]);


		#
		# check it's headcount!
		#

		$from = (string) $xml['generatedFrom'];

		if ($from != 'HeadCount'){
			return array(
				'status' => 'not_hc',
			);

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

		$start	= strtotime((string) $xml->start);

		list($y,$m,$d,$h) = explode('-', date('Y-m-d-H', $start));

		# which day is this raid from?
		# raids that start before 6am count for the day before!
		$day = date('Y-m-d', $start);
		if ($h < 6){
			$ts = mktime(0,0,0,$m,$d-1,$y);
			$day = date('Y-m-d', $ts);
		}


		if ($clear_day){
			db_query("DELETE FROM loots WHERE raid_day='$day'");
			db_query("DELETE FROM bosses WHERE raid_day='$day'");
			db_query("DELETE FROM attendance WHERE raid_day='$day'");
		}


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

					# move the border forward by up to 10 mins
					$dif = $t - $prev_end;
					$dif = min($dif, 10 * 60);
					$prev_end += $dif;

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

		return array(
			'status'	=> 'ok',
			'day'		=> $day,
		);
	}


	echo 'ok';
?>