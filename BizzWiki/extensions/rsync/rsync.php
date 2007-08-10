<?php
/*<!--<wikitext>-->
{{Extension
|name        = rsync
|status      = experimental
|type        = hook
|author      = [[user:jldupont|Jean-Lou Dupont]]
|image       =
|version     = See SVN ($Id$)
|update      =
|mediawiki   = tested on 1.10 but probably works with a earlier versions
|download    = [http://bizzwiki.googlecode.com/svn/trunk/BizzWiki/extensions/rsync/ SVN]
|readme      =
|changelog   =
|description = 
|parameters  =
|rights      =
|example     =
}}
<!--@@
== File Status ==
This section is only valid when viewing the page in a BizzWiki environment.
<code>(($#extractmtime|@@mtime@@$))  (($#extractfile|@@file@@$))</code>

Status: (($#comparemtime|<b>File system copy is newer - [{{fullurl:{{NAMESPACE}}:{{PAGENAME}}|action=reload}} Reload] </b>|Up to date$))
@@-->
== Purpose==


== Features ==
* Page
** Creation
** Update
** Delete
** Move
* User
** Account creation
** Account options update
* File
** Upload
** Re-upload
** Delete
** Move (???)

== Theory Of Operation ==
Page change events are trapped and the resulting new/updated pages are written to a specified filesystem directory.


== Dependancy ==
* [[Extension:StubManager|StubManager extension]]

== Installation ==
To install independantly from BizzWiki:
* Download [[Extension:StubManager]] extension
* Apply the following changes to 'LocalSettings.php'
<source lang=php>
require_once('/extensions/StubManager.php');
require('/extensions/rsync/rsync_stub.php');
</source>

== History ==

== See Also ==
This extension is part of the [[Extension:BizzWiki|BizzWiki Platform]].

== Code ==
<!--</wikitext>--><source lang=php>*/

$wgExtensionCredits[rsync::thisType][] = array( 
	'name'    => rsync::thisName,
	'version' => StubManager::getRevisionId('$Id$'),
	'author'  => 'Jean-Lou Dupont',
	'description' => " ", 
);

class rsync
{
	const thisType = 'other';
	const thisName = 'rsync';
	
	static $directory = '_backup';

	var $rc_timestamp;
	var $rc_id;
	
	// Operations
	var $opList;
	
	// Constants
	const action_none       = 0;
	const action_create     = 1; // TBD
	const action_edit       = 2;
	const action_delete     = 3;
	const action_move       = 4; // TBD
	const action_createfile = 5;
	const action_deletefile = 6;
	const action_editfile   = 7;
	
	/**
	 */
	public function __construct() 
	{
		// we might have more than one operation
		// per transaction i.e. case of 'move' action.
		$this->opList = array();
		
		// format the directory path.
		global $IP;
		$this->dir = $IP.'/'.self::$directory;
	}
	
	/**
		Handles article creation & update
	 */	
	public function hArticleSaveComplete( &$article, &$user, &$text, &$summary, $minor, $dontcare1, $dontcare2, &$flags )
	{
		$title = $article->mTitle; // shortcut
		
		$ns =    $title->getNamespace();
		$titre = $title->getDBkey();
		
		$action = self::action_edit;
		
		$this->addOperation( $action, $ns, $titre );
		
		$this->doOperations();	
	}
	
	/**
		Handles article delete.
	 */
	public function hArticleDeleteComplete( &$article, &$user, $reason )
	{
		
	}
	
	/**
		Handles article move.
		
		This hook is often called twice:
		- Once for the page
		- Once for the 'talk' page corresponding to 'page'
	 */
	public function hSpecialMovepageAfterMove( &$sp, &$oldTitle, &$newTitle )
	{
		// send a 'delete' 
		
		// send a 'update' 
	}
	
	/**
		TBD
	 */
	public function hAddNewAccount( &$user )
	{
		
	}
	
	/**
		File Upload
	 */
	public function hUploadComplete( &$img )
	{
		// make a copy of the uploaded file to the rsync directory.
		
		// what about the meta data of the file???	
	}
	
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%	
	
	/**
		This function packages a 'commit operation' based on the
		current transaction.
		
		The Mediawiki 'WikiExporter' class is used to perform
		most of the work.
	 */
	private function addOperation( &$action, &$ns, &$title, &$history = WikiExporter::CURRENT )
	{
		$this->opList[] = new rsync_operation( $action, $ns, $title, $history );	
	}
	
	/**
		Just grab the essential parameters we need to 
		complete the transaction.
	 */
	public function hRecentChange_save( &$rc )
	{
		$this->rc_timestamp = $rc->mAttribs['rc_timestamp'];
		$this->rc_id        = $rc->mAttribs['rc_id'];		
	}
	private function doOperations()
	{
		// first update the operations list
		// with essential parameters we 'just grabbed'
		// The call to this function also generates the unique filename.
		rsync_operation::updateList( $this->opList, $this->rc_id, $this->rc_timestamp );
		
		if (!empty( $this->opList ))
			foreach( $this->opList as $op )
				$this->export( $op );
	}
	/**
		This function uses MediaWiki's 'WikiExporter' class.
	 */
	private function export( &$op )
	{
		$dump = new DumpFileOutput( $this->dir.'/'.$op->filename );

		#echo __METHOD__."\n";
		#echo 'filename:'.$this->dir.'/'.$op->filename."\n";
		#die();

		$db = wfGetDB( DB_SLAVE );
		$exporter = new WikiExporter( $db, $op->history );
		
		$exporter->setOutputSink( $dump );

		$exporter->openStream();
		
		$title = Title::makeTitle( $op->ns, $op->title );
		if( is_null( $title ) ) return;

		$exporter->pageByTitle( $title );
		
		$exporter->closeStream();
	}
	
} // end class

class rsync_operation
{
	// Commit Operation parameters
	var $id;
	var $action;
	var $ns;
	var $title;
	var $timestamp;
	
	var $filename;
	var $history;		// current or full
	
	var $text;
	
	public function __construct( &$action, &$ns, &$title, &$history )
	{
		$this->action = $action;
		$this->ns = $ns;
		$this->title = $title;
		$this->history = $history;

		// will get filled later.
		$this->id = null;
		$this->timestamp = null;
		
		$this->text = null;			// TBD
		$this->filename = null;		// gets filled during 'updateList'
	}	
	
	public static function updateList( &$liste, &$id, &$ts )
	{
		if (!empty( $liste ))	
			foreach( $liste as $item )
			{
				$item->id = $id;
				$item->timestamp = $ts;
				$item->filename = self::generateFilename( $item );
			}
	}
	
	/**
		rc_id-action-ns-title.xml
	 */
	private static function generateFilename( &$op )
	{
		return "Page-".$op->id.'-'.$op->action.'-'.$op->ns.'-'.$op->title.'.xml';
	}
	
} // end class

//</source>
