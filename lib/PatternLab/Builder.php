<?php

/*!
 * Builder Class
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Holds most of the "generate" functions used in the the Generator and Watcher class
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\PatternData\Exporters\NavItemsExporter;
use \PatternLab\PatternData\Exporters\PatternPartialsExporter;
use \PatternLab\PatternData\Exporters\PatternPathDestsExporter;
use \PatternLab\PatternData\Exporters\ViewAllPathsExporter;
use \PatternLab\Render;
use \PatternLab\Template\Helper;

class Builder {
	
	/**
	* When initializing the Builder class make sure the template helper is set-up
	*/
	public function __construct() {
		
		//$this->patternCSS   = array();
		
		// set-up the various attributes for rendering templates
		Helper::setup();
		
	}
	
	/**
	* Finds Media Queries in CSS files in the source/css/ dir
	*
	* @return {Array}        an array of the appropriate MQs
	*/
	protected function gatherMQs() {
		
		$mqs = array();
		
		foreach(glob(Config::$options["sourceDir"]."/css/*.css") as $filename) {
			$data = file_get_contents($filename);
			preg_match_all("/(min|max)-width:([ ]+)?(([0-9]{1,5})(\.[0-9]{1,20}|)(px|em))/",$data,$matches);
			foreach ($matches[3] as $match) {
				if (!in_array($match,$mqs)) {
					$mqs[] = $match;
				}
			}	
		}
		
		usort($mqs, "strnatcmp");
		
		return $mqs;
		
	}
	
	/**
	* Generates the index page and style guide
	*/
	protected function generateIndex() {
		
		// grab the items for the nav
		$niExporter       = new NavItemsExporter();
		$navItems         = $niExporter->run();
		
		// grab the pattern paths that will be used on the front-end
		$ppdExporter      = new PatternPathDestsExporter();
		$patternPathDests = $ppdExporter->run();
		
		// grab the view all paths that will be used on the front-end
		$vapExporter      = new ViewAllPathsExporter();
		$viewAllPaths     = $vapExporter->run($navItems);
		
		// add the various configuration options that need to be drawn on the front-end
		$navItems["autoreloadnav"]     = Config::$options["autoReloadNav"];
		$navItems["autoreloadport"]    = Config::$options["autoReloadPort"];
		$navItems["cacheBuster"]       = Config::$options["cacheBuster"];
		$navItems["ipaddress"]         = getHostByName(getHostName());
		$navItems["ishminimum"]        = Config::$options["ishMinimum"];
		$navItems["ishmaximum"]        = Config::$options["ishMaximum"];
		$navItems["ishControlsHide"]   = Config::$options["ishControlsHide"];
		$navItems["mqs"]               = $this->gatherMQs();
		$navItems["pagefollownav"]     = Config::$options["pageFollowNav"];
		$navItems["pagefollowport"]    = Config::$options["pageFollowPort"];
		$navItems["patternpaths"]      = json_encode($patternPathDests);
		$navItems["qrcodegeneratoron"] = Config::$options["qrCodeGeneratorOn"];
		$navItems["viewallpaths"]      = json_encode($viewAllPaths);
		$navItems["xiphostname"]       = Config::$options["xipHostname"];
		
		// render the index page
		$index = Helper::$filesystemLoader->render('index',$navItems);
		file_put_contents(Config::$options["publicDir"]."/index.html",$index);
		
	}
	
	/**
	* Generates all of the patterns and puts them in the public directory
	*/
	protected function generatePatterns() {
		
		// make sure patterns exists
		if (!is_dir(Config::$options["publicDir"]."/patterns")) {
			mkdir(Config::$options["publicDir"]."/patterns");
		}
		
		// loop over the pattern data store to render the individual patterns
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (!$patternStoreData["hidden"])) {
				
				$path          = $patternStoreData["pathDash"];
				$pathName      = (isset($patternStoreData["pseudo"])) ? $patternStoreData["pathOrig"] : $patternStoreData["pathName"];
				
				// modify the pattern mark-up
				$markup        = $patternStoreData["code"];
				$markupEncoded = htmlentities($markup);
				$markupFull    = $patternStoreData["header"].$markup.$patternStoreData["footer"];
				$markupEngine  = htmlentities(file_get_contents(__DIR__.Config::$options["patternSourceDir"].$pathName.".".Config::$options["patternExtension"]));
				
				// if the pattern directory doesn't exist create it
				if (!is_dir(__DIR__.Config::$options["patternPublicDir"].$path)) {
					mkdir(__DIR__.Config::$options["patternPublicDir"].$path);
				}
				
				// write out the various pattern files
				file_put_contents(__DIR__.Config::$options["patternPublicDir"].$path."/".$path.".html",$markupFull);
				file_put_contents(__DIR__.Config::$options["patternPublicDir"].$path."/".$path.".escaped.html",$markupEncoded);
				file_put_contents(__DIR__.Config::$options["patternPublicDir"].$path."/".$path.".".Config::$options["patternExtension"],$markupEngine);
				if (Config::$options["enableCSS"] && isset($this->patternCSS[$p])) {
					file_put_contents(__DIR__.Config::$options["patternPublicDir"].$path."/".$path.".css",htmlentities($this->patternCSS[$p]));
				}
				
			}
			
		}
		
	}
	
	/**
	* Generates the style guide view
	*/
	protected function generateStyleguide() {
		
		if (!is_dir(Config::$options["publicDir"]."/styleguide/html/")) {
			
			print "ERROR: the main style guide wasn't written out. make sure public/styleguide exists. can copy core/styleguide\n";
			
		} else {
			
			// grab the partials into a data object for the style guide
			$ppExporter     = new PatternPartialsExporter();
			$partialsAll    = $ppExporter->run();
			
			// render the style guide
			$styleGuideHead = Helper::$htmlLoader->render(Helper::$mainPageHead,Data::$store);
			$styleGuideFoot = Helper::$htmlLoader->render(Helper::$mainPageFoot,Data::$store);
			$styleGuidePage = $styleGuideHead.Helper::$filesystemLoader->render("viewall",$partialsAll).$styleGuideFoot;
			
			file_put_contents(Config::$options["publicDir"]."/styleguide/html/styleguide.html",$styleGuidePage);
			
		}
		
	}
	
	/**
	* Generates the view all pages
	*/
	protected function generateViewAllPages() {
		
		// add view all to each list
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if ($patternStoreData["category"] == "patternSubtype") {
				
				// grab the partials into a data object for the style guide
				$ppExporter  = new PatternPartialsExporter();
				$partials    = $ppExporter->run($patternStoreData["type"],$patternStoreData["name"]);
				
				if (!empty($partials["partials"])) {
					
					$partials["patternPartial"] = "viewall-".$patternStoreData["typeDash"]."-".$patternStoreData["nameDash"];
					
					$viewAllHead = Helper::$htmlLoader->render(Helper::$mainPageHead,Data::$store);
					$viewAllFoot = Helper::$htmlLoader->render(Helper::$mainPageFoot,Data::$store);
					$viewAllPage = $viewAllHead.Helper::$filesystemLoader->render("viewall",$partials).$viewAllFoot;
					
					// if the pattern directory doesn't exist create it
					$patternPath = $patternStoreData["pathDash"];
					if (!is_dir(__DIR__.Config::$options["patternPublicDir"].$patternPath)) {
						mkdir(__DIR__.Config::$options["patternPublicDir"].$patternPath);
						file_put_contents(__DIR__.Config::$options["patternPublicDir"].$patternPath."/index.html",$viewAllPage);
					} else {
						file_put_contents(__DIR__.Config::$options["patternPublicDir"].$patternPath."/index.html",$viewAllPage);
					}
					
				}
				
			}
			
		}
		
	}
	
	/**
	* Loads the CSS from source/css/ into CSS Rule Saver to be used for code view
	* Will eventually get pushed elsewhere
	*/
	protected function initializeCSSRuleSaver() {
		
		$loader = new \SplClassLoader('CSSRuleSaver', __DIR__.'/../../lib');
		$loader->register();
		
		$this->cssRuleSaver = new \CSSRuleSaver\CSSRuleSaver;
		
		foreach(glob(Config::$options["sourceDir"]."/css/*.css") as $filename) {
			$this->cssRuleSaver->loadCSS($filename);
		}
		
	}
	
}
