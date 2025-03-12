<?php
namespace Php2Core\GUI\NoHTML;

/**
 * Description of XHtml
 *
 * @author Peter
 */
class Xhtml implements IXhtml 
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
     * @var string|null
     */
    private ?string $prefix = null;
    
    /**
     * @throws \Exception
     */
    #[\Override]
    public function __construct(string $prefix = null)
    {
        $this -> prefix = $prefix;
        $this -> oAttributes = new Attributes();
        if(!defined('XHTML'))
        {
            define('XHTML', $this);
        }
    }
    
    /** 
     * @return string
     */
    #[\Override]
    public function __toString(): string 
    {
        $tab = str_repeat('  ', $this -> iPosition);
        
        $out = $this -> prefix;
        $out .= $tab.'<'.$this -> sTag.$this -> oAttributes.'>'."\r\n";
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
    #[\Override]
    public function attributes(): Attributes
    {
        return $this -> oAttributes;
    }
    
    /**
     * @return Xhtml|null
     */
    public function parent(): ?Xhtml
    {
        $parts = explode('/', $this -> sPath);
        unset($parts[count($parts) - 1]);
        unset($parts[0]);
        $parentPath = implode('/', $parts);
        
        $parent = null;
        XHTML -> get($parentPath, function(Xhtml $object) use(&$parent)
        {
            $parent = $object;
        });
        
        return $parent;
    }
    
    /**
     * @param string $tag
     * @param \Closure $callback
     * @return Xhtml
     * @throws \Php2Core\Exceptions\NotImplementedException
     */
    #[\Override]
    public function add(string $tag, \Closure $callback=null): Xhtml
    {
        $current = $this;
        $components = explode('/', $tag);
        
        foreach($components as $idx => $component)
        {
            $cParts = explode('@', $component);
            $component = $cParts[0];
            $extra = isset($cParts[1]) ? $cParts[1] : null;
            
            $obj = new XHtml();
            $obj -> sTag = $component;
            $obj -> sPath = $current -> sPath.'/'.$component;
            $obj -> iPosition = $current -> iPosition + 1;
            $current -> aChildren[] = $obj;
            
            $hasExtra = $extra !== null;
            if($hasExtra)
            {
                $extras = explode('&', $extra);
                
                foreach($extras as $e)
                {
                    $isClass = substr($e, 0, 1) === '.';
                    $isId = substr($e, 0, 1) === '#';
                    $isOther = !$isClass && !$isId;

                    $attributes = $obj -> attributes();

                    if($isClass)
                    {
                        $attributes -> set('class', substr($e, 1));
                    }

                    if($isId)
                    {
                        $attributes -> set('id', substr($e, 1));
                    }

                    if($isOther)
                    {
                        list($k, $v) = explode('=', $e);
                        $attributes -> set($k, $v);
                    }
                }
            }
            
            if($idx === count($components) - 1 && $callback !== null)
            {
                $callback($obj);
            }
            
            $current = $obj;
        }
        
        return $current;
    }
    
    /**
     * @param string $text
     * @return void
     */
    #[\Override]
    public function text(string $text): void
    {
        $this -> aChildren[] = $text;
    }
    
    /**
     * @param string|IXhtml $content
     * @return void
     */
    #[\Override]
    public function append(mixed $content): void
    {
        if(is_string($content) || $content instanceof IXhtml)
        {
            $this -> aChildren[] = $content;
        }
    }
    
    /**
     * @return (IXhtml|string)[]
     */
    #[\Override]
    public function children(): array
    {
        return $this -> aChildren;
    }
    
    /**
     * @return void
     */
    #[\Override]
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
    #[\Override]
    public function get(string $path, \Closure $callback): void
    {
        $components = explode('/', $path);
        $current = [ $this ];
        
        foreach($components as $component)
        {
            $cParts = explode('@', $component);
            $component = $cParts[0];
            $extra = isset($cParts[1]) ? $cParts[1] : null;

            $matches = [];
            foreach($current as $cObj)
            {
                foreach($cObj -> aChildren as $child)
                {
                    if($child instanceof XHtml && $child -> sTag === $component)
                    {
                        $stateExtraOK = $extra === null;
                        
                        if(!$stateExtraOK)
                        {
                            $isClass = substr($extra, 0, 1) === '.';
                            $isId = substr($extra, 0, 1) === '#';
                            $isOther = !$isClass && !$isId;
                            
                            $attributes = $child -> attributes();
                            
                            if($isClass)
                            {
                                $cls = $attributes -> get('class');
                                if($cls === substr($extra, 1))
                                {
                                    $stateExtraOK = true;
                                }
                            }
                            
                            if($isId)
                            {
                                $cls = $attributes -> get('id');
                                if($cls === substr($extra, 1))
                                {
                                    $stateExtraOK = true;
                                }
                            }
                            
                            if($isOther)
                            {
                                throw new \Php2Core\Exceptions\NotImplementedException($extra);
                            }
                        }

                        if($stateExtraOK)
                        {
                            $matches[] = $child;
                        }
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
