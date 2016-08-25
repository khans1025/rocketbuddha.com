<?php
header("Access-Control-Allow-Origin: http://localhost");

$S = (object)$_SERVER;
$baseUrl = ($S->REQUEST_SCHEME?$S->REQUEST_SCHEME:'http') .'://'. $S->HTTP_HOST . dirname($S->REQUEST_URI .'.');

$opts    = array(
	'roots' => array(
		array(
			'driver'		=> 'Local',
			'path'			=> __DIR__,
			'accessControl' => 'access',
			'URL'			=> $baseUrl,
			'tmbBgColor'	=> 'transparent',
			'tmbPathMode'	=> 0755,
			'acceptedName'	=> '/^[\w\d\s\.\@\%\-\_]+$/u',
			'mimeDetect'	=> 'internal'
		)
	)
);

$connector = new fileManagerConnector(new fileManager($opts));
$connector->run();

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from  '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume) {
	$filename = basename($path);
	
	$hidden_files = array('.tmb', '.quarantine');
	
	if( in_array($filename, $hidden_files) ) {
		return true;
	} else {
		return null;
	}
}

/**
 * fileManager - file manager for web.
 * Core class.
 *
 * @package fileManager
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 * @author Alexey Sukhotin
 **/
class fileManager
{
	
	protected $version = '2.0';
	
	protected $volumes = array();
	
	public static $volumesCnt = 1;
	
	protected $default = null;
	
	protected $commands = array('open' => array('target' => false, 'tree' => false, 'init' => false, 'mimes' => false), 'ls' => array('target' => true, 'mimes' => false), 'tree' => array('target' => true), 'parents' => array('target' => true), 'tmb' => array('targets' => true), 'file' => array('target' => true, 'download' => false), 'size' => array('targets' => true), 'mkdir' => array('target' => true, 'name' => true), 'mkfile' => array('target' => true, 'name' => true, 'mimes' => false), 'rm' => array('targets' => true), 'rename' => array('target' => true, 'name' => true, 'mimes' => false), 'duplicate' => array('targets' => true, 'suffix' => false), 'paste' => array('dst' => true, 'targets' => true, 'cut' => false, 'mimes' => false), 'upload' => array('target' => true, 'FILES' => true, 'mimes' => false, 'html' => false), 'get' => array('target' => true), 'put' => array('target' => true, 'content' => '', 'mimes' => false), 'archive' => array('targets' => true, 'type' => true, 'mimes' => false), 'extract' => array('target' => true, 'mimes' => false), 'search' => array('q' => true, 'mimes' => false), 'info' => array('targets' => true), 'dim' => array('target' => true), 'resize' => array('target' => true, 'width' => true, 'height' => true, 'mode' => false, 'x' => false, 'y' => false, 'degree' => false));
	
	protected $listeners = array();
	
	protected $time = 0;
	protected $loaded = false;
	protected $debug = false;
	
	protected $uploadDebug = '';
	
	public $mountErrors = array();
	
	const ERROR_UNKNOWN = 'errUnknown';
	const ERROR_UNKNOWN_CMD = 'errUnknownCmd';
	const ERROR_CONF = 'errConf';
	const ERROR_CONF_NO_JSON = 'errJSON';
	const ERROR_CONF_NO_VOL = 'errNoVolumes';
	const ERROR_INV_PARAMS = 'errCmdParams';
	const ERROR_OPEN = 'errOpen';
	const ERROR_DIR_NOT_FOUND = 'errFolderNotFound';
	const ERROR_FILE_NOT_FOUND = 'errFileNotFound';
	const ERROR_TRGDIR_NOT_FOUND = 'errTrgFolderNotFound';
	const ERROR_NOT_DIR = 'errNotFolder';
	const ERROR_NOT_FILE = 'errNotFile';
	const ERROR_PERM_DENIED = 'errPerm';
	const ERROR_LOCKED = 'errLocked';
	const ERROR_EXISTS = 'errExists';
	const ERROR_INVALID_NAME = 'errInvName';
	const ERROR_MKDIR = 'errMkdir';
	const ERROR_MKFILE = 'errMkfile';
	const ERROR_RENAME = 'errRename';
	const ERROR_COPY = 'errCopy';
	const ERROR_MOVE = 'errMove';
	const ERROR_COPY_FROM = 'errCopyFrom';
	const ERROR_COPY_TO = 'errCopyTo';
	const ERROR_COPY_ITSELF = 'errCopyInItself';
	const ERROR_REPLACE = 'errReplace';
	const ERROR_RM = 'errRm';
	const ERROR_RM_SRC = 'errRmSrc';
	const ERROR_UPLOAD = 'errUpload';
	const ERROR_UPLOAD_FILE = 'errUploadFile';
	const ERROR_UPLOAD_NO_FILES = 'errUploadNoFiles';
	const ERROR_UPLOAD_TOTAL_SIZE = 'errUploadTotalSize';
	const ERROR_UPLOAD_FILE_SIZE = 'errUploadFileSize';
	const ERROR_UPLOAD_FILE_MIME = 'errUploadMime';
	const ERROR_UPLOAD_TRANSFER = 'errUploadTransfer';
	const ERROR_NOT_REPLACE = 'errNotReplace';
	const ERROR_SAVE = 'errSave';
	const ERROR_EXTRACT = 'errExtract';
	const ERROR_ARCHIVE = 'errArchive';
	const ERROR_NOT_ARCHIVE = 'errNoArchive';
	const ERROR_ARCHIVE_TYPE = 'errArcType';
	const ERROR_ARC_SYMLINKS = 'errArcSymlinks';
	const ERROR_ARC_MAXSIZE = 'errArcMaxSize';
	const ERROR_RESIZE = 'errResize';
	const ERROR_UNSUPPORT_TYPE = 'errUsupportType';
	const ERROR_NOT_UTF8_CONTENT = 'errNotUTF8Content';
	
	public function __construct($opts)
	{
		
		$this->time  = $this->utime();
		$this->debug = (isset($opts['debug']) && $opts['debug'] ? true : false);
		
		setlocale(LC_ALL, !empty($opts['locale']) ? $opts['locale'] : 'en_US.UTF-8');
		
		if (!empty($opts['bind']) && is_array($opts['bind'])) {
			foreach ($opts['bind'] as $cmd => $handler) {
				$this->bind($cmd, $handler);
			}
		}
		
		if (isset($opts['roots']) && is_array($opts['roots'])) {
			
			foreach ($opts['roots'] as $i => $o) {
				$class = 'fileManagerVolume' . (isset($o['driver']) ? $o['driver'] : '');
				
				if (class_exists($class)) {
					$volume = new $class();
					
					if ($volume->mount($o)) {
						$id = $volume->id();
						
						$this->volumes[$id] = $volume;
						if (!$this->default && $volume->isReadable()) {
							$this->default = $this->volumes[$id];
						}
					} else {
						$this->mountErrors[] = 'Driver "' . $class . '" : ' . implode(' ', $volume->error());
					}
				} else {
					$this->mountErrors[] = 'Driver "' . $class . '" does not exists';
				}
			}
		}
		$this->loaded = !empty($this->default);
	}
	
	public function loaded()
	{
		return $this->loaded;
	}
	
	public function version()
	{
		return $this->version;
	}
	
	public function bind($cmd, $handler)
	{
		$cmds = array_map('trim', explode(' ', $cmd));
		
		foreach ($cmds as $cmd) {
			if ($cmd) {
				if (!isset($this->listeners[$cmd])) {
					$this->listeners[$cmd] = array();
				}
				
				if ((is_array($handler) && count($handler) == 2 && is_object($handler[0]) && method_exists($handler[0], $handler[1])) || function_exists($handler)) {
					$this->listeners[$cmd][] = $handler;
				}
			}
		}
		
		return $this;
	}
	
	public function unbind($cmd, $handler)
	{
		if (!empty($this->listeners[$cmd])) {
			foreach ($this->listeners[$cmd] as $i => $h) {
				if ($h === $handler) {
					unset($this->listeners[$cmd][$i]);
					return $this;
				}
			}
		}
		return $this;
	}
	
	public function commandExists($cmd)
	{
		return $this->loaded && isset($this->commands[$cmd]) && method_exists($this, $cmd);
	}
	
	public function commandArgsList($cmd)
	{
		return $this->commandExists($cmd) ? $this->commands[$cmd] : array();
	}
	
	public function exec($cmd, $args)
	{
		
		if (!$this->loaded) {
			return array(
				'error' => $this->error(self::ERROR_CONF, self::ERROR_CONF_NO_VOL)
			);
		}
		
		if (!$this->commandExists($cmd)) {
			return array(
				'error' => $this->error(self::ERROR_UNKNOWN_CMD)
			);
		}
		
		if (!empty($args['mimes']) && is_array($args['mimes'])) {
			foreach ($this->volumes as $id => $v) {
				$this->volumes[$id]->setMimesFilter($args['mimes']);
			}
		}
		
		$result = $this->$cmd($args);
		
		if (isset($result['removed'])) {
			foreach ($this->volumes as $volume) {
				$result['removed'] = array_merge($result['removed'], $volume->removed());
				$volume->resetRemoved();
			}
		}
		
		if (!empty($this->listeners[$cmd])) {
			foreach ($this->listeners[$cmd] as $handler) {
				if ((is_array($handler) && $handler[0]->{$handler[1]}($cmd, $result, $args, $this)) || (!is_array($handler) && $handler($cmd, $result, $args, $this))) {
					$result['sync'] = true;
				}
			}
		}
		
		if (!empty($result['removed'])) {
			$removed = array();
			foreach ($result['removed'] as $file) {
				$removed[] = $file['hash'];
			}
			$result['removed'] = array_unique($removed);
		}
		if (!empty($result['added'])) {
			$result['added'] = $this->filter($result['added']);
		}
		if (!empty($result['changed'])) {
			$result['changed'] = $this->filter($result['changed']);
		}
		
		if ($this->debug || !empty($args['debug'])) {
			$result['debug'] = array(
				'connector' => 'php',
				'phpver' => PHP_VERSION,
				'time' => $this->utime() - $this->time,
				'memory' => (function_exists('memory_get_peak_usage') ? ceil(memory_get_peak_usage() / 1024) . 'Kb / ' : '') . ceil(memory_get_usage() / 1024) . 'Kb / ' . ini_get('memory_limit'),
				'upload' => $this->uploadDebug,
				'volumes' => array(),
				'mountErrors' => $this->mountErrors
			);
			
			foreach ($this->volumes as $id => $volume) {
				$result['debug']['volumes'][] = $volume->debug();
			}
		}
		
		foreach ($this->volumes as $volume) {
			$volume->umount();
		}
		
		return $result;
	}
	
	public function realpath($hash)
	{
		if (($volume = $this->volume($hash)) == false) {
			return false;
		}
		return $volume->realpath($hash);
	}
	
	
	public function error()
	{
		$errors = array();
		
		foreach (func_get_args() as $msg) {
			if (is_array($msg)) {
				$errors = array_merge($errors, $msg);
			} else {
				$errors[] = $msg;
			}
		}
		
		return count($errors) ? $errors : array(
			self::ERROR_UNKNOWN
		);
	}
	
	protected function open($args)
	{
		$target = $args['target'];
		$init   = !empty($args['init']);
		$tree   = !empty($args['tree']);
		$volume = $this->volume($target);
		$cwd    = $volume ? $volume->dir($target, true) : false;
		$hash   = $init ? 'default folder' : '#' . $target;
		
		if ((!$cwd || !$cwd['read']) && $init) {
			$volume = $this->default;
			$cwd    = $volume->dir($volume->defaultPath(), true);
		}
		
		if (!$cwd) {
			return array(
				'error' => $this->error(self::ERROR_OPEN, $hash, self::ERROR_DIR_NOT_FOUND)
			);
		}
		if (!$cwd['read']) {
			return array(
				'error' => $this->error(self::ERROR_OPEN, $hash, self::ERROR_PERM_DENIED)
			);
		}
		
		$files = array();
		
		if ($args['tree']) {
			foreach ($this->volumes as $id => $v) {
				
				if (($tree = $v->tree('', 0, $cwd['hash'])) != false) {
					$files = array_merge($files, $tree);
				}
			}
		}
		
		if (($ls = $volume->scandir($cwd['hash'])) === false) {
			return array(
				'error' => $this->error(self::ERROR_OPEN, $cwd['name'], $volume->error())
			);
		}
		
		foreach ($ls as $file) {
			if ($file['name']!=basename(__FILE__) && !in_array($file, $files)) {
				$files[] = $file;
			}
		}
		
		$result = array(
			'cwd' => $cwd,
			'options' => $volume->options($cwd['hash']),
			'files' => $files
		);
		
		if (!empty($args['init'])) {
			$result['api']        = $this->version;
			$result['uplMaxSize'] = ini_get('upload_max_filesize');
		}
		
		return $result;
	}
	
	protected function ls($args)
	{
		$target = $args['target'];
		
		if (($volume = $this->volume($target)) == false || ($list = $volume->ls($target)) === false) {
			return array(
				'error' => $this->error(self::ERROR_OPEN, '#' . $target)
			);
		}
		return array(
			'list' => $list
		);
	}
	
	protected function tree($args)
	{
		$target = $args['target'];
		
		if (($volume = $this->volume($target)) == false || ($tree = $volume->tree($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_OPEN, '#' . $target)
			);
		}
		
		return array(
			'tree' => $tree
		);
	}
	
	protected function parents($args)
	{
		$target = $args['target'];
		
		if (($volume = $this->volume($target)) == false || ($tree = $volume->parents($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_OPEN, '#' . $target)
			);
		}
		
		return array(
			'tree' => $tree
		);
	}
	
