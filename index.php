<?php
/**
 * Very simple private twitter
 *
 * @author Towny Pooky
 * @copyright MIT
 * @version 0.0.1
 */
error_reporting(E_ALL);


/**
 * Set alias names for library classes
 * @note write a line when you use another class
 * @note this section associates with the below section
 */
use \General\HttpStatus as HttpStatus;
use \MyTwitter\Html as Html;

/**
 * Load the library files
 * @note write a line when you use another class
 * @note this section associates with above the "use" section
 */
require_once './HttpStatus.php';
require_once './Html.php';


/**
 * Load a required file
 * @param string $base_name relative file path from this directory
 * @note this function is to die after execution
 */
function require_file($base_name){
	$file_path = __DIR__ . '/' . $base_name;
	if(!file_exists($file_path)){
		Html::writeFull(500, function($file_path){
			$base_name = basename($file_path);
			echo sprintf('<p>%s is not found; %s is necessary for execution.</p>', $base_name, $base_name);
		}, array($file_path));
	}
	require_once $file_path;
}


/**
 * Load user files
 * @note write a line when you use another user file
 */
require_file('config.php');


/**
 * Protected by SUPASS
 */
if(!isset($_GET['su']) || $_GET['su'] !== SUPASS){
	Html::writeFull(401, function(){
	}, array());
}



// Connect to the database
function connect(){
	$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->set_charset(DB_CHARSET);
	return $db;
}

// Create table if not yet
function createTableIfYet(\mysqli $db){
	return $db->query(sprintf("create table if not exists `%s` (".
		" id bigint(20) unsigned not null auto_increment,".
		" title char(255) not null,".
		" body text not null,".
		" createtime TIMESTAMP default CURRENT_TIMESTAMP,".
		" primary key (id)".
		") engine=InnoDB charset utf8 collate utf8_general_ci auto_increment=1",
		TB_NAME));
}

// Post a new entry
function add_entry(\mysqli $db, $title, $body){
	$len1 = mb_strlen($title, 'utf8');
	$len2 = mb_strlen($body, 'utf8');
	if(!TITLE_SKIP || $len1 < MIN_TITLE_LEN) return -1;
	if($len2 < MIN_BODY_LEN) return -2;
	if(!TITLE_SKIP || $len1 > MAX_TITLE_LEN) return -3;
	if($len2 > MAX_BODY_LEN) return -4;
	$etitle = $db->real_escape_string(TITLE_SKIP ? UNTITLED_TITLE : $title);
	$ebody = $db->real_escape_string($body);
	return $db->query(sprintf("insert into `%s` set title='%s', body='%s'",
		TB_NAME, $etitle, $ebody));
}

/**
 * Get the error message from the error code
 */
function get_error_message($error_code){
	$errors = array(
		-1 => 'タイトルの文字数が少なすぎます。最低限' . MIN_TITLE_LEN . '文字必要です。',
		-2 => '本分の文字数が少なすぎます。最低限' . MIN_BODY_LEN . '文字必要です。',
		-3 => 'タイトルの文字数が多すぎます。最大' . MAX_TITLE_LEN . '文字までです。',
		-4 => '本分の文字数が多すぎます。最大' . MAX_BODY_LEN . '文字までです。'
	);
	return isset($errors[$error_code]) ? $errors[$error_code] : '';
}


/**
 * Execute for each article
 * @param \mysqli $db
 * @param callable $callback
 * @param array $args
 */
function each_article(\mysqli $db, callable $callback, array $args=array(), $order='ASC'){
	$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 0;
	$rows = isset($_GET['rows']) && is_numeric($_GET['rows']) ? (int)$_GET['rows'] : 10;
	$result = $db->query(sprintf('select id, title, body, createtime from `%s` order by id %s limit %d,%d',
		TB_NAME,
		$order,
		$page * $rows,
		$rows));
	if(!$result) return false;
	while($row = $result->fetch_assoc()){
		$callback_result = call_user_func_array($callback, array_merge(array($row), $args));
		if($callback_result === false) return false;
	}
	return true;
}

// Get the entry
function get_entry(\mysqli $db, $id){
	$result = $db->query(sprintf('select id, title, body from `%s` where id=\'%d\' limit 0,1',
		TB_NAME, $id));
	if(!$result) return false; // false is error
	$row = $result->fetch_row();
	return $row ? $row : null; // null is empty
}

