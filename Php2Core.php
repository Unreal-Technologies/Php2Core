<?php
require_once('Php2Core/TServerAdminCommands.php');
require_once('Php2Core/TDatabase.php');
require_once('Php2Core/TRouting.php');
require_once('Php2Core/TAutoloading.php');
require_once('Php2Core/THandlers.php');
require_once('Php2Core/TOtherInitializers.php');
require_once('Php2Core/TSession.php');

class Php2Core
{
    use \Php2Core\Php2Core\TServerAdminCommands;
    use \Php2Core\Php2Core\TDatabase;
    use \Php2Core\Php2Core\TRouting;
    use \Php2Core\Php2Core\TAutoloading;
    use \Php2Core\Php2Core\THandlers;
    use \Php2Core\Php2Core\TOtherInitializers;
    use \Php2Core\Php2Core\TSession;

    /**
     * @param string $url
     * @return void
     */
    public static function refresh(string $url): void
    {
        header('Location: '.$url);
        exit;
    }
    
    /**
     * @return void
     */
    public static function trace(): void
    {
        $path = [];
        $components = debug_backtrace();
        
        for($i=1; $i<count($components); $i++)
        {
            $entry = $components[$i];
            
            $args = [];
            foreach($entry['args'] as $arg)
            {
                if(is_object($arg))
                {
                    $args[] = get_class($arg);
                    continue;
                }
                else if(is_string($arg) && !is_numeric($arg))
                {
                    $args[] = '"'.$arg.'"';
                    continue;
                }
                else if(is_array($arg))
                {
                    $args[] = 'array';
                    continue;
                }
                $args[] = $arg;
            }
            
            if(isset($entry['class']))
            {
                if(!isset($entry['file']))
                {
                    $path = [];
                    continue;
                }
                $path[] = [$entry['file'].':'.$entry['line'], $entry['class'].' '.$entry['type'].' '.$entry['function'].'('.implode(', ', $args).')'];
                continue;
            }
            $path[] = [$entry['file'].':'.$entry['line'], $entry['function'].'('.implode(', ', $args).')'];
        }
        $pathReversed = array_reverse($path);
        
        XHTML -> get('body', function(Php2Core\NoHTML\Xhtml $body) use($pathReversed)
        {
            $table = $body -> add('table@#trace');
            $table -> add('tr/th@colspan=3') -> text('Trace');
            
            foreach($pathReversed as $idx => $data)
            {
                list($line, $call) = $data;
                
                $tr = $table -> add('tr');
                $tr -> add('td') -> text($idx + 1);
                $tr -> add('td') -> text($line === null ? '' : $line);
                $tr -> add('td') -> text($call);
            }
        });
    }
    
    /**
     * @return string
     */
    public static function baseUrl(): string
    {
        $pi = pathinfo($_SERVER['SCRIPT_NAME']);
        if(!isset($_SERVER['SCRIPT_URI']))
        {
            $result = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$pi['dirname'];

            if(substr($result, -1, 1) === '/')
            {
                    return substr($result, 0, -1);
            }

            return $result;
        }

        return preg_replace('/'.substr($pi['dirname'], 1).'.+$/i', substr($pi['dirname'], 1), $_SERVER['SCRIPT_URI']);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function physicalToRelativePath(string $path): string
    {
        $basePath = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];
        $root = CORE -> get(\Php2Core\Php2Core\CoreProperties::Root);
        
        $new = str_replace([$root.'\\', $root.'/', '\\', '//', ':/'], ['', '', '/', '/', '://'], $path);
        if($new !== $path)
        {
            return $basePath.'/'.$new;
        }

        throw new Php2Core\Exceptions\NotImplementedException($path);
    }
    
    /**
     * @return string
     */
    public static function root(): string
    {
        //get Directory
        $pi = pathinfo(__DIR__);
        return $pi['dirname'];
    }
    
    /**
     * @return \Php2Core\IO\Directory
     */
    public static function cache(): \Php2Core\IO\Directory
    {
        $cache = Php2Core\IO\Directory::fromString(__DIR__.'/__CACHE__');
        if(!$cache -> exists())
        {
            $cache -> create();
        }
        return $cache;
    }
    
    /**
     * @return Php2Core\IO\Directory
     */
    public static function temp(): Php2Core\IO\Directory
    {
        $temp = Php2Core\IO\Directory::fromString(__DIR__.'/__TEMP__');
        if($temp -> exists())
        {
            $temp -> remove();
        }
        $temp -> create();
        
        return $temp;
    }
    
    /**
     * @param string $directory
     * @return array
     */
    public static function scanDir(string $directory): array
    {
        $entries = [];
        if (($handle = opendir($directory)) !== false) //Open Dir
        {
            while (false !== ($entry = readdir($handle))) //Loop through files
            {
                if ($entry != "." && $entry != "..") 
                {
                    $path = $directory.'/'.$entry; //Get new Path
                    $entries[] = ['Path' => realpath($path), 'Type' => is_dir($path) ? 'Dir' : 'File']; //Register Path & Type
                }
            }

            closedir($handle); //Close Dir
        }
        return $entries;
    }
}
