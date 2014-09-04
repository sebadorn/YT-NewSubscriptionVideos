<?php

require_once( 'config.php' );
require_once( 'LogMemory.php' );
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
	<title>YouTube – New Subscription Videos</title>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="stylesheet" href="bootstrap-3.2.0/css/bootstrap.min.css" />
	<style>
		.label {
			margin-right: 8px;
		}
		.panel {
			display: inline-block;
			min-width: 400px;
		}
		.panel textarea {
			background-color: #fafafa;
			border: 1px solid #e0e0e0;
			border-radius: 4px;
			box-sizing: border-box;
			font-family: monospace;
			font-size: 12px;
			min-height: 80px;
			padding: 10px;
			resize: vertical;
			width: 100%;
		}
	</style>
</head>
<body>

<div class="container">
	<div class="page-header">
		<h1>YouTube – New Subscription Videos</h1>
	</div>

	<?php
		if( LogMemory::hasErrors() ) {
			LogMemory::printErrors();
		}
	?>

	<?php if( !$gh->hasRefreshToken() ): ?>

	<h3>
		<span class="label label-danger">No access token</span>
		<a href="<?php echo $gh->getLink(); ?>">Obtain access token.</a>
	</h3>

	<?php else: ?>

	<h3>
		<span class="label label-success">Access token found</span>
		<a href="rss.php">View your RSS feed.</a>
	</h3>

	<?php endif ?>
</div>

</body>
</html>