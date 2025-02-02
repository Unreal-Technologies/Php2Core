<?php

namespace Php2Core\IO\Xml;

class Element implements IXmlElement
{
    /**
     * @var array|null
     */
    private ?array $attributes = null;

    /**
     * @var array|null
     */
    private ?array $children = null;

    /**
     * @var string|null
     */
    private ?string $text = null;

    /**
     * @var string|null
     */
    private ?string $name = null;

    /**
     * @var string|null
     */
    private ?string $id = null;

    /**
     * @var string|null
     */
    private ?string $parent = null;

    /**
     * @var int|null
     */
    private ?int $position = null;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this -> attributes = array();
        $this -> name = $name;
        $this -> children = array();
        $this -> id = get_class() . $name . rand(0, 0xffff);
        $this -> position = 0;
    }
    
    #[\Override]
    public function asArray(): array
    {
        return [$this -> name => $this -> asMixed()];
    }
    
    /**
     * @return mixed
     */
    private function asMixed(): mixed
    {
        if(count($this -> children) !== 0)
        {
            $aObjectCount = [];
            
            foreach($this -> children as $child)
            {
                if(!isset($aObjectCount[$child -> name]))
                {
                    $aObjectCount[$child -> name] = 0;
                }
                $aObjectCount[$child -> name]++;
            }
            

            $aOut = [];
            foreach($this -> children as $child)
            {
                $iCount = $aObjectCount[$child -> name];
                if($iCount > 1)
                {
                    $aOut[$child -> name][] = $child -> asMixed();
                }
                else
                {
                    $aOut[$child -> name] = $child -> asMixed();
                }
            }
            return $aOut;
        }
        else
        {
            return $this -> text;
        }
    }

    /**
     * @return Element[]
     */
    #[\Override]
    public function children(): array
    {
        return $this -> children;
    }

    /**
     * @return void
     */
    #[\Override]
    public function __clone(): void
    {
        foreach ($this -> children as $index => $child) {
            $this -> children[$index] = clone $child;
        }
    }

    /**
     * @param IXmlElement $element
     * @return bool
     */
    #[\Override]
    public function remove(IXmlElement $element): bool
    {
        if ($element -> parent() !== $this -> id) {
            return false;
        }

        $pos = -1;
        foreach ($this -> children as $index => $child) {
            if ($child -> id() === $element -> id()) {
                $pos = $index;
                break;
            }
        }
        if ($pos !== -1) {
            unset($this -> children[$pos]);
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString(): string
    {
        $xml = '';
        $tab = str_repeat("\t", $this -> position);

        $list = array();
        foreach ($this -> attributes as $key => $value) {
            $list[] = $key . '="' . $value . '"';
        }
        $attributeString = (isset($list[0]) ? ' ' : null) . implode(' ', $list);

        if ($this -> text === null && !isset($this -> children[0])) {
            $xml .= $tab . '<' . $this -> name . '' . $attributeString . '/>' . "\r\n";
        } elseif ($this -> text !== null) {
            $xml .= $tab .
                '<' . $this -> name . '' . $attributeString . '>' .
                $this -> text .
                '</' . $this -> name . '>' . "\r\n";
        } else {
            $xml .= $tab . '<' . $this -> name . '' . $attributeString . '>' . "\r\n";
            foreach ($this -> children as $child) {
                $xml .= $child;
            }
            $xml .= $tab . '</' . $this -> name . '>' . "\r\n";
        }
        return $xml;
    }

    /**
     * @param \Core\IO\File $file
     * @param Doctype $doctype
     * @return Element
     */
    final public static function createFromFile(\Php2Core\IO\Common\Xml $file, Doctype $doctype = null): Element
    {
        return Element::createFromXml($file -> read(), $doctype);
    }

    /**
     * @param  string $xmlstring
     * @return Element
     */
    final public static function createFromXml(string $xmlstring, Doctype $doctype = null): Element
    {
        $xml = simplexml_load_string($xmlstring);
        return Element::createFromSimpleXml($xml, $doctype);
    }

    /**
     * @param  SimpleXMLElement $element
     * @return Element
     */
    final public static function createFromSimpleXml(\SimpleXMLElement $element, Doctype $doctype = null): Element
    {
        $node = new Element($element -> getName());
        foreach ($element -> attributes() as $key => $value) {
            $node -> attributes[$key] = (string)$value;
        }
        foreach ($element -> children() as $child) {
            $node -> addChild(Element::createFromSimpleXml($child));
        }
        $string = (string)$element;
        if ($string !== null && $string !== '') {
            $node -> text($string);
        }

        return $doctype == null ? $node : $node -> asDocument($doctype);
    }

    /**
     * @return array
     */
    #[\Override]
    final public function attributes(array $list = null): array
    {
        if ($list !== null) {
            $this -> attributes = array_merge($this -> attributes, $list);
        }

        return $this -> attributes;
    }

    /**
     * @return string
     */
    #[\Override]
    final public function parent(?string $value = null): string
    {
        if ($value !== null) {
            $this -> parent = $value;
        }
        return $this -> parent;
    }

    /**
     * @return string
     */
    #[\Override]
    final public function id(): string
    {
        return $this -> id;
    }

    /**
     * @param  string $text
     * @return string
     */
    private function ampParser(string $text): string
    {
        $apos = strpos($text, '&');
        while ($apos !== false) {
            $qpos = strpos($text, ';', $apos);
            if ($qpos === false) {
                $left = substr($text, 0, $apos);
                $right = substr($text, $apos + 1);
                $text = $left . '&amp;' . $right;
            } else {
                $spos = strpos($text, ' ', $apos);
                if ($spos < $qpos && !$qpos) {
                    var_dump($text);
                    var_dump($apos);
                    var_dump($qpos);
                    var_dump($spos);
                    echo '-----------' . "\n";
                }
            }
            $apos = strpos($text, '&', $apos + 1);
        }

        return $text;
    }

    /**
     * @param  string $text
     * @return null|string
     */
    private function textParser(string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }
        return $this -> ampParser(str_replace('<br />', "\n", $text));
    }

    /**
     * @param  string $text
     * @return string
     */
    #[\Override]
    final public function text(string $text = null): ?string
    {
        if ($text === null) {
            return $this -> text;
        }
        if (count($this -> children) === 0) {
            $this -> text = $this -> textParser($text);
        }
        return null;
    }

    /**
     * @return string
     */
    #[\Override]
    final public function name(): string
    {
        return $this -> name;
    }

    /**
     * @param  string $name
     * @return Element|null
     */
    #[\Override]
    final public function createChild(string $name): ?Element
    {
        if ($this -> text === null) {
            $element = new Element($name);
            $this -> addChild($element);
            return $element;
        }
        return null;
    }

    /**
     * @param IXmlElement $element
     * @return bool
     */
    #[\Override]
    final public function addChild(IXmlElement $element): bool
    {
        if ($this -> text === null) {
            $element -> parent($this -> id);
            $element -> updatePosition($this -> position + 1);
            $this -> children[] = $element;
            return true;
        }
        return false;
    }

    /**
     * @param IXmlDoctype $doctype
     * @return Document
     */
    final public function asDocument(
        IXmlDoctype $doctype = null
    ): Document {
        if ($doctype === null) {
            $doctype = Doctype::xml();
        }

        $children = $this -> search(
            '/^' . str_replace('\\', '\\\\', $this -> id) . '$/',
            null,
            self::SEARCH_PARENT,
            false
        );
        $doc = new Document($this -> name, $doctype);
        foreach ($children as $child) {
            $doc -> addChild(clone $child);
        }

        return $doc;
    }

    /**
     * @param  string $element
     * @param  int    $returnIndex default null
     * @param  string $type        default self::Search_Name
     * @return array|Element|null
     */
    #[\Override]
    final public function search(
        string $regex,
        int $returnIndex = null,
        string $type = self::SEARCH_NAME,
        $recursive = true,
        $recursivePos = 0
    ): ?array {
        $list = array();
        $value = null;
        switch ($type) {
            case self::SEARCH_ATTRIBUTES:
                $value = $this -> attributes;
                break;
            case self::SEARCH_ID:
                $value = $this -> id;
                break;
            case self::SEARCH_NAME:
                $value = $this -> name;
                break;
            case self::SEARCH_PARENT:
                $value = $this -> parent;
                break;
            case self::SEARCH_POSITION:
                $value = $this -> position;
                break;
            case self::SEARCH_TEXT:
                $value = $this -> text;
                break;
            default:
                throw new \Php2Core\Exceptions\NotImplementedException('Unknown type "' . $type . '"');
        }

        if ($type == self::SEARCH_ATTRIBUTES) {
            $keys = array_keys($this -> attributes);
            foreach ($keys as $key) {
                if (preg_match($regex, $key)) {
                    $list[] = $this;
                }
            }
        } elseif ($value != null && preg_match($regex, $value)) {
            $list[] = $this;
        }
        if ($recursive || (!$recursive && $recursivePos === 0)) {
            foreach ($this -> children as $child) {
                $result = $child -> search($regex, null, $type, $recursive, $recursivePos + 1);
                if ($result !== null) {
                    if (is_array($result)) {
                        $list = array_merge($list, $result);
                    } else {
                        $list[] = $result;
                    }
                }
            }
        }
        return !isset($list[0]) ? null : ($returnIndex === null ? $list : [$list[$returnIndex]]);
    }

    /**
     * @param int $pos
     */
    #[\Override]
    final public function updatePosition(int $pos): void
    {
        $this -> position = $pos;
        foreach ($this -> children as $child) {
            $child -> updatePosition($pos + 1);
        }
    }
}
