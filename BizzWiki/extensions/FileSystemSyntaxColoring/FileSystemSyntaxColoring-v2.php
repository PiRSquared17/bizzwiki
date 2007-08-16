<?php
/*<!--<wikitext>-->
{{Extension
|name        = FileSystemSyntaxColoring
|status      = beta
|type        = hook
|author      = [[user:jldupont|Jean-Lou Dupont]]
|image       =
|version     = See SVN ($Id: FileSystemSyntaxColoring.php 668 2007-08-16 17:27:56Z jeanlou.dupont $)
|update      =
|mediawiki   = tested on 1.10 but probably works with a earlier versions
|download    = [http://bizzwiki.googlecode.com/svn/trunk/BizzWiki/extensions/FileSystemSyntaxColoring/ SVN]
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
This extension 'colors' a page in the NS_FILESYSTEM namespace based on its syntax.

== Features ==
* Can be used independantly of BizzWiki environment 
* No mediawiki installation source level changes
* For parser cache integration outside BizzWiki, use ParserCacheControl extension
* Uses the hook 'SyntaxHighlighting' or defaults to PHP's highlight

== Dependancy ==
* [[Extension:StubManager]]

== Installation ==
To install independantly from BizzWiki:
* Download & Install [[Extension:StubManager]] extension
* Dowload all this extension's files and place in the desired directory
* Apply the following changes to 'LocalSettings.php' after the statements of [[Extension:StubManager]]:
<source lang=php>
require('extensions/FileSystemSyntaxColoring/FileSystemSyntaxColoring_stub.php');
</source>

== History ==
* Added 'wiki text' section support
* Added support for hook based syntax highlighting
* Moved singleton invocation to end of file to accomodate some PHP versions
* Removed dependency on ExtensionClass
* Added stubbing capability through 'StubManager'
* Added namespace trigger
* Added additional checks to speed-up detection of NS_FILESYSTEM namespace
* Added the pattern '<!--@@ wikitext @@-->' to hide wikitext when 'copy and paste' operation is used
to save document in a non-BizzWiki wiki.

== Todo ==
* Handle multiple <!--@@ wikitext @@--> sections

== Code ==
<!--</wikitext>--><source lang=php>*/

$wgExtensionCredits[FileSystemSyntaxColoring::thisType][] = array( 
	'name'    		=> FileSystemSyntaxColoring::thisName, 
	'version'		=> StubManager::getRevisionId( '$Id: FileSystemSyntaxColoring.php 668 2007-08-16 17:27:56Z jeanlou.dupont $' ),
	'author'		=> 'Jean-Lou Dupont', 
	'description'	=>  'Syntax highlights filesystem related pages',
	'url' 			=> StubManager::getFullUrl(__FILE__),			
);

class FileSystemSyntaxColoring
{
	const thisName = 'FileSystem Syntax Coloring';
	const thisType = 'other';  // must use this type in order to display useful info in Special:Version
		
	var $text;
	
	static $patterns = array(
	'/\<\?php/siU'							=> '',
	'/\/\*\<\!\-\-\<wikitext\>\-\-\>/siU'	=> '',
	'/\/*\<\!\-\-\<(.?)wikitext\>\-\->/siU'	=> '',
	'/\/\/\<(.?)source\>/siU' 				=> '<$1source>',
	'/\<source(.*)\>\*\//siU'				=> '<source $1>',
	);
	
	public function __construct() 
	{
		$this->text  = null;
		$this->found = false;
	}
	
	public function hArticleAfterFetchContent( &$article, &$content )
	{
		// we are only interested in page views.
		global $action;
		if ($action != 'view') return true;

		// first round of checks
		if (!$this->isFileSystem( $article )) return true; // continue hook-chain
		
		// grab the content for later inspection.
		$this->text = $article->mContent;
		
		return true;
	}

	public function hParserBeforeStrip( &$parser, &$text, &$mStripState )
	// wfRunHooks( 'ParserBeforeStrip', array( &$this, &$text, &$this->mStripState ) );
	{
		// first round of checks
		if (!$this->isFileSystem( $parser )) return true; // continue hook-chain
		
		// since the parser is called multiple times, 
		// we need to make sure we are dealing the with article per-se
		if (strcmp( $this->text, $text)!=0 ) return true;
		
		// Check for a <wikitext> section
		$text = $this->cleanCode( $text );
		
		return true;		
	}
	
	private function isFileSystem( &$obj )
	{
		// is the namespace defined at all??
		if (!defined('NS_FILESYSTEM')) return false;
		
		$ns = null;
		
		if ( $obj instanceof Parser )
			$ns = $obj->mTitle->getNamespace();

		if ( $obj instanceof Article )
			$ns = $obj->mTitle->getNamespace();

		if ( $obj instanceof Title )
			$ns = $obj->getNamespace();
		
		// is the current article in the right namespace??		
		return (NS_FILESYSTEM == $ns)? true:false;
	}

	public function cleanCode( &$text )
	{
		foreach( self::$patterns as $pattern => $replacement )	
		{
			$r = preg_match_all( $pattern, $text, $m );
			if ( ( $r === false ) || ( $r ===0 ) )
				continue;
				
			foreach( $m[0] as $index => $c_match )
			{
				$rep = str_replace('$1', $m[1][$index], $replacement );
				$text = str_replace( $c_match, $rep, $text );
			}
		}

		return $text;
	}
	
} // end class definition.

//</source>