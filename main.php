#!/usr/bin/php
<?php
interface iNode {
	public function getName();
	public function getSize();
	public function getChildren();
}

class VirtualFile implements iNode {
	private $name, $size;
	public function __construct($name){
		$this->name = basename($name);
		$this->size = filesize($name);
	}
	public function getName(){
		return $this->name;
	}
	public function getSize(){
		return $this->size;
	}
	public function getChildren(){
		return null;
	}
}

class VirtualDirectory implements iNode {
	private $name, $children = array();
	
	public function __construct($directory){
		if ($handle = @opendir($directory)) {
			$this->name = basename($directory);
			while (($entry = readdir($handle)) !== false) {
				if ($entry != "." && $entry != "..") {
					$childNode = $directory.DIRECTORY_SEPARATOR.$entry;
					$isChildDirectory = is_dir($childNode);
					$vd = get_class($this);
					$newNode = $isChildDirectory ? (new $vd($childNode)) : (new VirtualFile($childNode));
					$this->children[] = $newNode;
				}
			}
			closedir($handle);
		} else {
			Program::error("Cannot read '$directory' directory. Could be a permissions or transport error. Exiting.") || die();
		}
	}
	
	public function getName(){
		return $this->name;
	}
	public function getSize(){
		return null;
	}
	public function getChildren(){
		return $this->children;
	}
}

class VirtualFileSystem {
	public $root, $printNodes, $printDirectories, $printNodesAndSizes;
	public static function walkFs($node, $callback){
		$callback($node);
		static $ownName = __FUNCTION__;
		if (($childNodes = $node->getChildren()) !== null) {
			foreach ($childNodes as $childNode) {
				self::$ownName($childNode, $callback);
			}
		}
	}
	public static function getHumanFileSize($bytes, $decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}
	public function __construct($path){
		$this->root = new VirtualDirectory($path);
		$printNodes = $this->printNodes = function($node){
			printf("    %s\n", $node->getName());
		};
		$this->printDirectories = function($node) use ($printNodes){
			if ($node->getChildren() === null) {
				return;
			}
			return $printNodes($node);
		};
		$vfs = get_class($this);
		$this->printNodesAndSizes = function($node) use ($vfs){
			$nodeSize = $node->getSize();
			$humanSize = array($vfs, 'getHumanFileSize');
			$sizeToPrint = $nodeSize === null ? 'DIR' : call_user_func($humanSize, $nodeSize, 0);
			printf("    %s [%s]\n", $node->getName(), $sizeToPrint);
		};
	}
}

class Program {
	public static function error($message){
		file_put_contents('php://stderr', "$message\n");
		return $message;
	}
	protected static function validateArgs($args){
		$isValid = true;
		$message = '';
		if (count($args) != 2) {
			$isValid = false;
			$message = "Usage: ".$args[0]." PATH_TO_DIRECTORY";
		}
		$givenPath = array_pop($args);
		if (!is_dir($givenPath)) {
			$isValid = false;
			$message = "'$givenPath' isn't a directory. Exiting.";
		}
		if ($isValid) {
			$message = $givenPath;
		}
		return array($isValid, $message);
	}

	public static function main($args){
		list($status, $message) = self::validateArgs($args);
		if (!$status) {
			self::error($message) && die();
		}
		$path = $message;
		echo "Building data structure of directory '$path'...\n";
		$vfs = new VirtualFileSystem($message);
		$root = $vfs->root;
		echo "\nDirectory recursive structure:\n";
		$vfs::walkFs($root, $vfs->printNodes);
		echo "\nDirectory recursive structure, only directories:\n";
		$vfs::walkFs($root, $vfs->printDirectories);
		echo "\nDirectory recursive structure, with file sizes:\n";
		$vfs::walkFs($root, $vfs->printNodesAndSizes);
	}
}

Program::main($argv);