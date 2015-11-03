<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrRouteRequest handles the initial routing of the request over to a handler
// This is a singleton because there is only ever one route request, and different
// parts of mrlib need access to this routing code.
//
// Execution stack, typically:
// Apache -> mod_rewrite -> route.php (or some other file) ->  determineHandler() -> handler file -> eventhandler -> controller -> views/templates
//

mrlib::load("core/MrSingleton");

class MrRouteRequest extends MrSingleton
{
    // Store the relative URI
    private $uri;
    
    // Store the name of the handler.
    private $handler;
    
    
    
    // Return the name of the handler that was determined by examining the request URI.
    // @return (string)   Handler name
    public function getHandler()
    {
        return $this->handler;
    }


    // Determine the URI relative to the application.  If your app is installed in a sub directory,
    // you won't see the sub directory in the URI.  If it sits in the root, the value returned from
    // this method will match the _SERVER['REQUEST_URI'].   Use this method and you don't have to
    // worry about determining that.
    //
    // @return (string)   Relative request URI.
    //
    public function getURI()
    {
        return $this->uri;
    }
    
    
    // Determine which handler file to use from the REQUEST_URI and include/execute it.
    // This works even if your code is already sitting in a sub directory on the server.
    //
    // NOTE:  The following constants should be defined.
    // MRLIB_ROUTER           The name of the file that is first executed by the web server (usually index.php in the root of your application directory)
    // MRLIB_DEFAULT_HANDLER  The name of the handler filename (MINUS .php) to include if handler cannot be determined.
    //
    // @param (string)  $prefix   Optional prefix directory to look for handlers.
    public function includeHandler($prefix="")
    {
        // If MRLIB_ROUTER is "index.php" the regex would be "/index.php\/([^?]+)?/"
        $preg_match_string = "/" . MRLIB_ROUTER . "\/([^?]+)?/";

        // See if we have a GET or POST handler value.  Our rewrite rules, if used, will set the handler
        // variable in the URL, so we check for it first.
        if (isset($_REQUEST['handler']))
        {
            $handler = $_REQUEST['handler'];
        }
        
        // See if we have a handler in the REQUEST_URI by matching a pattern like 'index.php/myhandler' (don't include the '?' character in match)
        elseif (isset($_SERVER['REQUEST_URI']) && preg_match($preg_match_string, $_SERVER['REQUEST_URI'], $uriData))
        {
            // Match after index.php and stop at / or ? character
            if (isset($uriData[1]) && !empty($uriData[1]))
            {
                $handler = $uriData[1];
            }
        }
        
        // If handler was not detected from URL, use default as a fallback.
        else
        {
            $handler = MRLIB_DEFAULT_HANDLER;
        }

        // Check and remove trailing slash.  We need to be consistent about it, so leave it off always considering
        // that we are talking about route names, and not necessarily real files and directories.
        if (substr($handler, -1) == "/")
        {
            $handler = substr($handler, 0, -1);
        }
        
        // Break down handler into parts if slashes are found.  We need the first part of the handler
        // to match to a handler include file.
        $handlerParts = explode("/", $handler);
  
        if (empty($prefix))
        {
            $handlerFile = MRLIB_HANDLERS_DIR . "/" . $handlerParts[0] . ".php";
        }
        else
        {
            $handlerFile = MRLIB_HANDLERS_DIR . "/" . $prefix . "/" . $handlerParts[0] . ".php";            
        }
        
        $this->handler = $handlerParts[0];
        $this->uri = $handler;
    
        if (file_exists($handlerFile))
        {
            include($handlerFile);
        }
        else
        {
            trigger_error("MrRouteRequest: Has URI '{$this->uri}' and expected to find handler '{$this->handler}.php' but did not.  Returning HTTP 404 ", E_USER_NOTICE);
            header("mrlib-status: Not Found");
            header("HTTP/1.0 404 Not Found");
        }
    }
    
    
    // Constructor
    function __construct()
    {
        $this->readerInfo = array();
        $this->writerInfo = array();
        $this->isConnected = FALSE;
    }



    // Override MrSingleton method and pass in our class name string
    public static function getInstance($classname = __CLASS__)
    {
        return parent::getInstance($classname);
    }
    
}