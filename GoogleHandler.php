<?php

class GoogleHandler {


	protected $oauth_accesstype = 'offline';
	protected $oauth_redirect = '';
	protected $oauth_responsetype = 'code';
	protected $oauth_scope = 'https://www.googleapis.com/auth/youtube.readonly';
	protected $oauth_url = 'https://accounts.google.com/o/oauth2/auth';

	protected $oauth_google_link = '';
	protected $access_token = '';
	protected $refresh_token = '';


	/**
	 * Constructor.
	 */
	public function GoogleHandler() {
		$path_parts = explode( '?', $_SERVER['REQUEST_URI'], 2 );
		$this->oauth_redirect = 'http://' . $_SERVER['HTTP_HOST'] . $path_parts[0];

		$this->refresh_token = $this->loadRefreshToken();
	}


	/**
	 * Build the OAuth URL for use in a hyperlink.
	 */
	protected function buildLink() {
		$this->oauth_google_link = $this->oauth_url;
		$this->oauth_google_link .= '?client_id=' . urlencode( OAUTH_CLIENTID );
		$this->oauth_google_link .= '&amp;access_type=' . urlencode( $this->oauth_accesstype );
		$this->oauth_google_link .= '&amp;redirect_uri=' . urlencode( $this->oauth_redirect );
		$this->oauth_google_link .= '&amp;response_type=' . urlencode( $this->oauth_responsetype );
		$this->oauth_google_link .= '&amp;scope=' . urlencode( $this->oauth_scope );
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
	 * Check if a refresh token exists. It is however unknown if this token is valid.
	 * @return boolean True, if a refresh tokens exists, false otherwise.
	 */
	public function hasRefreshToken() {
		return ( strlen( $this->refresh_token ) > 0 );
	}


	/**
	 * Load the last received refresh token from the file.
	 * @return {string} The refresh token or an empty string on error.
	 */
	protected function loadRefreshToken() {
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


	public function queryYouTube() {
		$ytapi_maxresults = 50;

		$ytapi_url = 'https://www.googleapis.com/youtube/v3/activities';
		$ytapi_url .= '?part=snippet';
		$ytapi_url .= '&home=true';
		$ytapi_url .= '&maxResults=' . $ytapi_maxresults;
		$ytapi_url .= '&key=' . OAUTH_CLIENTID;

		$filter_type = 'upload';

		$this->requestAccessToken();
		$auth_header = array( 'Authorization: Bearer ' . urlencode( $this->access_token ) );

		$curl_handle = curl_init();
		curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 4 );
		curl_setopt( $curl_handle, CURLOPT_HTTPGET, true );
		curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, $auth_header );
		curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl_handle, CURLOPT_URL, $ytapi_url );
		$answer = curl_exec( $curl_handle );
		curl_close( $curl_handle );

		return json_decode( $answer, true );
	}


	protected function requestAccessToken() {
		$url = 'https://accounts.google.com/o/oauth2/token';

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
		curl_setopt( $curl_handle, CURLOPT_URL, $url );
		$answer = curl_exec( $curl_handle );
		curl_close( $curl_handle );

		$json = json_decode( $answer, true );

		if( !isset( $json['access_token'] ) ) {
			echo '<div class="error">';
			echo '<span>Did not receive access token.</span>';
			echo '<textarea readonly>' . htmlspecialchars( $answer ) . '</textarea>';
			echo '</div>';
			return;
		}

		$this->access_token = $json['access_token'];
	}


	/**
	 * Request the authentication and refresh token for the received code.
	 * @param  {string} $code Received OAuth code.
	 */
	public function requestTokens( $code ) {
		$url = 'https://accounts.google.com/o/oauth2/token';

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
		curl_setopt( $curl_handle, CURLOPT_URL, $url );
		$answer = curl_exec( $curl_handle );
		curl_close( $curl_handle );

		$json = json_decode( $answer, true );

		if( !isset( $json['access_token'] ) || !isset( $json['refresh_token'] ) ) {
			echo '<div class="error">';
			echo '<span>Did not receive tokens.</span>';
			echo '<textarea readonly>' . htmlspecialchars( $answer ) . '</textarea>';
			echo '</div>';
			return;
		}

		$this->access_token = $json['access_token'];
		$this->refresh_token = $json['refresh_token'];

		$this->saveRefreshToken( $this->refresh_token );
	}


	/**
	 * Save the refresh token to file.
	 * @param  {string} $refresh_token The refresh token.
	 */
	protected function saveRefreshToken( $refresh_token ) {
		$rt_handle = fopen( OAUTH_REFRESHTOKEN_FILE, 'w+b' );
		$result = fwrite( $rt_handle, $refresh_token );
		fclose( $rt_handle );

		if( $result === false ) {
			echo '<div class="error">';
			echo '<span>Error writing refresh token to file.</span>';
			echo '</div>';
		}
	}


}

?>
