<?php





$rssurl = "http://brians-computer-2.local/~brian/svnomb/omb/?posts.rss";
$atlink = "http://brians-computer-2.local/~brian/svnomb/omb/?$2";
$taglink = "http://brians-computer-2.local/~brian/svnomb/omb/?$1";




error_reporting(E_ALL);

require_once('magpierss/rss_fetch.inc');

$at_pattern = "#(\s@{1}(([a-z0-9]{1,64})))#";
$url_pattern = "#http://[A-Za-z0-9/.=?-]*#";
$tag_pattern = "!#{1}([A-Za-z0-9]*)!";

define('MAGPIE_CACHE_AGE', 60); 

function get_dings($url, $wordwrap)
{
	global $at_pattern;
	global $url_pattern;
	global $tag_pattern;
	global $profilelink;
	global $servicelink;



	$rss = fetch_rss($url);
	$profilelink = $rss->channel['link'];
	$servicelink = "http://" . parse_url($url, PHP_URL_HOST) . "/";
	$itemcount = sizeof($rss->items);
	for ($i=0; $i<$itemcount; $i++)
	{
		$items[$i] = htmlspecialchars($rss->items[$i]['title']);
		$splitted = split(":", $items[$i], 2);
		//$ding[$i]['nick']['ding'] = split(":", $ding[$i], 2);
		$ding[$i]['nick'] = $splitted[0];
		$ding[$i]['ding'] = $splitted[1];
		$ding[$i]['profilelink'] = 
		$ding[$i]['ding'] = wordwrap($ding[$i]['ding'], $wordwrap, " <br />", true);
		$ding[$i]['ding'] = preg_replace($url_pattern, "<a href=\"$0\" class=\"dingshowlink\">$0</a>", $ding[$i]['ding']);
		$ding[$i]['ding'] = preg_replace($at_pattern, "@<a href=\"http://".$atlink."\" class=\"dingshowlink\">$2</a>", $ding[$i]['ding']);
		$ding[$i]['ding'] = preg_replace($tag_pattern, "#<a href=\"http://".$taglink."\" class=\"dingshowlink\">$1</a>", $ding[$i]['ding']);
		
		
	}
	return $ding;
}

function ding_show($url, $count=1, $shownick, $linknick, $show_profilelink, $profilelinktext, $wordwrap)
{	
	$ding = get_dings($url, $wordwrap);
	global $profilelink;
	global $servicelink;

	$i = 0;
	echo "<ul class=\"dinglist\">";
	$li_class = "dinglist_item";

	if ($shownick == 0)
	{
		while ($i < $count)
		{
			echo "<li class=\"" . $li_class . "\">" . $ding[$i]['ding'] . "</li>";
			$i++;
		}
	}
	else if ($linknick && $shownick)
	{
		while ($i < $count)
		{
			echo "<li class=\"" . $li_class . "\"><strong><a href=\"" . $servicelink . $ding[$i]['nick'] . "\">" . $ding[$i]['nick'] . ":</a></strong>" .   $ding[$i]['ding'] . "</li>";
			$i++;
		}	
	}

	else
	{
		while ($i < $count)
		{
			echo "<li class=\"" . $li_class . "\"><strong>" . $ding[$i]['nick'] . ":</strong>" .  $ding[$i]['ding'] . "</li>";
			$i++;
		}
	}
	echo "</ul>";
	
	if ($show_profilelink)
	{
		echo "<br /><a href = \"". $profilelink . "\" id=\"dingshow_profilelink\">" . $profilelinktext . "</a>";
	}
}




ding_show($rssurl, 10, 1, 1, 1, "Profile", 75);


?>