	protected function tmb($args)
	{
		
		$result  = array(
			'images' => array()
		);
		$targets = $args['targets'];
		
		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) != false && (($tmb = $volume->tmb($target)) != false)) {
				$result['images'][$target] = $tmb;
			}
		}
		return $result;
	}
	
	protected function file($args)
	{
		$target   = $args['target'];
		$download = !empty($args['download']);
		$h403     = 'HTTP/1.x 403 Access Denied';
		$h404     = 'HTTP/1.x 404 Not Found';
		
		if (($volume = $this->volume($target)) == false) {
			return array(
				'error' => 'File not found',
				'header' => $h404,
				'raw' => true
			);
		}
		
		if (($file = $volume->file($target)) == false) {
			return array(
				'error' => 'File not found',
				'header' => $h404,
				'raw' => true
			);
		}
		
		if (!$file['read']) {
			return array(
				'error' => 'Access denied',
				'header' => $h403,
				'raw' => true
			);
		}
		
		if (($fp = $volume->open($target)) == false) {
			return array(
				'error' => 'File not found',
				'header' => $h404,
				'raw' => true
			);
		}
		
		if ($download) {
			$disp = 'attachment';
			$mime = 'application/octet-stream';
		} else {
			$disp = preg_match('/^(image|text)/i', $file['mime']) || $file['mime'] == 'application/x-shockwave-flash' ? 'inline' : 'attachment';
			$mime = $file['mime'];
		}
		
		$filenameEncoded = rawurlencode($file['name']);
		if (strpos($filenameEncoded, '%') === false) {
			$filename = 'filename="' . $file['name'] . '"';
		} else {
			$ua = $_SERVER["HTTP_USER_AGENT"];
			if (preg_match('/MSIE [4-8]/', $ua)) {
				$filename = 'filename="' . $filenameEncoded . '"';
			} else {
				$filename = 'filename*=UTF-8\'\'' . $filenameEncoded;
			}
		}
		
		$result = array(
			'volume' => $volume,
			'pointer' => $fp,
			'info' => $file,
			'header' => array(
				'Content-Type: ' . $mime,
				'Content-Disposition: ' . $disp . '; ' . $filename,
				'Content-Location: ' . $file['name'],
				'Content-Transfer-Encoding: binary',
				'Content-Length: ' . $file['size'],
				'Connection: close'
			)
		);
		return $result;
	}
	
	protected function size($args)
	{
		$size = 0;
		
		foreach ($args['targets'] as $target) {
			if (($volume = $this->volume($target)) == false || ($file = $volume->file($target)) == false || !$file['read']) {
				return array(
					'error' => $this->error(self::ERROR_OPEN, '#' . $target)
				);
			}
			
			$size += $volume->size($target);
		}
		return array(
			'size' => $size
		);
	}
	
	protected function mkdir($args)
	{
		$target = $args['target'];
		$name   = $args['name'];
		
		if (($volume = $this->volume($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_MKDIR, $name, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target)
			);
		}
		
		return ($dir = $volume->mkdir($target, $name)) == false ? array(
			'error' => $this->error(self::ERROR_MKDIR, $name, $volume->error())
		) : array(
			'added' => array(
				$dir
			)
		);
	}
	
	protected function mkfile($args)
	{
		$target = $args['target'];
		$name   = $args['name'];
		
		if (($volume = $this->volume($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_MKFILE, $name, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target)
			);
		}
		
		return ($file = $volume->mkfile($target, $args['name'])) == false ? array(
			'error' => $this->error(self::ERROR_MKFILE, $name, $volume->error())
		) : array(
			'added' => array(
				$file
			)
		);
	}
	
	protected function rename($args)
	{
		$target = $args['target'];
		$name   = $args['name'];
		
		if (($volume = $this->volume($target)) == false || ($rm = $volume->file($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_RENAME, '#' . $target, self::ERROR_FILE_NOT_FOUND)
			);
		}
		$rm['realpath'] = $volume->realpath($target);
		
		return ($file = $volume->rename($target, $name)) == false ? array(
			'error' => $this->error(self::ERROR_RENAME, $rm['name'], $volume->error())
		) : array(
			'added' => array(
				$file
			),
			'removed' => array(
				$rm
			)
		);
	}
	
	protected function duplicate($args)
	{
		$targets = is_array($args['targets']) ? $args['targets'] : array();
		$result  = array(
			'added' => array()
		);
		$suffix  = empty($args['suffix']) ? 'copy' : $args['suffix'];
		
		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) == false || ($src = $volume->file($target)) == false) {
				$result['warning'] = $this->error(self::ERROR_COPY, '#' . $target, self::ERROR_FILE_NOT_FOUND);
				break;
			}
			
			if (($file = $volume->duplicate($target, $suffix)) == false) {
				$result['warning'] = $this->error($volume->error());
				break;
			}
			
			$result['added'][] = $file;
		}
		
		return $result;
	}
	
	protected function rm($args)
	{
		$targets = is_array($args['targets']) ? $args['targets'] : array();
		$result  = array(
			'removed' => array()
		);
		
		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) == false) {
				$result['warning'] = $this->error(self::ERROR_RM, '#' . $target, self::ERROR_FILE_NOT_FOUND);
				return $result;
			}
			if (!$volume->rm($target)) {
				$result['warning'] = $this->error($volume->error());
				return $result;
			}
		}
		
		return $result;
	}
	
	protected function upload($args)
	{
		$target = $args['target'];
		$volume = $this->volume($target);
		$files  = isset($args['FILES']['upload']) && is_array($args['FILES']['upload']) ? $args['FILES']['upload'] : array();
		$result = array(
			'added' => array(),
			'header' => empty($args['html']) ? false : 'Content-Type: text/html; charset=utf-8'
		);
		
		if (empty($files)) {
			return array(
				'error' => $this->error(self::ERROR_UPLOAD, self::ERROR_UPLOAD_NO_FILES),
				'header' => $header
			);
		}
		
		if (!$volume) {
			return array(
				'error' => $this->error(self::ERROR_UPLOAD, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target),
				'header' => $header
			);
		}
		
		foreach ($files['name'] as $i => $name) {
			if (($error = $files['error'][$i]) > 0) {
				$result['warning'] = $this->error(self::ERROR_UPLOAD_FILE, $name, $error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE ? self::ERROR_UPLOAD_FILE_SIZE : self::ERROR_UPLOAD_TRANSFER);
				$this->uploadDebug = 'Upload error code: ' . $error;
				break;
			}
			
			$tmpname = $files['tmp_name'][$i];
			
			if (($fp = fopen($tmpname, 'rb')) == false) {
				$result['warning'] = $this->error(self::ERROR_UPLOAD_FILE, $name, self::ERROR_UPLOAD_TRANSFER);
				$this->uploadDebug = 'Upload error: unable open tmp file';
				break;
			}
			
			if (($file = $volume->upload($fp, $target, $name, $tmpname)) === false) {
				$result['warning'] = $this->error(self::ERROR_UPLOAD_FILE, $name, $volume->error());
				fclose($fp);
				break;
			}
			
			fclose($fp);
			$result['added'][] = $file;
		}
		
		return $result;
	}
	
	protected function paste($args)
	{
		$dst     = $args['dst'];
		$targets = is_array($args['targets']) ? $args['targets'] : array();
		$cut     = !empty($args['cut']);
		$error   = $cut ? self::ERROR_MOVE : self::ERROR_COPY;
		$result  = array(
			'added' => array(),
			'removed' => array()
		);
		
		if (($dstVolume = $this->volume($dst)) == false) {
			return array(
				'error' => $this->error($error, '#' . $targets[0], self::ERROR_TRGDIR_NOT_FOUND, '#' . $dst)
			);
		}
		
		foreach ($targets as $target) {
			if (($srcVolume = $this->volume($target)) == false) {
				$result['warning'] = $this->error($error, '#' . $target, self::ERROR_FILE_NOT_FOUND);
				break;
			}
			
			if (($file = $dstVolume->paste($srcVolume, $target, $dst, $cut)) == false) {
				$result['warning'] = $this->error($dstVolume->error());
				break;
			}
			
			$result['added'][] = $file;
		}
		return $result;
	}
	
	protected function get($args)
	{
		$target = $args['target'];
		$volume = $this->volume($target);
		
		if (!$volume || ($file = $volume->file($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_OPEN, '#' . $target, self::ERROR_FILE_NOT_FOUND)
			);
		}
		
		if (($content = $volume->getContents($target)) === false) {
			return array(
				'error' => $this->error(self::ERROR_OPEN, $volume->path($target), $volume->error())
			);
		}
		
		$json = json_encode($content);
		
		if ($json == 'null' && strlen($json) < strlen($content)) {
			return array(
				'error' => $this->error(self::ERROR_NOT_UTF8_CONTENT, $volume->path($target))
			);
		}
		
		return array(
			'content' => $content
		);
	}
	
	protected function put($args)
	{
		$target = $args['target'];
		
		if (($volume = $this->volume($target)) == false || ($file = $volume->file($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_SAVE, '#' . $target, self::ERROR_FILE_NOT_FOUND)
			);
		}
		
		if (($file = $volume->putContents($target, $args['content'])) == false) {
			return array(
				'error' => $this->error(self::ERROR_SAVE, $volume->path($target), $volume->error())
			);
		}
		
		return array(
			'changed' => array(
				$file
			)
		);
	}
	
	protected function extract($args)
	{
		$target = $args['target'];
		$mimes  = !empty($args['mimes']) && is_array($args['mimes']) ? $args['mimes'] : array();
		$error  = array(
			self::ERROR_EXTRACT,
			'#' . $target
		);
		
		if (($volume = $this->volume($target)) == false || ($file = $volume->file($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_EXTRACT, '#' . $target, self::ERROR_FILE_NOT_FOUND)
			);
		}
		
		return ($file = $volume->extract($target)) ? array(
			'added' => array(
				$file
			)
		) : array(
			'error' => $this->error(self::ERROR_EXTRACT, $volume->path($target), $volume->error())
		);
	}
	
	protected function archive($args)
	{
		$type    = $args['type'];
		$targets = isset($args['targets']) && is_array($args['targets']) ? $args['targets'] : array();
		
		if (($volume = $this->volume($targets[0])) == false) {
			return $this->error(self::ERROR_ARCHIVE, self::ERROR_TRGDIR_NOT_FOUND);
		}
		
		return ($file = $volume->archive($targets, $args['type'])) ? array(
			'added' => array(
				$file
			)
		) : array(
			'error' => $this->error(self::ERROR_ARCHIVE, $volume->error())
		);
	}
	
	protected function search($args)
	{
		$q      = trim($args['q']);
		$mimes  = !empty($args['mimes']) && is_array($args['mimes']) ? $args['mimes'] : array();
		$result = array();
		
		foreach ($this->volumes as $volume) {
			$result = array_merge($result, $volume->search($q, $mimes));
		}
		
		return array(
			'files' => $result
		);
	}
	
	protected function info($args)
	{
		$files = array();
		
		foreach ($args['targets'] as $hash) {
			if (($volume = $this->volume($hash)) != false && ($info = $volume->file($hash)) != false) {
				$files[] = $info;
			}
		}
		
		return array(
			'files' => $files
		);
	}
	
	protected function dim($args)
	{
		$target = $args['target'];
		
		if (($volume = $this->volume($target)) != false) {
			$dim = $volume->dimensions($target);
			return $dim ? array(
				'dim' => $dim
			) : array();
		}
		return array();
	}
	
	protected function resize($args)
	{
		$target = $args['target'];
		$width  = $args['width'];
		$height = $args['height'];
		$x      = (int) $args['x'];
		$y      = (int) $args['y'];
		$mode   = $args['mode'];
		$bg     = null;
		$degree = (int) $args['degree'];
		
		if (($volume = $this->volume($target)) == false || ($file = $volume->file($target)) == false) {
			return array(
				'error' => $this->error(self::ERROR_RESIZE, '#' . $target, self::ERROR_FILE_NOT_FOUND)
			);
		}
		
		return ($file = $volume->resize($target, $width, $height, $x, $y, $mode, $bg, $degree)) ? array(
			'changed' => array(
				$file
			)
		) : array(
			'error' => $this->error(self::ERROR_RESIZE, $volume->path($target), $volume->error())
		);
	}
	
	
	protected function volume($hash)
	{
		foreach ($this->volumes as $id => $v) {
			if (strpos('' . $hash, $id) === 0) {
				return $this->volumes[$id];
			}
		}
		return false;
	}
	
	protected function toArray($data)
	{
		return isset($data['hash']) || !is_array($data) ? array(
			$data
		) : $data;
	}
	
	protected function hashes($files)
	{
		$ret = array();
		foreach ($files as $file) {
			$ret[] = $file['hash'];
		}
		return $ret;
	}
	
	protected function filter($files)
	{
		foreach ($files as $i => $file) {
			if (!empty($file['hidden']) || !$this->default->mimeAccepted($file['mime'])) {
				unset($files[$i]);
			}
		}
		return array_merge($files, array());
	}
	
	protected function utime()
	{
		$time = explode(" ", microtime());
		return (double) $time[1] + (double) $time[0];
	}
	
}


/**
 * Default fileManager connector
 *
 * @author Dmitry (dio) Levashov
 **/
class fileManagerConnector
{
	protected $fileManager;
	
	protected $options = array();
	
	protected $header = 'Content-Type: application/json';
	
	
	public function __construct($fileManager, $debug = false)
	{
		
		$this->fileManager = $fileManager;
		if ($debug) {
			$this->header = 'Content-Type: text/html; charset=utf-8';
		}
	}
	
	public function run()
	{
		$isPost = $_SERVER["REQUEST_METHOD"] == 'POST';
		$src    = $_SERVER["REQUEST_METHOD"] == 'POST' ? $_POST : $_GET;
		$cmd    = isset($src['cmd']) ? $src['cmd'] : '';
		$args   = array();
		
		if (!function_exists('json_encode')) {
			$error = $this->fileManager->error(fileManager::ERROR_CONF, fileManager::ERROR_CONF_NO_JSON);
			$this->output(array(
				'error' => '{"error":["' . implode('","', $error) . '"]}',
				'raw' => true
			));
		}
		
		if (!$this->fileManager->loaded()) {
			$this->output(array(
				'error' => $this->fileManager->error(fileManager::ERROR_CONF, fileManager::ERROR_CONF_NO_VOL),
				'debug' => $this->fileManager->mountErrors
			));
		}
		
		if (!$cmd && $isPost) {
			$this->output(array(
				'error' => $this->fileManager->error(fileManager::ERROR_UPLOAD, fileManager::ERROR_UPLOAD_TOTAL_SIZE),
				'header' => 'Content-Type: text/html'
			));
		}
		
		if (!$this->fileManager->commandExists($cmd)) {
			$this->output(array(
				'error' => $this->fileManager->error(fileManager::ERROR_UNKNOWN_CMD)
			));
		}
		
		foreach ($this->fileManager->commandArgsList($cmd) as $name => $req) {
			$arg = $name == 'FILES' ? $_FILES : (isset($src[$name]) ? $src[$name] : '');
			
			if (!is_array($arg)) {
				$arg = trim($arg);
			}
			if ($req && (!isset($arg) || $arg === '')) {
				$this->output(array(
					'error' => $this->fileManager->error(fileManager::ERROR_INV_PARAMS, $cmd)
				));
			}
			$args[$name] = $arg;
		}
		
		$args['debug'] = isset($src['debug']) ? !!$src['debug'] : false;
		
		$this->output($this->fileManager->exec($cmd, $args));
	}
	
