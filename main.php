#!/usr/bin/php
<?php
function p($message = ''){
	print($message."\n");
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

class VirtualDirectory extends VirtualFile {
	private $children = array();

	public function addChild($childNode){
		$childName = $childNode->getName();
		if (array_key_exists($childName, $this->children)) {
			throw new Exception("Can't add child with name '$childName', since thid directory already has a child with this name", 1);
		} else {
			$this->children[] = $childNode;
		}
		return true;
	}
	public function toString(){
    	print parent::toString();
    	foreach ($this->children as $childNode) {
    		$childNode->toString();
    	}
    	return true;
    }
}

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