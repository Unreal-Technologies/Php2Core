<?php
namespace Php2Core;

class Version
{
    /**
     * @var Version[]
     */
    private array $aChildren = [];
    
    /**
     * @var int
     */
    private int $iPosition = 0;
    
    /**
     * @var string
     */
    private string $sName = '';
    
    /**
     * @var int
     */
    private int $iBuild = 0;
    
    /**
     * @var int
     */
    private int $iMajor = 0;
    
    /**
     * @var int
     */
    private int $iMinor = 0;
    
    /**
     * @var int
     */
    private int $iRevision = 0;
    
    /**
     * @var string|null
     */
    private ?string $sUrl = null;
    
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
    public function clear(): void
    {
        $this -> aChildren = [];
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
    public function update(string $name, int $build, int $major, int $minor, int $revision, ?string $url = null): void
    {
        $this -> sName = $name;
        $this -> iBuild = $build;
        $this -> iMajor = $major;
        $this -> iMinor = $minor;
        $this -> iRevision = $revision;
        $this -> sUrl = $url;
    }
    
    /**
     * @param Version $version
     * @return void
     */
    public function add(Version $version): void
    {
        $this -> UpdatePositionRecursive($version, $this -> iPosition + 1);
        $this -> aChildren[] = $version;
    }
    
    /**
     * @param Version $version
     * @param int $value
     * @return void
     */
    private function updatePositionRecursive(Version $version, int $value): void
    {
        $version -> iPosition = $value;
        
        foreach($version -> aChildren as $child)
        {
            $this -> updatePositionRecursive($child, $value + 1);
        }
    }
    
    /**
     * @param NoHTML\Raw $container
     * @return void
     */
    public function render(GUI\NoHTML\XHtml $container): void
    {
        $raw = $this -> sName.' ( '.$this -> iBuild.'.'.$this -> iMajor.'.'.$this -> iMinor.'.'.$this -> iRevision.' )';
        
        //Create Url link where needed
        if($this -> sUrl === null)
        {
            $container -> text($raw);
        }
        else
        {
            $container -> add('a', function(GUI\NoHTML\XHtml $a) use($raw)
            {
                $a -> text($raw);
                $a -> attributes() -> set('href', $this -> sUrl);
                $a -> attributes() -> set('target', '_blank');
            });
        }
        
        //Go Through Children
        if(count($this -> aChildren) !== 0)
        {
            $container -> add('ul', function(GUI\NoHTML\XHtml $ul)
            {
                foreach($this -> aChildren as $child)
                {
                    $ul -> add('li', function(GUI\NoHTML\XHtml $li) use($child)
                    {
                        new GUI\NoHTML\FontAwesome\Icon($li, 'fad fa-chevron-double-right');
                        $child -> render($li);
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
        foreach($this -> aChildren as $child)
        {
            $children[] = (string)$child;
        }
        
        return "Version[aChildren={" . implode(' & ', $children)
                . "}, iPosition=" . $this->iPosition
                . ", sName=" . $this->sName
                . ", iBuild=" . $this->iBuild
                . ", iMajor=" . $this->iMajor
                . ", iMinor=" . $this->iMinor
                . ", iRevision=" . $this->iRevision
                . "]";
    }
}
