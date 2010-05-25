<?
	putenv("TZ=PST8PDT");

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
		if ($zone == 'The Obsidian Sanctum') $zone = 'OS';
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
?>