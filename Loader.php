<?php

namespace Lib;

interface iLoader
{
	public function register();
	public function load($class, $ret = false);
}

class Loader implements iLoader
{
	/**
	 * Separator to be used for namespaces.
	 *
	 * Defaults to \, but can be set to whatever.
	 */
	protected $ns_separator = '\\';
	
	/**
	 * The base directory path to prepend to the file path
	 */
	protected $inc_path;
	
	/**
	 * The base namespace to grab classes from
	 */
	protected $package;
	
	private $package_len;
	
	public function __construct($package = '', $inc_path = '')
	{
		$this->package = $package;
		$this->package_len = strlen($package);
		$this->inc_path = $inc_path;
	}
	
	/**
	 * Registers the current loader to SplAutoloader
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'load'));
	}
	
	/**
	 * Loads a class file - PSR0
	 *
	 * @param string	$class	The name of the class to load
	 * @param bool		$ret	Return the file name as string or require automatically.
	 * @return void|string
	 */
	public function load($class, $ret = false)
	{
		$file  = '';
		$namespace = '';
		
		/*
		 * If we have a package path, then we can just validate the class
		 * by checking the package/ns prefix.
		 * If we don't have a package, then we have to make sure the file
		 * exists before requiring. file_exists is pretty slow because of the
		 * system calls.
		 */
		if ($this->package)
		{		
			// let's make sure we are in the right namespace
			if (strpos($class, $this->package) !== 0)
			{
				return;
			}

			// take out the Base NS
			$class = substr($class, $this->package_len);

			// replace ns separator with DIRECTORY_SEPARATOR separators
			$file	= str_replace($this->ns_separator, DIRECTORY_SEPARATOR, $class);
			$path	= $this->inc_path . $file . '.php';
		}
		else
		{
			$file	= str_replace($this->ns_separator, DIRECTORY_SEPARATOR, $class);
			$path	= $this->inc_path . $file . '.php';
			
			// validate the file
			if (!file_exists($path))
			{
				return;
			}
		}
		
		if ($ret == false)
		{
			include_once $path;
		}
		else
		{
			return $path;
		}
	}
	
	/*
	 * Getters & Setters
	 */
	 
	/**
	 * Gets the package
	 *
	 * @return string
	 */
	public function getPackage()
	{
		return $this->package;
	}
}
