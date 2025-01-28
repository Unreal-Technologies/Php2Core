<?php
namespace Php2Core;

class Version
{
    /**
     * @var Version[]
     */
    private array $_children = [];
    
    /**
     * @var int
     */
    private int $_position = 0;
    
    /**
     * @var string
     */
    private string $_name = '';
    
    /**
     * @var int
     */
    private int $_build = 0;
    
    /**
     * @var int
     */
    private int $_major = 0;
    
    /**
     * @var int
     */
    private int $_minor = 0;
    
    /**
     * @var int
     */
    private int $_revision = 0;
    
    /**
     * @var string|null
     */
    private ?string $_url = null;
    
    /**
     * @param string $name
     * @param int $build
     * @param int $major
     * @param int $minor
     * @param int $revision
     * @param string|null $url
     */
    public function __construct(string $name, int $build, int $major, int $minor, int $revision, ?string $url = null)
    {
        $this -> Update($name, $build, $major, $minor, $revision, $url);
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
     * @param string|null $url
     * @return void
     */
    public function Update(string $name, int $build, int $major, int $minor, int $revision, ?string $url = null): void
    {
        $this -> _name = $name;
        $this -> _build = $build;
        $this -> _major = $major;
        $this -> _minor = $minor;
        $this -> _revision = $revision;
        $this -> _url = $url;
    }
    
    /**
     * @param Version $version
     * @return void
     */
    public function Add(Version $version): void
    {
        $this -> UpdatePositionRecursive($version, $this -> _position + 1);
        $this -> _children[] = $version;
    }
    
    /**
     * @param Version $version
     * @param int $value
     * @return void
     */
    private function UpdatePositionRecursive(Version $version, int $value): void
    {
        $version -> _position = $value;
        
        foreach($version -> _children as $child)
        {
            $this -> UpdatePositionRecursive($child, $value + 1);
        }
    }
    
    /**
     * @param NoHTML\Raw $container
     * @return void
     */
    public function Render(NoHTML\XHtml $container): void
    {
        $raw = $this -> _name.' ( '.$this -> _build.'.'.$this -> _major.'.'.$this -> _minor.'.'.$this -> _revision.' )';
        
        //Create Url link where needed
        if($this -> _url === null)
        {
            $container -> Text($raw);
        }
        else
        {
            $container -> Add('a', function(NoHTML\XHtml $a) use($raw)
            {
                $a -> Text($raw);
                $a -> Attributes() -> Set('href', $this -> _url);
                $a -> Attributes() -> Set('target', '_blank');
            });
        }
        
        //Go Through Children
        if(count($this -> _children) !== 0)
        {
            $container -> Add('ul', function(NoHTML\XHtml $ul)
            {
                foreach($this -> _children as $child)
                {
                    $ul -> Add('li', function(NoHTML\XHtml $li) use($child)
                    {
                        new NoHTML\FontAwesome\Icon($li, 'fad fa-chevron-double-right');
                        //$li -> IconFA('fad fa-chevron-double-right');
                        $child -> Render($li);
                    });
                }
            });
        }
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