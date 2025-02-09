<?php

namespace Php2Core\IO;

interface IFile extends IDiskManager
{
    public function relativeTo(IDirectory $oDir): ?string;
    public function copyTo(IDirectory $oDir, string $sName = null): bool;
    public function parent(): ?IDirectory;
    public function extension(): string;
    public function basename(): string;
    public function read(): string;
    public function write(string $sStream, bool $bCreateDirectory = true): void;
    public static function fromString(string $sPath): IFile;
    public static function fromDirectory(IDirectory $oDir, string $sName): ?IFile;
    public static function fromFile(IFile $oFile): IFile;
    public function asDtd(): ?Common\IDtdFile;
    public function asXml(): ?Common\IXmlFile;
    public function fClose(): bool;
    public function fRead(int $length): ?string;
    public function fOpen(string $mode, bool $useIncludePath = false, $context = null): bool;
}
