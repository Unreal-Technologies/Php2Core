<?php

namespace Php2Core\IO\Common;

interface IXmlFile extends \Php2Core\IO\IFile
{
    public function document(): ?\Php2Core\IO\Xml\IXmlDocument;
}
