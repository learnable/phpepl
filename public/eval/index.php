<?php
require_once(dirname(__FILE__) . '/../../vendor/autoload.php');

// Turn off errors since eval will throw them on invalid syntax
$inString = @ini_set('log_errors', false);
$token = @ini_set('display_errors', true);

// CORS support
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");
  
// No need for the open and close php tags
$toRemove   = array("<?php", "?>", "<?");

if (!$_SERVER['REQUEST_METHOD'] === 'POST' || !array_key_exists('code', $_POST) || !is_string($_POST['code'])) {
  exit;
}

$code = $_POST['code'];
$code = str_replace($toRemove, "", $code);

// Blacklist
$blackList  = array(
    "ReflectionFunction", "SplFileObject", "`", "apache_child_terminate",
    "assert", "bzopen", "chgrp", "chmod", "chown", "copy",
    "create_function", "disk_free_space", "disk_total_space",
    "diskfreespace", "eval", "exec", "exif_imagetype", "exif_read_data",
    "exif_thumbnail", "extract", "file", "file_exists",
    "file_get_contents", "file_get_contents", "file_put_contents",
    "fileatime", "filectime", "filegroup", "fileinode", "filemtime",
    "fileowner", "fileperms", "filesize", "filetype", "fopen",
    "fsockopen", "ftp_get", "ftp_nb_get", "ftp_nb_put", "ftp_put",
    "get_cfg_var", "get_current_user", "get_meta_tags" "getcwd", "getenv",
    "getimagesize", "getlastmo", "getmygid", "getmyinode", "getmypid",
    "getmyuid", "glob", "gzfile", "gzopen", "hash_file", "hash_hmac_file",
    "hash_update_file", "header", "highlight_file", "image2wbmp",
    "imagecreatefromgif", "imagecreatefromjpeg", "imagecreatefrompng",
    "imagecreatefromwbmp", "imagecreatefromxbm", "imagecreatefromxpm",
    "imagegd", "imagegd2", "imagegif", "imagejpeg", "imagepng",
    "imagewbmp", "imagexbm", "include", "include_once", "ini_set",
    "iptcembed", "is_dir", "is_executable", "is_file", "is_link",
    "is_readable", "is_uploaded_file", "is_writable", "is_writeable",
    "lchgrp", "lchown", "link", "linkinfo", "lstat", "mail", "md5_file",
    "mkdir", "move_uploaded_file", "parse_ini_file", "parse_str",
    "passthru", "pathinfo", "pcntl_exec", "pfsockopen",
    "php_strip_whitespace", "phpinfo", "popen", "posix_getlogin",
    "posix_kill", "posix_mkfifo", "posix_mkfifo", "posix_setpgid",
    "posix_setsid", "posix_setuid", "posix_ttyname", "proc_close",
    "proc_get_status", "proc_nice", "proc_open", "proc_terminate",
    "putenv", "read_exif_data", "readfile", "readgzfile", "readlink",
    "realpath", "rename", "require", "require_once", "rmdir", "sha1_file",
    "shell_exec", "show_source", "stat", "symlink", "system", "tempnam",
    "tmpfile", "touch", "unlink",
);

$sandbox = new \PHPSandbox\PHPSandbox();
$sandbox->blacklist_func($blackList);
$sandbox->allow_functions = true;
$sandbox->allow_closures = true;
$sandbox->allow_constants = true;
$sandbox->allow_aliases = true;
$sandbox->allow_interfaces = true;
$sandbox->allow_casting = true;
$sandbox->allow_classes = true;
$sandbox->error_level = false;

// Output buffering to catch the results and errors
ob_start();
$result = NULL; $error = [];
try {
  $sandbox->execute($code);
} catch (Exception $e) {
  if ($e->getPrevious() !== NULL) {
    // A regular error
    $error['message'] = $e->getPrevious()->getRawMessage();
    $error['line'] = $e->getPrevious()->getRawLine();
  } else {
    // Used a blacklisted function
    $error['message'] = $e->getMessage();
    if ($e->getNode() === NULL) {
      $error['function'] = $e->getData();
    } else {
      $error['line'] = $e->getNode()->getLine();
    }
  }
}

@ini_set('display_errors', $token);
@ini_set('log_errors', $inString);

$result = ob_get_clean();

echo getJsonOutput(array('result' => $result, 'error' => $error));

// Helper for constructing a response
function getJsonOutput($options) {
  $result = isset($options['result']) ? $options['result']: '';
  $error  = isset($options['error']) ? $options['error'] : '';
  return json_encode(array("result" => $result, "error" => $error));
}
