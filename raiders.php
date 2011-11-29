<?
	include('init.php');

	$page_title = 'Guild Raiders';

	include('head.txt');
?>


<h2>Guild Raiders</h2>

<?
	$latest = array();
	$result = db_query("SELECT player_name, MAX(raid_day) AS latest FROM attendance GROUP BY player_name");
	while ($row = db_fetch_hash($result)){
		$latest[$row[player_name]] = $row[latest];
	}

	$players = array();

	$result = db_query("SELECT * FROM roster ORDER BY name ASC");
	while ($row = db_fetch_hash($result)){

		$row[latest] = $latest[$row[name]];
		$players[$row[name]] = $row;
	}


	#
	# remove alts if the main is listed (only for officers/trials)
	#

	$alt_map = array(
		'Zusan'		=> 'Atelo',
		'Impedimenta'	=> 'Atelo',
		'Exp'		=> 'Lebec',
		'Huggle'	=> 'Tahiti',
		'Tharra'	=> 'Tahiti',
		"Care\xC3\x9Fear"	=> 'Tahiti',
		'Duderman'	=> 'Tahiti',
		'Moorea'	=> 'Tahiti',
		'Arroway'	=> 'Tahiti',
		'Raiatea'	=> 'Tahiti',
		'Madmo'		=> 'Poobah',
		'Vilhelm'	=> 'Poobah',
		'Ranee'		=> 'Poobah',
		'Prolikos'	=> 'Antilikos',
		'Crojo'		=> 'Antilikos',
		'Musahshi'	=> 'Ander',
		'Glaciers'	=> 'Ander',
		'Janes'		=> 'Ander',
		'Jessalyn'	=> 'Bees',
	);

	foreach ($alt_map as $alt => $main){
		if (!$players[$alt]) continue;
		if (!$players[$main]) continue;

		if ($players[$alt][rank] > 2) continue;
		if ($players[$main][rank] > 2) continue;

		unset($players[$alt]);
	}

	$GLOBALS[days_raid_limit] = 30;

	insert_textarea($players, 0);
	insert_textarea($players, 1);


	function insert_textarea($players, $show_times){

		echo '<textarea style="width: 49%; height: 600px;" wrap="virtual">';

		echo "[b]Guild Master[/b]\n";
		echo "[list]\n";
		dump_players($players, array(0), $show_times);
		echo "[/list]\n";
		echo "\n";

		echo "[b]Officers[/b]\n";
		echo "[list]\n";
		dump_players($players, array(1, 2), $show_times);
		echo "[/list]\n";
		echo "\n";

		echo "[b]Core Raiders[/b]\n";
		echo "[list]\n";
		dump_players($players, array(4, 5), $show_times);
		echo "[/list]\n";
		echo "\n";

		echo "[b]Initiate Raiders[/b]\n";
		echo "[list]\n";
		dump_players($players, array(6), $show_times);
		echo "[/list]\n";

		echo '</textarea>';
	}

	function dump_players($players, $ranks, $show_times){
		foreach ($players as $row){
			if (in_array($row[rank], $ranks)){
				echo "[*] $row[name]";
				if ($show_times){
					if ($row[latest]){
						list($y, $m, $d) = explode('-', $row[latest]);
						$ts = mktime(0,0,0,$m,$d,$y);
						$since = time() - $ts;
						$days = round($since / (60 * 60 * 24));

						if ($days > $GLOBALS[days_raid_limit]){
							echo " ($days days)";
						}
					}else{
						echo " (NEVER)";
					}
					if ($row[level] < 85){
						echo " (level $row[level])";
					}
				}
				echo "\n";
			}
		}
	};
?>

<?
	include('foot.txt');
?>