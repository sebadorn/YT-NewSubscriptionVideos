<?php

class LogMemory {


	static protected $errors = array();


	/**
	 * Add an error message.
	 * @param {string} $msg  Error message.
	 * @param {string} $data Additional error data.
	 */
	static public function error( $msg, $data = '' ) {
		self::$errors[] = array( 'msg' => $msg, 'data' => $data );
	}


	/**
	 * Check if at least one error exists.
	 * @return {int} Number of errors.
	 */
	static public function hasErrors() {
		return count( self::$errors );
	}


	/**
	 * Print the errors as HTML.
	 * @return {string} Errors as HTML.
	 */
	static public function printErrors() {
		foreach( self::$errors as $err ) {
			if( strlen( $err['data'] ) > 0 ) {
				echo '<div class="panel panel-danger">' . PHP_EOL;
				echo '<div class="panel-heading"><h3 class="panel-title">' . $err['msg'] . '</h3></div>' . PHP_EOL;
				echo '<div class="panel-body">' . PHP_EOL;
				echo '<textarea readonly>' . $err['data'] . '</textarea>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
			}
			else {
				echo '<div class="alert alert-danger"><strong>' . $err['msg'] . '</strong></div>' . PHP_EOL;
			}
		}
	}


}