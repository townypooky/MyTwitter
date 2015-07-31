<?php
/**
 * Class definition of Html
 */
namespace MyTwitter;

use \General\HttpStatus as HttpStatus;

/**
 * Quick write HTML
 *
 * @static
 */
abstract class Html
{
	final public static function writeFull($status_int, callable $inner_body_writer, array $args=array()){
		HttpStatus::setHeader($status_int);
		echo '<!DOCTYPE html>', "\n";
		echo '<head>', "\n";
		echo "\t", '<meta charset="utf-8" />', "\n";
		echo "\t", '<title>', HttpStatus::getString($status_int, ''), '</title>', "\n";
		echo '</head>', "\n";
		echo '<body>', "\n";
		echo '<section id="header">',"\n";
		echo "\t", '<header><h1>', HttpStatus::getString($status_int, ''), '</h1></header>', "\n";
		echo '</section>', "\n";
		echo '<section id="main">', "\n";
		echo "\n\n", call_user_func_array($inner_body_writer, $args), "\n\n";
		echo '</section>', "\n";
		echo '</body>', "\n";
		echo '</html>', "\n";
		die;
	}
}
