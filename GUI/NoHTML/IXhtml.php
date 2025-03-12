<?php
namespace Php2Core\GUI\NoHTML;

interface IXhtml
{
    public function __construct();
    public function __toString(): string;
    public function attributes(): Attributes;
    public function add(string $tag, \Closure $callback=null): Xhtml;
    public function text(string $text): void;
    public function append(mixed $content): void;
    public function children(): array;
    public function clear(): void;
    public function get(string $path, \Closure $callback): void;
}