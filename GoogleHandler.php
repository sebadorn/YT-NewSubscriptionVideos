<?php

class GoogleHandler {


	protected $oauth_google_link = '';
	protected $oauth_redirect = '';

	protected $access_token = '';
	protected $refresh_token = '';


	/**
	 * Constructor.
	 */
	public function GoogleHandler() {
		$this->oauth_redirect = self::getServerURL();
		$this->refresh_token = $this->loadRefreshToken();
	}


	/**
	 * Build the OAuth URL for use in a hyperlink.
	 */
	protected function buildLink() {
		$this->oauth_google_link = OAUTH_URL;
		$this->oauth_google_link .= '?client_id=' . urlencode( OAUTH_CLIENTID );
		$this->oauth_google_link .= '&amp;access_type=' . urlencode( OAUTH_ACCESSTYPE );
		$this->oauth_google_link .= '&amp;approval_prompt=' . urlencode( OAUTH_APPROVALPROMPT );
		$this->oauth_google_link .= '&amp;redirect_uri=' . urlencode( $this->oauth_redirect );
		$this->oauth_google_link .= '&amp;response_type=' . urlencode( OAUTH_RESPONSETYPE );
		$this->oauth_google_link .= '&amp;scope=' . urlencode( OAUTH_SCOPE );
	}


