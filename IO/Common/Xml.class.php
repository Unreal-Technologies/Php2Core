<?php

namespace Php2Core\IO\Common;

class Xml extends \Php2Core\IO\File implements IXmlFile
{
    /**
     * @param string $path
     * @param bool $requiresExtension
     * @throws \Exception
     */
    #[\Override]
    public function __construct(string $path, bool $requiresExtension = true)
    {
        parent::__construct($path);

        if ($requiresExtension && strtolower($this -> extension()) != 'xml') {
            throw new \Exception('"' . $path . '" does not have the .xml extension');
        }
    }

    /**
     * 
     * @return \Php2Core\IO\Xml\IXmlDocument|null
     */
    #[\Override]
    public function document(): ?\Php2Core\IO\Xml\IXmlDocument
    {
        if (!$this -> exists()) {
            return null;
        }
        return \Php2Core\IO\Xml\Document::createFromFile($this) -> asDocument();
    }
}
