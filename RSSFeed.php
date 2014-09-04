<?php

class RSSFeed {


	protected $items = array();


	/**
	 * Constructor.
	 * @param {array} $feed_items Data to create the RSS feed from.
	 */
	public function RSSFeed( $feed_items ) {
		// filter uploads
		foreach( $feed_items as $item ) {
			if( $item['snippet']['type'] == 'upload' ) {
				array_push( $this->items, $item );
			}
		}

		usort( $this->items, array( 'RSSFeed', 'sortSnippets' ) );
		reset( $this->items );
	}


	/**
	 * Get the next item for the feed.
	 * @return {array} Feed item.
	 */
	public function nextItem() {
		if( current( $this->items ) === false ) {
			return false;
		}

		$data = current( $this->items );
		$item = $this->prepareItem( $data );
		next( $this->items );

		return $item;
	}


	/**
	 * Prepare the next item for the RSS feed.
	 * @param  {array} $data Item data.
	 * @return {array}       The prepared item.
	 */
	protected function prepareItem( $data ) {
		$thumb_url = $data['snippet']['thumbnails'][RSS_THUMBNAIL_SIZE]['url'];
		$thumb_width = $data['snippet']['thumbnails'][RSS_THUMBNAIL_SIZE]['width'];
		$thumb_height = $data['snippet']['thumbnails'][RSS_THUMBNAIL_SIZE]['height'];

		$snippet_desc = $data['snippet']['description'];
		$snippet_desc = str_replace( PHP_EOL, '<br />', $snippet_desc );

		$description = '<img src="' . $thumb_url . '" alt="thumbnail"';
		$description .= ' style="float: left; margin: 0 10px 10px 0;';
		$description .= ' width: ' . $thumb_width . 'px; height: ' . $thumb_height . 'px;" />';
		$description .= '<div>' . $snippet_desc . '</div>';
		$description .= '<div style="clear: both; width: 100%; height: 0px;"></div>';

		$url_parts = explode( '/', $thumb_url );
		$url_parts = array_reverse( $url_parts );
		$link_v = $url_parts[1];

		$pubDate = strtotime( $data['snippet']['publishedAt'] );
		$pubDate = date( 'D, d M Y H:i:s O', $pubDate );

		$item = array(
			'author' => htmlspecialchars( $data['snippet']['channelTitle'] ),
			'description' => htmlspecialchars( $description ),
			'link' => YT_VIDEO_URL . $link_v,
			'pubDate' => $pubDate,
			'title' => htmlspecialchars( $data['snippet']['title'] )
		);

		return $item;
	}


	/**
	 * Compare function for usort(). Sort the snippets descending by date.
	 * @param  {array} $a Feed item.
	 * @param  {array} $b Feed item.
	 * @return {int}      0 if published at the same time. 1 if $a was publised earlier, -1 otherwise.
	 */
	static protected function sortSnippets( $a, $b ) {
		$dateA = strtotime( $a['snippet']['publishedAt'] );
		$dateB = strtotime( $b['snippet']['publishedAt'] );

		if( $dateA == $dateB ) {
			return 0;
		}

		return ( $dateA < $dateB ) ? 1 : -1;
	}


}
