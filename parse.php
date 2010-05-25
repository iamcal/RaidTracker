<?
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
	# import players
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
	# import raid instance
	#

	putenv("TZ=PST8PDT");

	$start	= strtotime((string) $xml->start);
	$end	= strtotime((string) $xml->end);

	list($y,$m,$d,$h) = explode('-', date('Y-m-d-H', $start));

	# which day is this raid from?
	# raids that start before 6am count for the day before!
	$day = date('Y-m-d', $start);
	if ($h < 6){
		$ts = mktime(0,0,0,$m,$d-1,$y);
		$day = date('Y-m-d', $ts);
	}


	#
	# import raid instance
	#

	$day_enc = AddSlashes($day);
	$zone_enc = AddSlashes($xml->zone);
	$difficulty_enc = AddSlashes($xml->difficulty);

	$where = "day='$day_enc' AND zone='$zone_enc' AND difficulty='$difficulty_enc'";

	if ($row = db_fetch_hash(db_query("SELECT * FROM raids WHERE $where"))){

		$raid_id = $row[id];

		# old raid
		db_query("UPDATE raids SET date_start=$start WHERE date_start>$start AND id=$raid_id");
		db_query("UPDATE raids SET date_end=$end WHERE date_end>$end AND id=$raid_id");

	}else{
		# new raid
		$raid_id = db_insert('raids', array(
			'day'		=> $day_enc,
			'zone'		=> $zone_enc,
			'difficulty'	=> $difficulty_enc,
			'date_start'	=> $start,
			'date_end'	=> $end,
		));
	}

	#echo "raid ID is $raid_id";
	#exit;


	#
	# import player attendance
	#

	foreach ($xml->players->player as $player){

		$name_enc = AddSlashes($player->name);
		$time_raid_enc = intval($player->raidDuration);
		$time_wait_enc = intval($player->waitDuration);
		$time_offline_enc = intval($player->offlineDuration);

		$where = "player_name='$name_enc' AND raid_id='$raid_id'";

		if ($row = db_fetch_hash(db_query("SELECT * FROM attendance WHERE $where"))){

			# existing attendance record
			db_query("UPDATE attendance SET time_raid=$time_raid_enc WHERE time_raid<$time_raid_enc AND $where");
			db_query("UPDATE attendance SET time_wait=$time_wait_enc WHERE time_wait<$time_wait_enc AND $where");
			db_query("UPDATE attendance SET time_offline=$time_offline_enc WHERE time_offline<$time_offline_enc AND $where");

		}else{

			# new record
			db_insert('attendance', array(
				'player_name'		=> $name_enc,
				'raid_id'		=> $raid_id,
				'raid_day'		=> $day_enc,
				'raid_zone'		=> $zone_enc,
				'raid_difficulty'	=> $difficulty_enc,
				'time_raid'		=> $time_raid_enc,
				'time_wait'		=> $time_wait_enc,
				'time_offline'		=> $time_offline_enc,
			));
		}

	}


	#
	# import loots
	#

	foreach ($xml->loot->item as $item){

		$hash = array(
			'player_name'		=> AddSlashes($item->looter),
			'raid_id'		=> $raid_id,
			'raid_day'		=> $day_enc,
			'raid_zone'		=> $zone_enc,
			'raid_difficulty'	=> $difficulty_enc,
			'item_id'		=> intval($item->id),
			'date_drop'		=> intval(strtotime((string) $item->time)),
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

		$hash = array(
			'raid_id'		=> $raid_id,
			'name'			=> AddSlashes($boss->name),
			'zone'			=> AddSlashes($boss->zone),
			'difficulty'		=> AddSlashes($boss->difficulty),
			'date_kill'		=> intval(strtotime((string) $boss->time)),
		);

		db_insert_on_dupe('bosses', $hash, $hash);
	}


	#
	# done!
	#

	header("location: raid.php?id=$raid_id");
	exit;


	echo date('Y-m-d H:i:s', $start)."<br />";
	echo date('Y-m-d H:i:s', $end)."<br />";


echo "<hr />";
dumper($xml);

?>