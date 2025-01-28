<?php

namespace Php2Core\IO\Xml;

interface IXmlDoctype
{
    public function __toString(): string;
    public function attributes(): array;
}
