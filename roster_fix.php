<?
	include('init.php');

	putenv("TZ=PST8PDT");

	header("Content-tyep: text/plain");


	#
	# get buckets
	#

	$buckets = array();
	$rows = array();

	$result = db_query("SELECT * FROM roster_changes ORDER BY date_create ASC");
	while ($row = db_fetch_hash($result)){

		$d = date('Y-m-d', $row[date_create]);

		$buckets[$d]++;
		$rows[$d][] = $row;
	}


	#
	# find large buckets
	#

	foreach ($buckets as $k => $v){

		if ($v > 100){

			echo "Cleaning bucket $k...";

			# aggregate rows by player
			$players = array();
			foreach ($rows[$k] as $row){
				$key = $row[action];

				$players[$row[name]][$key]++;
				$players[$row[name]][ids][] = $row[id];
			}


			# now delete rows for players that had an equal number of
			# 'left' and 'joined' rows

			foreach ($players as $k2 => $actions){

				$num_left = intval($actions['left']);
				$num_joined = intval($actions['joined']);

				if ($num_left == $num_joined){

					#echo "need to remove $k2, ".implode(',', $actions[ids])."\n";
					echo ".";
					foreach ($actions[ids] as $id){
						db_query("DELETE FROM roster_changes WHERE id=".intval($id));
					}
				}
			}

			echo "ok\n"; flush();
		}
	}

	echo "all done\n";
?>