<?php

// ============================================================================================
// CAS class
//
// (2017.10.31) v1.0 First version
//
class CAS
{

	private $HEADER = "\x1F\xA6\xDE\xBA\xCC\x13\x7D\x74";
	private $ASCII  = "\xEA\xEA\xEA\xEA\xEA\xEA\xEA\xEA\xEA\xEA";
	private $BIN    = "\xD0\xD0\xD0\xD0\xD0\xD0\xD0\xD0\xD0\xD0";
	private $BASIC  = "\xD3\xD3\xD3\xD3\xD3\xD3\xD3\xD3\xD3\xD3";

	private $blocks = array();


	public function __construct($filename = NULL)
	{
		$this->clear();
		if ($filename!==NULL) {
			$this->loadFromFile($filename);
		}
	}

	public function clear()
	{
		$this->blocks = array();
	}

	public function loadFromFile($filename)
	{
		if (($bytes=file_get_contents($filename))!==NULL) {
			$this->clear();

			//Search for block headers
			while (strlen($bytes)>0 && substr($bytes, 0, 8)===$this->HEADER) {
				$bytes = substr($bytes, 8);
				$type = substr($bytes, 0, 10);

				$b = new CASBlock();
				$tmp = "";
				while (strlen($bytes)>0 && substr($bytes, 0, 8)!==$this->HEADER) {
					$tmp .= substr($bytes, 0, 8);
					$bytes = substr($bytes, 8);
				}
				$b->data($tmp);
				$this->blocks[] = $b;
			}
			return true;
		}
		return false;
	}

	public function saveToFile($filename)
	{
		file_put_contents($filename, $this->getBytes());
	}

	public function getBytes()
	{
		$bytes = "";
		foreach ($this->blocks as $b) {
			$bytes .= $this->HEADER . $b->data();
		}
		return $bytes;
	}

	public function getNumBlocks()
	{
		return count($this->blocks);
	}

	public function getBlock($pos)
	{
		return $this->blocks[$pos];
	}

	public function getLastBlock()
	{
		return end($this->blocks);
	}

	public function addBlock($newBlock)
	{
		$this->blocks[] = $newBlock;
	}

	public function insertBlock($index, $newBlock)
	{
		if ($index>count($this->blocks)) {
			$this->addBlock($newBlock);
		} else {
			array_splice($this->blocks, $index, 0, "");
			$this->blocks[$index] = $newBlock;
		}
	}

	public function deleteBlock($index)
	{
		array_splice($this->blocks, $index, 1);
	}

	public function getInfo()
	{
				$info = array();

		//File info
		$file = array();
		$tmp = $this->getBytes();
		$file['fileSize'] = strlen($tmp);
		$file['blocks'] = $this->getNumBlocks();
		$file['crc32'] = hash("crc32b", $tmp);
		$file['md5'] = md5($tmp);
		$file['sha1'] = sha1($tmp);
		$info['file'] = $file;

		//Blocks
		$blocks = array();
		$pos = 0;
		foreach ($this->blocks as $b) {
			$tmp = $b->getInfo();
			if ($tmp!="") $blocks[$pos] = $tmp;
			$pos++;
		}
		$info['blocks'] = $blocks;

		return $info;
	}
}

class CASBlock
{
	private $ASCII  = "\xEA\xEA\xEA\xEA\xEA\xEA\xEA\xEA\xEA\xEA";
	private $BIN    = "\xD0\xD0\xD0\xD0\xD0\xD0\xD0\xD0\xD0\xD0";
	private $BASIC  = "\xD3\xD3\xD3\xD3\xD3\xD3\xD3\xD3\xD3\xD3";

	private $header;
	private $data;

	public function data($value=NULL)
	{
		if ($value===NULL) {
			return $this->data;
		} else {
			$this->data = $value;
		}
	}

	public function getInfo() {
		$info = array();
		$info['bytesLength'] = strlen($this->data);
		$info['crc32'] = hash("crc32b", $this->data);
		$info['md5'] = md5($this->data);
		$info['sha1'] = sha1($this->data);
		return $info;
	}
}
	
?>