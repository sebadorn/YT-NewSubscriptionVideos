<?php

class RSSFeed {


	protected $items = array();


	/**
	 * Constructor.
	 */
	public function RSSFeed( $feed_data ) {
		// filter uploads
		foreach( $feed_data['items'] as $item ) {
			if( $item['snippet']['type'] == 'upload' ) {
				array_push( $this->items, $item );
			}
		}

		usort( $this->items, array( 'RSSFeed', 'sortSnippets' ) );
		reset( $this->items );
	}


	/**
	 * Compare function for usort(). Sort the snippets descending by date.
	 * @param  {array} $a Feed item.
	 * @param  {array} $b Feed item.
	 * @return {int}      0 if published at the same time. 1 if $a was publised earlier, -1 otherwise.
	 */
	protected static function sortSnippets( $a, $b ) {
		$dateA = strtotime( $a['snippet']['publishedAt'] );
		$dateB = strtotime( $b['snippet']['publishedAt'] );

		if( $dateA == $dateB ) {
			return 0;
		}

		return ( $dateA < $dateB ) ? 1 : -1;
	}


	/**
	 * Get the next item for the feed.
	 * @return {array} Feed item.
	 */
	public function nextItem() {
		if( next( $this->items ) === false ) {
			return false;
		}

		$data = current( $this->items );

		$thumb_medium = $data['snippet']['thumbnails']['medium']['url'];
		$thumb_width = $data['snippet']['thumbnails']['medium']['width'];
		$thumb_height = $data['snippet']['thumbnails']['medium']['height'];

		$snippet_desc = $data['snippet']['description'];
		$snippet_desc = str_replace( "\n", '<br />', $snippet_desc );

		$description = '<img src="' . $thumb_medium . '" alt="thumbnail"';
		$description .= ' style="float: left; margin: 0 10px 10px 0; width: ' . $thumb_width . 'px; height: ' . $thumb_height . 'px;" />';
		$description .= '<div>' . $snippet_desc . '</div>';
		$description .= '<div style="clear: both; width: 100%; height: 0px;"></div>';

		$url_parts = explode( '/', $thumb_medium );
		$url_parts = array_reverse( $url_parts );
		$link_v = $url_parts[1];

		$pubDate = strtotime( $data['snippet']['publishedAt'] );
		$pubDate = date( 'D, d M Y H:i:s O', $pubDate );

		$item = array(
			'author' => htmlspecialchars( $data['snippet']['channelTitle'] ),
			'description' => htmlspecialchars( $description ),
			'link' => 'https://www.youtube.com/watch?v=' . $link_v,
			'pubDate' => $pubDate,
			'title' => htmlspecialchars( $data['snippet']['title'] )
		);

		return $item;
	}


}
