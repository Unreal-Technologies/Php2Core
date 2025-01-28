<?php
namespace Php2Core\NoHTML;

/**
 * Description of XHtml
 *
 * @author Peter
 */
class XHtml 
{
    /**
     * @var string
     */
    private string $_tag = 'html';
    
    /**
     * @var string
     */
    private string $_path = 'html';
    
    /**
     * @var int
     */
    private int $_position = 0;
    
    /**
     * @var Attributes
     */
    private Attributes $_attributes;
    
    /** 
     * @var array
     */
    private array $_children = [];
    
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this -> _attributes = new Attributes();
        if(!defined('XHTML'))
        {
            define('XHTML', $this);
        }
    }
    
    public function __toString(): string 
    {
        $tab = str_repeat('  ', $this -> _position);
        
        $out = $tab.'<'.$this -> _tag.$this -> _attributes.'>'."\r\n";
        foreach($this -> _children as $child)
        {
            if($child instanceof XHtml)
            {
                $out .= (string)$child;
            }
            else
            {
                $out .= $child."\r\n";
            }
        }
        $out .= $tab.'</'.$this -> _tag.'>'."\r\n";
        
        return $out;
    }
    
    /**
     * @return Attributes
     */
    public function Attributes(): Attributes
    {
        return $this -> _attributes;
    }
    
    /**
     * @param string $tag
     * @param \Closure $callback
     * @return void
     */
    public function Add(string $tag, \Closure $callback=null): void
    {
        $obj = new XHtml();
        $obj -> _tag = $tag;
        $obj -> _path = $this -> _path.'/'.$tag;
        $obj -> _position = $this -> _position + 1;
        $this -> _children[] = $obj;
        
        if($callback !== null)
        {
            $callback($obj);
        }
    }
    
    /**
     * @param string $text
     * @return void
     */
    public function Text(string $text): void
    {
        $this -> _children[] = $text;
    }
    
    /**
     * @param string|XHtml $content
     * @return void
     */
    public function Append(mixed $content): void
    {
        if(is_string($content) || $content instanceof XHtml)
        {
            $this -> _children[] = $content;
        }
    }
    
    /**
     * @return (XHtml|string)[]
     */
    public function Children(): array
    {
        return $this -> _children;
    }
    
    /**
     * @return void
     */
    public function Clear(): void
    {
        $this -> _children = [];
        $this -> _attributes -> Clear();
    }
    
    /**
     * @param string $path
     * @param \Closure $callback
     * @return void
     */
    public function Get(string $path, \Closure $callback): void
    {
        $components = explode('/', $path);
        $current = [ $this ];
        
        foreach($components as $component)
        {
            $matches = [];
            foreach($current as $cObj)
            {
                foreach($cObj -> _children as $child)
                {
                    if($child instanceof XHtml && $child -> _tag === $component)
                    {
                        $matches[] = $child;
                    }
                }
            }
            $current = $matches;
            
            if(count($current) === 0)
            {
                break;
            }
        }
        
        foreach($current as $cObj)
        {
            $callback($cObj);
        }
    }
}
