<?
	include('init.php');
	include('lib_parse.php');


	$result = db_query("SELECT raid_day FROM reports GROUP BY raid_day");
	while ($row = db_fetch_hash($result)){

		echo "$row[raid_day]..."; flush();
		parse_day($row[raid_day]);
		echo "ok<br />\n"; flush();
	}

	echo "all done";
?>