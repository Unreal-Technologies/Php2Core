<?php
class Php2Core
{
    public const Root           = 0x01000000;
    public const Temp           = 0x01000001;
    public const Cache          = 0x01000002;
    public const Start          = 0x02000000;
    public const IsDebug        = 0x02000001;
    public const Title          = 0x02000002;
    public const IsServerAdmin  = 0x02000003;
    public const Version        = 0x03000000;
    public const Configuration  = 0x04000000;
    public const Database       = 0x05000000;
    public const Route          = 0x06000000;
    public const DefaultRoute   = 0x06000001;

    public static $dumpAsHtml = false;
    public static $dumpTitle = null;
    
    /**
     * @var array
     */
    private array $data = [];
    
    private function __construct(\Closure $cb)
    {
        $cb($this);
    }
    
    /**
     * @param int $property
     * @param mixed $value
     */
    public function set(int $property, mixed $value)
    {
        $this -> data[$property] = $value;
    }
    
    /**
     * @param int $property
     * @return mixed
     */
    public function get(int $property): mixed
    {
        if(isset($this -> data[$property]))
        {
            return $this -> data[$property];
        }
        return null;
    }
    
    /**
     * @param string $path
     * @return mixed
     */
    public function session_get(string $path): mixed
    {
        $eval = '$_SESSION';
        foreach(explode('/', $path) as $token)
        {
            $eval .= '["'.$token.'"]';
        }
        $data = null;
        eval('$data = isset('.$eval.') ? '.$eval.' : null;');
        
        return $data;
    }
    
    /**
     * @param string $path
     * @param mixed $data
     * @return void
     */
    public function session_set(string $path, mixed $data): void
    {
        $eval = '$_SESSION';
        foreach(explode('/', $path) as $token)
        {
            $eval .= '["'.$token.'"]';
        }
        $eval .= ' = $data;';
        
        eval($eval);
    }
    
    /**
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this -> session_get('user/id') !== null;
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
     * @return int
     */
    public function getInstanceID(): int
    {
        $coreDbc = Php2Core\IO\Data\Db\Database::getInstance('Php2Core');
        $coreDbc -> query('select `id` from `instance` where `name` = "'.$this -> get(Php2Core::Title).'"');
        $result = $coreDbc -> execute();

        if($result['iRowCount'] > 0)
        {
            return $result['aResults'][0]['id'];
        }

        return -1;
    }
    
    /**
     * @param string $path
     * @return string
     */
    public function physicalToRelativePath(string $path): string
    {
        $basePath = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];
        $root = $this -> get(\Php2Core::Root) -> path();
        
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
            if(isset($entry['args']))
            {
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
        
        if(defined('XHTML'))
        {
            XHTML -> get('body', function(Php2Core\GUI\NoHTML\Xhtml $body) use($pathReversed)
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
        else
        {
            echo '<xmp>';
            print_r($pathReversed);
            echo '</xmp>';
        }
    }

    /**
     * @param mixed $arguments
     * @return void
     */
    public static function dump(mixed ...$arguments): ?string
    {
        $self = debug_backtrace()[0];
        $file = \Php2Core\IO\File::fromString($self['file']);
        
        $tokens = self::dumpGetTokens($file, $self['line']);
        
        if(self::$dumpAsHtml)
        {
            ob_start();
        }
        echo '<div class="dump">';
        echo '<h2>'.(self::$dumpTitle === null ? __METHOD__ : self::$dumpTitle).'</h2>';
        echo '<span>'.$self['file'].':'.$self['line'].'</span><br />';
        echo '<div>';
        foreach($arguments as $idx => $argument)
        {
            echo '<span>';
            $doPrint = is_array($argument) || is_object($argument);
            echo '<span>'.$tokens[$idx].'</span> = <span>';
            //echo '<span>Arg[\''.$idx.'\']</span> = <span>';
            if($doPrint)
            {
                echo '<xmp>'.print_r($argument, true).'</xmp>';
            }
            else
            {
                var_dumP($argument);
            }
            echo '</span><br />';
            echo '</span>';
        }
        echo '</div>';
        echo '</div>';
        if(self::$dumpAsHtml)
        {
            return ob_get_clean();
        }
        return null;
    }
    
    /**
     * @return void
     */
    public static function initialize(): void
    {
        session_start();
        
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

            $core -> set($core::Root, $root);
            $core -> set($core::Temp, $temp);
            $core -> set($core::Cache, $cache);
            $core -> set($core::Start, microtime(true));
            $core -> set($core::Version, new \Php2Core\Data\Version('Php2Core', 1,0,0,2, 'https://github.com/Unreal-Technologies/Php2Core'));
            
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
            
            $configuration = new \Php2Core\Data\Configuration(
                array_merge(parse_ini_file($appConfigFile -> path(), true), parse_ini_file($coreConfigFile -> path(), true)),
            );
            
            $core -> set($core::Configuration, $configuration);
            $core -> set($core::IsDebug, (int)$configuration -> get('Configuration/Debug') === 1);
            $core -> set($core::Title, $configuration -> get('Configuration/Title'));

        }));

