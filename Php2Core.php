<?php
require_once('Php2Core/TServerAdminCommands.php');
require_once('Php2Core/TDatabase.php');
require_once('Php2Core/TRouting.php');
require_once('Php2Core/TAutoloading.php');

class Php2Core
{
    use \Php2Core\Php2Core\TServerAdminCommands;
    use \Php2Core\Php2Core\TDatabase;
    use \Php2Core\Php2Core\TRouting;
    use \Php2Core\Php2Core\TAutoloading;
    
    /**
     * @return void
     */
    public static function initialize(): void
    {
        self::initializeStart();
        self::initializeVersion();
        self::initializeRoot();
        self::initializeConfiguration();
        self::initializeAutoloading();
        self::initializeHandlerOverride();
        self::initializeDatabase();
        self::initializeServerAdminCommands();
        self::initializeRouting();
        self::executeServerAdminCommands();
    }

    /**
     * @return string
     */
    public static function baseUrl(): string
    {
        $pi = pathinfo($_SERVER['SCRIPT_NAME']);
        return preg_replace('/'.substr($pi['dirname'], 1).'.+$/i', substr($pi['dirname'], 1), $_SERVER['SCRIPT_URI']);
    }

    /**
     * @return void
     */
    private static function initializeHandlerOverride(): void
    {
        if((int)CONFIGURATION -> Get('Logic/ErrorHandling') === 1)
        {
            //register handlers
            set_error_handler('Php2Core::ErrorHandler');
            set_exception_handler('Php2Core::ExceptionHandler');
            register_shutdown_function('Php2Core::Shutdown');
        }
    }

    /**
     * @return void
     */
    private static function initializeConfiguration(): void
    {
        $appConfigFile = ROOT.'/Assets/Config.ini';
        $coreConfigFile = __DIR__.'/Assets/Config.ini';
        if(!file_exists($appConfigFile))
        {
            file_put_contents($appConfigFile, file_get_contents(__DIR__.'/Assets/Config.default.ini'));
        }
        if(!file_exists($coreConfigFile))
        {
            file_put_contents($coreConfigFile, file_get_contents(__DIR__.'/Assets/Config.default.ini'));
        }
        
        require_once('Configuration.php');

        define('CONFIGURATION', new \Php2Core\Configuration(
            array_merge(parse_ini_file($appConfigFile, true), parse_ini_file($coreConfigFile, true)),
        ));
        define('DEBUG', (int)CONFIGURATION -> Get('Configuration/Debug') === 1);
        define('TITLE', CONFIGURATION -> Get('Configuration/Title'));
    }
    
    /**
     * @return void
     */
    private static function initializeStart(): void
    {
        define('TSTART', microtime(true));
        session_start();
    }
    
    /**
     * @return void
     */
    private static function initializeRoot(): void
    {
        define('ROOT', self::root());
    }
    
    /**
     * @return void
     */
    private static function initializeVersion(): void
    {
        require_once('Version.php');
        define('VERSION', new Php2Core\Version('Php2Core', 1,0,0,0, 'https://github.com/Unreal-Technologies/Php2Core'));
    }
    
    /**
     * @param string $path
     * @return string
     */
    public static function physicalToRelativePath(string $path): string
    {
        $basePath = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];

        $new = str_replace([ROOT.'\\', '\\', '//', ':/'], ['', '/', '/', '://'], $path);
        if($new !== $path)
        {
            return $basePath.'/'.$new;
        }
        
        var_dump($path);
        throw new Php2Core\Exceptions\NotImplementedException();
    }
    
    /**
     * @return string
     */
    private static function root(): string
    {
        //get Directory
        $pi = pathinfo(__DIR__);
        return $pi['dirname'];
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
        
        XHTML -> Get('body', function(Php2Core\NoHTML\XHtml $body) use(&$hasBody, $errno, $errstr, $errfile, $errline)
        {
            $body -> Clear();
            $body -> Add('h2', function(Php2Core\NoHTML\XHtml $h2)
            {
                $h2 -> Text('Php2Core::ErrorHandler');
            });
            $body -> Add('xmp', function(Php2Core\NoHTML\XHtml $xmp) use($errfile, $errline)
            {
                $xmp -> Text(print_r($errfile.':'.$errline, true));
            });
            $body -> Add('xmp', function(Php2Core\NoHTML\XHtml $xmp) use($errstr, $errno)
            {
                $xmp -> Text($errno.' ');
                $xmp -> Text(print_r($errstr, true));
            });
            
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
        $hasBody = false;
        
        XHTML -> Get('body', function(Php2Core\NoHTML\XHtml $body) use(&$hasBody, $ex)
        {
            $body -> Clear();
            $body -> Add('h2', function(Php2Core\NoHTML\XHtml $h2)
            {
                $h2 -> Text('Php2Core::ExceptionHandler');
            });
            $body -> Add('xmp', function(Php2Core\NoHTML\XHtml $xmp) use($ex)
            {
                $xmp -> Text(print_r($ex, true));
            });
            
            $hasBody = true; 
        });
        
        if(!$hasBody)
        {
            echo '<h2>Php2Core::ExceptionHandler</h2>';
            echo '<xmp>';
            print_r($ex);
            echo '</xmp>';
        }
        exit;
    }
    
    /**
     * @return void
     */
    public static function shutdown(): void
    {
        XHTML -> Get('body', function(Php2Core\NoHTML\XHtml $body)
        {
            $body -> Add('div', function(Php2Core\NoHTML\XHtml $div)
            {
                $dif = microtime(true) - TSTART;
                
                $div -> Attributes() -> Set('id', 'execution-time');
                $div -> Text('Process time: '.number_format(round($dif * 1000, 4), 4, ',', '.').' ms');
            });
            $body -> Add('div', function(\Php2Core\NoHTML\XHtml $div)
            {
                $div -> Attributes() -> Set('id', 'version');
                VERSION -> Render($div);
            });
        });
        XHTML -> Get('head', function(Php2Core\NoHTML\XHtml $head)
        {
            $children = $head -> Children();
            $head -> Clear();
            
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'icon');
                $link -> Attributes() -> Set('type', 'image/x-icon');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Images/favicon.ico'));
            });
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/FA-all.min.5.15.4.css'));
            });
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Materialize.css'));
            });
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Php2Core.css'));
            });
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', 'https://fonts.googleapis.com/icon?family=Material+Icons');
            });
            $head -> Add('script', function(Php2Core\NoHTML\XHtml $script)
            {
                $script -> Attributes() -> Set('type', 'text/javascript');
                $script -> Attributes() -> Set('src', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/jquery-3.7.1.min.js'));
            });
            $head -> Add('script', function(Php2Core\NoHTML\XHtml $script)
            {
                $script -> Attributes() -> Set('type', 'text/javascript');
                $script -> Attributes() -> Set('src', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Materialize.js'));
            });

            foreach($children as $child)
            {
                $head -> Append($child);
            }
        });

        //output
        echo XHTML;
        
        if(DEBUG && (int)CONFIGURATION -> Get('Configuration/XhtmlOut') === 1)
        {
            echo '<hr />';
            echo '<xmp>';
            print_r(str_replace(['<xmp>', '</xmp>'], ['<.xmp>', '</.xmp>'], (string)XHTML));
            echo '</xmp>';
        }
    }
}
