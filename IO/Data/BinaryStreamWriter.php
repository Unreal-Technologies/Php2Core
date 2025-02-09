<?php
namespace Php2Core\IO\Data;

class BinaryStreamWriter
{
    /**
     * @var int
     */
    private int $iPosition = 0;
    
    /**
     * @var string
     */
    private string $sData = '';
    
    /**
     * @param int $value
     * @return void
     */
    public function i32(int $value): void
    {
        $this -> write(pack('i', $value), 4);
    }
    
    /**
     * @param string $value
     * @return void
     */
    public function u16(string $value): void
    {
        $this -> write(pack('S', $value), 2);
    }
    
    /**
     * @param string $guid
     * @return void
     */
    public function guid(string $guid): void
    {
        list($p1, $p2, $p3, $p4, $p56) = explode('-', $guid);
        $p5 = substr($p56, 0, 4);
        $p6 = substr($p56, 4);
        
        $i1 = hexdec($p1);
        $i2 = hexdec($p2);
        $i3 = hexdec($p3);
        $i4 = hexdec($p4);
        $i5 = hexdec($p5);
        $i6 = hexdec($p6);
        
        $b = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        
        $b[0] = $i1 & 0xff;
        $b[1] = ($i1 >> 8) & 0xff;
        $b[2] = ($i1 >> 16) & 0xff;
        $b[3] = ($i1 >> 24) & 0xff;
        $b[6] = $i2 & 0xff;
        $b[7] = ($i2 >> 8) & 0xff;
        $b[4] = $i3 & 0xff;
        $b[5] = ($i3 >> 8) & 0xff;
        $b[0xa] = $i4 & 0xff;
        $b[0xb] = ($i4 >> 8) & 0xff;
        $b[8] = $i5 & 0xff;
        $b[9] = ($i5 >> 8) & 0xff;
        $b[0xc] = $i6 & 0xff;
        $b[0xd] = ($i6 >> 8) & 0xff;
        $b[0xe] = ($i6 >> 16) & 0xff;
        $b[0xf] = ($i6 >> 24) & 0xff;
        
        $string = '';
        foreach($b as $byte)
        {
            $string .= chr($byte);
        }
        $this -> write($string, 16);
    }
    
    /**
     * @param array $values
     * @param \Closure $callback
     * @return void
     */
    public function tArray(array $values, \Closure $callback): void
    {
        $this -> i32(count($values));
        foreach($values as $value)
        {
            $callback($this, $value);
        }
    }
    
    /**
     * @param string $value
     * @return void
     */
    public function fString(string $value): void
    {
        if($value === '')
        {
            $this -> i32(0);
        }
        else
        {
            $encValue = mb_convert_encoding($value, 'ascii', 'UTF-8');
            $len = strlen($encValue) + 1;
            
            $this -> i32($len);
            $this -> write($encValue, $len, STR_PAD_RIGHT);
        }
    }
    
    /**
     * @param mixed $value
     * @param int $length
     * @param int $padType
     * @return void
     */
    private function write(mixed $value, int $length, int $padType = STR_PAD_LEFT): void
    {
        $left = substr($this -> sData, 0, $this -> iPosition);
        $right = substr($this -> sData, $this -> iPosition);
        
        $this -> sData = $left.str_pad($value, $length, chr(0), $padType).$right;
        $this -> iPosition += $length;
    }
    
    /**
     * @return string
     */
    public function __toString(): string 
    {
        return $this -> sData;
    }
}
