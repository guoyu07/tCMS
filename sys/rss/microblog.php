<?php
  header('Content-Type: application/rss+xml'); 
  if (!empty($_SERVER["HTTPS"])) {
    $protocol = "http";
  } else {
    $protocol = "https";
  }
  //Import your config, set some stuff up, then construct the mining laser
  extract(json_decode(file_get_contents('../admin/config/main.json'),true));
  $tcmsUsers = json_decode(file_get_contents('../admin/config/users.json'),true);
  date_default_timezone_set($timezone);
  $tiem = date(DATE_RSS);
  $today = date("m.d.y");
  $atomlink = "$protocol://".$_SERVER["SERVER_NAME"]."/".$basedir.$rssdir."microblog.php";
  $newsdir = $_SERVER["DOCUMENT_ROOT"]."/".$basedir.$microblogdir;
  $files = glob($newsdir.$today."/*");
  $slen = count($files);
  $feed = '<?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	     <channel>
	      <atom:link href="'.$atomlink.'" rel="self" type="application/rss+xml" />
	      <title>'.$htmltitle.'</title>
	      <description>'.$microblogtitle.' RSS Feed</description>
	      <link>http://'.$_SERVER['SERVER_NAME'].'/'.$basedir.'</link>
	      <lastBuildDate>'.$tiem.'</lastBuildDate>
	      <pubDate>'.$tiem.'</pubDate>';
  foreach ($files as $shitpost) {
    $storyPubDate =  date(DATE_RSS, strtotime(basename($shitpost)));
    $contents = file_get_contents($shitpost);
    #Set some sane defaults for cases where no user exists
    $email = "null@example.com";
    $author = "X";
    #Check the format, do needful based on what's here
    $json = json_decode($contents);
    if(is_null($json)) {
      //HAHAHA You thought you needed an XML parser, didn't you?
      $theRipper = explode("<",$contents);
      $theRipper = explode(">",$theRipper[2]);
      $storyTitle = $theRipper[1];
      $theRipper = explode('"',$theRipper[0]);
      $storyLink = htmlspecialchars($theRipper[1]);
      $theRipper = explode("</h3>",$contents);
      $theRipper = explode("<hr />",$theRipper[1]);
      $storyText = $theRipper[0];
      $theRipper = explode("title=\"Posted by ",$contents);
      $theRipper = explode('"',$theRipper[1]);
      $poster = $theRipper[0];
      if(isset($tcmsUsers[$poster])) {
          $email = $tcmsUsers[$poster]["email"];
          $author = $tcmsUsers[$poster]["fullName"]; 
      }
      $feed .= '<item>
                 <title>'.$storyTitle.'</title>
                 <description><![CDATA['.$storyText.']]></description>
                 <link>'.preg_replace('/&/', '&#038;', $storyLink).'</link>
                 <guid isPermaLink="false">'.basename($shitpost).'-'.$_SERVER["SERVER_NAME"].'</guid>
                 <pubDate>'.$storyPubDate.'</pubDate>
                 <author>'.$email.' ('.$author.')</author>
                </item>';
    } elseif (!empty($json->title) && !empty($json->url) && !empty($json->poster)) {
        if(isset($tcmsUsers[$json->poster])) {
            $email = $tcmsUsers[$json->poster]["email"];
            $author = $tcmsUsers[$json->poster]["fullName"]; 
        }
        $feed .= '<item>
                   <title>'.$json->title.'</title>
                   <description><![CDATA['.$json->comment.']]></description>
                   <link>'.preg_replace('/&/', '&#038;', $json->url).'</link>
                   <guid isPermaLink="false">'.basename($shitpost).'-'.$_SERVER["SERVER_NAME"].'</guid>
                   <pubDate>'.$storyPubDate.'</pubDate>
                   <author>'.$email.' ('.$author.')</author>
                  </item>';
    }
  }
  $feed .= ' </channel>
            </rss>';
  print_r($feed);
 ?>
