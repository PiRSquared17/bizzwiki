<?php
//<source lang=php>
/***************************************************************************
	
	BizzWikiSettings.php
	
	$Id$

	TODO:
	=====
	- disable proxy caching functionality for 'file system' & BizzWiki namespaces
	

****************************************************************************/
// Define the base of BizzWiki
define('BIZZWIKI', '1_10_11');
$bwVersion = BIZZWIKI;
$bwPath    = $IP.'/BizzWiki';
$bwExtPath = $IP.'/BizzWiki/extensions';

// Base class for multiple extensions
require('extensions/ExtensionClass.php');
require('extensions/RunPHP_class.php');
require('extensions/StubManager.php');

require( $bwExtPath.'/ParserPhase2/ParserPhase2_stub.php' );

// Parser & Page caching.
$wgEnableParserCache = true;
$wgParserCacheType   = CACHE_ANYTHING;
$wgCachePages        = true;

// Disable raw html
// (There is the extension 'SecureHTML' to better cover this)
$wgRawHtml = false;  // on protected pages, one can use 'SecureHTML' extension 
					// to achieve the same goal

// Capital Letter Links
// are annoying for NS_FILESYSTEM & NS_DIRECTORY namespaces
$wgCapitalLinks = false;

// Need to include standard 'Namespace.php'
require($IP.'/includes/Namespace.php');

## DEFINE NEW NAMESPACES HERE 
## {{

	// define new namespaces constants
	define('NS_BIZZWIKI',   100);
	define('NS_FILESYSTEM', 102);
	define('NS_INTERWIKI',  104);
	define('NS_API',		106);	
	define('NS_DIRECTORY',	108);		// extension DirectoryManager
	define('NS_EXTENSION',  110);		// for easy integration with MediaWiki.org
	
	// Add the new namespaces to the global variables
	$wgExtraNamespaces[NS_BIZZWIKI]   = 'Bizzwiki';
	$wgExtraNamespaces[NS_FILESYSTEM] = 'Filesystem';
	$wgExtraNamespaces[NS_INTERWIKI]  = 'Interwiki';
	$wgExtraNamespaces[NS_API]		  = 'Api';
	$wgExtraNamespaces[NS_DIRECTORY]  = 'Directory';	
	$wgExtraNamespaces[NS_EXTENSION]  = 'Extension';		

## }}

$wgCanonicalNamespaceNames[NS_MAIN] = 'Main';

// Put all the namespaces in the global variable
$wgCanonicalNamespaceNames = $wgCanonicalNamespaceNames + $wgExtraNamespaces;

// Subpages
$bwNamespacesWithSubpages = array ( NS_MAIN,
									NS_TALK,
									NS_PROJECT,
									NS_PROJECT_TALK,
									NS_CATEGORY,
									NS_CATEGORY_TALK,
									NS_MEDIAWIKI,
									NS_MEDIAWIKI_TALK,									
									NS_BIZZWIKI,
									NS_FILESYSTEM,
									NS_INTERWIKI,	// not used at the moment.
									NS_DIRECTORY,
									NS_EXTENSION,
									);
foreach ( $bwNamespacesWithSubpages as $index => $bwx )
	$wgNamespacesWithSubpages[ $bwx ] = true;
	
/* *******************************************************
	Apply new permission management functionality
	1) Hierarchical Namespace Permissions
	2) Wipe out the standard Mediawiki permission settings
	3) All rights
	4) Provision the new permission settings
*/
require('extensions/HierarchicalNamespacePermissions/HierarchicalNamespacePermissions.php');

require( $bwExtPath.'/RawRight/RawRight_stub.php');
require( $bwExtPath.'/ViewsourceRight/ViewsourceRight_stub.php');
require( $bwExtPath.'/WatchRight/WatchRight_stub.php');

require('extensions/MakeSysop/SpecialMakesysop.php');
require('extensions/DeSysop/SpecialDesysop.php');

// define group hierarchy
// default is: sysop -> user -> *
#hnpClass::singleton()->setGroupHierarchy( array('sysop', 'user', '*') ));

unset( $wgGroupPermissions );