	protected function output(array $data)
	{
		$header = isset($data['header']) ? $data['header'] : $this->header;
		unset($data['header']);
		if ($header) {
			if (is_array($header)) {
				foreach ($header as $h) {
					header($h);
				}
			} else {
				header($header);
			}
		}
		
		if (isset($data['pointer'])) {
			rewind($data['pointer']);
			fpassthru($data['pointer']);
			if (!empty($data['volume'])) {
				$data['volume']->close($data['pointer'], $data['info']['hash']);
			}
			exit();
		} else {
			if (!empty($data['raw']) && !empty($data['error'])) {
				exit($data['error']);
			} else {
				exit(json_encode($data));
			}
		}
		
	}
	
}

/**
 * Base class for fileManager volume.
 * Provide 2 layers:
 *  1. Public API (commands)
 *  2. abstract fs API
 *
 * All abstract methods begin with "_"
 *
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 * @author Alexey Sukhotin
 **/
abstract class fileManagerVolumeDriver
{
	
	protected $driverId = 'a';
	
	protected $id = '';
	
	protected $mounted = false;
	
	protected $root = '';
	
	protected $rootName = '';
	
	protected $startPath = '';
	
	protected $URL = '';
	
	protected $tmbPath = '';
	
	protected $tmbPathWritable = false;
	
	protected $tmbURL = '';
	
	protected $tmbSize = 48;
	
	protected $imgLib = 'auto';
	
	protected $cryptLib = '';
	
	protected $archivers = array('create' => array(), 'extract' => array());
	
	protected $treeDeep = 1;
	
	protected $error = array();
	
	protected $today = 0;
	
	protected $yesterday = 0;
	
	protected $options = array('id' => '', 'path' => '', 'startPath' => '', 'treeDeep' => 1, 'URL' => '', 'separator' => DIRECTORY_SEPARATOR, 'cryptLib' => '', 'mimeDetect' => 'auto', 'mimefile' => '', 'tmbPath' => '.tmb', 'tmbPathMode' => 0777, 'tmbURL' => '', 'tmbSize' => 48, 'tmbCrop' => true, 'tmbBgColor' => '#ffffff', 'imgLib' => 'auto', 'copyOverwrite' => true, 'copyJoin' => true, 'uploadOverwrite' => true, 'uploadAllow' => array(), 'uploadDeny' => array(), 'uploadOrder' => array('deny', 'allow'), 'uploadMaxSize' => 0, 'dateFormat' => 'j M Y H:i', 'timeFormat' => 'H:i', 'checkSubfolders' => true, 'copyFrom' => true, 'copyTo' => true, 'disabled' => array(), 'acceptedName' => '/^\w[\w\s\.\%\-\(\)\[\]]*$/u', 'accessControl' => null, 'accessControlData' => null, 'defaults' => array('read' => true, 'write' => true), 'attributes' => array(), 'archiveMimes' => array(), 'archivers' => array(), 'utf8fix' => false, 'utf8patterns' => array("\u0438\u0306", "\u0435\u0308", "\u0418\u0306", "\u0415\u0308", "\u00d8A", "\u030a"), 'utf8replace' => array("\u0439", "\u0451", "\u0419", "\u0401", "\u00d8", "\u00c5"));
	
	protected $defaults = array('read' => true, 'write' => true, 'locked' => false, 'hidden' => false);
	
	protected $attributes = array();
	
	protected $access = null;
	
	protected $uploadAllow = array();
	
	protected $uploadDeny = array();
	
	protected $uploadOrder = array();
	
	protected $uploadMaxSize = 0;
	
	protected $mimeDetect = 'auto';
	
	private static $mimetypesLoaded = false;
	
	protected $finfo = null;
	
	protected $diabled = array();
	
	protected static $mimetypes = array(
		// applications
		'ai'		=> 'application/postscript',
		'eps'		=> 'application/postscript',
		'exe'		=> 'application/x-executable',
		'doc'		=> 'application/vnd.ms-word',
		'xls'		=> 'application/vnd.ms-excel',
		'ppt'		=> 'application/vnd.ms-powerpoint',
		'pps'		=> 'application/vnd.ms-powerpoint',
		'pdf'		=> 'application/pdf',
		'odt'		=> 'application/vnd.oasis.opendocument.text',
		'swf'		=> 'application/x-shockwave-flash',
		'torrent'	=> 'application/x-bittorrent',
		'jar'		=> 'application/x-jar',
		// archives
		'gz'		=> 'application/x-gzip',
		'tgz'		=> 'application/x-gzip',
		'bz'		=> 'application/x-bzip2',
		'bz2'		=> 'application/x-bzip2',
		'tbz'		=> 'application/x-bzip2',
		'zip'		=> 'application/zip',
		'rar'		=> 'application/x-rar',
		'tar'		=> 'application/x-tar',
		'7z'		=> 'application/x-7z-compressed',
		// texts
		'txt'		=> 'text/plain',
		'php'		=> 'text/x-php',
		'html'		=> 'text/html',
		'htm'		=> 'text/html',
		'js'		=> 'text/javascript',
		'json'		=> 'text/json',
		'css'		=> 'text/css',
		'rtf'		=> 'text/rtf',
		'rtfd'		=> 'text/rtfd',
		'py'		=> 'text/x-python',
		'java'		=> 'text/x-java-source',
		'rb'		=> 'text/x-ruby',
		'sh'		=> 'text/x-shellscript',
		'pl'		=> 'text/x-perl',
		'xml'		=> 'text/xml',
		'sql'		=> 'text/x-sql',
		'c'			=> 'text/x-csrc',
		'h'			=> 'text/x-chdr',
		'cpp'		=> 'text/x-c++src',
		'hh'		=> 'text/x-c++hdr',
		'log'		=> 'text/plain',
		'csv'		=> 'text/x-comma-separated-values',
		'htaccess'	=> 'text/x-apache',
		// images
		'bmp'		=> 'image/x-ms-bmp',
		'jpg'		=> 'image/jpeg',
		'jpeg'		=> 'image/jpeg',
		'gif'		=> 'image/gif',
		'png'		=> 'image/png',
		'tif'		=> 'image/tiff',
		'tiff'		=> 'image/tiff',
		'tga'		=> 'image/x-targa',
		'psd'		=> 'image/vnd.adobe.photoshop',
		'ai'		=> 'image/vnd.adobe.photoshop',
		'xbm'		=> 'image/xbm',
		'pxm'		=> 'image/pxm',
		//audio
		'mp3'		=> 'audio/mpeg',
		'mid'		=> 'audio/midi',
		'ogg'		=> 'audio/ogg',
		'oga'		=> 'audio/ogg',
		'm4a'		=> 'audio/x-m4a',
		'wav'		=> 'audio/wav',
		'wma'		=> 'audio/x-ms-wma',
		// video
		'avi'		=> 'video/x-msvideo',
		'dv'		=> 'video/x-dv',
		'mp4'		=> 'video/mp4',
		'mpeg'		=> 'video/mpeg',
		'mpg'		=> 'video/mpeg',
		'mov'		=> 'video/quicktime',
		'wm'		=> 'video/x-ms-wmv',
		'flv'		=> 'video/x-flv',
		'mkv'		=> 'video/x-matroska',
		'webm'		=> 'video/webm',
		'ogv'		=> 'video/ogg',
		'ogm'		=> 'video/ogg'
	);
	
	protected $separator = DIRECTORY_SEPARATOR;
	
	protected $onlyMimes = array();
	
	protected $removed = array();
	
	protected $cache = array();
	
	protected $dirsCache = array();
	
	
	protected function init()
	{
		return true;
	}
	
	protected function configure()
	{
		$path = $this->options['tmbPath'];
		if ($path) {
			if (!file_exists($path)) {
				if (@mkdir($path)) {
					chmod($path, $this->options['tmbPathMode']);
				} else {
					$path = '';
				}
			}
			
			if (is_dir($path) && is_readable($path)) {
				$this->tmbPath         = $path;
				$this->tmbPathWritable = is_writable($path);
			}
		}
		
		$type = preg_match('/^(imagick|gd|auto)$/i', $this->options['imgLib']) ? strtolower($this->options['imgLib']) : 'auto';
		
		if (($type == 'imagick' || $type == 'auto') && extension_loaded('imagick')) {
			$this->imgLib = 'imagick';
		} else {
			$this->imgLib = function_exists('gd_info') ? 'gd' : '';
		}
		
	}
	
	
	
	public function driverId()
	{
		return $this->driverId;
	}
	
	public function id()
	{
		return $this->id;
	}
	
	public function debug()
	{
		return array(
			'id' => $this->id(),
			'name' => strtolower(substr(get_class($this), strlen('fileManagerdriver'))),
			'mimeDetect' => $this->mimeDetect,
			'imgLib' => $this->imgLib
		);
	}
	
	public function mount(array $opts)
	{
		if (!isset($opts['path']) || $opts['path'] === '') {
			return false;
		}
		
		$this->options   = array_merge($this->options, $opts);
		$this->id        = $this->driverId . (!empty($this->options['id']) ? $this->options['id'] : fileManager::$volumesCnt++) . '_';
		$this->root      = $this->_normpath($this->options['path']);
		$this->separator = isset($this->options['separator']) ? $this->options['separator'] : DIRECTORY_SEPARATOR;
		
		$this->defaults = array(
			'read' => isset($this->options['defaults']['read']) ? !!$this->options['defaults']['read'] : true,
			'write' => isset($this->options['defaults']['write']) ? !!$this->options['defaults']['write'] : true,
			'locked' => false,
			'hidden' => false
		);
		
		$this->attributes[] = array(
			'pattern' => '~^' . preg_quote(DIRECTORY_SEPARATOR) . '$~',
			'locked' => true,
			'hidden' => false
		);
		if (!empty($this->options['attributes']) && is_array($this->options['attributes'])) {
			
			foreach ($this->options['attributes'] as $a) {
				if (!empty($a['pattern']) || count($a) > 1) {
					$this->attributes[] = $a;
				}
			}
		}
		
		if (!empty($this->options['accessControl'])) {
			if (is_string($this->options['accessControl']) && function_exists($this->options['accessControl'])) {
				$this->access = $this->options['accessControl'];
			} elseif (is_array($this->options['accessControl']) && count($this->options['accessControl']) > 1 && is_object($this->options['accessControl'][0]) && method_exists($this->options['accessControl'][0], $this->options['accessControl'][1])) {
				$this->access = array(
					$this->options['accessControl'][0],
					$this->options['accessControl'][1]
				);
			}
		}
		
		$this->today     = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		$this->yesterday = $this->today - 86400;
		
		if (!$this->init()) {
			return false;
		}
		
		$this->uploadAllow = isset($this->options['uploadAllow']) && is_array($this->options['uploadAllow']) ? $this->options['uploadAllow'] : array();
		
		$this->uploadDeny = isset($this->options['uploadDeny']) && is_array($this->options['uploadDeny']) ? $this->options['uploadDeny'] : array();
		
		if (is_string($this->options['uploadOrder'])) {
			$parts             = explode(',', isset($this->options['uploadOrder']) ? $this->options['uploadOrder'] : 'deny,allow');
			$this->uploadOrder = array(
				trim($parts[0]),
				trim($parts[1])
			);
		} else {
			$this->uploadOrder = $this->options['uploadOrder'];
		}
		
		if (!empty($this->options['uploadMaxSize'])) {
			$size = '' . $this->options['uploadMaxSize'];
			$unit = strtolower(substr($size, strlen($size) - 1));
			$n    = 1;
			switch ($unit) {
				case 'k':
					$n = 1024;
					break;
				case 'm':
					$n = 1048576;
					break;
				case 'g':
					$n = 1073741824;
			}
			$this->uploadMaxSize = intval($size) * $n;
		}
		
		$this->disabled = isset($this->options['disabled']) && is_array($this->options['disabled']) ? $this->options['disabled'] : array();
		
		$this->cryptLib   = $this->options['cryptLib'];
		$this->mimeDetect = $this->options['mimeDetect'];
		
		$type   = strtolower($this->options['mimeDetect']);
		$type   = preg_match('/^(finfo|mime_content_type|internal|auto)$/i', $type) ? $type : 'auto';
		$regexp = '/text\/x\-(php|c\+\+)/';
		
		if (($type == 'finfo' || $type == 'auto') && class_exists('finfo') && preg_match($regexp, array_shift(explode(';', @finfo_file(finfo_open(FILEINFO_MIME), __FILE__))))) {
			$type        = 'finfo';
			$this->finfo = finfo_open(FILEINFO_MIME);
		} elseif (($type == 'mime_content_type' || $type == 'auto') && function_exists('mime_content_type') && preg_match($regexp, array_shift(explode(';', mime_content_type(__FILE__))))) {
			$type = 'mime_content_type';
		} else {
			$type = 'internal';
		}
		$this->mimeDetect = $type;
		
		if ($this->mimeDetect == 'internal' && !self::$mimetypesLoaded) {
			self::$mimetypesLoaded = true;
			$this->mimeDetect      = 'internal';
			$file	  = false;
			if (!empty($this->options['mimefile']) && file_exists($this->options['mimefile'])) {
				$file = $this->options['mimefile'];
			} elseif (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mime.types')) {
				$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mime.types';
			} elseif (file_exists(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'mime.types')) {
				$file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'mime.types';
			}
			
			if ($file && file_exists($file)) {
				$mimecf = file($file);
				
				foreach ($mimecf as $line_num => $line) {
					if (!preg_match('/^\s*#/', $line)) {
						$mime = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
						for ($i = 1, $size = count($mime); $i < $size; $i++) {
							if (!isset(self::$mimetypes[$mime[$i]])) {
								self::$mimetypes[$mime[$i]] = $mime[0];
							}
						}
					}
				}
			}
		}
		
		$this->rootName = empty($this->options['alias']) ? $this->_basename($this->root) : $this->options['alias'];
		$root           = $this->stat($this->root);
		
		if (!$root) {
			return $this->setError('Root folder does not exists.');
		}
		if (!$root['read'] && !$root['write']) {
			return $this->setError('Root folder has not read and write permissions.');
		}
		
		
		if ($root['read']) {
			if ($this->options['startPath']) {
				$start = $this->stat($this->options['startPath']);
				if (!empty($start) && $start['mime'] == 'directory' && $start['read'] && empty($start['hidden']) && $this->_inpath($this->options['startPath'], $this->root)) {
					$this->startPath = $this->options['startPath'];
					if (substr($this->startPath, -1, 1) == $this->options['separator']) {
						$this->startPath = substr($this->startPath, 0, -1);
					}
				}
			}
		} else {
			$this->options['URL']     = '';
			$this->options['tmbURL']  = '';
			$this->options['tmbPath'] = '';
			array_unshift($this->attributes, array(
				'pattern' => '/.*/',
				'read' => false
			));
		}
		$this->treeDeep = $this->options['treeDeep'] > 0 ? (int) $this->options['treeDeep'] : 1;
		$this->tmbSize  = $this->options['tmbSize'] > 0 ? (int) $this->options['tmbSize'] : 48;
		$this->URL      = $this->options['URL'];
		if ($this->URL && preg_match("|[^/?&=]$|", $this->URL)) {
			$this->URL .= '/';
		}
		
		$this->tmbURL = !empty($this->options['tmbURL']) ? $this->options['tmbURL'] : '';
		if ($this->tmbURL && preg_match("|[^/?&=]$|", $this->tmbURL)) {
			$this->tmbURL .= '/';
		}
		
		$this->nameValidator = is_string($this->options['acceptedName']) && !empty($this->options['acceptedName']) ? $this->options['acceptedName'] : '';
		
		$this->_checkArchivers();
		if (!empty($this->options['archiveMimes']) && is_array($this->options['archiveMimes'])) {
			foreach ($this->archivers['create'] as $mime => $v) {
				if (!in_array($mime, $this->options['archiveMimes'])) {
					unset($this->archivers['create'][$mime]);
				}
			}
		}
		
		if (!empty($this->options['archivers']['create']) && is_array($this->options['archivers']['create'])) {
			foreach ($this->options['archivers']['create'] as $mime => $conf) {
				if (strpos($mime, 'application/') === 0 && !empty($conf['cmd']) && isset($conf['argc']) && !empty($conf['ext']) && !isset($this->archivers['create'][$mime])) {
					$this->archivers['create'][$mime] = $conf;
				}
			}
		}
		
		if (!empty($this->options['archivers']['extract']) && is_array($this->options['archivers']['extract'])) {
			foreach ($this->options['archivers']['extract'] as $mime => $conf) {
				if (substr($mime, 'application/') === 0 && !empty($cons['cmd']) && isset($conf['argc']) && !empty($conf['ext']) && !isset($this->archivers['extract'][$mime])) {
					$this->archivers['extract'][$mime] = $conf;
				}
			}
		}
		
		$this->configure();
		return $this->mounted = true;
	}
	
