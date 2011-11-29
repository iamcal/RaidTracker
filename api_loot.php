<?
	include('init.php');

	$id = intval($_POST[id]);
	$state = intval($_POST[state]);

	db_query("UPDATE loots SET ded=$state WHERE id=$id");


	#
	# what links to show?
	#

	$links = array();
	if ($state != 0) $links[] = "<a href=\"#\" onclick=\"return lootItem($id,0);\">Loot</a>";
	if ($state != 1) $links[] = "<a href=\"#\" onclick=\"return lootItem($id,1);\">DE</a>";
	if ($state != 2) $links[] = "<a href=\"#\" onclick=\"return lootItem($id,2);\">Bank</a>";


	#
	# what looter status to show?
	#

	$row = db_fetch_hash(db_query("SELECT * FROM loots WHERE id=$id"));

	$looter = 'ERROR';
	if ($state == 0) $looter = "<a href=\"player.php?name=$row[player_name]\">$row[player_name]</a>";
	if ($state == 1) $looter = "DE'd";
	if ($state == 2) $looter = "Banked";


	#
	# output
	#

	header('Content-type: text/plain');

	echo json_encode(array(
		'ok'		=> 1,
		'links'		=> implode(' ', $links),
		'looter'	=> $looter,
	));
?>