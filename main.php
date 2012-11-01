#!/usr/bin/php
<?php

function p($message = ''){
	print($message."\n");
}
function error($message){
	file_put_contents('php://stderr', "$message\n");
}

interface iNode {
	public function toString();
	public function getSize();
	public function getChildren();
}

function human_filesize($bytes, $decimals = 2) {
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

class VirtualFile implements iNode {
	private $name, $size;
	public function __construct($name){
		$this->name = basename($name);
		$this->size = filesize($name);
	}
	protected function getName(){
		return $this->name;
	}
	public function getSize(){
		return $this->size;
	}
	public function getChildren(){
		return null;
	}
	public function toString(){
		return sprintf("%s [%s]", $this->getName(), human_filesize($this->getSize(), 0));
	}
}

class VirtualDirectory implements iNode {
	private $name, $size = null, $children = array();
	
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
	
	protected function getName(){
		return $this->name;
	}
	public function getSize(){
		if ($this->size !== null) {
			return $this->size;
		}
		$size = 0;
		foreach ($this->children as $childNode) {
			$size += $childNode->getSize();
		}
		return $this->size = $size;
	}
	public function getChildren(){
		return $this->children;
	}
	public function toString(){
		$childNodes = '';
		foreach ($this->children as $childNode) {
			$childNodes .= "└── ".$childNode->toString()."\n";
		}
		return sprintf("%s [%s]\n%s", $this->getName(), human_filesize($this->getSize(), 0), $childNodes);
	}
}

class VirtualFileSystem {
	private $root;
	public function __construct($path){
		$this->root = new VirtualDirectory($path);
	}
	public function walkFs(){
		print $this->root->toString();
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
		p($givenPath);
		//$vd = new VirtualDirectory($givenPath);
		//print_r($vd);
		//print $vd->toString();
		$vfs = new VirtualFileSystem($givenPath);
		$vfs->walkFs();
	}
}

Program::main($argv);