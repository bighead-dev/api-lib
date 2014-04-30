<?php

namespace Lib;

use StringTemplate;

class Lang
{
    const LANG_BASE = './application/lang';
    
    public static $lang = null;
    
    private $data;
    private $engine;
    
    private function __construct()
    {
        $this->data   = [];
        $this->engine = new StringTemplate\Engine();
    }
    
    public static function instance()
    {
        if (self::$lang == null)
            self::$lang = new Lang();
    
        return self::$lang;
    }
    
    public static function get($lang_file)
    {
        $lang = self::instance();
        
        if (isset($lang->data[$lang_file])) {
            return $lang->data[$lang_file];
        }
        
        $file = self::LANG_BASE . '/' . $lang_file . '.php';
        
        if (!file_exists($file)) {
            throw new \Exception('Lang Loader - file not found');
        }
        
        $lang_data = $lang->get_lang_data($file);        
        $lang->data[$lang_file] = $lang_data;
        
        return $lang->data[$lang_file];
    }
    
    public static function parse($str, $data)
    {
        $lang = self::instance();
        return $lang->engine->render($str, $data);
    }
    
    private function get_lang_data($file)
    {
        return require $file;
    }
}
