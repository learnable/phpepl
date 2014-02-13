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

$code = $_POST['code'];
$code = str_replace($toRemove, "", $code);

// Blacklist
$blackList  = array("phpinfo", "file_get_contents", "exec", "passthru", 
          "system", "shell_exec", "`", "popen", "proc_open", 
          "pcntl_exec", "eval", "assert", "create_function", 
          "include", "include_once", "require", "require_once", 
          "ReflectionFunction", "posix_mkfifo", "posix_getlogin", "posix_ttyname", "getenv", 
          "get_current_user", "proc_get_status", "get_cfg_var", "disk_free_space", "disk_total_space", 
          "diskfreespace", "getcwd", "getlastmo", "getmygid", "getmyinode", "getmypid", "getmyuid",
          "extract", "parse_str", "putenv", "ini_set", "mail", "header", "proc_nice", "proc_terminate",
          "proc_close", "pfsockopen", "fsockopen", "apache_child_terminate", "posix_kill", 
          "posix_mkfifo", "posix_setpgid", "posix_setsid", "posix_setuid", "fopen", "tmpfile", "bzopen",
          "gzopen", "SplFileObject", "chgrp", "chmod", "chown", "copy", "file_put_contents",
          "lchgrp", "lchown", "link", "mkdir", "move_uploaded_file", "rename", "rmdir", "symlink",
          "tempnam", "touch", "unlink", "imagepng", "imagewbmp", "image2wbmp", "imagejpeg", "imagexbm",
          "imagegif", "imagegd", "imagegd2", "iptcembed", "ftp_get", "ftp_nb_get", "file_exists",
          "file_get_contents", "file", "fileatime", "filectime", "filegroup", "fileinode", "filemtime", 
          "fileowner", "fileperms", "filesize", "filetype", "glob", "is_dir", "is_executable", "is_file", 
          "is_link", "is_readable", "is_uploaded_file", "is_writable", "is_writeable", "linkinfo", "lstat", 
          "parse_ini_file", "pathinfo", "readfile", "readlink", "realpath", "stat", "gzfile", 
          "readgzfile", "getimagesize", "imagecreatefromgif", "imagecreatefromjpeg", "imagecreatefrompng", 
          "imagecreatefromwbmp", "imagecreatefromxbm", "imagecreatefromxpm", "ftp_put", "ftp_nb_put", 
          "exif_read_data", "read_exif_data", "exif_thumbnail", "exif_imagetype", "hash_file", "hash_hmac_file", 
          "hash_update_file", "md5_file", "sha1_file", "highlight_file", "show_source", "php_strip_whitespace", 
          "get_meta_tags"
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
