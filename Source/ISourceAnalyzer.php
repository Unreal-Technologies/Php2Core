<?php
namespace Php2Core\Source;

interface ISourceAnalyzer
{
    public function __construct(\Php2Core\IO\IFile $target);
}