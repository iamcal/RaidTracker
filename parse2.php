<?
#14 is a good test!

	include('init.php');


	$id = intval($_GET[id]);
	$ret = parse_report($id, 1);


	if ($ret[status] == 'not_hc'){

		include('head.txt');
?>
	<h1>Error - Bad XML</h1>
	<p>It looks like that XML wasn't in the usual HeadCount format. <a href="import.php">Try again</a>.</p>
<?
		include('foot.txt');
		exit;
	}


	#
	# done!
	#

	header("location: date.php?d=$ret[day]");
	exit;


	echo date('Y-m-d H:i:s', $start)."<br />";
	echo date('Y-m-d H:i:s', $end)."<br />";


echo "<hr />";
dumper($xml);

?>