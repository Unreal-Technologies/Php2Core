<?php

namespace Php2Core\IO;

class Memory
{
    /**
     * @param string $memory
     * @return Memory
     * @throws \Php2Core\Exceptions\NotImplementedException
     */
    public static function parse(string $memory): Memory
    {
        $parts = explode(' ', $memory);
        if (count($parts) == 1) {
            return new Memory((int)$parts[0]);
        }

        list($value, $indicator) = $parts;
        $v = (float)str_replace(['.', ','], ['', '.'], $value);

        switch (strtolower($indicator)) {
            case 'k':
                $v *= 1024;
                break;
            default:
                throw new \Php2Core\Exceptions\NotImplementedException('Undefined indicator "' . $indicator . '"');
        }

        return new Memory($v);
    }

    /**
     * @param int $value
     * @return Memory
     */
    public static function fromInt(int $value): Memory
    {
        return new Memory($value);
    }

    /**
     * @var int
     */
    private int $value;

    /**
     * @param int $value
     */
    private function __construct(int $value)
    {
        $this -> value = $value;
    }

    /**
     * @param int $decimals
     * @return string
     */
    public function format(int $decimals = 2): string
    {
        $idx = 0;
        $value = $this -> value;
        while ($value >= 1024) {
            $value /= 1024;
            $idx++;
        }
        $list = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'RiB', 'QiB'];

        return number_format($value, $decimals, ',', '.') . ' ' . $list[$idx];
    }

    /**
     * @return int
     */
    public function value(): int
    {
        return $this -> value;
    }
}
