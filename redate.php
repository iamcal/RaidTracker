<?
	include('init.php');


	$result = db_query("SELECT id FROM reports");
	while ($row = db_fetch_hash($result)){

		parse_raid_date($row[id], $row[data]);
	}

	echo "all done";
?>