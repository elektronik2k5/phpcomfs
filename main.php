#!/usr/bin/php
<?php

function error($message){
	file_put_contents('php://stderr', "$message\n");
}

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
		if ($handle = opendir($directory)) {
			$this->name = basename($directory);
			while (($entry = readdir($handle)) !== false) {
				if ($entry != "." && $entry != "..") {
					//echo "└── $entry\n";
					$childNode = $directory.DIRECTORY_SEPARATOR.$entry;
					$isChildDirectory = is_dir($childNode);
					$vd = get_class($this);
					$newNode = $isChildDirectory ? (new $vd($childNode)) : (new VirtualFile($childNode));
					$this->children[] = $newNode;
				}
			}
			closedir($handle);
		} else {
			error("Cannot read '$directory' directory. Could be a permissions or transport error. Exiting.") || die();
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
}

class Program {
	public static function main($args){
		if (count($args) != 2) {
			error("Usage: ".$args[0]." PATH_TO_DIRECTORY") || die();
		}
		$givenPath = array_pop($args);
		if (!is_dir($givenPath)) {
			error("'$givenPath' isn't a directory. Exiting.") || die();
		}
		$vfs = 'VirtualFileSystem';
		$printNodes = function($node){
			printf("\t%s\n", $node->getName());
		};
		$printDirectories = function($node) use ($vfs, $printNodes){
			if ($node->getChildren() === null) {
				return;
			}
			return $printNodes($node);
		};
		$printNodesAndSizes = function($node) use ($vfs){
			$nodeSize = $node->getSize();
			$humanSize = array($vfs, 'getHumanFileSize');
			$sizeToPrint = $nodeSize === null ? 'DIR' : call_user_func($humanSize, $nodeSize, 0);
			printf("\t%s [%s]\n", $node->getName(), $sizeToPrint);
		};
		echo "Building data structure of directory '$givenPath'...\n";
		$root = new VirtualDirectory($givenPath);
		echo "\nDirectory recursive structure:\n";
		$vfs::walkFs($root, $printNodes);
		echo "\nDirectory recursive structure, only directories:\n";
		$vfs::walkFs($root, $printDirectories);
		echo "\nDirectory recursive structure, with file sizes:\n";
		$vfs::walkFs($root, $printNodesAndSizes);
	}
}

Program::main($argv);