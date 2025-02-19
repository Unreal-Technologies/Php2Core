<?php
namespace Php2Core\IO\Data;

class BinaryStreamReader
{
    /**
     * @var int
     */
    private int $iPosition = 0;
    
    /**
     * @var string|null
     */
    private static ?string $sStream = null;
    
    /**
     * @param string $stream
     */
    public function __construct(string $stream) 
    {
        self::$sStream = $stream;
        $this -> iPosition = 0;
    }
    
    /**
     * @param int $length
     * @return void
     */
    public function skip(int $length): void
    {
        $this -> iPosition += $length;
    }

    /**
     * @param int $length
     * @return string
     */
    public function read(int $length): string
    {
        $data = substr(self::$sStream, $this -> iPosition, $length);
        $this -> iPosition += $length;
        return $data;
    }

    /**
     * @return int
     */
    public function i64(): int
    {
        return unpack('q', $this -> read(8))[1];
    }

    /**
     * @return int
     */
    public function u64(): int
    {
        return unpack('Q', $this -> read(8))[1];
    }

    /**
     * @return int
     */
    public function i32(): int
    {
        return unpack('i', $this -> read(4))[1];
    }
    
    /**
     * @return int
     */
    public function u32(): int
    {
        return unpack('I', $this -> read(4))[1];
    }
    
    /**
     * @return int
     */
    public function i16(): int
    {
        return unpack('s', $this -> read(2))[1];
    }
    
    /**
     * @return int
     */
    public function u16(): int
    {
        return unpack('S', $this -> read(2))[1];
    }

    /**
     * @return bool
     */
    public function bool(): bool
    {
        return ord($this -> read(1)) > 0;
    }
    
    /**
     * @param int $length
     * @return string
     */
    public function bytes(int $length): string
    {
        return $this -> read($length);
    }
    
    /**
     * @return string
     */
    public function byte(): string
    {
        return $this -> bytes(1);
    }
    
    /**
     * @return float
     */
    public function double(): float
    {
        return unpack('d', $this -> read(8))[1];
    }
    
    /**
     * @return float
     */
    public function float(): float
    {
        return unpack('f', $this -> read(4))[1];
    }
}
