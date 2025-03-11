<?php
require_once('Php2Core/TServerAdminCommands.php');
require_once('Php2Core/TDatabase.php');
require_once('Php2Core/TRouting.php');
require_once('Php2Core/TAutoloading.php');
require_once('Php2Core/THandlers.php');
require_once('Php2Core/TSession.php');

class Php2Core
{
    use \Php2Core\Php2Core\TServerAdminCommands;
    use \Php2Core\Php2Core\TDatabase;
    use \Php2Core\Php2Core\TRouting;
    use \Php2Core\Php2Core\TAutoloading;
    use \Php2Core\Php2Core\THandlers;
    use \Php2Core\Php2Core\TSession;

    /**
     * @return void
     */
    public static function initialize(): void
    {
        require_once(__DIR__.'/IO/IDiskManager.php');
        require_once(__DIR__.'/IO/IDirectory.php');
        require_once(__DIR__.'/IO/Directory.php');
        require_once(__DIR__.'/IO/IFile.php');
        require_once(__DIR__.'/IO/File.php');
        require_once(__DIR__.'/Php2Core/CoreProperties.php');
        require_once(__DIR__.'/Php2Core/Core.php');
        require_once(__DIR__.'/Version.php');
        
        define('PHP2CORE', new Php2Core\Php2Core\Core(function(Php2Core\Php2Core\Core $core)
        {
            $root = \Php2Core\IO\Directory::fromString(__DIR__.'/../');
            
            $temp = Php2Core\IO\Directory::fromString(__DIR__.'/__TEMP__');
            if($temp -> exists())
            {
                $temp -> remove();
            }
            $temp -> create();
            
            $cache = Php2Core\IO\Directory::fromString(__DIR__.'/__CACHE__');
            if(!$cache -> exists())
            {
                $cache -> create();
            }

            $core -> set(\Php2Core\Php2Core\CoreProperties::Root, $root);
            $core -> set(\Php2Core\Php2Core\CoreProperties::Temp, $temp);
            $core -> set(\Php2Core\Php2Core\CoreProperties::Cache, $cache);
            $core -> set(\Php2Core\Php2Core\CoreProperties::Start, microtime(true));
            $core -> set(\Php2Core\Php2Core\CoreProperties::Version, new \Php2Core\Version('Php2Core', 1,0,0,2, 'https://github.com/Unreal-Technologies/Php2Core'));
            
            
            $appConfigFile = \Php2Core\IO\File::fromDirectory($cache, 'Config.app.ini');
            if(!$appConfigFile -> exists())
            {
                $appConfigFile -> write(file_get_contents(__DIR__.'/Assets/Config.default.ini'));
            }
            
            $coreConfigFile = \Php2Core\IO\File::fromDirectory($cache, 'Config.Core.ini');
            if(!$coreConfigFile -> exists())
            {
                $coreConfigFile -> write(file_get_contents(__DIR__.'/Assets/Config.default.ini'));
            }
            
            require_once(__DIR__.'/Configuration.php');
            
            $configuration = new \Php2Core\Configuration(
                array_merge(parse_ini_file($appConfigFile -> path(), true), parse_ini_file($coreConfigFile -> path(), true)),
            );
            
            $core -> set(\Php2Core\Php2Core\CoreProperties::Configuration, $configuration);
            $core -> set(\Php2Core\Php2Core\CoreProperties::Debug, (int)$configuration -> get('Configuration/Debug') === 1);
            $core -> set(\Php2Core\Php2Core\CoreProperties::Title, $configuration -> get('Configuration/Title'));

        }));
        
        session_start();

        self::initializeAutoloading();
        self::initializeHandlerOverride();
        self::initializeDatabase();
        self::initializeServerAdminCommands();
        self::initializeSession();
        self::initializeRouting();
        self::executeServerAdminCommands();
    }
    
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
        $root = PHP2CORE -> get(\Php2Core\Php2Core\CoreProperties::Root) -> path();
        
        $new = str_replace([$root.'\\', $root.'/', '\\', '//', ':/'], ['', '', '/', '/', '://'], $path);
        if($new !== $path)
        {
            return $basePath.'/'.$new;
        }

        throw new Php2Core\Exceptions\NotImplementedException($path);
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
