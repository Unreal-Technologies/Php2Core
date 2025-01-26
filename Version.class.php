<?php
namespace Php2Core;

class Version
{
    /**
     * @var Version[]
     */
    private $_children = [];
    
    /**
     * @var int
     */
    private $_position = 0;
    
    /**
     * @var string
     */
    private $_name = '';
    
    /**
     * @var int
     */
    private $_build = 0;
    
    /**
     * @var int
     */
    private $_major = 0;
    
    /**
     * @var int
     */
    private $_minor = 0;
    
    /**
     * @var int
     */
    private $_revision = 0;
    
    /**
     * @param string $name
     * @param int $build
     * @param int $major
     * @param int $minor
     * @param int $revision
     */
    public function __construct(string $name, int $build, int $major, int $minor, int $revision)
    {
        $this -> Update($name, $build, $major, $minor, $revision);
        $this -> Clear();
    }
    
    /**
     * @return void
     */
    public function Clear(): void
    {
        $this -> _children = [];
    }
    
    /**
     * @param string $name
     * @param int $build
     * @param int $major
     * @param int $minor
     * @param int $revision
     * @return void
     */
    public function Update(string $name, int $build, int $major, int $minor, int $revision): void
    {
        $this -> _name = $name;
        $this -> _build = $build;
        $this -> _major = $major;
        $this -> _minor = $minor;
        $this -> _revision = $revision;
    }
    
    /**
     * @param Version $version
     * @return void
     */
    public function Add(Version $version): void
    {
        $version -> _position = $this -> _position + 1;
        $this -> _children[] = $version;
    }
    
    /**
     * @return string
     */
    public function __toString(): string 
    {
        $children = [];
        foreach($this -> _children as $child)
        {
            $children[] = (string)$child;
        }
        
        return "Version[_children={" . implode(' & ', $children)
                . "}, _position=" . $this->_position
                . ", _name=" . $this->_name
                . ", _build=" . $this->_build
                . ", _major=" . $this->_major
                . ", _minor=" . $this->_minor
                . ", _revision=" . $this->_revision
                . "]";
    }
}