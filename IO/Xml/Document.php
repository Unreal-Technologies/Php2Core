<?php

namespace Php2Core\IO\Xml;

final class Document extends Element implements IXmlDocument
{
    /**
     * @var IXmlDoctype
     */
    private IXmlDoctype $doctype;

    /**
     * @var boolean
     */
    private bool $closed;

    /**
     * @param string $name
     * @param IXmlDoctype $doctype
     */
    #[\Override]
    public function __construct(string $name, IXmlDoctype $doctype = null)
    {
        $this -> closed = false;
        parent::__construct($name);
        if ($doctype === null) {
            $doctype = Doctype::xml();
        }
        $this -> doctype = $doctype;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString(): string
    {
        $xml = $this -> doctype . "\r\n";
        $xml .= parent::__toString();
        return $xml;
    }

    /**
     * @return IXmlElement
     */
    #[\Override]
    final public function asElement(): IXmlElement
    {
        $element = new Element($this -> name());
        $children = $this -> search('/^' . $this -> id() . '$/', null, self::SEARCH_PARENT, false);
        foreach ($children as $child) {
            $element -> addChild(clone $child);
        }

        return $element;
    }

    /**
     * @return IXmlDoctype
     */
    #[\Override]
    final public function doctype(): IXmlDoctype
    {
        return $this -> doctype;
    }

    /**
     * @param  boolean $value default null
     * @return null|boolean
     */
    #[\Override]
    final public function closed(bool $value = null): ?bool
    {
        if ($value === null) {
            return $this -> closed;
        }
        $this -> closed = $value;
        return null;
    }

    /**
     * @param  string  $stream
     * @param  string  $root
     * @param  boolean $output
     * @param  string  $encoding
     * @return boolean
     */
    #[\Override]
    final public function validateDtdStream(
        string $stream,
        string $root,
        bool $output = true,
        string $encoding = 'utf-8'
    ): bool {
        $file = abs(crc32(date('U') . rand(0, 0xfff))) . '.b';
        file_put_contents($file, $stream);

        $res = $this -> validateDtd($file, $root, $output, $encoding);
        unlink($file);
        return $res;
    }

    /**
     * @param  string  $stream
     * @param  boolean $output default true
     * @return boolean
     */
    #[\Override]
    final public function validateXsdStream(string $stream, bool $output = true): bool
    {
        $file = abs(crc32(date('U') . rand(0, 0xfff))) . '.b';
        file_put_contents($file, $stream);

        $res = $this -> validateXsd($file, $output);
        unlink($file);
        return $res;
    }

    /**
     * @param  \UT_Php_Core\IO\IFile $xsdSchemaFile
     * @param  boolean       $output
     * @return boolean
     */
    #[\Override]
    final public function validateXsd(\Core\Classes\IO\IFile $xsdSchemaFile, bool $output = true): bool
    {
        $xml = (string)$this;

        $dom = new DOMDocument();
        $dom -> loadXML($xml);

        $result = $output ?
            $dom -> schemaValidate($xsdSchemaFile -> path()) :
            @$dom -> schemaValidate($xsdSchemaFile -> path());
        if (!$result) {
            echo $xml;
        }

        return $result;
    }

    /**
     * @param \UT_Php_Core\IO\Common\IDtdFile $dtdSchemaFile
     * @param string $root
     * @param bool $output
     * @param string $encoding
     * @return bool
     */
    #[\Override]
    final public function validateDtd(
        \Core\Classes\IO\Common\IDtdFile $dtdSchemaFile,
        string $root,
        bool $output = true,
        string $encoding = 'utf-8'
    ): bool {
        $xml = (string)$this;
        if (!$dtdSchemaFile -> exists()) {
            return false;
        }

        $systemId = $dtdSchemaFile -> systemId();

        $old = new \DOMDocument();
        $old -> loadXML($xml);

        $creator = new \DOMImplementation();
        $docType = $creator -> createDocumentType($root, '', $systemId);
        $new = $creator -> createDocument(null, '', $docType);
        $new -> encoding = $encoding;

        $oldNode = $old -> getElementsByTagName($root) -> item(0);
        $newNode = $new -> importNode($oldNode, true);
        $new -> appendChild($newNode);

        return $output ? $new -> validate() : @$new -> validate();
    }
}
