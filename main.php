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
		$childName = $childNode->toString();
		if (array_key_exists($childName, $this->children)) {
			throw new Exception("Can't add child with name '$childName', since this directory already has a child with this name", 1);
		} else {
			$this->children[] = $childNode;
		}
		return true;
	}

	public function toString(){
    	print $this->getName()."\n";
    	foreach ($this->children as $childNode) {
    		$childNode->toString();
    	}
    	return true;
    }
}

class Program {
	public static $padding = "    ";
	public static function readDir($directory, $padding = '') {
		static $ownFunctionName = __FUNCTION__;
		if ($handle = @opendir($directory)) {
			while (($entry = readdir($handle)) !== false) {
				if ($entry != "." && $entry != "..") {
					echo "${padding}└── $entry\n";
					if (is_dir($childDir = $directory.DIRECTORY_SEPARATOR.$entry)) {
						self::$ownFunctionName($childDir, $padding.self::$padding);
					}
				}
			}
			closedir($handle);
		} else {
			error("Cannot read '$directory' directory. Could be a permissions or transport error. Exiting.") || die();
		}
	}

	public static function main($args){
		if (count($args) != 2) {
			error("Usage: ".$args[0]." PATH_TO_DIRECTORY") || die();
		}
		$givenPath = array_pop($args);
		if (!is_dir($givenPath)) {
			error("'$givenPath' isn't a directory. Exiting.") || die();
		}
		p($givenPath);
		self::readDir($givenPath);
	}
}

class VirtualDirectoryCreator {
	private $root;
	public function __construct($args){
		
	}
}



Program::main($argv);
return;
$f1 = new VirtualFile('1');
$f2 = new VirtualFile('second');
$d1 = new VirtualDirectory('root');
$d1->addChild($f1);

$files = array($f1, $f2, $d1);
foreach ($files as $file) {
	p();
	print $file->toString();
}

p();