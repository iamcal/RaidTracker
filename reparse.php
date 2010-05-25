<?
	include('init.php');

	db_query("DELETE FROM attendance");
	db_query("DELETE FROM bosses");
	db_query("DELETE FROM loots");
	db_query("DELETE FROM players");
	db_query("DELETE FROM raids");


	$result = db_query("SELECT id FROM reports");
	while ($row = db_fetch_hash($result)){

		$ret = parse_report($row[id], 0);
	}

	echo "all done";
?>