$bwNamespaceIndependantRights =  array( 'createaccount',
										'ipblock-exempt',
										'hideuser',
										'userrights',		
										'siteadmin',
										'import',
										'importupload',										
										'deletedhistory',
										'deleterevision',	// TODO
										'hiderevision',		// TODO
										'block',
										'oversight',
										'bot',             	// TODO
										'proxyunbannable', 	// TODO
										'trackback',		// TODO
										'unwatchedpages',	// TODO
										'readlog',  		// BizzWiki specific
										'siteupdate',		// BizzWiki specific
										'undelete',			// BizzWiki specific
										'userdetails',		// BizzWiki specific			
																				
										'skipcaptcha',		// ReCAPTCHA specific
										'makesysop',		// MakeSysop extension specific
										'desysop',			// DeSysop extension specific	
									);


$bwNamespaceDependantRights =  array(	'read', 'edit', 'minoredit', 'create', 'delete', 'move',								
										'nominornewtalk', 
										"createpage", "createtalk",
										
										"rollback",			// TODO
										'protect',
										'patrol', "autopatrol",
										'purge',			// TODO
										'upload', 'reupload', 'reupload-shared', // for now, those rights
										'upload_by_url',						// are only relevant to NS_IMAGE
										
										'browse',     // BizzWiki specific
										'search',     // BizzWiki specific
										'raw',        // BizzWiki specific -- RawRight extension
										'viewsource', // BizzWiki specific -- ViewsourceRight extension
										'watch',	  // BizzWiki specific -- WatchRight extension
										'unwatch',	  // BizzWiki specific -- WatchRight extension
										'reload',	  // BizzWiki specific -- FileManager extension
										'coding',		// BizzWiki specific -- SecurePHP extension
									);
									
// Critical permission system initialization -- DO NOT TOUCH
## {{
hnpClass::singleton()->addNamespaceIndependantRights( $bwNamespaceIndependantRights );
hnpClass::singleton()->addNamespaceDependantRights(   $bwNamespaceDependantRights );
## }}

	// Steward Group
$wgGroupPermissions['steward' ][hnpClass::buildPermissionKey("~","~","makesysop")]		= true;	
$wgGroupPermissions['steward' ][hnpClass::buildPermissionKey("~","~","userrights")]		= true;	

	// Bureaucrat Group
$wgGroupPermissions['bureaucrat' ][hnpClass::buildPermissionKey("~","~","makesysop")]	= true;	
$wgGroupPermissions['bureaucrat' ][hnpClass::buildPermissionKey("~","~","desysop")]		= true;	

	// Sysop gets all the rights.
	// ##########################
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey("~","~","~")]     = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey("~","~","!bot")]  = true;  // required !!
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/newusers",	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/commitfil",	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/upload",		"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/delete",   	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/rights",     "browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/move",     	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/block",    	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/emaillog",  	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/usetchglog",	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/watchlog",	"browse")] = true;
// replication related.
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/ftchrclog",	"browse")] = true;	// partner RC
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/schlog",		"browse")] = true;	// task scheduler
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/usrloglog",	"browse")] = true;	// UserLoginLogoutLog

	// Anonymous
	// #########
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey("~","~","createaccount")] = true;

if (!defined('BIZZWIKIDEMO'))
{
	// remove access to some log entries.
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/newusers",	"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/commitfil",	"!browse")] = true; // FileManager extension
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/upload",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/delete",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/rights",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/move",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/block",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/emaillog",  	"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/usetchglog",	"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/watchlog",	"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/usrloglog",	"!browse")] = true;	// UserLoginLogoutLog

	// replication related.
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/ftchrclog",	"!browse")] = true;	// partner RC
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/schlog",		"!browse")] = true;	// task scheduler

#$wgGroupPermissions['*' ][hnpClass::buildPermissionKey("~","~","readlog")] = true;  // debugging
}

	// Namespace accessible by 'Anonynous'
	// ###################################
$bwAnonymousNamespaces = array( NS_MAIN, NS_TALK,
								NS_PROJECT, NS_PROJECT_TALK,
								NS_CATEGORY, NS_CATEGORY_TALK,
								NS_HELP, NS_HELP_TALK,
								NS_SPECIAL,  // comment out this namespace to restrict access to special pages.
								NS_INTERWIKI // BizzWiki specific
								); 

	// Rights available to 'Anonymous'
	// ###############################
