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
    private string $sTag = 'html';
    
    /**
     * @var string
     */
    private string $sPath = 'html';
    
    /**
     * @var int
     */
    private int $iPosition = 0;
    
    /**
     * @var Attributes
     */
    private Attributes $oAttributes;
    
    /** 
     * @var array
     */
    private array $aChildren = [];
    
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this -> oAttributes = new Attributes();
        if(!defined('XHTML'))
        {
            define('XHTML', $this);
        }
    }
    
    public function __toString(): string 
    {
        $tab = str_repeat('  ', $this -> iPosition);
        
        $out = $tab.'<'.$this -> sTag.$this -> oAttributes.'>'."\r\n";
        foreach($this -> aChildren as $child)
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
        $out .= $tab.'</'.$this -> sTag.'>'."\r\n";
        
        return $out;
    }
    
    /**
     * @return Attributes
     */
    public function attributes(): Attributes
    {
        return $this -> oAttributes;
    }
    
    /**
     * @param string $tag
     * @param \Closure $callback
     * @return void
     */
    public function add(string $tag, \Closure $callback=null): void
    {
        $obj = new XHtml();
        $obj -> sTag = $tag;
        $obj -> sPath = $this -> sPath.'/'.$tag;
        $obj -> iPosition = $this -> iPosition + 1;
        $this -> aChildren[] = $obj;
        
        if($callback !== null)
        {
            $callback($obj);
        }
    }
    
    /**
     * @param string $text
     * @return void
     */
    public function text(string $text): void
    {
        $this -> aChildren[] = $text;
    }
    
    /**
     * @param string|XHtml $content
     * @return void
     */
    public function append(mixed $content): void
    {
        if(is_string($content) || $content instanceof XHtml)
        {
            $this -> aChildren[] = $content;
        }
    }
    
    /**
     * @return (XHtml|string)[]
     */
    public function children(): array
    {
        return $this -> aChildren;
    }
    
    /**
     * @return void
     */
    public function clear(): void
    {
        $this -> aChildren = [];
        $this -> oAttributes -> Clear();
    }
    
    /**
     * @param string $path
     * @param \Closure $callback
     * @return void
     */
    public function get(string $path, \Closure $callback): void
    {
        $components = explode('/', $path);
        $current = [ $this ];
        
        foreach($components as $component)
        {
            $matches = [];
            foreach($current as $cObj)
            {
                foreach($cObj -> aChildren as $child)
                {
                    if($child instanceof XHtml && $child -> sTag === $component)
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
