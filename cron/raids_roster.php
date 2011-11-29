<?
	#
	# $Id$
	#

	ini_set('memory_limit', '100M');

	include(dirname(__FILE__).'/../include/init.php');

	loadlib('xml');
	loadlib('curl');

	#######################################################################################################

	$map_class = array(
		1 => 'Warrior',
		2 => 'Paladin',
		3 => 'Hunter',
		4 => 'Rogue',
		5 => 'Priest',
		6 => 'Death Knight',
		7 => 'Shaman',
		8 => 'Mage',
		9 => 'Warlock',
		11 => 'Druid',
	);
	$map_race = array(
		3 => 'Dwarf',
		1 => 'Human',
		4 => 'Night Elf',
		7 => 'Gnome',
		11 => 'Draenei',
	);
	$map_sex = array(
		0 => 'Male',
		1 => 'Female',
	);

	echo "Grabing current roster...";

	$players = array();

	$tree = fetch_safe("http://www.wowarmory.com/guild-info.xml?r=Hyjal&cn=Bees&gn=The+Eternal");
	$player_nodes = $tree->findMulti('page/guildInfo/guild/members/character');
	foreach ($player_nodes as $node){

		$players[] = array(
			'name'	=> $node->attributes[name],
			'class'	=> $map_class[$node->attributes[classId]],
			'race'	=> $map_race[$node->attributes[raceId]],
			'sex'	=> $map_sex[$node->attributes[genderId]],
			'level'	=> $node->attributes[level],
			'rank'	=> $node->attributes[rank],
		);
	}

	if (!count($players)){
		echo "failed\n";
		exit;
	}

	$tree->cleanup();
	echo "ok\n";

	#######################################################################################################

	echo "Loading old roster...";

	$old = local_load_roster();

	echo "ok\n";

	#######################################################################################################

	echo "Saving new roster...";

	db_query("TRUNCATE eternal_raids.roster");

	foreach ($players as $row){
		db_insert('eternal_raids.roster', array(
			'name'	=> AddSlashes($row[name]),
			'class'	=> AddSlashes($row['class']),
			'race'	=> AddSlashes($row[race]),
			'sex'	=> AddSlashes($row[sex]),
			'level'	=> AddSlashes($row[level]),
			'rank'	=> AddSlashes($row[rank]),
		));
	}

	$new = local_load_roster();

	echo "ok\n";

	#######################################################################################################

	echo "Comparing...";

	$changes = array();

	foreach ($old as $k => $v){

		if ($new[$k]){
			unset($new[$k]);
		}else{
			$v[action] = 'left';
			$changes[] = $v;
		}
	}

	foreach ($new as $v){
		$v[action] = 'joined';
		$changes[] = $v;
	}

	$c = count($changes);

	foreach ($changes as $row){
		db_insert('eternal_raids.roster_changes', array(
			'date_create' => time(),
			'action'=> AddSlashes($row[action]),
			'name'	=> AddSlashes($row[name]),
			'class'	=> AddSlashes($row['class']),
			'race'	=> AddSlashes($row[race]),
			'sex'	=> AddSlashes($row[sex]),
			'level'	=> AddSlashes($row[level]),
		));
	}

	echo "ok ($c changes)\n";

	#######################################################################################################

	function local_load_roster(){

		$out = array();
		$result = db_query("SELECT * FROM eternal_raids.roster");
		while ($row = db_fetch_hash($result)){
			$out[$row[name]] = $row;
		}

		return $out;
	}


	function fetch_safe($url, $tries=10, $die=1){

		$attempts = 0;
		while (1){
			$tree = xml_fetch($url);
			if ($tree) return $tree;
			echo '.'; flush();
			$attempts++;
			if ($attempts == $tries){
				if ($die) die("failed to fetch $url");
				return null;
			}
			sleep(1);
		}
	}
?>