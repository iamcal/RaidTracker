<?
	putenv("TZ=Etc/GMT+8");

	include('db.php');


	function dumper($foo){
            echo "<pre style=\"text-align: left;\">";
            echo HtmlSpecialChars(var_export($foo, 1));
            echo "</pre>\n";
	}

	function format_zone($zone, $dif){

		if ($dif == '25 Player') $dif = '25';
		if ($dif == '10 Player') $dif = '10';

		if ($zone == 'Icecrown Citadel') $zone = 'ICC';
		if ($zone == 'The Obsidian Sanctum') $zone = 'Sarth';
		if ($zone == 'Vault of Archavon') $zone = 'VoA';
		if ($zone == 'Naxxramas') $zone = 'Naxx';
		if ($zone == 'Trial of the Crusader') $zone = 'ToC';

		return "$zone $dif";
	}

	function format_period($secs, $compact=0){

		$mins = round($secs / 60);
		$hours = floor($mins / 60);
		$mins -= $hours * 60;

		if ($hours){
			if ($compact){
				return "{$hours}h {$mins}m";
			}else{
				return "$hours hours, $mins minutes";
			}
		}

		if ($compact){
			return "{$mins}m";
		}else{
			return "$mins minutes";
		}
	}

	function format_time($ts){
		return date('Y-m-d g:ia', $ts);
	}

	function format_time_only($ts){
		return date('g:ia', $ts);
	}

	function get_raid_week($date){
		list($y, $m, $d) = explode('-', $date);
		$ts = mktime(0,0,0,$m,$d,$y);
		$day = date('w', $ts);

		if ($day == 0) return date('Y-m-d', mktime(0,0,0,$m,$d-5,$y));
		if ($day == 1) return date('Y-m-d', mktime(0,0,0,$m,$d-6,$y));
		if ($day == 2) return $date;
		if ($day == 3) return date('Y-m-d', mktime(0,0,0,$m,$d-1,$y));
		if ($day == 4) return date('Y-m-d', mktime(0,0,0,$m,$d-2,$y));
		if ($day == 5) return date('Y-m-d', mktime(0,0,0,$m,$d-3,$y));
		if ($day == 6) return date('Y-m-d', mktime(0,0,0,$m,$d-4,$y));
		return $date; # error!
	}

	function get_calendar_weeks(){

		list($first) = db_fetch_list(db_query("SELECT MIN(day) FROM raids"));
		$last = date('Y-m-d');
		$week = get_raid_week($first);
		#$week = get_raid_week('2010-05-02');
		list($y, $m, $d) = explode('-', $week);

		$weeks = array();

		while (1){
			$week = array();
			$matched = 0;
			for ($i=0; $i<7; $i++){
				$key = date('Y-m-d', mktime(0,0,0,$m,$d+$i,$y));
				$week[$key] = array();
				if ($key == $last) $matched = 1;
			}
			$weeks[] = $week;
			$d += 7;
			if ($matched) break;
		}

		return $weeks;
	}

	function format_percent($p){

		if ($p >= 90) return "<span class=\"atnd90\">$p%</span>";
		if ($p >= 50) return "<span class=\"atnd50\">$p%</span>";
		if ($p >= 20) return "<span class=\"atnd20\">$p%</span>";
		return "<span class=\"atnd0\">$p%</span>";
	}

	##################################################################################

	function get_raid_id($raids, $time){
		foreach ($raids as $raid){
			if ($raid[start] <= $time && $raid[end] >= $time){
				#echo "$time IS between $raid[start] & $raid[end] ($raid[id])<br />";
				return $raid[id];
			}else{
				#echo "$time is not between $raid[start] & $raid[end]<br />";
			}
		}
		return 0;
	}

	##################################################################################

	function parse_raid_date($id, $data){

		$id = intval($id);

		$row = db_fetch_hash(db_query("SELECT * FROM reports WHERE id=$id"));

		$xml = new SimpleXMLElement($row[data]);


		#
		# check it's headcount...
		#

		$from = (string) $xml['generatedFrom'];

		if ($from != 'HeadCount'){
			return 0;
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


		#
		# save it
		#

		db_query("UPDATE reports SET raid_day='$day' WHERE id=$id");

		return $day;
	}

	##################################################################################

	$GLOBALS[_cache] = array(
		'players'	=> array(),
		'items'		=> array(),
		'raids'		=> array(),
	);

	function load_player($name){
		if (!$GLOBALS[_cache][players][$name]){
			$name_enc = AddSlashes($name);

			$row = db_fetch_hash(db_query("SELECT * FROM players WHERE name='$name_enc'"));
			$row[class_id] = StrToLower(str_replace(' ', '', $row['class']));

			$GLOBALS[_cache][players][$name] = $row;
		}
		return $GLOBALS[_cache][players][$name];
	}

	function load_item($id){
		if (!$GLOBALS[_cache][items][$id]){
			$id_enc = intval($id);
			$GLOBALS[_cache][items][$id] = db_fetch_hash(db_query("SELECT * FROM items WHERE id='$id_enc'"));
		}
		return $GLOBALS[_cache][items][$id];
	}

	function load_raid($id){
		if (!$GLOBALS[_cache][raids][$id]){
			$id_enc = intval($id);
			$GLOBALS[_cache][raids][$id] = db_fetch_hash(db_query("SELECT * FROM raids WHERE id='$id_enc'"));
		}
		return $GLOBALS[_cache][raids][$id];
	}

	##################################################################################

	function insert_icon($icon){

		return "<img src=\"http://static.wowhead.com/images/wow/icons/medium/{$icon}.jpg\" width=\"24\" height=\"24\" />";
	}
?>