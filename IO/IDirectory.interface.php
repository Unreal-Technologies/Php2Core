<?php

namespace Php2Core\IO;

interface IDirectory extends IDiskManager
{
    public function create(): bool;
    public function list(string $sRegex = null, bool $bRefresh = false): array;
    public function read(?string &$sOut): bool;
    public function open(): bool;
    public function close(): void;
    public function parent(): Directory;
    public function contains(string $sRegex): bool;
    public function copyTo(IDirectory $oDirectory): void;
}