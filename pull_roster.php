<?
	#
	# $Id$
	#

	ini_set('memory_limit', '100M');

	include(dirname(__FILE__).'/../include/init.php');

	loadlib('xml');
	loadlib('curl');

	#######################################################################################################

	$html = xml_fetch_safe("http://www.wowarmory.com/guild-info.xml?r=Hyjal&cn=Bees&gn=The+Eternal", 0);

	header("Content-type: text/plain");
	echo $html;
?>