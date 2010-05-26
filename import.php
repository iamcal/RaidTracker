<?
	include('init.php');

	if ($_POST[done]){

		$data = trim($_POST[data]);

		if (strlen($data)){

			$id = db_insert('reports', array(
				'date_create'	=> time(),
				'user'		=> AddSlashes('_TEMP_'),
				'data'		=> AddSlashes($data),
			));

			$day = parse_raid_date($id, $data);

			header("location: parse.php?id=$id");
			exit;
		}
	}

	$page_title = 'Import Raid Info';
	$nav = 'import';

	include('head.txt');
?>

<p>Just copy and paste the XML output from Headcount into the box below:</p>

<form action="import.php" method="post">
<input type="hidden" name="done" value="1" />

<textarea name="data" wrap="virtual" style="width: 100%; height: 400px;"></textarea>

<input type="submit" value="Import Raid" />

</form>

<?
	include('foot.txt');
?>