	public function umount()
	{
	}
	
	public function error()
	{
		return $this->error;
	}
	
	public function setMimesFilter($mimes)
	{
		if (is_array($mimes)) {
			$this->onlyMimes = $mimes;
		}
	}
	
	public function root()
	{
		return $this->encode($this->root);
	}
	
	public function defaultPath()
	{
		return $this->encode($this->startPath ? $this->startPath : $this->root);
	}
	
	public function options($hash)
	{
		return array(
			'path' => $this->_path($this->decode($hash)),
			'url' => $this->URL,
			'tmbUrl' => $this->tmbURL,
			'disabled' => $this->disabled,
			'separator' => $this->separator,
			'copyOverwrite' => intval($this->options['copyOverwrite']),
			'archivers' => array(
				'create' => array_keys($this->archivers['create']),
				'extract' => array_keys($this->archivers['extract'])
			)
		);
	}
	
	public function commandDisabled($cmd)
	{
		return in_array($cmd, $this->disabled);
	}
	
	public function mimeAccepted($mime, $mimes = array(), $empty = true)
	{
		$mimes = !empty($mimes) ? $mimes : $this->onlyMimes;
		if (empty($mimes)) {
			return $empty;
		}
		return $mime == 'directory' || in_array('all', $mimes) || in_array('All', $mimes) || in_array($mime, $mimes) || in_array(substr($mime, 0, strpos($mime, '/')), $mimes);
	}
	
	public function isReadable()
	{
		$stat = $this->stat($this->root);
		return $stat['read'];
	}
	
	public function copyFromAllowed()
	{
		return !!$this->options['copyFrom'];
	}
	
	public function path($hash)
	{
		return $this->_path($this->decode($hash));
	}
	
	public function realpath($hash)
	{
		$path = $this->decode($hash);
		return $this->stat($path) ? $path : false;
	}
	
	public function removed()
	{
		return $this->removed;
	}
	
	public function resetRemoved()
	{
		$this->removed = array();
	}
	
	public function closest($hash, $attr, $val)
	{
		return ($path = $this->closestByAttr($this->decode($hash), $attr, $val)) ? $this->encode($path) : false;
	}
	
	public function file($hash)
	{
		$path = $this->decode($hash);
		
		return ($file = $this->stat($path)) ? $file : $this->setError(fileManager::ERROR_FILE_NOT_FOUND);
		
		if (($file = $this->stat($path)) != false) {
			if ($realpath) {
				$file['realpath'] = $path;
			}
			return $file;
		}
		return $this->setError(fileManager::ERROR_FILE_NOT_FOUND);
	}
	
	public function dir($hash, $resolveLink = false)
	{
		if (($dir = $this->file($hash)) == false) {
			return $this->setError(fileManager::ERROR_DIR_NOT_FOUND);
		}
		
		if ($resolveLink && !empty($dir['thash'])) {
			$dir = $this->file($dir['thash']);
		}
		
		return $dir && $dir['mime'] == 'directory' && empty($dir['hidden']) ? $dir : $this->setError(fileManager::ERROR_NOT_DIR);
	}
	
	public function scandir($hash)
	{
		if (($dir = $this->dir($hash)) == false) {
			return false;
		}
		
		return $dir['read'] ? $this->getScandir($this->decode($hash)) : $this->setError(fileManager::ERROR_PERM_DENIED);
	}
	
	public function ls($hash)
	{
		if (($dir = $this->dir($hash)) == false || !$dir['read']) {
			return false;
		}
		
		$list = array();
		$path = $this->decode($hash);
		
		foreach ($this->getScandir($path) as $stat) {
			if (empty($stat['hidden']) && $this->mimeAccepted($stat['mime'])) {
				$list[] = $stat['name'];
			}
		}
		
		return $list;
	}
	
	public function tree($hash = '', $deep = 0, $exclude = '')
	{
		$path = $hash ? $this->decode($hash) : $this->root;
		
		if (($dir = $this->stat($path)) == false || $dir['mime'] != 'directory') {
			return false;
		}
		
		$dirs = $this->gettree($path, $deep > 0 ? $deep - 1 : $this->treeDeep - 1, $this->decode($exclude));
		array_unshift($dirs, $dir);
		return $dirs;
	}
	
	public function parents($hash)
	{
		if (($current = $this->dir($hash)) == false) {
			return false;
		}
		
		$path = $this->decode($hash);
		$tree = array();
		
		while ($path && $path != $this->root) {
			$path = $this->_dirname($path);
			$stat = $this->stat($path);
			if (!empty($stat['hidden']) || !$stat['read']) {
				return false;
			}
			
			array_unshift($tree, $stat);
			if ($path != $this->root) {
				foreach ($this->gettree($path, 0) as $dir) {
					if (!in_array($dir, $tree)) {
						$tree[] = $dir;
					}
				}
			}
		}
		
		return $tree ? $tree : array(
			$current
		);
	}
	
	public function tmb($hash)
	{
		$path = $this->decode($hash);
		$stat = $this->stat($path);
		
		if (isset($stat['tmb'])) {
			return $stat['tmb'] == "1" ? $this->createTmb($path, $stat) : $stat['tmb'];
		}
		return false;
	}
	
	public function size($hash)
	{
		return $this->countSize($this->decode($hash));
	}
	
	public function open($hash)
	{
		if (($file = $this->file($hash)) == false || $file['mime'] == 'directory') {
			return false;
		}
		
		return $this->_fopen($this->decode($hash), 'rb');
	}
	
	public function close($fp, $hash)
	{
		$this->_fclose($fp, $this->decode($hash));
	}
	
	public function mkdir($dst, $name)
	{
		if ($this->commandDisabled('mkdir')) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		if (!$this->nameAccepted($name)) {
			return $this->setError(fileManager::ERROR_INVALID_NAME);
		}
		
		if (($dir = $this->dir($dst)) == false) {
			return $this->setError(fileManager::ERROR_TRGDIR_NOT_FOUND, '#' . $dst);
		}
		
		if (!$dir['write']) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		$path = $this->decode($dst);
		$dst  = $this->_joinPath($path, $name);
		$stat = $this->stat($dst);
		if (!empty($stat)) {
			return $this->setError(fileManager::ERROR_EXISTS, $name);
		}
		$this->clearcache();
		return ($path = $this->_mkdir($path, $name)) ? $this->stat($path) : false;
	}
	
	public function mkfile($dst, $name)
	{
		if ($this->commandDisabled('mkfile')) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		if (!$this->nameAccepted($name)) {
			return $this->setError(fileManager::ERROR_INVALID_NAME);
		}
		
		if (($dir = $this->dir($dst)) == false) {
			return $this->setError(fileManager::ERROR_TRGDIR_NOT_FOUND, '#' . $dst);
		}
		
		if (!$dir['write']) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		$path = $this->decode($dst);
		
		if ($this->stat($this->_joinPath($path, $name))) {
			return $this->setError(fileManager::ERROR_EXISTS, $name);
		}
		$this->clearcache();
		return ($path = $this->_mkfile($path, $name)) ? $this->stat($path) : false;
	}
	
	public function rename($hash, $name)
	{
		if ($this->commandDisabled('rename')) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		if (!$this->nameAccepted($name)) {
			return $this->setError(fileManager::ERROR_INVALID_NAME, $name);
		}
		
		if (!($file = $this->file($hash))) {
			return $this->setError(fileManager::ERROR_FILE_NOT_FOUND);
		}
		
		if ($name == $file['name']) {
			return $file;
		}
		
		if (!empty($file['locked'])) {
			return $this->setError(fileManager::ERROR_LOCKED, $file['name']);
		}
		
		$path = $this->decode($hash);
		$dir  = $this->_dirname($path);
		$stat = $this->stat($this->_joinPath($dir, $name));
		if ($stat) {
			return $this->setError(fileManager::ERROR_EXISTS, $name);
		}
		
		if (!$this->_move($path, $dir, $name)) {
			return false;
		}
		
		if (!empty($stat['tmb']) && $stat['tmb'] != "1") {
			$this->rmTmb($stat['tmb']);
		}
		
		$path = $this->_joinPath($dir, $name);
		
		$this->clearcache();
		return $this->stat($path);
	}
	
	public function duplicate($hash, $suffix = 'copy')
	{
		if ($this->commandDisabled('duplicate')) {
			return $this->setError(fileManager::ERROR_COPY, '#' . $hash, fileManager::ERROR_PERM_DENIED);
		}
		
		if (($file = $this->file($hash)) == false) {
			return $this->setError(fileManager::ERROR_COPY, fileManager::ERROR_FILE_NOT_FOUND);
		}
		
		$path = $this->decode($hash);
		$dir  = $this->_dirname($path);
		
		return ($path = $this->copy($path, $dir, $this->uniqueName($dir, $this->_basename($path), ' ' . $suffix . ' '))) == false ? false : $this->stat($path);
	}
	
	public function upload($fp, $dst, $name, $tmpname)
	{
		if ($this->commandDisabled('upload')) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		if (($dir = $this->dir($dst)) == false) {
			return $this->setError(fileManager::ERROR_TRGDIR_NOT_FOUND, '#' . $dst);
		}
		
		if (!$dir['write']) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		if (!$this->nameAccepted($name)) {
			return $this->setError(fileManager::ERROR_INVALID_NAME);
		}
		
		$mime = $this->mimetype($this->mimeDetect == 'internal' ? $name : $tmpname);
		if ($mime == 'unknown' && $this->mimeDetect == 'internal') {
			$mime = fileManagerVolumeDriver::mimetypeInternalDetect($name);
		}
		
		$allow  = $this->mimeAccepted($mime, $this->uploadAllow, null);
		$deny   = $this->mimeAccepted($mime, $this->uploadDeny, null);
		$upload = true;
		if (strtolower($this->uploadOrder[0]) == 'allow') {
			$upload = false;
			if (!$deny && ($allow === true)) {
				$upload = true;
			}
		} else {
			$upload = true;
			if (($deny === true) && !$allow) {
				$upload = false;
			}
		}
		if (!$upload) {
			return $this->setError(fileManager::ERROR_UPLOAD_FILE_MIME);
		}
		
		if ($this->uploadMaxSize > 0 && filesize($tmpname) > $this->uploadMaxSize) {
			return $this->setError(fileManager::ERROR_UPLOAD_FILE_SIZE);
		}
		
		$dstpath = $this->decode($dst);
		$test    = $this->_joinPath($dstpath, $name);
		
		$file = $this->stat($test);
		$this->clearcache();
		
		if ($file) {
			if ($this->options['uploadOverwrite']) {
				if (!$file['write']) {
					return $this->setError(fileManager::ERROR_PERM_DENIED);
				} elseif ($file['mime'] == 'directory') {
					return $this->setError(fileManager::ERROR_NOT_REPLACE, $name);
				}
				$this->remove($file);
			} else {
				$name = $this->uniqueName($dstpath, $name, '-', false);
			}
		}
		
		$w = $h = 0;
		if (strpos($mime, 'image') === 0 && ($s = getimagesize($tmpname))) {
			$w = $s[0];
			$h = $s[1];
		}
		if (($path = $this->_save($fp, $dstpath, $name, $mime, $w, $h)) == false) {
			return false;
		}
		
		
		
		return $this->stat($path);
	}
	
