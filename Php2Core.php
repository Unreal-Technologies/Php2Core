<?php
class Php2Core
{
    //<editor-fold defaultstate="collapsed" desc="Traits">
    
    use \Php2Core\Php2Core\TServerAdminCommands;
    use \Php2Core\Php2Core\TDatabase;
    use \Php2Core\Php2Core\TRouting;
    use \Php2Core\Php2Core\THandlers;
    use \Php2Core\Php2Core\TSession;
    
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Members">
    
    /**
     * @var array
     */
    private array $data = [];
    
    //</editor-fold>
    
    //<editor-fold defaultstate="collapsed" desc="Constructor">
    
    private function __construct(\Closure $cb)
    {
        $cb($this);
    }
    
    //</editor-fold>
    
    //<editor-fold defaultstate="collapsed" desc="Methods">
    //<editor-fold defaultstate="collapsed" desc="Public">
    
    /**
     * @param CoreProperties $property
     * @param mixed $value
     */
    public function set(Php2Core\CoreProperties $property, mixed $value)
    {
        $this -> data[$property -> value] = $value;
    }
    
    /**
     * @param CoreProperties $property
     * @return mixed
     */
    public function get(Php2Core\CoreProperties $property): mixed
    {
        if(isset($this -> data[$property -> value]))
        {
            return $this -> data[$property -> value];
        }
        return null;
    }
    
    /**
     * @param string $url
     * @return void
     */
    public function refresh(string $url): void
    {
        header('Location: '.$url);
        exit;
    }
    
    /**
     * @return string
     */
    public function baseUrl(): string
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
    public function physicalToRelativePath(string $path): string
    {
        $basePath = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];
        $root = $this -> get(\Php2Core\CoreProperties::Root) -> path();
        
        $new = str_replace([$root.'\\', $root.'/', '\\', '//', ':/'], ['', '', '/', '/', '://'], $path);
        if($new !== $path)
        {
            return $basePath.'/'.$new;
        }

        throw new Php2Core\Exceptions\NotImplementedException($path);
    }
    
    /**
     * @return void
     */
    public function trace(): void
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
    
    //</editor-fold>
    //<editor-fold defaultstate="collapsed" desc="Private">
    
    //</editor-fold>
    //</editor-fold>
    
    //<editor-fold defaultstate="collapsed" desc="Static Methods">
    //<editor-fold defaultstate="collapsed" desc="Public">
    
    /**
     * @return void
     */
    public static function initialize(): void
    {
        define('PHP2CORE', new Php2Core(function(Php2Core $core)
        {
            $root = \Php2Core\IO\Directory::fromString(__DIR__.'/../');
            
            $temp = Php2Core\IO\Directory::fromDirectory($root, '/__TEMP__');
            if($temp -> exists())
            {
                $temp -> remove();
            }
            $temp -> create();
            
            $cache = Php2Core\IO\Directory::fromDirectory($root, '__CACHE__');
            if(!$cache -> exists())
            {
                $cache -> create();
            }

            $core -> set(Php2Core\CoreProperties::Root, $root);
            $core -> set(Php2Core\CoreProperties::Temp, $temp);
            $core -> set(Php2Core\CoreProperties::Cache, $cache);
            $core -> set(Php2Core\CoreProperties::Start, microtime(true));
            $core -> set(Php2Core\CoreProperties::Version, new \Php2Core\Version('Php2Core', 1,0,0,2, 'https://github.com/Unreal-Technologies/Php2Core'));
            
            
            $appConfigFile = \Php2Core\IO\File::fromDirectory($cache, 'Config.app.ini');
            if(!$appConfigFile -> exists())
            {
                $appConfigFile -> write(file_get_contents(__DIR__.'/Assets/Config.App.Default.ini'));
            }
            
            $coreConfigFile = \Php2Core\IO\File::fromDirectory($cache, 'Config.Core.ini');
            if(!$coreConfigFile -> exists())
            {
                $coreConfigFile -> write(file_get_contents(__DIR__.'/Assets/Config.Core.Default.ini'));
            }
            
            require_once(__DIR__.'/Configuration.php');
            
            $configuration = new \Php2Core\Configuration(
                array_merge(parse_ini_file($appConfigFile -> path(), true), parse_ini_file($coreConfigFile -> path(), true)),
            );
            
            $core -> set(Php2Core\CoreProperties::Configuration, $configuration);
            $core -> set(Php2Core\CoreProperties::Debug, (int)$configuration -> get('Configuration/Debug') === 1);
            $core -> set(Php2Core\CoreProperties::Title, $configuration -> get('Configuration/Title'));

        }));
        
        session_start();

        self::initializeDatabase();
        self::initializeServerAdminCommands();
        self::initializeRouting();
        self::initializeHandlerOverride();
        self::executeServerAdminCommands();
    }
    
    //</editor-fold>
    //<editor-fold defaultstate="collapsed" desc="Private">
    
    //</editor-fold>
    //</editor-fold>
}