// Get the count of entries
function get_count_entries(\mysqli $db){
	$result = $db->query(sprintf('select count(id) from `%s`',
		TB_NAME));
	if(!$result) return -1; // false is error
	$row = $result->fetch_row();
	return $row ? $row[0] : 0; // null is empty
}



$db = connect();
createTableIfYet($db);

// Post
if(isset($_GET['su'], $_POST['body']) && (TITLE_SKIP || isset($_POST['title'])) && $_GET['su'] === SUPASS){
	$r = add_entry($db, TITLE_SKIP ? UNTITLED_TITLE : $_POST['title'], $_POST['body']);
	define('ERRNO', is_int($r) ? $r : 0);
}

// Export
if(isset($_GET['save']) && $_GET['su'] === SUPASS){
	each_article($db, function($row){
		$fp = fopen($row['id'].'.txt', 'w');
		fwrite($fp, "\n\n");
		fwrite($fp, mb_convert_encoding('タイトル：', 'SJIS', 'UTF-8'));
		fwrite($fp, mb_convert_encoding(trim($row['title']), 'SJIS', 'UTF-8'));
		fwrite($fp, "\n\n\n");
		fwrite($fp, mb_convert_encoding('本文;', 'SJIS', 'UTF-8'));
		fwrite($fp, "\n\n");
		fwrite($fp, mb_convert_encoding(trim($row['body']), 'SJIS', 'UTF-8'));
		fwrite($fp, "\n\n");
		fclose($fp);
	}, array());
}



?>
<!DOCTYPE html>
<head>
	<meta charset="utf-8" />
	<title><?php echo SITE_TITLE; ?></title>
	<script type="text/javascript" src="./jquery-2.1.3.js"></script>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>


<h1 id="header"><?php echo SITE_TITLE; ?> (<?php echo get_count_entries($db); ?>件)</h1>

<?php if(defined('ERRNO')){ ?>
<h1>
	<?php if(ERRNO === 0){ define('HAS_ERROR', false); ?>
	投稿できました
	<?php }else{ ?>
	<?php
		echo get_error_message(ERRNO);
		define('HAS_ERROR', true);
	?>
	<?php } ?>
</h1>
<?php
}else{
	define('HAS_ERROR', false);
}
?>

<form action="./<?php echo basename(__FILE__); ?>?su=<?php echo SUPASS; ?>" method="POST">
	<?php if(!TITLE_SKIP){ ?>
	<h1>Title</h1>
	<input type="text" name="title" value="<?php if(isset($_POST['title'])){ echo $_POST['title']; } ?>" size="35" />
	<div><span id="wc1"><?php if(isset($_POST['title']) && HAS_ERROR){ echo mb_strlen($_POST['title'], 'utf8'); }else{ echo '0'; }  ?></span>words</div>
	<h1>Body</h1>
	<?php } ?>
	<textarea name="body"><?php if(isset($_POST['body']) && HAS_ERROR){ echo $_POST['body']; } ?></textarea>
	<div><span id="wc2"><?php if(isset($_POST['body']) && HAS_ERROR){ echo mb_strlen($_POST['body'], 'utf8'); }else{ echo '0'; }  ?></span>words</div>
	<div><input type="submit" value="Submit" />
</form>

<section id="article-list">
	<section class="contents">
		<?php each_article($db, function($row){ ?>
			<article>
				<?php echo $row['body']; ?>
				<aside>
					<time><?php echo date('Y年n月d日 H:i', strtotime($row['createtime'])); ?></time>
				</aside>
			</article>
		<?php }, array(), 'DESC'); ?>
	</section>
</section>


<footer>
	<span id="mytwitter-credit" class="copyright">MyTwitter</span>
</footer>

<script type="text/javascript">
(function(){
	var inputArea = $('[name=body]');
	setInterval(function(){
		if(!$(inputArea).is(':focus')) $(inputArea).focus();
	}, 500);
})();
$('[type="text"]').keydown(function(){
	$('#wc1').html($(this).val().length);
});
$('textarea').keydown(function(e){
	$('#wc2').html($(this).val().length);
	if(e.keyCode === 13 && e.ctrlKey){
		$('form').submit();
		return false;
	}
});
</script>
</body>
</html>