        self::initializeDatabase();
        self::initializeServerAdminCommands();
        self::initializeRouting();
        self::initializeHandlerOverride();
        self::executeServerAdminCommands();
    }
    
    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return void
     */
    public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $hasBody = false;
        
        XHTML -> get('body', function(\Php2Core\GUI\NoHTML\Xhtml $body) use(&$hasBody, $errno, $errstr, $errfile, $errline)
        {
            $trace = self::getTrace($body);
            
            $body -> clear();
            $body -> add('h2') -> text('Php2Core::ErrorHandler');
            $body -> add('xmp') -> text(print_r($errfile.':'.$errline, true));
            $body -> add('xmp') -> text($errno."\r\n".print_r($errstr, true));
            if($trace !== null)
            {
                $body -> append($trace);
            }

            $hasBody = true; 
        });

        if(!$hasBody)
        {
            echo '<h2>Php2Core::ErrorHandler</h2>';
            echo '<xmp>';
            var_dump($errfile);
            var_dumP($errline);
            var_dumP($errno);
            var_dumP($errstr);
            echo '</xmp>';
        }
        exit;
    }
    
    /**
     * @param \Throwable $ex
     * @return void
     */
    public static function exceptionHandler(\Throwable $ex): void
    {
        self::$dumpTitle = __METHOD__;
        $hasBody = false;
        
        if(defined('XHTML'))
        {
            XHTML -> get('body', function(\Php2Core\GUI\NoHTML\Xhtml $body) use(&$hasBody, $ex)
            {
                self::$dumpAsHtml = true;
                $res = self::dump($ex);
                self::$dumpAsHtml = false;
                
                $body -> clear();
                $body -> text($res);

                $hasBody = true; 
            });
        }
        
        if(!$hasBody)
        {
            self::dump($ex);
        }
        self::$dumpTitle = null;
        exit;
    }
    
    /**
     * @return void
     */
    public static function shutdown(): void
    {
        XHTML -> get('body', function(\Php2Core\GUI\NoHTML\Xhtml $body)
        {
            $dif = microtime(true) - PHP2CORE -> get(\Php2Core::Start);
            
            $body -> add('div@#execution-time') -> text('Process time: '.number_format(round($dif * 1000, 4), 4, ',', '.').' ms');
            $body -> add('div@#version', function(\Php2Core\GUI\NoHTML\Xhtml $div)
            {
                PHP2CORE -> get(\Php2Core::Version) -> Render($div);
            });
        });
        XHTML -> get('head', function(\Php2Core\GUI\NoHTML\Xhtml $head)
        {
            $children = $head -> children();
            $head -> clear();
            
            $head -> add('link', function(\Php2Core\GUI\NoHTML\Xhtml $link)
            {
                $link -> Attributes() -> Set('rel', 'icon');
                $link -> Attributes() -> Set('type', 'image/x-icon');
                $link -> Attributes() -> Set('href', PHP2CORE -> physicalToRelativePath(__DIR__.'/Assets/Images/favicon.ico'));
            });
            $head -> add('link', function(\Php2Core\GUI\NoHTML\Xhtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', 'https://fonts.googleapis.com/icon?family=Material+Icons');
            });
            
            foreach(\Php2Core\IO\Directory::fromString(__DIR__.'/Assets/Css') -> list('/\.css$/i') as $entry)
            {
                if($entry instanceof \Php2Core\IO\File)
                {
                    $head -> add('link', function(\Php2Core\GUI\NoHTML\Xhtml $link) use($entry)
                    {
                        $link -> Attributes() -> Set('rel', 'stylesheet');
                        $link -> Attributes() -> Set('href', PHP2CORE -> physicalToRelativePath($entry -> path()));
                    });
                }
            }
            
            foreach(\Php2Core\IO\Directory::fromString(__DIR__.'/Assets/Js') -> list('/\.js/i') as $entry)
            {
                if($entry instanceof \Php2Core\IO\File)
                {
                    $head -> add('script', function(\Php2Core\GUI\NoHTML\Xhtml $script) use($entry)
                    {
                        $script -> Attributes() -> Set('type', 'text/javascript');
                        $script -> Attributes() -> Set('src', PHP2CORE -> physicalToRelativePath($entry -> path()));
                    });
                }
            }

            foreach($children as $child)
            {
                $head -> Append($child);
            }
        });

        //output
        echo XHTML;
        
        if(PHP2CORE -> get(Php2Core::IsDebug) && (int)PHP2CORE -> get(\Php2Core::Configuration) -> get('Configuration/XhtmlOut') === 1)
        {
            echo '<hr />';
            echo '<xmp>';
            print_r(str_replace(['<xmp>', '</xmp>'], ['<.xmp>', '</.xmp>'], (string)XHTML));
            echo '</xmp>';
        }
    }
    
    /**
     * @param \Php2Core\IO\File $file
     * @param int $line
     * @return array
     */
    private static function dumpGetTokens(\Php2Core\IO\File $file, int $line): array
    {
        $tokens = token_get_all($file -> read());
        
        $match = false;
        $open = false;
        $depth = 0;
        $lineTokenParameters = [];
        foreach($tokens as $token)
        {
            if(!$match && $token[0] === 262 && strtolower($token[1]) === 'dump' && $token[2] === $line)
            {
                $match = true;
            }
            
            if(!$open && $match && $token === '(')
            {
                $open = true;
            }
            else if($open)
            {
                if($token === ')' && $depth === 0)
                {
                    $open = false;
                    $match = false;
                    break;
                }
                else if($token === '(')
                {
                    $depth++;
                    $lineTokenParameters[] = '(';
                }
                else if($token === ')')
                {
                    $depth--;
                    $lineTokenParameters[] = ')';
                }
                else
                {
                    $lineTokenParameters[] = $token;
                }
            }
        }
        
        $components = [];
        $current = 0;

        $ltdepth = 0;
        foreach($lineTokenParameters as $token)
        {
            $type = is_array($token) && isset($token[0]) ? $token[0] : null;
            $inSubcomponent = $ltdepth > 0;
            
            if($type === 392 && !$inSubcomponent)
            {
                continue;
            }
            else if($token === ',' && !$inSubcomponent)
            {
                $current++;
                continue;
            }
            
            if($token === '(')
            {
                $ltdepth++;
                $components[$current] .= '(';
            }
            else if($token === ')')
            {
                $ltdepth--;
                $components[$current] .= ')';
            }
            else
            {
                if(!isset($components[$current]))
                {
                    $components[$current] = '';
                }
                $components[$current] .= $type === null ? $token : $token[1];
            }
            
        }
        
        return $components;
    }
    
    /**
     * @return void
     * @throws \Exception
     */
    private static function initializeRouting(): void
    {
        //Get DB Instance
        $coreDbc = Php2Core\IO\Data\Db\Database::getInstance('Php2Core');
        $instanceId = PHP2CORE -> getInstanceID();
        $authenticated = PHP2CORE -> isAuthenticated();
        $isServerAdmin = PHP2CORE -> get(Php2Core::IsServerAdmin);
        
        //Get Default handler
        $coreDbc -> query(
            'select '
            . 'case when `match` is null then \'index\' else `match` end as `match` '
            . 'from `route` '
            . 'where `default` = "true" '
            . 'and '.($isServerAdmin ? '( `instance-id` = '.$instanceId.' or `instance-id` is null )' : '`instance-id` = '.$instanceId).' '
            . ($authenticated ? '' : 'and `auth` = "false" ')
            . 'order by `id` asc '
            . 'limit 0,1'
        );
        
        $defaultResults = $coreDbc -> execute();
        $defaultRoute = $defaultResults['iRowCount'] === 0 ? 'index' : $defaultResults['aResults'][0]['match'];
        PHP2CORE -> set(Php2Core::DefaultRoute, $defaultRoute);
        
        //Get Router Information
        $router = new \Php2Core\Data\Router($defaultRoute);
        $slug = $router -> slug();
        $possibilities = self::getPossibleMatchesFromSlug($slug);
        
        //Get Possible routes
        $coreDbc -> query(
            'select '
            . '`method`, `match`, `target`, `type`, `auth`, `mode` '
            . 'from `route` '
            . 'where '.($isServerAdmin ? '( `instance-id` = '.$instanceId.' or `instance-id` is null )' : '`instance-id` = '.$instanceId).' '
            . 'and (`match` regexp \''.implode('\' or `match` regexp \'', $possibilities).'\') '
            . ($authenticated ? '' : 'and `auth` = "false" ')
            . ($isServerAdmin ? '' : 'and `type` != \'function\' ')
        );
        
        //Register possible routes
        $routeResult = $coreDbc -> execute();
        foreach($routeResult['aResults'] as $row)
        {
            $router -> register($row['method'].'::'.$row['match'], $row['type'].'#'.$row['target'], $row['mode']);
        }
        
        //get current route (if matched)
        $route = $router -> match();
        PHP2CORE -> set(Php2Core::Route, $route);

        //throw exception when not actually matched
        if($route === null)
        {
            throw new \Exception('Route not found');
        }
        else if($route -> target()['type'] === 'function')
        {
            eval($route -> target()['target'].'();');
            exit;
        }
    }
    
    /**
     * @return void
     */
    private static function initializeServerAdminCommands(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        PHP2CORE -> set(Php2Core::IsServerAdmin, preg_match('/'.$ip.'/i', PHP2CORE -> get(\Php2Core::Configuration) -> get('RemoteAdmin/IPs')));
    }
    
    /**
     * @return void
     */
    private static function resetDatabases(): void //Callable Server Command
    {
        $appDbc = \Php2Core\IO\Data\Db\Database::getInstance(PHP2CORE -> get(Php2Core::Title));
        $coreDbc = \Php2Core\IO\Data\Db\Database::getInstance('Php2Core');
        
        $appDbc -> query('drop database `'.$appDbc -> database().'`;');
        $appDbc -> execute();
        
        $coreDbc -> query('drop database `'.$coreDbc -> database().'`;');
        $coreDbc -> execute();

        PHP2CORE -> refresh(PHP2CORE -> baseUrl());
    }
    
    /**
     * @return void
     */
    private static function executeServerAdminCommands(): void
    {
        $info = PHP2CORE -> get(Php2Core::Route) -> target();
        $isServerAdmin = PHP2CORE -> get(Php2Core::IsServerAdmin);
        
        if($isServerAdmin && $info['type'] === 'function')
        {
            eval($info['target'].'();');
            exit;
        }
    }
    
    /**
     * @return void
     */
    private static function initializeHandlerOverride(): void
    {
        if((int)PHP2CORE -> get(Php2Core::Configuration) -> get('Logic/ErrorHandling') === 1)
        {
            //register handlers
            set_error_handler('Php2Core::errorHandler');
            set_exception_handler('Php2Core::exceptionHandler');
        }
        
        if(PHP2CORE -> get(Php2Core::Route) -> mode() === Php2Core\Data\Route::Routingmode_Full)
        {
            register_shutdown_function('Php2Core::shutdown');
        }
    }
    
    /**
     * @param \Php2Core\GUI\NoHTML\Xhtml $body
     * @return \Php2Core\GUI\NoHTML\Xhtml|null
     */
    private static function getTrace(\Php2Core\GUI\NoHTML\Xhtml $body): ?\Php2Core\GUI\NoHTML\Xhtml
    {
        $trace = null;
        $body -> get('table@#trace', function(\Php2Core\GUI\NoHTML\Xhtml $table) use(&$trace)
        {
            $trace = $table;
        });
        if($trace === null)
        {
            PHP2CORE -> trace();
            $body -> get('table@#trace', function(\Php2Core\GUI\NoHTML\Xhtml $table) use(&$trace)
            {
                $trace = $table;
            });
        }
        return $trace;
    }
    
    /**
     * @return void
     */
    private static function initializeDatabase(): void
    {
        $dbInfo1 = PHP2CORE -> get(Php2Core::Configuration) -> get('Database');
        $dbInfo2 = PHP2CORE -> get(Php2Core::Configuration) -> get('CDatabase');
        
        $dbc2 = Php2Core\IO\Data\Db\Database::createInstance('Php2Core', $dbInfo2['Host'], $dbInfo2['Username'], $dbInfo2['Password'], $dbInfo2['Database']);
        $dbc1 = Php2Core\IO\Data\Db\Database::createInstance(PHP2CORE -> get(Php2Core::Title), $dbInfo1['Host'], $dbInfo1['Username'], $dbInfo1['Password'], $dbInfo1['Database']);

        PHP2CORE -> set(Php2Core::Database, [$dbc1, $dbc2]);
        
        self::initializeDatabaseOverride($dbc2, $dbInfo2);
        self::initializeDatabaseOverride($dbc1, $dbInfo1);
    }
    
    /**
     * @param string $slug
     * @return array
     */
    private static function getPossibleMatchesFromSlug(string $slug): array
    {
        $parts = explode('/', $slug);
        $buffer = [ '^'.$parts[0].'$' ];
        $offParts = [ $parts[0] ];
        
        for($i=1; $i<count($parts); $i++)
        {
            $offParts[$i] = '{.+}';
            
            $temp1 = implode('\\/', array_slice($parts, 0, $i));
            $temp2 = implode('\\/', array_slice($offParts, 0, $i));
            
            $buffer[] = '^'.$temp1.'\\/'.$parts[$i].'$';
            $buffer[] = '^'.$temp2.'\\/'.$parts[$i].'$';
            
            $buffer[] = '^'.$temp1.'\\/{.+}$';
            $buffer[] = '^'.$temp2.'\\/{.+}$';
        }
        
        return array_values(array_unique($buffer));
    }
    
    /**
     * @param \Php2Core\IO\Data\Db\Database $instance
     * @param array $configuration
     * @return void
     * @throws \PDOException
     */
    private static function initializeDatabaseOverride(Php2Core\IO\Data\Db\Database $instance, array $configuration): void
    {
        $instance -> query('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \''.$configuration['Database'].'\'');
        
        try
        {
            $instance -> execute();
        } 
        catch (\PDOException $pex) 
        {
            if($pex -> getCode() === 1049)
            {
                $root = PHP2CORE -> get(Php2Core::Root) -> path();
                $structureFile = realpath(str_replace(['{ROOT}', '{__DIR__}'], [$root, __DIR__], $configuration['Structure']));
                $contentFile = realpath(str_replace(['{ROOT}', '{__DIR__}'], [$root, __DIR__], $configuration['Content']));

                if($structureFile !== false)
                {
                    $instance -> structure(file_get_contents($structureFile), \Php2Core\IO\Data\Db\Cache::CACHE_MEMORY, true);
                }
                
                if($contentFile !== false)
                {
                    include($contentFile);
                }
                
                PHP2CORE -> refresh(PHP2CORE -> baseUrl());
                return;
            }
            throw $pex;
        }
    }
}
