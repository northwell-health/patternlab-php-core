<?php

/*!
 * Template Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Set-ups the vars needed related to setting up and rendering templates. Meaning putting 
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Timer;

class Template {
	
	protected static $htmlHead;
	protected static $htmlFoot;
	protected static $patternHead;
	protected static $patternFoot;
	protected static $filesystemLoader;
	protected static $patternLoader;
	protected static $stringLoader;
	
	/**
	* Set-up default vars
	*/
	public static function init() {
		
		// make sure config vars exist
		if (!Config::getOption("patternExtension")) {
			Console::writeError("the pattern extension config option needs to be set...");
		}
		
		if (!Config::getOption("styleguideKit")) {
			Console::writeError("the styleguideKit config option needs to be set...");
		}
		
		// set-up config vars
		$patternExtension        = Config::getOption("patternExtension");
		$pluginDir               = Config::getOption("packagesDir");
		$sourceDir               = Config::getOption("sourceDir");
		$styleguideKit           = Config::getOption("styleguideKit");
		
		// load pattern-lab's resources
		$partialPath             = $pluginDir."/".$styleguideKit."/views/partials";
		self::$htmlHead          = file_get_contents($partialPath."/general-header.".$patternExtension);
		self::$htmlFoot          = file_get_contents($partialPath."/general-footer.".$patternExtension);
		
		// gather the user-defined header and footer information
		$patternHeadPath         = $sourceDir."/_meta/_00-head.".$patternExtension;
		$patternFootPath         = $sourceDir."/_meta/_01-foot.".$patternExtension;
		self::$patternHead       = (file_exists($patternHeadPath)) ? file_get_contents($patternHeadPath) : "";
		self::$patternFoot       = (file_exists($patternFootPath)) ? file_get_contents($patternFootPath) : "";
		
		// add the generic loaders
		$options                 = array();
		$options["templatePath"] = $pluginDir."/".$styleguideKit."/views";
		$options["partialsPath"] = $pluginDir."/".$styleguideKit."/views/partials";
		self::$filesystemLoader  = PatternEngine::getInstance()->getFileSystemLoader($options);
		$stringLoaderClass       = $patternEngineBasePath."\Loaders\StringLoader";
		self::$stringLoader      = new $stringLoaderClass();
		
	}
	
	/*
	 * Get the html header
	 */
	public static function getHTMLHead() {
		return self::$htmlHead;
	}
	
	/*
	 * Get the html foot
	 */
	public static function getHTMLFoot() {
		return self::$htmlFoot;
	}
	
	/*
	 * Get the pattern header
	 */
	public static function getPatternHead() {
		return self::$patternHead;
	}
	
	/*
	 * Get the pattern footer
	 */
	public static function getPatternFoot() {
		return self::$patternFoot;
	}
	
	/*
	 * Get the file system loader
	 */
	public static function getFilesystemLoader() {
		return self::$filesystemLoader;
	}
	
	/*
	 * Get the html loader
	 */
	
	/*
	 * Get the pattern loader
	 */
	public static function getPatternLoader() {
		
		if (empty(self::$patternLoader)) {
			Console::writeTag("error","pattern loader needs to be set before you can get it...");
			Console::writeError("try this first: <info>Template::setPatternLoader(PatternEngine::getInstance()->getPatternLoader($options));</info>");
		}
		
		return self::$patternLoader;
		
	}
	
	/*
	 * Get the pattern loader
	 */
	public static function setPatternLoader($instance) {
		self::$patternLoader = $instance;
	public static function getStringLoader() {
		return self::$stringLoader;
	}
	
}
