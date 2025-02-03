<?php
require_once('Php2Core/TServerAdminCommands.php');
require_once('Php2Core/TDatabase.php');
require_once('Php2Core/TRouting.php');
require_once('Php2Core/TAutoloading.php');
require_once('Php2Core/THandlers.php');

class Php2Core
{
    use \Php2Core\Php2Core\TServerAdminCommands;
    use \Php2Core\Php2Core\TDatabase;
    use \Php2Core\Php2Core\TRouting;
    use \Php2Core\Php2Core\TAutoloading;
    use \Php2Core\Php2Core\THandlers;
    
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
}
