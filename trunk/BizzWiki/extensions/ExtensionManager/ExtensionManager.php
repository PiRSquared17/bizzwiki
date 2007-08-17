<?php
/*<!--<wikitext>-->
{{Extension
|name        = ExtensionManager
|status      = beta
|type        = parser
|author      = [[user:jldupont|Jean-Lou Dupont]]
|image       =
|version     = See SVN ($Id$)
|update      =
|mediawiki   = tested on 1.10 but probably works with a earlier versions
|download    = [http://bizzwiki.googlecode.com/svn/trunk/BizzWiki/extensions/ExtensionManager/ SVN]
|readme      =
|changelog   =
|description = 
|parameters  =
|rights      =
|example     =
}}
<!--@@
{{#autoredirect: Extension|{{#noext:{{SUBPAGENAME}} }} }}
== File Status ==
This section is only valid when viewing the page in a BizzWiki environment.
<code>(($#extractmtime|@@mtime@@$))  (($#extractfile|@@file@@$))</code>

Status: (($#comparemtime|<b>File system copy is newer - [{{fullurl:{{NAMESPACE}}:{{PAGENAME}}|action=reload}} Reload] </b>|Up to date$))
@@-->
== Purpose==
Provides a means of easily installing 'extensions' to MediaWiki.

== Features ==
* Definition of 'repositories'

== Theory of Operation ==

== Usage ==
Use the parser function '#extension' in the NS_EXTENSION namespace.
<nowiki>{{#extension: repo=REPOSITORY TYPE | dir=DIRECTORY [| name=NAME ] }}</nowiki>

== Dependancy ==
* [[Extension:StubManager|StubManager extension]]

== Installation ==
To install independantly from BizzWiki:
* Download & Install [[Extension:StubManager]] extension
* Dowload all this extension's files and place in the desired directory
* Apply the following changes to 'LocalSettings.php' after the statements of [[Extension:StubManager]]:
<source lang=php>
require('extensions/ExtensionManager/ExtensionManager_stub.php');
</source>

== History ==

== See Also ==
This extension is part of the [[Extension:BizzWiki|BizzWiki Platform]].

== Code ==
<!--</wikitext>--><source lang=php>*/

$wgExtensionCredits[ExtensionManager::thisType][] = array( 
	'name'    	=> ExtensionManager::thisName,
	'version' 	=> StubManager::getRevisionId('$Id$'),
	'author'  	=> 'Jean-Lou Dupont',
	'description' => "Provides installation and maintenance functions for MediaWiki extensions. ", 
	'url' 		=> StubManager::getFullUrl(__FILE__),	
);

abstract class ExtensionRepository
{
	public function __construct()
	{
	}
	
}

requires('ExtensionManager.i18n.php');

class ExtensionManager
{
	const thisType = 'other';
	const thisName = 'ExtensionManager';

	const repoClassesDir = '/Repositories';
	const keyREPO = 'repo';
	const keyDIR  = 'dir';
	const keyNAME = 'name';

	static $msg;

	// Variables
	var $currentRepo;
	
	public function __construct() 
	{
		global $wgMessageCache;
		foreach( self::$msg as $key => $value )
			$wgMessageCache->addMessages( self::$msg[$key], $key );
			
		$this->init();
	}
	/**
		Initialize variables
	 */
	protected function init()
	{
		$this->currentRepo = null;	
	}
	/**
		Reports the status of this extension in the [[Special:Version]] page.
	 */	
	public function hSpecialVersionExtensionTypes( &$sp, &$extensionTypes )
	{
		global $wgExtensionCredits;

		$result = '';
		if (!defined('NS_EXTENSION'))
			$result .= wfMsg('extensionmanager-missing-namespace');
		
		// add other checks here.
		
		foreach ( $wgExtensionCredits[self::thisType] as $index => &$el )
			if (isset($el['name']))		
				if ($el['name']==self::thisName)
					$el['description'] .= $result;
				
		return true; // continue hook-chain.
	}
	/**
		This method implements the 'parser function magic word' #extension.
	 */
	public function mg_extension( &$parser )
	{
		// process the argument list
		$args = func_get_args();
		$argv = StubManager::processArgList( $args, true );
		
		// get the parameters
		$repo = $argv[self::keyREPO];
		$dir  = $argv[self::keyDIR];
		
		$result = $this->validateParameters( $repo, $dir );
		if (!empty( $result ))
			return $result;
			
			
	}
	
	protected function validateParameters( &$repo, &$dir )
	{
		// First, let's try to load the class defining
		// the requested repository
		if (!$this->loadRepoClass( $repo ) )
			return wfMsg('extensionmanager').wfMsgForContent('extensionmanager'.'-error-loadingrepo', $repo );
			
	}
	/**
		Load the class definition of the required repository
	 */
	protected function loadRepoClass( &$name )
	{
		// is the class already loaded??
		if ( class_exists( $name ) )
			return true;

		$filename = __FILE__.self::repoClassesDir.'/'.$name.'.php';
		
		// silently try to load the class describing the repository
		@require( $filename );
		
		// check if we have succeeded (!)
		if ( class_exists( $name ) )
			return true;
			
		return null;
	}
	
} // end class
//</source>
