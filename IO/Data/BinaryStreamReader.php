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
    private ?string $sStream = null;
    
    /**
     * @param string $stream
     */
    public function __construct(string $stream) 
    {
        $this -> sStream = $stream;
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
        $data = substr($this -> sStream, $this -> iPosition, $length);
        $this -> iPosition += $length;
        return $data;
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
    public function u64(): int
    {
        return unpack('Q', $this -> read(8))[1];
    }
    
    /**
     * @return int
     */
    public function u16(): int
    {
        return unpack('S', $this -> read(2))[1];
    }
    
    /**
     * @return int
     */
    public function u32(): int
    {
        return unpack('I', $this -> read(4))[1];
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
        return unpack($length.'B', $this -> read($length));
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
    
    /**
     * @return string|null
     */
    public function optionalGuid(): ?string
    {
        $check = $this -> read(1);
        if(ord($check) !== 0)
        {
            return $this -> guid();
        }
        return null;
    }
    
    /**
     * @return string
     */
    public function guid(): string
    {
        $data = $this -> read(16);
        $b = [];
        
        for($i=0; $i<strlen($data); $i++)
        {
            $s = $data[$i];
            $b[] = ord($s);
        }

        return sprintf(
            '%08x-%04x-%04x-%04x-%04x%08x',
            ($b[3] << 24) | ($b[2] << 16) | ($b[1] << 8) | $b[0],
            ($b[7] << 8) | $b[6],
            ($b[5] << 8) | $b[4],
            ($b[0xb] << 8) | $b[0xa],
            ($b[9] << 8) | $b[8],
            ($b[0xf] << 24) | ($b[0xe] << 16) | ($b[0xd] << 8) | $b[0xc]
        );
    }
    
    /**
     * @return string
     */
    public function fString(): string
    {
        $length = $this -> i32();
        
        if($length === 0)
        {
            return '';
        }
        
        $encoding = null;
        $data = null;
        if($length < 0)
        {
            $length = -$length;
            $data = substr($this -> read($length * 2), 0, -2);
            $encoding = 'utf-16-le';
        }
        else
        {
            $data = substr($this -> read($length), 0, -1);
            $encoding = 'ascii';
        }
        
        return mb_convert_encoding($data, 'UTF-8', $encoding);
    }
    
    /**
     * @param \Closure $closure
     * @return array
     */
    public function tArray(\Closure $closure): array
    {
        $count = $this -> i32();
        $buffer = [];
        
        for($i=0; $i<$count; $i++)
        {
            $buffer[] = $closure($this);
        }
        
        return $buffer;
    }
}
