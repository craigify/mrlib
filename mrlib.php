<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Pronounced "Mister Lib" - kind of like Mr. Coffee and Mr. Radar in the movie Space Balls.
//
// MrLib is a static class that initializes the library and provides some basic functionality
// for use throughout.  Include this file before including any other mrlib file to make sure
// that things have been initialized correctly.
//

class mrlib
{
    public static $filesystemLocation;
    
    
    // Initialize MrLib.
    public static function init()
    {
        mrlib::detectFilesystemLocation();
        
        if (MRLIB_AUTOLOAD_ENABLED == TRUE)
        {
            mrlib::autoload();
        }
    }
    
    

    // Include a mrlib module file.  You don't need to know the full path, since MrLib detects the location in the
    // filesystem automatucally.
    //
    // This method can be called in the following two ways:
    // 1 -  Specify the path syntax:
    //      mrlib::load("sql/MrDatabaseManager");
    //
    // 2 -  Specify the module and filename explicitly: 
    //      mrlib::load("sql", "MrDatabaseManager.php");
    //
    public static function load()
    {
        // Path syntax
        if (func_num_args() == 1)
        {
            $path = func_get_arg(0);
            $elements = explode("/", $path);
            $last = count($elements) - 1;
            $module = $elements[0];
            $file = $elements[$last] . ".php";
        }
        
        // Specified module and filename
        else
        {
            $module = func_get_arg(0);
            $file = func_get_arg(1);
        }
        
        $location = mrlib::getFileSystemLocation();
        require_once($location['modules_dir'] . "/{$module}/{$file}");
    }



    // Factory method to initialize and return new object.  Pass it a path to an object in the module tree
    // A path of "event/MrEventHandler" would return a new MrEventHandler object
    //
    // @param string $path
    // @return object
    public static function getNew($path)
    {
        $elements = explode("/", $path);
        $last = count($elements) - 1;
        $file = $elements[$last] . ".php";

        mrlib::load($elements[0], $file);
        $obj = new $elements[$last];
        return $obj;
    }


    // Factory method to get a reference to a singleton object.  Initialize the singleton if not already done.
    // @return object
    public static function getSingleton($path)
    {
        $elements = explode("/", $path);
        $last = count($elements) - 1;
        $file = $elements[$last] . ".php";

        mrlib::load($elements[0], $file);
        $code = "\$ref = {$elements[$last]}::getInstance();";
        eval($code);

        return $ref;
    }


    // Return filesystem location information
    // @return array
    public static function getFileSystemLocation()
    {
        return mrlib::$filesystemLocation;
    }



    // Detect location of mrlib in filesystem.
    public static function detectFilesystemLocation()
    {
        $f = dirname(__FILE__);
        mrlib::$filesystemLocation['lib_dir'] = "";
        mrlib::$filesystemLocation['modules_dir'] = "";

        $arr = explode("/", $f);
        $arr_num = count($arr);

        for ($i=0; $i < $arr_num; $i++)
        {
            mrlib::$filesystemLocation['lib_dir'] .= $arr[$i] . "/";
        }

        for ($i=0; $i < $arr_num - 1; $i++)
        {
            mrlib::$filesystemLocation['modules_dir'] .= $arr[$i] . "/";
        }
        
        mrlib::$filesystemLocation['modules_dir'] .= $arr[$i] . "/modules/";
    }


    // Automatically include any files in the specified autoload directory.
    public static function autoload()
    {
        if (is_dir(MRLIB_AUTOLOAD_DIR))
        {
            if ($dh = opendir(MRLIB_AUTOLOAD_DIR))
            {
                while (($file = readdir($dh)) !== false)
                {
                    if ($file != "." && $file != ".." && strpos($file, '.') != 0)
                    {
                        include(MRLIB_AUTOLOAD_DIR . "/" . $file);
                    }
                }

                closedir($dh);
            }    
        }        
    }


// end class
}



// Initialize mrlib!
include_once(realpath(dirname(__FILE__)) . "/config.php");
mrlib::init();


?>