foreach( $bwAnonymousNamespaces as $index => $bwx )
{	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","read")]   = true;
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","browse")] = true;
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","search")] = true;
	#$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","raw")] = true;   
	#$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","viewsource")] = true;
}
// to enable anonymous access to the login/create account special page.
#$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Userlogin","read")] = true;

	// 'Users' inherit all rights from '*' (anonymous)
	// ############################################### 
	
		// Log Entries access
$wgGroupPermissions['user' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/newusers","!browse")] = true;

	// Namespace accessible by 'User'
	// ##############################
$bwUserNamespaces = array (	NS_TEMPLATE, NS_TEMPLATE_TALK,
							NS_IMAGE, NS_IMAGE_TALK,
							NS_USER, NS_USER_TALK														
							);	

$bwUserNamespaces = array_merge( $bwAnonymousNamespaces, $bwUserNamespaces);

	// Additional rights available to 'User'
	// #####################################
foreach( $bwUserNamespaces as $index => $bwx )
	{
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","read")] = true;
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","browse")] = true;
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","search")] = true;
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","watch")] = true;		
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","unwatch")] = true;				
	}

	// Equivalent functionality to 'KeepYourHandsToYourself' extension
	// ###############################################################
$wgExtensionFunctions[] = 'bwKeepYourHandsToYourself';

function bwKeepYourHandsToYourself()
{
	global $wgUser, $wgGroupPermissions;
	$userName = $wgUser->getName();
	$wgGroupPermissions['user'][hnpClass::buildPermissionKey(NS_USER,$userName,"edit")]   = true;
	$wgGroupPermissions['user'][hnpClass::buildPermissionKey(NS_USER,$userName,"create")] = true;	
	$wgGroupPermissions['user'][hnpClass::buildPermissionKey(NS_USER,$userName,"delete")] = true;	
}

	// Page Level Restrictions
	// %%%%%%%%%%%%%%%%%%%%%%%
require('extensions/PageRestrictions/PageRestrictions.php');

// For testing QueryPage.php functionality
// as in 'SpecialPopularpages.php'.
// DEBUG
## {{
$wgContentNamespaces[] = NS_BIZZWIKI;
$wgContentNamespaces[] = NS_FILESYSTEM;
$wgContentNamespaces[] = NS_INTERWIKI;
## }}

// *****************************************************************************************

// Protect critical namespaces from Templating level permission bypassing.
// It is strongly suggested not to mess with this.
$wgNonincludableNamespaces[] = NS_FILESYSTEM;
$wgNonincludableNamespaces[] = NS_BIZZWIKI;
$wgNonincludableNamespaces[] = NS_INTERWIKI;

// *****************************************************************************************

/**
 * Show a bar of language selection links in the user login and user
 * registration forms; edit the "loginlanguagelinks" message to
 * customise these
 */
$wgLoginLanguageSelector = false;

// readfile & commitfile rights
require( $bwExtPath.'/FileManager/FileManager_stub.php');
#require('extensions/FileManager/FileManager.php');

// syntax highlighting for the NS_FILESYSTEM namespace.
require( $bwExtPath.'/FileSystemSyntaxColoring/FileSystemSyntaxColoring_stub.php' );

// New User Logging
require('extensions/NewUserLog/Newuserlog.php');

// Show Redirect Page Text extension
require( $bwExtPath.'/ShowRedirectPageText/ShowRedirectPageText_stub.php' );

// Interwiki table management
require( $bwExtPath.'/InterWikiLinkManager/InterWikiLinkManager_stub.php' );

// Sidebar extended
require('extensions/SidebarEx/SidebarEx.php');

// Generic Syntax Highlighter
require( $bwExtPath.'/GeSHi/geshi_stub.php' );

// Enhanced Special Pages
require('extensions/SpecialPagesManager/SpecialPagesManager.php');
 // Use the following to customize the source path of the files.
 // TODO

// Form/Page related tools
require( $bwExtPath.'/SecureHTML/SecureHTML_stub.php' );
# SecureHTMLclass::enableExemptNamespaces = false; # turn off
# SecureHTMLclass::exemptNamespaces[] = NS_XYZ;    # to add namespaces to exemption list

require( $bwExtPath.'/SecureProperties/SecureProperties_stub.php' );
require( $bwExtPath.'/FormProc/FormProc_stub.php');
require( $bwExtPath.'/AutoLanguage/AutoLanguage_stub.php' );

// can be added through 'createStub2' method.
#AutoLanguageClass::$exemptNamespaces[] = NS_BIZZWIKI;
#AutoLanguageClass::$exemptNamespaces[] = NS_INTERWIKI;
#AutoLanguageClass::$exemptNamespaces[] = NS_FILESYSTEM;

	// Parser Extensions
	// %%%%%%%%%%%%%%%%%
// http://meta.wikimedia.org/wiki/ParserFunctions
require( 'extensions/ParserExt/ParserFunctions/ParserFunctions.php' );

// http://www.mediawiki.org/wiki/Extension:StringFunctions
require( 'extensions/ParserExt/StringFunctions/StringFunctions.php' );
require( $bwExtPath.'/ParserExt/PermissionFunctions/PermissionFunctions_stub.php');

require('extensions/ParserExt/NamespaceFunctions/NamespaceFunctions.php'); // NOT STUBBED

	// Stubs
	//  Used for rare events handling.

require( $bwExtPath.'/addScriptCss/AddScriptCss_stub.php' );
require( $bwExtPath.'/EmailLog/EmailLog_stub.php' );
require( $bwExtPath.'/UserSettingsChangedLog/UserSettingsChangedLog_stub.php' );
require( $bwExtPath.'/WatchLog/WatchLog_stub.php' );
require( $bwExtPath.'/RegexNamespaceContext/RegexNamespaceContext_stub.php' );
require( $bwExtPath.'/RawPageTools/RawPageTools_stub.php' );
require( $bwExtPath.'/NewUserEmailNotification/NewUserEmailNotification_stub.php' );
require( $bwExtPath.'/UserLoginLogoutLog/UserLoginLogoutLog_stub.php' );

// Parser Functions
require( $bwExtPath.'/ParserExt/ForeachFunction/ForeachFunction_stub.php' );
require( $bwExtPath.'/ParserExt/MiscParserFunctions/MiscParserFunctions_stub.php' );
require( $bwExtPath.'/ParserExt/PageFunctions/PageFunctions_stub.php' );
require( $bwExtPath.'/ParserExt/ParserTools/ParserTools_stub.php' );
require( $bwExtPath.'/ParserExt/RegexTools/RegexTools_stub.php');
require( $bwExtPath.'/DocProc/DocProc_stub.php' );
require( $bwExtPath.'/ScriptingTools/ScriptingTools_stub.php' );
require( $bwExtPath.'/RecentChangesManager/RecentChangesManager_stub.php' );
require( $bwExtPath.'/ImageLink/ImageLink_stub.php');
require( $bwExtPath.'/PageAfterAndBefore/PageAfterAndBefore_stub.php');
require( $bwExtPath.'/ParserExt/SkinTools/SkinTools_stub.php' );
require( $bwExtPath.'/ParserExt/UserTools/UserTools_stub.php' );
require( $bwExtPath.'/ParserExt/UserTools/UserTools_stub.php' );

require('extensions/VirtualPage/VirtualPage_stub.php');
require('extensions/AutoRedirect/AutoRedirect_stub.php');
require('extensions/DirectoryManager/DirectoryManager_stub.php');

require('extensions/SecurePHP/SecurePHP_stub.php');

require('extensions/GoogleCode/GoogleCode_stub.php');
/* TODO
require('extensions/DPL/DynamicPageList2.php');

#Adjust the following parameters according to user permissions:
$wgDPL2AllowedNamespaces = array(

								);
$wgDPL2Options['namespace'] 
$wgDPL2Options['notnamespace'] 
*/

// ReCaptcha plug-in
//  Some customization required below.
//  Depending on your setup, it might be more appropriate to set these keys
//  in your LocalSettings.php
#require('extensions/ReCaptcha/ReCaptcha.php');
#$recaptcha_public_key = '';
#$recaptcha_private_key = '';

// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
//                           TASK SCHEDULING FUNCTIONALITY
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
/*
StubManager::createStub(	'TaskScheduler', 
							$bwExtPath.'/TaskScheduler/TaskScheduler.php',
							$bwExtPath.'/TaskScheduler/TaskScheduler.i18n.php',							
							array( 'SpecialVersionExtensionTypes','ClockTickEvent' ), // created by 'ClockTick' extension
							true // logging included
						 );
*/
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
//                           REPLICATION FUNCTIONALITY
//                             *** EXPERIMENTAL ***
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
# Customize 'clocktick.php' as required
#require('extensions/SimpleReplicator/SimpleReplicator.php');
// Customize the following parameters
#PartnerMachine::$url		= 'http://bizzwiki.org';
#PartnerMachine::$port		= 80; 	//HTTP/TCP port
#PartnerMachine::$timeout	= 15;	//Timeout (in seconds) for request to partner machine

// Enable this extension if you require locally generated 'ClockTickEvent'
// A local 'cron' job must be setup to request a page from the local web server
// with an 'action=ping' command.
#require('extensions/ClockTick/ClockTick.php');

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads     = true;

## This must be set if the 'Updater' extension is to be functional
#$wgFileExtensions[]  = "zip";
$wgFileExtensions[]  = "png";

// Updater extension (ongoing)
#require('extensions/Updater/Updater.php');


// The API functionality requires this function defined in MW1.11
if (!defined('wfScript'))
{
	function wfScript( $script = 'index' ) 
	{
		global $wgScriptPath, $wgScriptExtension;
		return "{$wgScriptPath}/{$script}{$wgScriptExtension}";
	}
}	



  // for web site demo only.
  // %%%%%%%%%%%%%%%%%%%%%%%
if (defined('BIZZWIKIDEMO'))
{
	require('extensions/ReCaptcha/ReCaptcha.php');

	// EXPERIMENTAL!!
	require($bwExtPath.'/rsync/rsync_stub.php');	
	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"LocalSettings.php","!read")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"AdminSettings.php","!read")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"LocalSettings.php","!raw")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"AdminSettings.php","!raw")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"LocalSettings.php","!viewsource")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"AdminSettings.php","!viewsource")] = true;  

	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"~",	"read")]   = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"~",	"browse")] = true; 
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"~",	"viewsource")] = true; 	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"~",	"raw")] = true; 	

	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_DIRECTORY,"~",	"read")]   = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_DIRECTORY,"~",	"browse")] = true; 
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_DIRECTORY,"~",	"viewsource")] = true; 	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_DIRECTORY,"~",	"raw")] = true; 	

	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_BIZZWIKI,	"~",	"read")]   = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_BIZZWIKI,	"~",	"browse")] = true; 
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_BIZZWIKI,	"~",	"viewsource")] = true; 	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_BIZZWIKI,	"~",	"raw")] = true; 	

	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER,		"~/details",	"!read")] = true; 
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER,		"~",			"read")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER,		"~",			"viewsource")] = true;  	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER_TALK,"~",			"read")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER,		"~",			"browse")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER_TALK,"~",			"browse")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,	"Log/~",		"browse")] = true;

 

	$bwAnonymousNamespaces = array( NS_MAIN, NS_TALK,
									NS_PROJECT, NS_PROJECT_TALK,
									NS_TEMPLATE,NS_TEMPLATE_TALK,
									NS_CATEGORY, NS_CATEGORY_TALK,
									NS_HELP, NS_HELP_TALK,
									NS_SPECIAL,
									NS_INTERWIKI // BizzWiki specific
									); 

	foreach( $bwAnonymousNamespaces as $index => $bwx )
	{	
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","read")] = true;   
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","browse")] = true;
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","search")] = true;   		
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","raw")] = true;   
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","viewsource")] = true;
	}
	
}

function bwVarDump( $var ) 
{
	return str_replace("\n","<br />\n", var_export( $var, true ) . "\n");
}
//</source>