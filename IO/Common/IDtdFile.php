<?php

namespace Php2Core\IO\Common;

interface IDtdFile extends \Php2Core\IO\IFile
{
    public function systemId(): ?string;
}
