<?php
namespace General;
/**
 * HTTP status enumeration (enum)
 */
abstract class HttpStatus
{
	/**
	 * Array of all HTTP errors
	 * @var array
	 */
	private static $dict = array(
		400 => 'Unauthorized',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		409 => 'Conflict',
		410 => 'Gone',
		423 => 'Locked',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		9999 => 'Unexpected Status'
	);

	/**
	 * Get the HTTP error code in int
	 * @param int $status_int error code in int
	 * @return string
	 */
	final public static function getName($status_int){
		return isset(self::$dict[$status_int]) ? self::$dict[$status_int] : self::$dict[9999];
	}

	/**
	 * Get the full expression of HTTP error code
	 * @param int $status_int error code in int
	 * @return string
	 */
	final public static function getString($status_int, $http_version='HTTP/1.0 '){
		return $http_version . ((string)$status_int) . ' ' . self::getName($status_int);
	}

	/**
	 * Send the header by the status code
	 * @param int $status_int error code in int
	 */
	final public static function setHeader($status_int){
		@\header(self::getString($status_int));
	}
}