	public function paste($volume, $src, $dst, $rmSrc = false)
	{
		$err = $rmSrc ? fileManager::ERROR_MOVE : fileManager::ERROR_COPY;
		
		if ($this->commandDisabled('paste')) {
			return $this->setError($err, '#' . $src, fileManager::ERROR_PERM_DENIED);
		}
		
		if (($file = $volume->file($src, $rmSrc)) == false) {
			return $this->setError($err, '#' . $src, fileManager::ERROR_FILE_NOT_FOUND);
		}
		
		$name    = $file['name'];
		$errpath = $volume->path($src);
		
		if (($dir = $this->dir($dst)) == false) {
			return $this->setError($err, $errpath, fileManager::ERROR_TRGDIR_NOT_FOUND, '#' . $dst);
		}
		
		if (!$dir['write'] || !$file['read']) {
			return $this->setError($err, $errpath, fileManager::ERROR_PERM_DENIED);
		}
		
		$destination = $this->decode($dst);
		
		if (($test = $volume->closest($src, $rmSrc ? 'locked' : 'read', $rmSrc))) {
			return $rmSrc ? $this->setError($err, $errpath, fileManager::ERROR_LOCKED, $volume->path($test)) : $this->setError($err, $errpath, fileManager::ERROR_PERM_DENIED);
		}
		
		$test = $this->_joinPath($destination, $name);
		$stat = $this->stat($test);
		$this->clearcache();
		if ($stat) {
			if ($this->options['copyOverwrite']) {
				if (!$this->isSameType($file['mime'], $stat['mime'])) {
					return $this->setError(fileManager::ERROR_NOT_REPLACE, $this->_path($test));
				}
				if (!$stat['write']) {
					return $this->setError($err, $errpath, fileManager::ERROR_PERM_DENIED);
				}
				if (($locked = $this->closestByAttr($test, 'locked', true))) {
					return $this->setError(fileManager::ERROR_LOCKED, $this->_path($locked));
				}
				if (!$this->remove($test)) {
					return $this->setError(fileManager::ERROR_REPLACE, $this->_path($test));
				}
			} else {
				$name = $this->uniqueName($destination, $name, ' ', false);
			}
		}
		
		if ($volume == $this) {
			$source = $this->decode($src);
			if ($this->_inpath($destination, $source)) {
				return $this->setError(fileManager::ERROR_COPY_INTO_ITSELF, $path);
			}
			$method = $rmSrc ? 'move' : 'copy';
			
			return ($path = $this->$method($source, $destination, $name)) ? $this->stat($path) : false;
		}
		
		
		if (!$this->options['copyTo'] || !$volume->copyFromAllowed()) {
			return $this->setError(fileManager::ERROR_COPY, $errpath, fileManager::ERROR_PERM_DENIED);
		}
		
		if (($path = $this->copyFrom($volume, $src, $destination, $name)) == false) {
			return false;
		}
		
		if ($rmSrc) {
			if ($volume->rm($src)) {
				$this->removed[] = $file;
			} else {
				return $this->setError(fileManager::ERROR_MOVE, $errpath, fileManager::ERROR_RM_SRC);
			}
		}
		return $this->stat($path);
	}
	
	public function getContents($hash)
	{
		$file = $this->file($hash);
		
		if (!$file) {
			return $this->setError(fileManager::ERROR_FILE_NOT_FOUND);
		}
		
		if ($file['mime'] == 'directory') {
			return $this->setError(fileManager::ERROR_NOT_FILE);
		}
		
		if (!$file['read']) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		return $this->_getContents($this->decode($hash));
	}
	
	public function putContents($hash, $content)
	{
		if ($this->commandDisabled('edit')) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		$path = $this->decode($hash);
		
		if (!($file = $this->file($hash))) {
			return $this->setError(fileManager::ERROR_FILE_NOT_FOUND);
		}
		
		if (!$file['write']) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		$this->clearcache();
		return $this->_filePutContents($path, $content) ? $this->stat($path) : false;
	}
	
	public function extract($hash)
	{
		if ($this->commandDisabled('extract')) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		if (($file = $this->file($hash)) == false) {
			return $this->setError(fileManager::ERROR_FILE_NOT_FOUND);
		}
		
		$archiver = isset($this->archivers['extract'][$file['mime']]) ? $this->archivers['extract'][$file['mime']] : false;
		
		if (!$archiver) {
			return $this->setError(fileManager::ERROR_NOT_ARCHIVE);
		}
		
		$path   = $this->decode($hash);
		$parent = $this->stat($this->_dirname($path));
		
		if (!$file['read'] || !$parent['write']) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		$this->clearcache();
		return ($path = $this->_extract($path, $archiver)) ? $this->stat($path) : false;
	}
	
	public function archive($hashes, $mime)
	{
		if ($this->commandDisabled('archive')) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		$archiver = isset($this->archivers['create'][$mime]) ? $this->archivers['create'][$mime] : false;
		
		if (!$archiver) {
			return $this->setError(fileManager::ERROR_ARCHIVE_TYPE);
		}
		
		$files = array();
		
		foreach ($hashes as $hash) {
			if (($file = $this->file($hash)) == false) {
				return $this->error(fileManager::ERROR_FILE_NOT_FOUND, '#' + $hash);
			}
			if (!$file['read']) {
				return $this->error(fileManager::ERROR_PERM_DENIED);
			}
			$path = $this->decode($hash);
			if (!isset($dir)) {
				$dir  = $this->_dirname($path);
				$stat = $this->stat($dir);
				if (!$stat['write']) {
					return $this->error(fileManager::ERROR_PERM_DENIED);
				}
			}
			
			$files[] = $this->_basename($path);
		}
		
		$name = (count($files) == 1 ? $files[0] : 'Archive') . '.' . $archiver['ext'];
		$name = $this->uniqueName($dir, $name, '');
		$this->clearcache();
		return ($path = $this->_archive($dir, $files, $name, $archiver)) ? $this->stat($path) : false;
	}
	
	public function resize($hash, $width, $height, $x, $y, $mode = 'resize', $bg = '', $degree = 0)
	{
		if ($this->commandDisabled('resize')) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		if (($file = $this->file($hash)) == false) {
			return $this->setError(fileManager::ERROR_FILE_NOT_FOUND);
		}
		
		if (!$file['write'] || !$file['read']) {
			return $this->setError(fileManager::ERROR_PERM_DENIED);
		}
		
		$path = $this->decode($hash);
		
		if (!$this->canResize($path, $file)) {
			return $this->setError(fileManager::ERROR_UNSUPPORT_TYPE);
		}
		
		switch ($mode) {
			
			case 'propresize':
				$result = $this->imgResize($path, $width, $height, true, true);
				break;
			
			case 'crop':
				$result = $this->imgCrop($path, $width, $height, $x, $y);
				break;
			
			case 'fitsquare':
				$result = $this->imgSquareFit($path, $width, $height, 'center', 'middle', ($bg ? $bg : $this->options['tmbBgColor']));
				break;
			
			case 'rotate':
				$result = $this->imgRotate($path, $degree, ($bg ? $bg : $this->options['tmbBgColor']));
				break;
			
			default:
				$result = $this->imgResize($path, $width, $height, false, true);
				break;
		}
		
		if ($result) {
			if (!empty($file['tmb']) && $file['tmb'] != "1") {
				$this->rmTmb($file['tmb']);
			}
			$this->clearcache();
			return $this->stat($path);
		}
		
		return false;
	}
	
	public function rm($hash)
	{
		return $this->commandDisabled('rm') ? array(
			fileManager::ERROR_ACCESS_DENIED
		) : $this->remove($this->decode($hash));
	}
	
	public function search($q, $mimes)
	{
		return $this->doSearch($this->root, $q, $mimes);
	}
	
	public function dimensions($hash)
	{
		if (($file = $this->file($hash)) == false) {
			return false;
		}
		
		return $this->_dimensions($this->decode($hash), $file['mime']);
	}
	
	protected function setError($error)
	{
		
		$this->error = array();
		
		foreach (func_get_args() as $err) {
			if (is_array($err)) {
				$this->error = array_merge($this->error, $err);
			} else {
				$this->error[] = $err;
			}
		}
		
		return false;
	}
	
	
	
	protected function encode($path)
	{
		if ($path !== '') {
			
			$p = $this->_relpath($path);
			if ($p === '') {
				$p = DIRECTORY_SEPARATOR;
			}
			
			$hash = $this->crypt($p);
			$hash = strtr(base64_encode($hash), '+/=', '-_.');
			$hash = rtrim($hash, '.');
			return $this->id . $hash;
		}
	}
	
	protected function decode($hash)
	{
		if (strpos($hash, $this->id) === 0) {
			$h    = substr($hash, strlen($this->id));
			$h    = base64_decode(strtr($h, '-_.', '+/='));
			$path = $this->uncrypt($h);
			return $this->_abspath($path);
		}
	}
	
	protected function crypt($path)
	{
		return $path;
	}
	
	protected function uncrypt($hash)
	{
		return $hash;
	}
	
	protected function nameAccepted($name)
	{
		if ($this->nameValidator) {
			if (function_exists($this->nameValidator)) {
				$f = $this->nameValidator;
				return $f($name);
			}
			return preg_match($this->nameValidator, $name);
		}
		return true;
	}
	
	public function uniqueName($dir, $name, $suffix = ' copy', $checkNum = true)
	{
		$ext = '';
		
		if (preg_match('/\.((tar\.(gz|bz|bz2|z|lzo))|cpio\.gz|ps\.gz|xcf\.(gz|bz2)|[a-z0-9]{1,4})$/i', $name, $m)) {
			$ext  = '.' . $m[1];
			$name = substr($name, 0, strlen($name) - strlen($m[0]));
		}
		
		if ($checkNum && preg_match('/(' . $suffix . ')(\d*)$/i', $name, $m)) {
			$i    = (int) $m[2];
			$name = substr($name, 0, strlen($name) - strlen($m[2]));
		} else {
			$i = 1;
			$name .= $suffix;
		}
		$max = $i + 100000;
		
		while ($i <= $max) {
			$n = $name . ($i > 0 ? $i : '') . $ext;
			
			if (!$this->stat($this->_joinPath($dir, $n))) {
				$this->clearcache();
				return $n;
			}
			$i++;
		}
		return $name . md5($dir) . $ext;
	}
	
	
	protected function attr($path, $name, $val = false)
	{
		if (!isset($this->defaults[$name])) {
			return false;
		}
		
		
		$perm = null;
		
		if ($this->access) {
			if (is_array($this->access)) {
				$obj    = $this->access[0];
				$method = $this->access[1];
				$perm   = $obj->{$method}($name, $path, $this->options['accessControlData'], $this);
			} else {
				$func = $this->access;
				$perm = $func($name, $path, $this->options['accessControlData'], $this);
			}
			
			if ($perm !== null) {
				return !!$perm;
			}
		}
		
		for ($i = 0, $c = count($this->attributes); $i < $c; $i++) {
			$attrs = $this->attributes[$i];
			$p     = $this->separator . $this->_relpath($path);
			if (isset($attrs[$name]) && isset($attrs['pattern']) && preg_match($attrs['pattern'], $p)) {
				$perm = $attrs[$name];
			}
		}
		
		return $perm === null ? $this->defaults[$name] : !!$perm;
	}
	
	protected function stat($path)
	{
		return isset($this->cache[$path]) ? $this->cache[$path] : $this->updateCache($path, $this->_stat($path));
	}
	
	protected function updateCache($path, $stat)
	{
		if (empty($stat) || !is_array($stat)) {
			return $this->cache[$path] = array();
		}
		
		$stat['hash'] = $this->encode($path);
		
		$root = $path == $this->root;
		
		if ($root) {
			$stat['volumeid'] = $this->id;
			if ($this->rootName) {
				$stat['name'] = $this->rootName;
			}
		} else {
			if (empty($stat['name'])) {
				$stat['name'] = $this->_basename($path);
			}
			if (empty($stat['phash'])) {
				$stat['phash'] = $this->encode($this->_dirname($path));
			}
		}
		
		if ($this->options['utf8fix'] && $this->options['utf8patterns'] && $this->options['utf8replace']) {
			$stat['name'] = json_decode(str_replace($this->options['utf8patterns'], $this->options['utf8replace'], json_encode($stat['name'])));
		}
		
		
		if (empty($stat['mime'])) {
			$stat['mime'] = $this->mimetype($stat['name']);
		}
		
		$stat['date'] = isset($stat['ts']) ? $this->formatDate($stat['ts']) : 'unknown';
		
		if (!isset($stat['size'])) {
			$stat['size'] = 'unknown';
		}
		
		$stat['read']  = intval($this->attr($path, 'read', isset($stat['read']) ? !!$stat['read'] : false));
		$stat['write'] = intval($this->attr($path, 'write', isset($stat['write']) ? !!$stat['write'] : false));
		if ($root) {
			$stat['locked'] = 1;
		} elseif ($this->attr($path, 'locked', !empty($stat['locked']))) {
			$stat['locked'] = 1;
		} else {
			unset($stat['locked']);
		}
		
		if ($root) {
			unset($stat['hidden']);
		} elseif ($this->attr($path, 'hidden', !empty($stat['hidden'])) || !$this->mimeAccepted($stat['mime'])) {
			$stat['hidden'] = $root ? 0 : 1;
		} else {
			unset($stat['hidden']);
		}
		
		if ($stat['read'] && empty($stat['hidden'])) {
			
			if ($stat['mime'] == 'directory') {
				
				if ($this->options['checkSubfolders']) {
					if (isset($stat['dirs'])) {
						if ($stat['dirs']) {
							$stat['dirs'] = 1;
						} else {
							unset($stat['dirs']);
						}
					} elseif (!empty($stat['alias']) && !empty($stat['target'])) {
						$stat['dirs'] = isset($this->cache[$stat['target']]) ? intval(isset($this->cache[$stat['target']]['dirs'])) : $this->_subdirs($stat['target']);
						
					} elseif ($this->_subdirs($path)) {
						$stat['dirs'] = 1;
					}
				} else {
					$stat['dirs'] = 1;
				}
			} else {
				$p = isset($stat['target']) ? $stat['target'] : $path;
				if ($this->tmbURL && !isset($stat['tmb']) && $this->canCreateTmb($p, $stat)) {
					$tmb         = $this->gettmb($p, $stat);
					$stat['tmb'] = $tmb ? $tmb : 1;
				}
				
			}
		}
		
		if (!empty($stat['alias']) && !empty($stat['target'])) {
			$stat['thash'] = $this->encode($stat['target']);
			unset($stat['target']);
		}
		
		return $this->cache[$path] = $stat;
	}
	
