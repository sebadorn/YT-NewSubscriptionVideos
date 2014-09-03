<?php

require_once( 'config.php' );
require_once( 'GoogleHandler.php' );

$gh = new GoogleHandler();

if( isset( $_GET['code'] ) ) {
	$gh->requestTokens( $_GET['code'] );
}

?>
<!DOCTYPE html>

<html>
<head>
	<meta charset="utf-8" />
	<title>YouTube – new subscription videos</title>
</head>
<body>

<?php if( !$gh->hasRefreshToken() ): ?>

<a href="<?php echo $gh->getLink(); ?>">obtain access token</a>

<?php else: ?>

<p>Valid access token found. You can view your RSS feed of new subscription videos here:</p>

<p><a href="rss.php">rss.php</a></p>

<?php endif ?>

</body>
</html>