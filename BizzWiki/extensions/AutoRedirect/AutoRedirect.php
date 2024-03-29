<?php
/*<!--<wikitext>-->
{{Extension
|name        = AutoRedirect
|status      = beta
|type        = parser
|author      = [[user:jldupont|Jean-Lou Dupont]]
|image       =
|version     = See SVN ($Id$)
|update      =
|mediawiki   = tested on 1.10 but probably works with a earlier versions
|download    = [http://bizzwiki.googlecode.com/svn/trunk/BizzWiki/extensions/XYZ/ SVN]
|readme      =
|changelog   =
|description = 
|parameters  =
|rights      =
|example     =
}}
<!--@@
{{#autoredirect: {{NAMESPACE}}|{{#noext:{{SUBPAGENAME}} }} }}
== File Status ==
This section is only valid when viewing the page in a BizzWiki environment.
<code>(($#extractmtime|@@mtime@@$))  (($#extractfile|@@file@@$))</code>

Status: (($#comparemtime|<b>File system copy is newer - [{{fullurl:{{NAMESPACE}}:{{PAGENAME}}|action=reload}} Reload] </b>|Up to date$))
@@-->
== Purpose==
Provides a magic word to automatically create a redirect page to the current page.

== Usage ==
<code>{{#autoredirect:namespace|page name [|alternateText] }}</code> creates a the specified page as a redirect
to the current page i.e. the one containing the magic word. When the parameter 'alternateText' is present,
it is used as indicator to create a link on the current with alternate text 'alternateText';
E.g. [[current page|alternateText]]

== Dependancy ==
* [[Extension:StubManager|StubManager extension]]

== Installation ==
To install independantly from BizzWiki:
* Download & Install [[Extension:StubManager]] extension
* Dowload all this extension's files and place in the desired directory
* Apply the following changes to 'LocalSettings.php' after the statements of [[Extension:StubManager]]:
<source lang=php>
require('extensions/AutoRedirect/AutoRedirect_stub.php');
</source>

== History ==

== Todo ==
* Prevent showing the newly create redirect page - stay on the current page.

== See Also ==
This extension is part of the [[Extension:BizzWiki|BizzWiki Platform]].

== Code ==
<!--</wikitext>--><source lang=php>*/

$wgExtensionCredits[AutoRedirect::thisType][] = array( 
	'name'    => AutoRedirect::thisName,
	'version' => StubManager::getRevisionId('$Id$'),
	'author'  => 'Jean-Lou Dupont',
	'description' => "Provides a magic word to automatically create redirect pages", 
	'url'		=> 'http://mediawiki.org/wiki/Extension:AutoRedirect',
);

class AutoRedirect
{
	const thisType = 'other';
	const thisName = 'AutoRedirect';
	
	public static $msg = array();
	
	public function __construct() 
	{
		global $wgMessageCache;
		foreach( self::$msg as $key => $value )
			$wgMessageCache->addMessages( self::$msg[$key], $key );		
	}
	
	public function mg_autoredirect( &$parser, &$ns = null, &$page = null, &$alternateText = null )
	{
		// if ns contains a numeric
		if (is_numeric( $ns ))
		{
			$name = Namespace::getCanonicalName( $ns );
			if (empty( $name ))
				return wfMsgForContent('autotedirect-invalid-namespace-numeric');
		}		
		else
		{
			if ( ($n = Namespace::getCanonicalIndex( strtolower($ns) )) === NULL)	
				return wfMsgForContent('autoredirect-invalid-namespace-string');				
			$ns = $n;
		}
	
		// if the source page already exists, bail out silently.
		$title   = Title::makeTitle( $ns, $page );
		$article = new Article( $title );
		if ( $article->getID() !=0 )
			return null;
			
		// the source page where the redirect should be created
		// does not exist currently. Great.
		$link = $this->createRedirectPage( $parser, $article, $alternateText );	
		
		if (!empty( $alternateText ))
			return $link;
			
		return null;
	}
	
	private function createRedirectPage( &$parser, &$article, &$alternateText )
	{
		$ns   = $parser->mTitle->getNamespace();
		$page = $parser->mTitle->getText();
		
		$nsName = Namespace::getCanonicalName( $ns );
		
		if (empty( $alternateText ))
			$text = wfMsgForContent('autoredirect-this-page');
		else
			$text = $alternateText;
			
		$link = wfMsgForContent('autoredirect-link-text', $nsName, $page, $text);
		$pageText = wfMsgForContent( 'autoredirect-page-text', $nsName, $page );
		$summary  = wfMsgForContent( 'autoredirect-summary-text', $nsName, $page, $text );
		$article->insertNewArticle( $pageText, $summary, false, false );

		return $link;	
	}
	
} // end class

require('AutoRedirect.i18n.php');
//</source>
