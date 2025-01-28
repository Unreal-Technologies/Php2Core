<?php

namespace Php2Core\IO\Xml;

interface IXmlDocument extends IXmlElement
{
    #[\Override]
    public function __toString(): string;
    public function asElement(): IXmlElement;
    public function doctype(): IXmlDoctype;
    public function closed(bool $value = null): ?bool;
    public function validateDtdStream(
        string $stream,
        string $root,
        bool $output = true,
        string $encoding = 'utf-8'
    ): bool;
    public function validateXsdStream(string $stream, bool $output = true): bool;
    public function validateXsd(\Core\Classes\IO\IFile $xsdSchemaFile, bool $output = true): bool;
    public function validateDtd(
        \Core\Classes\IO\Common\IDtdFile $dtdSchemaFile,
        string $root,
        bool $output = true,
        string $encoding = 'utf-8'
    ): bool;
}
