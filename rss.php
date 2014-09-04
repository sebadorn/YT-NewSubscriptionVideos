<?php

header( 'Content-Type: application/rss+xml; charset=utf-8' );

require_once( 'config.php' );
require_once( 'LogMemory.php' );
require_once( 'GoogleHandler.php' );
require_once( 'RSSFeed.php' );

$gh = new GoogleHandler();

if( !$gh->hasRefreshToken() ) {
	header( 'Location: index.php?error=norefreshtoken' );
	exit;
}

$feed_items = $gh->queryYouTube();
$rss = new RSSFeed( $feed_items );
$rss_link = GoogleHandler::getServerURL();

echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;

?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
	<channel>
		<title>YT – New Subscription Videos</title>
		<description>New uploaded videos of YouTube channels you are subscribed to.</description>
		<pubDate><?php echo date( 'D, d M Y H:i:s O' ); ?></pubDate>
		<link><?php echo $rss_link; ?></link>
		<atom:link href="<?php echo $rss_link; ?>" rel="self" type="application/rss+xml" />
		<?php
			while( $item = $rss->nextItem() ):
		?>
		<item>
			<title><?php echo $item['title']; ?></title>
			<pubDate><?php echo $item['pubDate']; ?></pubDate>
			<link><?php echo $item['link']; ?></link>
			<guid><?php echo $item['link']; ?></guid>
			<dc:creator><?php echo $item['author']; ?></dc:creator>
			<description><?php echo $item['description']; ?></description>
		</item>
		<?php
			endwhile
		?>
	</channel>
</rss>