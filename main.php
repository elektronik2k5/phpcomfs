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
}

class VirtualFile implements iNode {
	private $name;
	public function __construct($name){
		$this->name = $name;
    }
    protected function getName(){
    	return $this->name;
    }
    public function toString(){
    	return $this->getName()."\n";
    }
}

class VirtualDirectory implements iNode {
	private $children = array();
	private $name;
	
	public function __construct($name){
		$this->name = $name;
    }
    protected function getName(){
    	return $this->name;
    }
	public function addChild($childNode){
		$this->children[] = $childNode;
		return $childNode;
	}
	public function toString(){
    	print $this->getName()."\n";
    	foreach ($this->children as $childNode) {
    		$childNode->toString();
    	}
    	return true;
    }
}

class VirtualDirectoryCreator {
	public static $padding = "    ";
	public static function readDir($node, $directory, $padding = '') {
		static $ownFunctionName = __FUNCTION__;
		if ($handle = @opendir($directory)) {
			while (($entry = readdir($handle)) !== false) {
				if ($entry != "." && $entry != "..") {
					echo "${padding}└── $entry\n";
					$isChildDirectory = is_dir($childDirectory = $directory.DIRECTORY_SEPARATOR.$entry);
					$newNode = $isChildDirectory ? (new VirtualDirectory($entry)) : (new VirtualFile($entry));
					$node->addChild($newNode);
					if ($isChildDirectory) {
						self::$ownFunctionName($newNode, $childDirectory, $padding.self::$padding);
					}
				}
			}
			closedir($handle);
		} else {
			error("Cannot read '$directory' directory. Could be a permissions or transport error. Exiting.") || die();
		}
		return $node;
	}

	private $root;
	public function __construct($path){
		$root = new VirtualDirectory($path);
		$this->root = self::readDir($root, $path);
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
		$vd = new VirtualDirectoryCreator($givenPath);
		print_r($vd);
	}
}

Program::main($argv);