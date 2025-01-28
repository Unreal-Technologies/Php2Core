<?php

namespace Php2Core\IO\Xml;

interface IXmlElement
{
    public const SEARCH_NAME = 'name';
    public const SEARCH_ID = 'id';
    public const SEARCH_TEXT = 'text';
    public const SEARCH_POSITION = 'position';
    public const SEARCH_PARENT = 'parent';
    public const SEARCH_ATTRIBUTES = 'attributes';

    public function children(): array;
    public function __clone(): void;
    public function remove(IXmlElement $element): bool;
    public function __toString(): string;
    public function attributes(array $list = null): array;
    public function parent(?string $value = null): string;
    public function id(): string;
    public function text(string $text = null): ?string;
    public function name(): string;
    public function createChild(string $name): ?IXmlElement;
    public function addChild(IXmlElement $element): bool;
    public function search(
        string $regex,
        int $returnIndex = null,
        string $type = self::SEARCH_NAME,
        $recursive = true,
        $recursivePos = 0
    ): ?array;
    public function updatePosition(int $pos): void;
    public function asArray(): array;
}
