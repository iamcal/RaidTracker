<?
	########################################################################################

	function parse_day($day){


		#
		# load & parse all reports
		#

		$data = array(
			'bad_xml'	=> 0,
			'events'	=> array(),
			'items'		=> array(),
			'loots'		=> array(),
			'bosses'	=> array(),
			'players'	=> array(),
		);

		$result = db_query("SELECT * FROM reports WHERE raid_day='$day'");
		while ($row = db_fetch_hash($result)){

			parse_raid_data($data, $row[data]);
		}


		#
		# parse stuff
		#

		$raids = parse_raids($day, $data);

		#dumper($raids);

		parse_items($data); 

		parse_loots($day, $raids, $data);

		parse_bosses($day, $raids, $data);

		parse_players($day, $raids, $data);
	}

	########################################################################################

	function parse_bosses($day, $raids, $data){

		$bosses = array();


		#
		# de-dupe boss rows
		#

		foreach ($data[bosses] as $row){

			$row[raid_id] = get_raid_id($raids, $row[when]);

			$key = "$row[raid_id]_$row[name]";

			if (!isset($bosses[$key])){
				$bosses[$key] = $row;
			}
		}


		#
		# fetch DB rows
		#

		$db_bosses = array();

		$result = db_query("SELECT * FROM bosses WHERE raid_day='$day'");
		while ($row = db_fetch_hash($result)){

			$db_bosses[$row[id]] = $row;
		}


		#
		# for each boss, try and match it against a db row
		#

		foreach ($bosses as $k => $boss){

			$matched = 0;

			foreach ($db_bosses as $row){

				if ($row[raid_id] == $boss[raid_id] && $row[name] == $boss[name]){

					db_update('bosses', array(
						'date_kill'	=> intval($boss[when]),
					), "id=$row[id]");

					$bosses[$k][id] = $row[id];
					$matched = 1;
					unset($db_bosses[$row[id]]);
					break;
				}
			}

			if (!$matched){

				$bosses[$k][id] = db_insert('bosses', array(
					'raid_id'	=> intval($boss[raid_id]),
					'raid_day'	=> $day,
					'name'		=> AddSlashes($boss[name]),
					'date_kill'	=> intval($boss[when]),
				));

				$bosses[$k][INSERTED] = 1;
			}
		}


		#
		# deal with un-matched DB bosses
		#

		foreach ($db_bosses as $row){

			db_query("DELETE FROM bosses WHERE id=$row[id]");
		}

	}

	########################################################################################

	function parse_players($day, $raids, $data){

		#
		# de-dupe / merge rows
		#

		$players = array();
		$copy_fields = array('class', 'guild', 'race', 'sex', 'level');

		foreach ($data[players] as $row){

			if (isset($players[$row[name]])){

				foreach ($copy_fields as $f){
					if (strlen($row[$f])){ $players[$row[name]][$f] = $row[$f]; }
				}

				foreach ($row[events] as $event){
					$players[$row[name]][events][] = $event;
				}
			}else{

				$players[$row[name]] = $row;
			}
		}


		#
		# create / update player rows
		#

		foreach ($players as $row){

			$hash = array(
				'name'	=> AddSlashes($row[name]),
				'class'	=> AddSlashes($row['class']),
				'guild'	=> AddSlashes($row[guild]),
				'race'	=> AddSlashes($row[race]),
				'sex'	=> AddSlashes($row[sex]),
				'level'	=> intval($row[level]),
			);

			$hash2 = $hash;
			foreach ($hash2 as $k => $v){ if (!strlen($v)){ unset($hash2[$k]); } }

			db_insert_on_dupe('players', $hash, $hash2);
		}


		#
		# de-dupe / merge player events
		#

		foreach ($players as $pk => $player){

			#
			# find all events
			#

			$events = array(
				'raid' => array(),
				'list' => array(),
				'offl' => array(),
			);

			foreach ($player[events] as $event){

				$key = 'raid';
				if ($event[note] == 'Wait list') $key = 'wait';
				if ($event[note] == 'Offline') $key = 'offl';

				$events[$key][] = array($event[start], $event[end]);
			}

			#$events['raid'][] = array(1,10);
			#$events['raid'][] = array(9,12);
			#$events['raid'][] = array(15,17);
			#$events['list'][] = array(11,16);
			#$events['offl'][] = array(0,20);


			#
			# merge all events of each type
			#

			$events['raid'] = merge_events($events['raid']);
			$events['list'] = merge_events($events['list']);
			$events['offl'] = merge_events($events['offl']);


			#
			# subtract more important events
			#

			$events['list'] = remove_events($events['list'], $events['raid']);
			$events['offl'] = remove_events($events['offl'], $events['raid']);
			$events['offl'] = remove_events($events['offl'], $events['list']);


			#
			# we should now have a single list
			#

			$new = array();
			foreach ($events as $k => $rows){
				foreach ($rows as $row){
					$new[] = array($k, $row[0], $row[1]);
				}
			}

			$players[$pk][events] = $new;
		}


		#
		# create attendance records
		#

		foreach ($players as $pk => $player){

			$players[$pk][attendance] = array();

			foreach ($raids as $raid){

				#
				# get times for this player in this raid...
				#

				$times = array(
					'raid' => 0,
					'wait' => 0,
					'offl' => 0,
				);
				$matched = 0;

				foreach ($player[events] as $event){

					$key		= $event[0];
					$e_start	= $event[1];
					$e_end		= $event[2];

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

					$players[$pk][attendance][$raid[id]] = $times;
				}
			}
		}


		#
		# fetch DB rows
		#

		$db_attendance = array();

		$result = db_query("SELECT * FROM attendance WHERE raid_day='$day'");
		while ($row = db_fetch_hash($result)){

			$db_attendance[$row[id]] = $row;
		}


		#
		# for each attendance, try and match it against a db row
		#

		foreach ($players as $pk => $player){
		foreach ($player[attendance] as $raid_id => $attendance){

			$matched = 0;

			foreach ($db_attendance as $row){
				if ($row[player_name] == $player[name] && $row[raid_id] == $raid_id){

					# found - update

					db_update('attendance', array(
						'time_raid'	=> intval($attendance['raid']),
						'time_wait'	=> intval($attendance['wait']),
						'time_offline'	=> intval($attendance['offl']),
					), "id=$row[id]");

					$players[$pk][attendance][$raid_id][id] = $row[id];
					$matched = 1;
					unset($db_attendance[$row[id]]);
					break;
				}
			}

			if (!$matched){

				$players[$pk][attendance][$raid_id][id] = db_insert('attendance', array(

					'player_name'	=> AddSlashes($player[name]),
					'raid_id'	=> intval($raid_id),
					'raid_day'	=> $day,

					'time_raid'	=> intval($attendance['raid']),
					'time_wait'	=> intval($attendance['wait']),
					'time_offline'	=> intval($attendance['offl']),
				));

				$players[$pk][attendance][$raid_id][INSERTED] = 1;
			}
		}
		}


		#
		# deal with un-matched DB attendance
		#

		foreach ($db_attendance as $row){

			#echo "nuking attendance row $row[id]<br />";
			db_query("DELETE FROM attendance WHERE id=$row[id]");
		}

		#dumper($players);
	}

	########################################################################################

	#
	# modify $events to make sure that no events in $remove are
	# intersecting with them.
	#

	function remove_events($events, $remove){

		foreach ($remove as $row){

			$new = array();
			foreach ($events as $event){

				if ($event[0] > $row[1] || $event[1] < $row[0]){

					# no overlap at all
					$new[] = $event;
				}else{

					# does any of $event occur before $row?
					if ($event[0] < $row[0]){
						$new[] = array($event[0], $row[0]);
					}

					# does any of $event occur after $row?
					if ($event[1] > $row[1]){
						$new[] = array($row[1], $event[1]);
					}

				}
			}

			$events = $new;
		}

		return $events;
	}

	########################################################################################

	#
	# take the list of events ([start, end] pairs) and merge them so
	# that none overlap.
	#

	function merge_events($events){

		$out = array();

		foreach ($events as $event){

			#
			# check for intersection
			#

			$matched = 0;

			foreach ($out as $k => $row){

				if ($event[0] > $row[1] || $event[1] < $row[0]){

					# no overlap at all
				}else{

					$start	= min($event[0], $row[0]);
					$end	= max($event[1], $row[1]);

					$out[$k] = array($start, $end);
					$matched = 1;
					break;
				}
			}

			if (!$matched){
				$out[] = $event;
			}
		}

		return $out;
	}

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

			$hash2 = $hash;

			if ($hash2[name] == 'Item unavailable'){
				unset($hash2[name]);
				unset($hash2[qual]);
				unset($hash2[level]);
				unset($hash2[icon]);
			}

			db_insert_on_dupe('items', $hash, $hash2);
		}

	}

	########################################################################################

	function parse_loots($day, $raids, $data){


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


		#
		# check it's headcount!
		#

		$from = (string) $xml['generatedFrom'];

		if ($from != 'HeadCount2'){
			$data[bad_xml]++;
			return;
		}


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


		#
		# players
		#

		foreach ($xml->players->player as $player){

			$row = array(
				'name'	=> utf8_cleanup((string) $player->name),
				'class'	=> (string) $player->class,
				'guild'	=> (string) $player->guild,
				'race'	=> (string) $player->race,
				'sex'	=> (string) $player->sex,
				'level'	=> (string) $player->level,
			);

			$row[events] = array();

			foreach ($player->attendance->event as $event){
				$row[events][] = array(
					'note'	=> (string) $event->note,
					'start'	=> strtotime((string) $event->start),
					'end'	=> strtotime((string) $event->end),
				);
			}

			$data[players][] = $row;
		}


		#
		# bosses
		#

		foreach ($xml->bossKills->boss as $boss){

			$row = array(
				'name'	=> (string) $boss->name,
				'zone'	=> (string) $boss->zone,
				'diff'	=> (string) $boss->difficulty,
				'when'	=> strtotime((string) $boss->time),
			);

			$data[bosses][] = $row;
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
			$raid[center] = $raid[start]+(($raid[end]-$raid[start])/2);

			foreach ($db_raids as $row){

				if ($row[zone] == $raid[zone] && $row[difficulty] == $raid[diff] && $raid[center] > $row[date_start] && $raid[center] < $row[date_end]){

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

	function utf8_cleanup($name){

		$conv = utf8_decode($name);

		#if (preg_match('!cade!',$name)){
		#	echo "$name / $conv<br />";
		#}

		if (is_valid_utf8($conv)){
			#echo "OUT: $conv<br />";
			return $conv;
		}else{
			#echo "OUT: $name<br />";
			return $name;
		}
	}

	function is_valid_utf8($bytes){

		$test = iconv('UTF-8', 'UTF-8//IGNORE', $bytes);

		return ($test == $bytes) ? 1 : 0;
	}

	########################################################################################
?>