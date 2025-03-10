<?php
namespace Php2Core\IO\Data;

class BinaryCompare
{
    /**
     * @var string[]
     */
    private array $aFiles;

    /**
     * @var bool
     */
    private bool $bIsEqual;

    /**
     * 
     */
    private array $aResults = [];

    /**
     * @param \Php2Core\IO\File $fileA
     * @param \Php2Core\IO\File $fileB
     */
    public function __construct(\Php2Core\IO\File $fileA, \Php2Core\IO\File $fileB)
    {
        if(!$fileA -> exists() || !$fileB -> exists())
        {
            throw new \Exception('One or both files do not exist.');
        }

        $this -> aFiles = [
            'A' => $fileA -> path(),
            'B' => $fileB -> path()
        ];

        $streamA = $fileA -> read();
        $streamB = $fileB -> read();

        $this -> bIsEqual = md5($streamA) === md5($streamB);
        $this -> compare($streamA, $streamB);
    }
	
    /**
     * @param string $input
     * @return string
     */
    private function toHex(string $input): string
    {
        $output = '';
        $len = strlen($input);

        for($i=0; $i<$len; $i++)
        {
            $byte = dechex(ord($input[$i]));
            if(strlen($byte) < 2)
            {
                $byte = '0'.$byte;
            }

            $output .= $byte.' ';
        }


        return $output;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $binA = '';
        $binB = '';
        $hexA = '';
        $hexB = '';

        $strA = '';
        $strB = '';

        foreach($this -> aResults as $result)
        {
            $color = $result['state'] !== 'same';
            if(!$color)
            {
                $binA .= $result['A'];
                $binB .= $result['B'];
                $hexA .= $this -> toHex($result['A']);
                $hexB .= $this -> toHex($result['B']);
            }
            else
            {
                $binA .= '<span class="green">'.$result['A'].'</span>';
                $binB .= '<span class="red">'.$result['B'].'</span>';
                $hexA .= $result['A'] === null ? '' : '<span class="green">'.$this -> toHex($result['A']).'</span>';
                $hexB .= $result['B'] === null ? '' : '<span class="red">'.$this -> toHex($result['B']).'</span>';
            }

            $strA .= $result['A'];
            $strB .= $result['B'];
        }
        
        $hashA = md5($strA);
        $hashB = md5($strB);

        return '<table>
            <tr>
                <th colspan="2">Hex:</th>
            </tr>
            <tr>
                <th>A (MD5: '.$hashA.')<br /><span style="font-size: 8pt;">'.$this -> aFiles['A'].'</span></th>
                <th>B (MD5: '.$hashB.')<br /><span style="font-size: 8pt;">'.$this -> aFiles['B'].'</span></th>
            </tr>
            <tr>
                <td style="font-family: monospace; vertical-align: top; white-space: normal; max-width: 50%; width: 50%;">'.$this -> coloredHexSlice($hexA).'</td>
                <td style="font-family: monospace; vertical-align: top; white-space: normal; max-width: 50%; width: 50%;">'.$this -> coloredHexSlice($hexB).'</td>
            </tr>
            <tr>
                <th colspan="2">Binary:</th>
            </tr>
            <tr>
                <th>A (MD5: '.$hashA.')<br /><span style="font-size: 8pt;">'.$this -> aFiles['A'].'</span></th>
                <th>B (MD5: '.$hashB.')<br /><span style="font-size: 8pt;">'.$this -> aFiles['B'].'</span></th>
            </tr>
            <tr>
                <td style="font-family: monospace; vertical-align: top; white-space: normal; max-width: 50%; width: 50%;">'.$binA.'</td>
                <td style="font-family: monospace; vertical-align: top; white-space: normal; max-width: 50%; width: 50%;">'.$binB.'</td>
            </tr>
        </table>';
    }

    /**
     * @param string $hex
     * @return string
     */
    private function coloredHexSlice(string $hex): string
    {
        $max = 32;
        $pos = 0;
        $len = strlen($hex);

        $buffer = [];
        $cur = '';
        $count = 0;
        while($pos < $len)
        {
            $sub = substr($hex, $pos, 3);
            $oSubGreen = substr($hex, $pos + 20, 3);
            $oSubRed = substr($hex, $pos + 18, 3);

            if(!preg_match('/[0-9a-z]{2}[ ]/i', $sub))
            {
                if(preg_match('/[0-9a-z]{2}[ ]/i', $oSubGreen))
                {
                    $sub = substr($hex, $pos, 30);
                }
                else if(preg_match('/[0-9a-z]{2}[ ]/i', $oSubRed))
                {
                    $sub = substr($hex, $pos, 28);
                }
            }

            $cur .= $sub;
            $count++;

            if($count === $max)
            {
                $buffer[] = trim($cur);
                $cur = '';
                $count = 0;
            }

            $pos += strlen($sub);
        }
        return implode('<br />', $buffer);
    }

    /**
     * @param string $streamA
     * @param string $streamB
     * @return void
     */
    private function compare(string $streamA, string $streamB): void
    {
        $results = [];
        $count = max(strlen($streamA), strlen($streamB));

        for($i=0; $i<$count; $i++)
        {
            $byteA = isset($streamA[$i]) ? $streamA[$i] : null;
            $byteB = isset($streamB[$i]) ? $streamB[$i] : null;

            $results[] = [
                'position' => $i,
                'state' => $byteA === $byteB ? 'same' : 'diff',
                'A' => $byteA,
                'B' => $byteB
            ];
        }

        $this -> aResults = $results;
    }
}