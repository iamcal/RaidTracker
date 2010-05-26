<?
	include('init.php');
	include('lib_parse.php');


	#
	# get day
	#

	$day = $_GET[d];
	if (!preg_match('!^\d\d\d\d-\d\d-\d\d$!', $day)){
		die('bad day: '.$day);
	}

	parse_day($day);


	#
	# done!
	#

	header("location: date.php?d=$day");
	exit;
?>