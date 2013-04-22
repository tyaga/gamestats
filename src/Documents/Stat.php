<?
namespace Documents;

use \Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="statdata") */
class Stat {
	/** @ODM\Id */
	private $id;

	/** @ODM\Timestamp */
	private $timestamp;

	/** @ODM\String */
	private $value;

	public function getId() { return $this->id; }
	public function getValue() { return $this->value; }
	public function getTimestamp() { return $this->timestamp; }
}