	protected function cacheDir($path)
	{
		$this->dirsCache[$path] = array();
		
		foreach ($this->_scandir($path) as $p) {
			if (($stat = $this->stat($p)) && empty($stat['hidden'])) {
				$this->dirsCache[$path][] = $p;
			}
		}
	}
	
	protected function clearcache()
	{
		$this->cache = $this->dirsCache = array();
	}
	
	protected function mimetype($path)
	{
		$type = '';
		
		if ($this->mimeDetect == 'finfo') {
			$type = @finfo_file($this->finfo, $path);
		} elseif ($type == 'mime_content_type') {
			$type = mime_content_type($path);
		} else {
			$type = fileManagerVolumeDriver::mimetypeInternalDetect($path);
		}
		
		$type = explode(';', $type);
		$type = trim($type[0]);
		
		if ($type == 'application/x-empty') {
			$type = 'text/plain';
		} elseif ($type == 'application/x-zip') {
			$type = 'application/zip';
		}
		
		return $type == 'unknown' && $this->mimeDetect != 'internal' ? fileManagerVolumeDriver::mimetypeInternalDetect($path) : $type;
		
	}
	
	static protected function mimetypeInternalDetect($path)
	{
		$pinfo = pathinfo($path);
		$ext   = isset($pinfo['extension']) ? strtolower($pinfo['extension']) : '';
		return isset(fileManagerVolumeDriver::$mimetypes[$ext]) ? fileManagerVolumeDriver::$mimetypes[$ext] : 'unknown';
		
	}
	
	protected function countSize($path)
	{
		$stat = $this->stat($path);
		
		if (empty($stat) || !$stat['read'] || !empty($stat['hidden'])) {
			return 'unknown';
		}
		
		if ($stat['mime'] != 'directory') {
			return $stat['size'];
		}
		
		$subdirs	          = $this->options['checkSubfolders'];
		$this->options['checkSubfolders'] = true;
		$result	           = 0;
		foreach ($this->getScandir($path) as $stat) {
			$size = $stat['mime'] == 'directory' && $stat['read'] ? $this->countSize($this->_joinPath($path, $stat['name'])) : $stat['size'];
			if ($size > 0) {
				$result += $size;
			}
		}
		$this->options['checkSubfolders'] = $subdirs;
		return $result;
	}
	
	protected function isSameType($mime1, $mime2)
	{
		return ($mime1 == 'directory' && $mime1 == $mime2) || ($mime1 != 'directory' && $mime2 != 'directory');
	}
	
	protected function closestByAttr($path, $attr, $val)
	{
		$stat = $this->stat($path);
		
		if (empty($stat)) {
			return false;
		}
		
		$v = isset($stat[$attr]) ? $stat[$attr] : false;
		
		if ($v == $val) {
			return $path;
		}
		
		return $stat['mime'] == 'directory' ? $this->childsByAttr($path, $attr, $val) : false;
	}
	
	protected function childsByAttr($path, $attr, $val)
	{
		foreach ($this->_scandir($path) as $p) {
			if (($_p = $this->closestByAttr($p, $attr, $val)) != false) {
				return $_p;
			}
		}
		return false;
	}
	
	
	protected function getScandir($path)
	{
		$files = array();
		
		!isset($this->dirsCache[$path]) && $this->cacheDir($path);
		
		foreach ($this->dirsCache[$path] as $p) {
			if (($stat = $this->stat($p)) && empty($stat['hidden'])) {
				$files[] = $stat;
			}
		}
		
		return $files;
	}
	
	
	protected function gettree($path, $deep, $exclude = '')
	{
		$dirs = array();
		
		!isset($this->dirsCache[$path]) && $this->cacheDir($path);
		
		foreach ($this->dirsCache[$path] as $p) {
			$stat = $this->stat($p);
			
			if ($stat && empty($stat['hidden']) && $path != $exclude && $stat['mime'] == 'directory') {
				$dirs[] = $stat;
				if ($deep > 0 && !empty($stat['dirs'])) {
					$dirs = array_merge($dirs, $this->gettree($p, $deep - 1));
				}
			}
		}
		
		return $dirs;
	}
	
	protected function doSearch($path, $q, $mimes)
	{
		$result = array();
		
		foreach ($this->_scandir($path) as $p) {
			$stat = $this->stat($p);
			
			if (!$stat) {
				continue;
			}
			
			if (!empty($stat['hidden']) || !$this->mimeAccepted($stat['mime'])) {
				continue;
			}
			
			$name = $stat['name'];
			
			if ($this->stripos($name, $q) !== false) {
				$stat['path'] = $this->_path($p);
				if ($this->URL && !isset($stat['url'])) {
					$stat['url'] = $this->URL . str_replace($this->separator, '/', substr($p, strlen($this->root) + 1));
				}
				
				$result[] = $stat;
			}
			if ($stat['mime'] == 'directory' && $stat['read'] && !isset($stat['alias'])) {
				$result = array_merge($result, $this->doSearch($p, $q, $mimes));
			}
		}
		
		return $result;
	}
	
	
	protected function copy($src, $dst, $name)
	{
		$srcStat = $this->stat($src);
		$this->clearcache();
		
		if (!empty($srcStat['thash'])) {
			$target = $this->decode($srcStat['thash']);
			$stat   = $this->stat($target);
			$this->clearcache();
			return $stat && $this->_symlink($target, $dst, $name) ? $this->_joinPath($dst, $name) : $this->setError(fileManager::ERROR_COPY, $this->_path($src));
		}
		
		if ($srcStat['mime'] == 'directory') {
			$test = $this->stat($this->_joinPath($dst, $name));
			
			if (($test && $test['mime'] != 'directory') || !$this->_mkdir($dst, $name)) {
				return $this->setError(fileManager::ERROR_COPY, $this->_path($src));
			}
			
			$dst = $this->_joinPath($dst, $name);
			
			foreach ($this->getScandir($src) as $stat) {
				if (empty($stat['hidden'])) {
					$name = $stat['name'];
					if (!$this->copy($this->_joinPath($src, $name), $dst, $name)) {
						return false;
					}
				}
			}
			$this->clearcache();
			return $dst;
		}
		
		return $this->_copy($src, $dst, $name) ? $this->_joinPath($dst, $name) : $this->setError(fileManager::ERROR_COPY, $this->_path($src));
	}
	
	protected function move($src, $dst, $name)
	{
		$stat             = $this->stat($src);
		$stat['realpath'] = $src;
		$this->clearcache();
		
		if ($this->_move($src, $dst, $name)) {
			$this->removed[] = $stat;
			return $this->_joinPath($dst, $name);
		}
		
		return $this->setError(fileManager::ERROR_MOVE, $this->_path($src));
	}
	
	protected function copyFrom($volume, $src, $destination, $name)
	{
		
		if (($source = $volume->file($src)) == false) {
			return $this->setError(fileManager::ERROR_COPY, '#' . $src, $volume->error());
		}
		
		$errpath = $volume->path($src);
		
		if (!$this->nameAccepted($source['name'])) {
			return $this->setError(fileManager::ERROR_COPY, $errpath, fileManager::ERROR_INVALID_NAME);
		}
		
		if (!$source['read']) {
			return $this->setError(fileManager::ERROR_COPY, $errpath, fileManager::ERROR_PERM_DENIED);
		}
		
		if ($source['mime'] == 'directory') {
			$stat = $this->stat($this->_joinPath($destination, $name));
			$this->clearcache();
			if ((!$stat || $stat['mime'] != 'directory') && !$this->_mkdir($destination, $name)) {
				return $this->setError(fileManager::ERROR_COPY, $errpath);
			}
			
			$path = $this->_joinPath($destination, $name);
			
			foreach ($volume->scandir($src) as $entr) {
				if (!$this->copyFrom($volume, $entr['hash'], $path, $entr['name'])) {
					return false;
				}
			}
			
		} else {
			$mime = $source['mime'];
			$w    = $h = 0;
			if (strpos($mime, 'image') === 0 && ($dim = $volume->dimensions($src))) {
				$s = explode('x', $dim);
				$w = $s[0];
				$h = $s[1];
			}
			
			if (($fp = $volume->open($src)) == false || ($path = $this->_save($fp, $destination, $name, $mime, $w, $h)) == false) {
				$fp && $volume->close($fp, $src);
				return $this->setError(fileManager::ERROR_COPY, $errpath);
			}
			$volume->close($fp, $src);
		}
		
		return $path;
	}
	
	protected function remove($path, $force = false)
	{
		$stat             = $this->stat($path);
		$stat['realpath'] = $path;
		if (!empty($stat['tmb']) && $stat['tmb'] != "1") {
			$this->rmTmb($stat['tmb']);
		}
		$this->clearcache();
		
		if (empty($stat)) {
			return $this->setError(fileManager::ERROR_RM, $this->_path($path), fileManager::ERROR_FILE_NOT_FOUND);
		}
		
		if (!$force && !empty($stat['locked'])) {
			return $this->setError(fileManager::ERROR_LOCKED, $this->_path($path));
		}
		
		if ($stat['mime'] == 'directory') {
			foreach ($this->_scandir($path) as $p) {
				$name = $this->_basename($p);
				if ($name != '.' && $name != '..' && !$this->remove($p)) {
					return false;
				}
			}
			if (!$this->_rmdir($path)) {
				return $this->setError(fileManager::ERROR_RM, $this->_path($path));
			}
			
		} else {
			if (!$this->_unlink($path)) {
				return $this->setError(fileManager::ERROR_RM, $this->_path($path));
			}
		}
		
		$this->removed[] = $stat;
		return true;
	}
	
	
	
	protected function tmbname($stat)
	{
		return $stat['hash'] . $stat['ts'] . '.png';
	}
	
	protected function gettmb($path, $stat)
	{
		if ($this->tmbURL && $this->tmbPath) {
			if (strpos($path, $this->tmbPath) === 0) {
				return basename($path);
			}
			
			$name = $this->tmbname($stat);
			if (file_exists($this->tmbPath . DIRECTORY_SEPARATOR . $name)) {
				return $name;
			}
		}
		return false;
	}
	
	protected function canCreateTmb($path, $stat)
	{
		return $this->tmbPathWritable && strpos($path, $this->tmbPath) === false && $this->imgLib && strpos($stat['mime'], 'image') === 0 && ($this->imgLib == 'gd' ? $stat['mime'] == 'image/jpeg' || $stat['mime'] == 'image/png' || $stat['mime'] == 'image/gif' : true);
	}
	
	protected function canResize($path, $stat)
	{
		return $this->canCreateTmb($path, $stat);
	}
	
	protected function createTmb($path, $stat)
	{
		if (!$stat || !$this->canCreateTmb($path, $stat)) {
			return false;
		}
		
		$name = $this->tmbname($stat);
		$tmb  = $this->tmbPath . DIRECTORY_SEPARATOR . $name;
		
		if (($src = $this->_fopen($path, 'rb')) == false) {
			return false;
		}
		
		if (($trg = fopen($tmb, 'wb')) == false) {
			$this->_fclose($src, $path);
			return false;
		}
		
		while (!feof($src)) {
			fwrite($trg, fread($src, 8192));
		}
		
		$this->_fclose($src, $path);
		fclose($trg);
		
		$result = false;
		
		$tmbSize = $this->tmbSize;
		
		if (($s = getimagesize($tmb)) == false) {
			return false;
		}
		
		if ($s[0] <= $tmbSize && $s[1] <= $tmbSize) {
			$result = $this->imgSquareFit($tmb, $tmbSize, $tmbSize, 'center', 'middle', $this->options['tmbBgColor'], 'png');
			
		} else {
			
			if ($this->options['tmbCrop']) {
				
				if (!(($s[0] > $tmbSize && $s[1] <= $tmbSize) || ($s[0] <= $tmbSize && $s[1] > $tmbSize)) || ($s[0] > $tmbSize && $s[1] > $tmbSize)) {
					$result = $this->imgResize($tmb, $tmbSize, $tmbSize, true, false, 'png');
				}
				
				if (($s = getimagesize($tmb)) != false) {
					$x      = $s[0] > $tmbSize ? intval(($s[0] - $tmbSize) / 2) : 0;
					$y      = $s[1] > $tmbSize ? intval(($s[1] - $tmbSize) / 2) : 0;
					$result = $this->imgCrop($tmb, $tmbSize, $tmbSize, $x, $y, 'png');
				}
				
			} else {
				$result = $this->imgResize($tmb, $tmbSize, $tmbSize, true, true, $this->imgLib, 'png');
				$result = $this->imgSquareFit($tmb, $tmbSize, $tmbSize, 'center', 'middle', $this->options['tmbBgColor'], 'png');
			}
			
		}
		if (!$result) {
			unlink($tmb);
			return false;
		}
		
		return $name;
	}
	