	/**
	 * Get the activities of the channel.
	 * @param  {array} $channels
	 * @return {array}
	 */
	public function getChannelsActivities( $channels ) {
		$feed_items = array();

		$ytapi_url = YT_API_URL . '/activities';
		$ytapi_url .= '?part=snippet';
		$ytapi_url .= '&fields=items%2Fsnippet%2CnextPageToken';
		$ytapi_url .= '&maxResults=' . YT_API_MAXRESULTS_PER_CHANNEL;
		$ytapi_url .= '&key=' . OAUTH_CLIENTID;

		$url_len = strlen( $ytapi_url );
		$num_channels = count( $channels );

		$auth_header = array( 'Authorization: Bearer ' . urlencode( $this->access_token ) );

		for( $i = 0; $i < $num_channels; $i++ ) {
			$channelId = $channels[$i]['snippet']['resourceId']['channelId'];

			$url = substr( $ytapi_url, 0, $url_len );
			$url .= '&channelId=' . $channelId;

			$curl_handle = curl_init();
			curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 4 );
			curl_setopt( $curl_handle, CURLOPT_HTTPGET, true );
			curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, $auth_header );
			curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl_handle, CURLOPT_URL, $url );
			$answer = curl_exec( $curl_handle );
			curl_close( $curl_handle );

			$data = json_decode( $answer, true );
			$feed_items = array_merge( $feed_items, $data['items'] );
		}

		return $feed_items;
	}


	/**
	 * Get the OAuth URL for use in a hyperlink.
	 * @return {string} The hyperlink URL.
	 */
	public function getLink() {
		if( strlen( $this->oauth_google_link ) == 0 ) {
			$this->buildLink();
		}

		return $this->oauth_google_link;
	}


	/**
	 * Get the path of this directory, but without any parameters.
	 * @return {string} Server URL with path.
	 */
	static public function getServerURL() {
		$path_parts = explode( '?', $_SERVER['REQUEST_URI'], 2 );
		$protocoll = ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) ? 'https' : 'http';

		return $protocoll . '://' . $_SERVER['HTTP_HOST'] . $path_parts[0];
	}


	/**
	 * Get the channels the user is subscriped to.
	 * @return {array}
	 */
	public function getSubscriptions() {
		$channels = array();
		$next_page = '';

		$ytapi_url = YT_API_URL . '/subscriptions';
		$ytapi_url .= '?part=snippet';
		$ytapi_url .= '&fields=items%2Fsnippet%2CnextPageToken';
		$ytapi_url .= '&maxResults=' . YT_API_MAXRESULTS;
		$ytapi_url .= '&mine=true';
		$ytapi_url .= '&key=' . OAUTH_CLIENTID;

		$url_len = strlen( $ytapi_url );

		$auth_header = array( 'Authorization: Bearer ' . urlencode( $this->access_token ) );

		for( $i = 0; $i < YT_NUMPAGES; $i++ ) {
			$url = substr( $ytapi_url, 0, $url_len );

			if( $next_page != '' ) {
				$url .= '&pageToken=' . urlencode( $next_page );
			}

			$curl_handle = curl_init();
			curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 4 );
			curl_setopt( $curl_handle, CURLOPT_HTTPGET, true );
			curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, $auth_header );
			curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl_handle, CURLOPT_URL, $url );
			$answer = curl_exec( $curl_handle );
			curl_close( $curl_handle );

			$data = json_decode( $answer, true );
			$channels = array_merge( $channels, $data['items'] );

			if( !isset( $data['nextPageToken'] ) || empty( $data['nextPageToken'] ) ) {
				break;
			}

			$next_page = $data['nextPageToken'];
		}

		return $channels;
	}


	/**
	 * Check if a refresh token exists. It is however unknown if this token is valid.
	 * @return {boolean} True, if a refresh tokens exists, false otherwise.
	 */
	public function hasRefreshToken() {
		return ( strlen( $this->refresh_token ) > 0 );
	}


	/**
	 * Load the last received refresh token from the file.
	 * @return {string} The refresh token or an empty string on error.
	 */
	protected function loadRefreshToken() {
		if( !file_exists( OAUTH_REFRESHTOKEN_FILE ) ) {
			return '';
		}

		$rt_handle = fopen( OAUTH_REFRESHTOKEN_FILE, 'rb' );

		if( $rt_handle === false ) {
			return '';
		}

		$read_size = filesize( OAUTH_REFRESHTOKEN_FILE );

		if( $read_size == 0 ) {
			fclose( $rt_handle );
			return '';
		}

		$refresh_token = fread( $rt_handle, $read_size );
		fclose( $rt_handle );

		return ( $refresh_token === false ) ? '' : $refresh_token;
	}


	/**
	 * Query YouTube API for activities list.
	 * @return {array} Activities list as array.
	 */
	public function queryYouTube() {
		$feed_items = array();
		$next_page = '';

		$ytapi_url = YT_API_URL . '/activities';
		$ytapi_url .= '?part=snippet';
		$ytapi_url .= '&home=true';
		$ytapi_url .= '&maxResults=' . YT_API_MAXRESULTS;
		$ytapi_url .= '&key=' . OAUTH_CLIENTID;

		$url_len = strlen( $ytapi_url );

		$auth_header = array( 'Authorization: Bearer ' . urlencode( $this->access_token ) );

		for( $i = 0; $i < YT_NUMPAGES; $i++ ) {
			$url = substr( $ytapi_url, 0, $url_len );

			if( $next_page != '' ) {
				$url .= '&pageToken=' . urlencode( $next_page );
			}

			$curl_handle = curl_init();
			curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 4 );
			curl_setopt( $curl_handle, CURLOPT_HTTPGET, true );
			curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, $auth_header );
			curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl_handle, CURLOPT_URL, $url );
			$answer = curl_exec( $curl_handle );
			curl_close( $curl_handle );

			$data = json_decode( $answer, true );
			$feed_items = array_merge( $feed_items, $data['items'] );

			if( !isset( $data['nextPageToken'] ) || empty( $data['nextPageToken'] ) ) {
				break;
			}

			$next_page = $data['nextPageToken'];
		}

		return $feed_items;
	}


	/**
	 * Request an access token from the OAuth API.
	 */
	public function updateAccessToken() {
		$post_data = array(
			'client_id' => OAUTH_CLIENTID,
			'client_secret' => OAUTH_CLIENTSECRET,
			'grant_type' => 'refresh_token',
			'refresh_token' => $this->refresh_token
		);

		$post_query = http_build_query( $post_data );

		$curl_handle = curl_init();
		curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 4 );
		curl_setopt( $curl_handle, CURLOPT_POST, true );
		curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $post_query );
		curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl_handle, CURLOPT_URL, OAUTH_TOKENURL );
		$answer = curl_exec( $curl_handle );
		curl_close( $curl_handle );

		$json = json_decode( $answer, true );

		if( !isset( $json['access_token'] ) ) {
			LogMemory::error( 'Did not receive access token.', htmlspecialchars( $answer ) );
			return;
		}

		$this->access_token = $json['access_token'];
	}


	/**
	 * Request the authentication and refresh token for the received code.
	 * @param {string} $code Received OAuth code.
	 */
	public function requestTokens( $code ) {
		$post_data = array(
			'client_id' => OAUTH_CLIENTID,
			'client_secret' => OAUTH_CLIENTSECRET,
			'code' => $code,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->oauth_redirect
		);

		$post_query = http_build_query( $post_data );

		$curl_handle = curl_init();
		curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 4 );
		curl_setopt( $curl_handle, CURLOPT_POST, true );
		curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $post_query );
		curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl_handle, CURLOPT_URL, OAUTH_TOKENURL );
		$answer = curl_exec( $curl_handle );
		curl_close( $curl_handle );

		$json = json_decode( $answer, true );

		if( !isset( $json['access_token'] ) || !isset( $json['refresh_token'] ) ) {
			LogMemory::error( 'Did not receive tokens.', htmlspecialchars( $answer ) );
			return;
		}

		$this->access_token = $json['access_token'];
		$this->refresh_token = $json['refresh_token'];

		$this->saveRefreshToken( $this->refresh_token );
	}


	/**
	 * Save the refresh token to file.
	 * @param {string} $refresh_token The refresh token.
	 */
	protected function saveRefreshToken( $refresh_token ) {
		$rt_handle = fopen( OAUTH_REFRESHTOKEN_FILE, 'w+b' );

		if( $rt_handle === false ) {
			LogMemory::error( 'Error opening or creating file for refresh token.' );
			return;
		}

		$result = fwrite( $rt_handle, $refresh_token );
		fclose( $rt_handle );

		if( $result === false ) {
			LogMemory::error( 'Error writing refresh token to file.' );
		}
	}


}