	protected function imgResize($path, $width, $height, $keepProportions = false, $resizeByBiggerSide = true, $destformat = null)
	{
		if (($s = @getimagesize($path)) == false) {
			return false;
		}
		
		$result = false;
		
		list($size_w, $size_h) = array(
			$width,
			$height
		);
		
		if ($keepProportions == true) {
			
			list($orig_w, $orig_h, $new_w, $new_h) = array(
				$s[0],
				$s[1],
				$width,
				$height
			);
			
			$xscale = $orig_w / $new_w;
			$yscale = $orig_h / $new_h;
			
			
			if ($resizeByBiggerSide) {
				
				if ($orig_w > $orig_h) {
					$size_h = $orig_h * $width / $orig_w;
					$size_w = $width;
				} else {
					$size_w = $orig_w * $height / $orig_h;
					$size_h = $height;
				}
				
			} else {
				if ($orig_w > $orig_h) {
					$size_w = $orig_w * $height / $orig_h;
					$size_h = $height;
				} else {
					$size_h = $orig_h * $width / $orig_w;
					$size_w = $width;
				}
			}
		}
		
		switch ($this->imgLib) {
			case 'imagick':
				
				try {
					$img = new imagick($path);
				}
				catch (Exception $e) {
					
					return false;
				}
				
				$img->resizeImage($size_w, $size_h, Imagick::FILTER_LANCZOS, true);
				
				$result = $img->writeImage($path);
				
				return $result ? $path : false;
				
				break;
			
			case 'gd':
				if ($s['mime'] == 'image/jpeg') {
					$img = imagecreatefromjpeg($path);
				} elseif ($s['mime'] == 'image/png') {
					$img = imagecreatefrompng($path);
				} elseif ($s['mime'] == 'image/gif') {
					$img = imagecreatefromgif($path);
				} elseif ($s['mime'] == 'image/xbm') {
					$img = imagecreatefromxbm($path);
				}
				
				if ($img && false != ($tmp = imagecreatetruecolor($size_w, $size_h))) {
					if (!imagecopyresampled($tmp, $img, 0, 0, 0, 0, $size_w, $size_h, $s[0], $s[1])) {
						return false;
					}
					
					if ($destformat == 'jpg' || ($destformat == null && $s['mime'] == 'image/jpeg')) {
						$result = imagejpeg($tmp, $path, 100);
					} else if ($destformat == 'gif' || ($destformat == null && $s['mime'] == 'image/gif')) {
						$result = imagegif($tmp, $path, 7);
					} else {
						$result = imagepng($tmp, $path, 7);
					}
					
					imagedestroy($img);
					imagedestroy($tmp);
					
					return $result ? $path : false;
					
				}
				break;
		}
		
		return false;
	}
	
	protected function imgCrop($path, $width, $height, $x, $y, $destformat = null)
	{
		if (($s = @getimagesize($path)) == false) {
			return false;
		}
		
		$result = false;
		
		switch ($this->imgLib) {
			case 'imagick':
				
				try {
					$img = new imagick($path);
				}
				catch (Exception $e) {
					
					return false;
				}
				
				$img->cropImage($width, $height, $x, $y);
				
				$result = $img->writeImage($path);
				
				return $result ? $path : false;
				
				break;
			
			case 'gd':
				if ($s['mime'] == 'image/jpeg') {
					$img = imagecreatefromjpeg($path);
				} elseif ($s['mime'] == 'image/png') {
					$img = imagecreatefrompng($path);
				} elseif ($s['mime'] == 'image/gif') {
					$img = imagecreatefromgif($path);
				} elseif ($s['mime'] == 'image/xbm') {
					$img = imagecreatefromxbm($path);
				}
				
				if ($img && false != ($tmp = imagecreatetruecolor($width, $height))) {
					
					if (!imagecopy($tmp, $img, 0, 0, $x, $y, $width, $height)) {
						return false;
					}
					
					if ($destformat == 'jpg' || ($destformat == null && $s['mime'] == 'image/jpeg')) {
						$result = imagejpeg($tmp, $path, 100);
					} else if ($destformat == 'gif' || ($destformat == null && $s['mime'] == 'image/gif')) {
						$result = imagegif($tmp, $path, 7);
					} else {
						$result = imagepng($tmp, $path, 7);
					}
					
					imagedestroy($img);
					imagedestroy($tmp);
					
					return $result ? $path : false;
					
				}
				break;
		}
		
		return false;
	}
	
	protected function imgSquareFit($path, $width, $height, $align = 'center', $valign = 'middle', $bgcolor = '#0000ff', $destformat = null)
	{
		if (($s = @getimagesize($path)) == false) {
			return false;
		}
		
		$result = false;
		
		$y = ceil(abs($height - $s[1]) / 2);
		$x = ceil(abs($width - $s[0]) / 2);
		
		switch ($this->imgLib) {
			case 'imagick':
				try {
					$img = new imagick($path);
				}
				catch (Exception $e) {
					return false;
				}
				
				$img1 = new Imagick();
				$img1->newImage($width, $height, new ImagickPixel($bgcolor));
				$img1->setImageColorspace($img->getImageColorspace());
				$img1->setImageFormat($destformat != null ? $destformat : $img->getFormat());
				$img1->compositeImage($img, imagick::COMPOSITE_OVER, $x, $y);
				$result = $img1->writeImage($path);
				return $result ? $path : false;
				
				break;
			
			case 'gd':
				if ($s['mime'] == 'image/jpeg') {
					$img = imagecreatefromjpeg($path);
				} elseif ($s['mime'] == 'image/png') {
					$img = imagecreatefrompng($path);
				} elseif ($s['mime'] == 'image/gif') {
					$img = imagecreatefromgif($path);
				} elseif ($s['mime'] == 'image/xbm') {
					$img = imagecreatefromxbm($path);
				}
				
				if ($img && false != ($tmp = imagecreatetruecolor($width, $height))) {
					
					if ($bgcolor == 'transparent') {
						list($r, $g, $b) = array(0, 0, 255);
					} else {
						list($r, $g, $b) = sscanf($bgcolor, "#%02x%02x%02x");
					}

					$bgcolor1 = imagecolorallocate($tmp, $r, $g, $b);
						
					if ($bgcolor == 'transparent') {
						$bgcolor1 = imagecolortransparent($tmp, $bgcolor1);
					}

					imagefill($tmp, 0, 0, $bgcolor1);
					
					if (!imagecopyresampled($tmp, $img, $x, $y, 0, 0, $s[0], $s[1], $s[0], $s[1])) {
						return false;
					}
					
					if ($destformat == 'jpg' || ($destformat == null && $s['mime'] == 'image/jpeg')) {
						$result = imagejpeg($tmp, $path, 100);
					} else if ($destformat == 'gif' || ($destformat == null && $s['mime'] == 'image/gif')) {
						$result = imagegif($tmp, $path, 9);
					} else {
						$result = imagepng($tmp, $path, 9);
					}
					
					imagedestroy($img);
					imagedestroy($tmp);
					
					return $result ? $path : false;
				}
				break;
		}
		
		return false;
	}
	
	protected function imgRotate($path, $degree, $bgcolor = '#ffffff', $destformat = null)
	{
		if (($s = @getimagesize($path)) == false) {
			return false;
		}
		
		$result = false;
		
		switch ($this->imgLib) {
			case 'imagick':
				try {
					$img = new imagick($path);
				}
				catch (Exception $e) {
					return false;
				}
				
				$img->rotateImage(new ImagickPixel($bgcolor), $degree);
				$result = $img->writeImage($path);
				return $result ? $path : false;
				
				break;
			
			case 'gd':
				if ($s['mime'] == 'image/jpeg') {
					$img = imagecreatefromjpeg($path);
				} elseif ($s['mime'] == 'image/png') {
					$img = imagecreatefrompng($path);
				} elseif ($s['mime'] == 'image/gif') {
					$img = imagecreatefromgif($path);
				} elseif ($s['mime'] == 'image/xbm') {
					$img = imagecreatefromxbm($path);
				}
				
				$degree = 360 - $degree;
				list($r, $g, $b) = sscanf($bgcolor, "#%02x%02x%02x");
				$bgcolor = imagecolorallocate($img, $r, $g, $b);
				$tmp     = imageRotate($img, $degree, (int) $bgcolor);
				
				if ($destformat == 'jpg' || ($destformat == null && $s['mime'] == 'image/jpeg')) {
					$result = imagejpeg($tmp, $path, 100);
				} else if ($destformat == 'gif' || ($destformat == null && $s['mime'] == 'image/gif')) {
					$result = imagegif($tmp, $path, 7);
				} else {
					$result = imagepng($tmp, $path, 7);
				}
				
				imageDestroy($img);
				imageDestroy($tmp);
				
				return $result ? $path : false;
				
				break;
		}
		
		return false;
	}
	
	protected function procExec($command, array &$output = null, &$return_var = -1, array &$error_output = null)
	{
		
		$descriptorspec = array(
			0 => array(
				"pipe",
				"r"
			),
			1 => array(
				"pipe",
				"w"
			),
			2 => array(
				"pipe",
				"w"
			)
		);
		
		$process = proc_open($command, $descriptorspec, $pipes, null, null);
		
		if (is_resource($process)) {
			
			fclose($pipes[0]);
			
			$tmpout = '';
			$tmperr = '';
			
			$output       = stream_get_contents($pipes[1]);
			$error_output = stream_get_contents($pipes[2]);
			
			fclose($pipes[1]);
			fclose($pipes[2]);
			$return_var = proc_close($process);
			
			
		}
		
		return $return_var;
		
	}
	
	protected function rmTmb($tmb)
	{
		$tmb = $this->tmbPath . DIRECTORY_SEPARATOR . $tmb;
		file_exists($tmb) && @unlink($tmb);
		clearstatcache();
	}
	
	
	protected function formatDate($ts)
	{
		if ($ts > $this->today) {
			return 'Today ' . date($this->options['timeFormat'], $ts);
		}
		
		if ($ts > $this->yesterday) {
			return 'Yesterday ' . date($this->options['timeFormat'], $ts);
		}
		
		return date($this->options['dateFormat'], $ts);
	}
	
	protected function stripos($haystack, $needle, $offset = 0)
	{
		if (function_exists('mb_stripos')) {
			return mb_stripos($haystack, $needle, $offset);
		} else if (function_exists('mb_strtolower') && function_exists('mb_strpos')) {
			return mb_strpos(mb_strtolower($haystack), mb_strtolower($needle), $offset);
		}
		return stripos($haystack, $needle, $offset);
	}
	
	
	
	abstract protected function _dirname($path);
	
	abstract protected function _basename($path);
	
	abstract protected function _joinPath($dir, $name);
	
	abstract protected function _normpath($path);
	
	abstract protected function _relpath($path);
	
	abstract protected function _abspath($path);
	
	abstract protected function _path($path);
	
	abstract protected function _inpath($path, $parent);
	
	abstract protected function _stat($path);
	
	
	
	
	abstract protected function _subdirs($path);
	
	abstract protected function _dimensions($path, $mime);
	
	
	abstract protected function _scandir($path);
	
	abstract protected function _fopen($path, $mode = "rb");
	
	abstract protected function _fclose($fp, $path = '');
	
	
	abstract protected function _mkdir($path, $name);
	
	abstract protected function _mkfile($path, $name);
	
	abstract protected function _symlink($source, $targetDir, $name);
	
	abstract protected function _copy($source, $targetDir, $name);
	
	abstract protected function _move($source, $targetDir, $name);
	
	abstract protected function _unlink($path);
	
	abstract protected function _rmdir($path);
	
	abstract protected function _save($fp, $dir, $name, $mime, $w, $h);
	
	abstract protected function _getContents($path);
	
	abstract protected function _filePutContents($path, $content);
	
	abstract protected function _extract($path, $arc);
	
	abstract protected function _archive($dir, $files, $name, $arc);
	
	abstract protected function _checkArchivers();
	
}

/**
 * fileManager driver for local filesystem.
 *
 * @author Dmitry (dio) Levashov
 * @author Troex Nevelin
 **/
class fileManagerVolumeLocal extends fileManagerVolumeDriver
{
	
	protected $driverId = 'l';
	
	protected $archiveSize = 0;
	
	public function __construct()
	{
		$this->options['alias']           = '';
		$this->options['dirMode']         = 0755;
		$this->options['fileMode']        = 0644;
		$this->options['quarantine']      = '.quarantine';
		$this->options['maxArcFilesSize'] = 0;
	}
	
	
	protected function configure()
	{
		$this->aroot = realpath($this->root);
		$root        = $this->stat($this->root);
		
		if ($this->options['quarantine']) {
			$this->attributes[] = array(
				'pattern' => '~^' . preg_quote(DIRECTORY_SEPARATOR . $this->options['quarantine']) . '$~',
				'read' => false,
				'write' => false,
				'locked' => true,
				'hidden' => true
			);
		}
		
		if ($this->options['tmbPath']) {
			$this->options['tmbPath'] = strpos($this->options['tmbPath'], DIRECTORY_SEPARATOR) === false ? $this->root . DIRECTORY_SEPARATOR . $this->options['tmbPath'] : $this->_normpath($this->options['tmbPath']);
		}
		
		parent::configure();
		
		if ($root['read'] && !$this->tmbURL && $this->URL) {
			if (strpos($this->tmbPath, $this->root) === 0) {
				$this->tmbURL = $this->URL . str_replace(DIRECTORY_SEPARATOR, '/', substr($this->tmbPath, strlen($this->root) + 1));
				if (preg_match("|[^/?&=]$|", $this->tmbURL)) {
					$this->tmbURL .= '/';
				}
			}
		}
		
		if (!empty($this->options['quarantine'])) {
			$this->quarantine = $this->root . DIRECTORY_SEPARATOR . $this->options['quarantine'];
			if ((!is_dir($this->quarantine) && !$this->_mkdir($this->root, $this->options['quarantine'])) || !is_writable($this->quarantine)) {
				$this->archivers['extract'] = array();
				$this->disabled[]           = 'extract';
			}
		} else {
			$this->archivers['extract'] = array();
			$this->disabled[]           = 'extract';
		}
		
	}
	
	
	
	protected function _dirname($path)
	{
		return dirname($path);
	}
	
	protected function _basename($path)
	{
		return basename($path);
	}
	
	protected function _joinPath($dir, $name)
	{
		return $dir . DIRECTORY_SEPARATOR . $name;
	}
	
	protected function _normpath($path)
	{
		if (empty($path)) {
			return '.';
		}
		
		if (strpos($path, '/') === 0) {
			$initial_slashes = true;
		} else {
			$initial_slashes = false;
		}
		
		if (($initial_slashes) && (strpos($path, '//') === 0) && (strpos($path, '///') === false)) {
			$initial_slashes = 2;
		}
		
		$initial_slashes = (int) $initial_slashes;
		
		$comps     = explode('/', $path);
		$new_comps = array();
		foreach ($comps as $comp) {
			if (in_array($comp, array(
				'',
				'.'
			))) {
				continue;
			}
			
			if (($comp != '..') || (!$initial_slashes && !$new_comps) || ($new_comps && (end($new_comps) == '..'))) {
				array_push($new_comps, $comp);
			} elseif ($new_comps) {
				array_pop($new_comps);
			}
		}
		$comps = $new_comps;
		$path  = implode('/', $comps);
		if ($initial_slashes) {
			$path = str_repeat('/', $initial_slashes) . $path;
		}
		
		return $path ? $path : '.';
	}
	
	protected function _relpath($path)
	{
		return $path == $this->root ? '' : substr($path, strlen($this->root) + 1);
	}
	
	protected function _abspath($path)
	{
		return $path == DIRECTORY_SEPARATOR ? $this->root : $this->root . DIRECTORY_SEPARATOR . $path;
	}
	
	protected function _path($path)
	{
		return $this->rootName . ($path == $this->root ? '' : $this->separator . $this->_relpath($path));
	}
	
	protected function _inpath($path, $parent)
	{
		return $path == $parent || strpos($path, $parent . DIRECTORY_SEPARATOR) === 0;
	}
	
	
	
	
	protected function _stat($path)
	{
		$stat = array();
		
		if (!file_exists($path)) {
			return $stat;
		}
		
		if ($path != $this->root && is_link($path)) {
			if (($target = $this->readlink($path)) == false || $target == $path) {
				$stat['mime']  = 'symlink-broken';
				$stat['read']  = false;
				$stat['write'] = false;
				$stat['size']  = 0;
				return $stat;
			}
			$stat['alias']  = $this->_path($target);
			$stat['target'] = $target;
			$path           = $target;
			$lstat          = lstat($path);
			$size           = $lstat['size'];
		} else {
			$size = @filesize($path);
		}
		
		$dir = is_dir($path);
		
		$stat['mime']  = $dir ? 'directory' : $this->mimetype($path);
		$stat['ts']    = filemtime($path);
		$stat['read']  = is_readable($path);
		$stat['write'] = is_writable($path);
		if ($stat['read']) {
			$stat['size'] = $dir ? 0 : $size;
		}
		
		return $stat;
	}
	
	
	protected function _subdirs($path)
	{
		
		if (($dir = dir($path))) {
			$dir = dir($path);
			while (($entry = $dir->read()) !== false) {
				$p = $dir->path . DIRECTORY_SEPARATOR . $entry;
				if ($entry != '.' && $entry != '..' && is_dir($p) && !$this->attr($p, 'hidden')) {
					$dir->close();
					return true;
				}
			}
			$dir->close();
		}
		return false;
	}
	
	protected function _dimensions($path, $mime)
	{
		clearstatcache();
		return strpos($mime, 'image') === 0 && ($s = @getimagesize($path)) !== false ? $s[0] . 'x' . $s[1] : false;
	}
	
	protected function readlink($path)
	{
		if (!($target = @readlink($path))) {
			return false;
		}
		
		if (substr($target, 0, 1) != DIRECTORY_SEPARATOR) {
			$target = dirname($path) . DIRECTORY_SEPARATOR . $target;
		}
		
		$atarget = realpath($target);
		
		if (!$atarget) {
			return false;
		}
		
		$root  = $this->root;
		$aroot = $this->aroot;
		
		if ($this->_inpath($atarget, $this->aroot)) {
			return $this->_normpath($this->root . DIRECTORY_SEPARATOR . substr($atarget, strlen($this->aroot) + 1));
		}
		
		return false;
	}
	
	protected function _scandir($path)
	{
		$files = array();
		
		foreach (scandir($path) as $name) {
			if ($name != '.' && $name != '..') {
				$files[] = $path . DIRECTORY_SEPARATOR . $name;
			}
		}
		return $files;
	}
	
	protected function _fopen($path, $mode = 'rb')
	{
		return @fopen($path, 'r');
	}
	
	protected function _fclose($fp, $path = '')
	{
		return @fclose($fp);
	}
	
	
	protected function _mkdir($path, $name)
	{
		$path = $path . DIRECTORY_SEPARATOR . $name;
		
		if (@mkdir($path)) {
			@chmod($path, $this->options['dirMode']);
			return $path;
		}
		
		return false;
	}
	
	protected function _mkfile($path, $name)
	{
		$path = $path . DIRECTORY_SEPARATOR . $name;
		
		if (($fp = @fopen($path, 'w'))) {
			@fclose($fp);
			@chmod($path, $this->options['fileMode']);
			return $path;
		}
		return false;
	}
	
	protected function _symlink($source, $targetDir, $name)
	{
		return @symlink($source, $targetDir . DIRECTORY_SEPARATOR . $name);
	}
	
	protected function _copy($source, $targetDir, $name)
	{
		return copy($source, $targetDir . DIRECTORY_SEPARATOR . $name);
	}
	
	protected function _move($source, $targetDir, $name)
	{
		$target = $targetDir . DIRECTORY_SEPARATOR . $name;
		return @rename($source, $target) ? $target : false;
	}
	
	protected function _unlink($path)
	{
		return @unlink($path);
	}
	
	protected function _rmdir($path)
	{
		return @rmdir($path);
	}
	
	protected function _save($fp, $dir, $name, $mime, $w, $h)
	{
		$path = $dir . DIRECTORY_SEPARATOR . $name;
		
		if (!($target = @fopen($path, 'wb'))) {
			return false;
		}
		
		while (!feof($fp)) {
			fwrite($target, fread($fp, 8192));
		}
		fclose($target);
		@chmod($path, $this->options['fileMode']);
		clearstatcache();
		return $path;
	}
	
	protected function _getContents($path)
	{
		return file_get_contents($path);
	}
	
	protected function _filePutContents($path, $content)
	{
		if (@file_put_contents($path, $content, LOCK_EX) !== false) {
			clearstatcache();
			return true;
		}
		return false;
	}
	
	protected function _checkArchivers()
	{
		if (!function_exists('exec')) {
			$this->options['archivers'] = $this->options['archive'] = array();
			return;
		}
		$arcs = array(
			'create' => array(),
			'extract' => array()
		);
		
		$this->procExec('tar --version', $o, $ctar);
		
		if ($ctar == 0) {
			$arcs['create']['application/x-tar']  = array(
				'cmd' => 'tar',
				'argc' => '-cf',
				'ext' => 'tar'
			);
			$arcs['extract']['application/x-tar'] = array(
				'cmd' => 'tar',
				'argc' => '-xf',
				'ext' => 'tar'
			);
			unset($o);
			$test = $this->procExec('gzip --version', $o, $c);
			
			if ($c == 0) {
				$arcs['create']['application/x-gzip']  = array(
					'cmd' => 'tar',
					'argc' => '-czf',
					'ext' => 'tgz'
				);
				$arcs['extract']['application/x-gzip'] = array(
					'cmd' => 'tar',
					'argc' => '-xzf',
					'ext' => 'tgz'
				);
			}
			unset($o);
			$test = $this->procExec('bzip2 --version', $o, $c);
			if ($c == 0) {
				$arcs['create']['application/x-bzip2']  = array(
					'cmd' => 'tar',
					'argc' => '-cjf',
					'ext' => 'tbz'
				);
				$arcs['extract']['application/x-bzip2'] = array(
					'cmd' => 'tar',
					'argc' => '-xjf',
					'ext' => 'tbz'
				);
			}
		}
		unset($o);
		$this->procExec('zip -v', $o, $c);
		if ($c == 0) {
			$arcs['create']['application/zip'] = array(
				'cmd' => 'zip',
				'argc' => '-r9',
				'ext' => 'zip'
			);
		}
		unset($o);
		$this->procExec('unzip --help', $o, $c);
		if ($c == 0) {
			$arcs['extract']['application/zip'] = array(
				'cmd' => 'unzip',
				'argc' => '',
				'ext' => 'zip'
			);
		}
		unset($o);
		$this->procExec('rar --version', $o, $c);
		if ($c == 0 || $c == 7) {
			$arcs['create']['application/x-rar']  = array(
				'cmd' => 'rar',
				'argc' => 'a -inul',
				'ext' => 'rar'
			);
			$arcs['extract']['application/x-rar'] = array(
				'cmd' => 'rar',
				'argc' => 'x -y',
				'ext' => 'rar'
			);
		} else {
			unset($o);
			$test = $this->procExec('unrar', $o, $c);
			if ($c == 0 || $c == 7) {
				$arcs['extract']['application/x-rar'] = array(
					'cmd' => 'unrar',
					'argc' => 'x -y',
					'ext' => 'rar'
				);
			}
		}
		unset($o);
		$this->procExec('7za --help', $o, $c);
		if ($c == 0) {
			$arcs['create']['application/x-7z-compressed']  = array(
				'cmd' => '7za',
				'argc' => 'a',
				'ext' => '7z'
			);
			$arcs['extract']['application/x-7z-compressed'] = array(
				'cmd' => '7za',
				'argc' => 'e -y',
				'ext' => '7z'
			);
			
			if (empty($arcs['create']['application/x-gzip'])) {
				$arcs['create']['application/x-gzip'] = array(
					'cmd' => '7za',
					'argc' => 'a -tgzip',
					'ext' => 'tar.gz'
				);
			}
			if (empty($arcs['extract']['application/x-gzip'])) {
				$arcs['extract']['application/x-gzip'] = array(
					'cmd' => '7za',
					'argc' => 'e -tgzip -y',
					'ext' => 'tar.gz'
				);
			}
			if (empty($arcs['create']['application/x-bzip2'])) {
				$arcs['create']['application/x-bzip2'] = array(
					'cmd' => '7za',
					'argc' => 'a -tbzip2',
					'ext' => 'tar.bz'
				);
			}
			if (empty($arcs['extract']['application/x-bzip2'])) {
				$arcs['extract']['application/x-bzip2'] = array(
					'cmd' => '7za',
					'argc' => 'a -tbzip2 -y',
					'ext' => 'tar.bz'
				);
			}
			if (empty($arcs['create']['application/zip'])) {
				$arcs['create']['application/zip'] = array(
					'cmd' => '7za',
					'argc' => 'a -tzip -l',
					'ext' => 'zip'
				);
			}
			if (empty($arcs['extract']['application/zip'])) {
				$arcs['extract']['application/zip'] = array(
					'cmd' => '7za',
					'argc' => 'e -tzip -y',
					'ext' => 'zip'
				);
			}
			if (empty($arcs['create']['application/x-tar'])) {
				$arcs['create']['application/x-tar'] = array(
					'cmd' => '7za',
					'argc' => 'a -ttar -l',
					'ext' => 'tar'
				);
			}
			if (empty($arcs['extract']['application/x-tar'])) {
				$arcs['extract']['application/x-tar'] = array(
					'cmd' => '7za',
					'argc' => 'e -ttar -y',
					'ext' => 'tar'
				);
			}
		}
		
		$this->archivers = $arcs;
	}
	
	protected function _unpack($path, $arc)
	{
		$cwd = getcwd();
		$dir = $this->_dirname($path);
		chdir($dir);
		$cmd = $arc['cmd'] . ' ' . $arc['argc'] . ' ' . escapeshellarg($this->_basename($path));
		$this->procExec($cmd, $o, $c);
		chdir($cwd);
	}
	
	protected function _findSymlinks($path)
	{
		if (is_link($path)) {
			return true;
		}
		
		if (is_dir($path)) {
			foreach (scandir($path) as $name) {
				if ($name != '.' && $name != '..') {
					$p = $path . DIRECTORY_SEPARATOR . $name;
					if (is_link($p)) {
						return true;
					}
					if (is_dir($p) && $this->_findSymlinks($p)) {
						return true;
					} elseif (is_file($p)) {
						$this->archiveSize += filesize($p);
					}
				}
			}
		} else {
			$this->archiveSize += filesize($path);
		}
		
		return false;
	}
	
	protected function _extract($path, $arc)
	{
		
		if ($this->quarantine) {
			$dir     = $this->quarantine . DIRECTORY_SEPARATOR . str_replace(' ', '_', microtime()) . basename($path);
			$archive = $dir . DIRECTORY_SEPARATOR . basename($path);
			
			if (!@mkdir($dir)) {
				return false;
			}
			
			chmod($dir, 0777);
			
			if (!copy($path, $archive)) {
				return false;
			}
			
			$this->_unpack($archive, $arc);
			@unlink($archive);
			
			$ls = array();
			foreach (scandir($dir) as $i => $name) {
				if ($name != '.' && $name != '..') {
					$ls[] = $name;
				}
			}
			
			if (empty($ls)) {
				return false;
			}
			
			$this->archiveSize = 0;
			
			$symlinks = $this->_findSymlinks($dir);
			$this->remove($dir);
			
			if ($symlinks) {
				return $this->setError(fileManager::ERROR_ARC_SYMLINKS);
			}
			
			if ($this->options['maxArcFilesSize'] > 0 && $this->options['maxArcFilesSize'] < $this->archiveSize) {
				return $this->setError(fileManager::ERROR_ARC_MAXSIZE);
			}
			
			
			
			if (count($ls) == 1) {
				$this->_unpack($path, $arc);
				$result = dirname($path) . DIRECTORY_SEPARATOR . $ls[0];
				
				
			} else {
				$name = basename($path);
				if (preg_match('/\.((tar\.(gz|bz|bz2|z|lzo))|cpio\.gz|ps\.gz|xcf\.(gz|bz2)|[a-z0-9]{1,4})$/i', $name, $m)) {
					$name = substr($name, 0, strlen($name) - strlen($m[0]));
				}
				$test = dirname($path) . DIRECTORY_SEPARATOR . $name;
				if (file_exists($test) || is_link($test)) {
					$name = $this->uniqueName(dirname($path), $name, '-', false);
				}
				
				$result  = dirname($path) . DIRECTORY_SEPARATOR . $name;
				$archive = $result . DIRECTORY_SEPARATOR . basename($path);
				
				if (!$this->_mkdir(dirname($path), $name) || !copy($path, $archive)) {
					return false;
				}
				
				$this->_unpack($archive, $arc);
				@unlink($archive);
			}
			
			return file_exists($result) ? $result : false;
		}
	}
	
	protected function _archive($dir, $files, $name, $arc)
	{
		$cwd = getcwd();
		chdir($dir);
		
		$files = array_map('escapeshellarg', $files);
		
		$cmd = $arc['cmd'] . ' ' . $arc['argc'] . ' ' . escapeshellarg($name) . ' ' . implode(' ', $files);
		$this->procExec($cmd, $o, $c);
		chdir($cwd);
		
		$path = $dir . DIRECTORY_SEPARATOR . $name;
		return file_exists($path) ? $path : false;
	}
	
}
