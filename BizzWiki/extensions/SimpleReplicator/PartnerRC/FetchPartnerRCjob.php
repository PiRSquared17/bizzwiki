<?php
/*<wikitext>
{| border=1
| <b>File</b> || FetchPartnerRCjob.php
|-
| <b>Revision</b> || $Id$
|-
| <b>Author</b> || Jean-Lou Dupont
|}<br/><br/>

== History ==

== Code ==
</wikitext>*/
require_once('RecentChangesPartnerTable.php');

class FetchPartnerRCjob extends Job
{
	var $user;
	
	function __construct( $title=null, $parameters=null, $id = 0 ) 
	{
		// ( $command, $title, $params = false, $id = 0 )
		parent::__construct( 'fetchRC', Title::newMainPage()/* don't care */, $parameters, $id );
		
		$this->logName  = 'WikiSysop';
	}

	function run() 
	{
		// User under which we will file the log entry
		$this->user = User::newFromName( $this->logName );

		$rct = new RecentChangesPartnerTable();
		$err = $rct->update();

		switch ($err )
		{
			case RecentChangesPartnerTable::errFetchingUrl:
					return $this->errorFetchingList();			
					
			case RecentChangesPartnerTable::errListEmpty:
					return $this->listEmpty();			
					
			case RecentChangesPartnerTable::errParsing:
					$missing_rc_id = $rct->missing_rc_id;
					$duplicate_rc_id = $rct->duplicate_rc_id;
					return $this->errorParsingList( $missing_rc_id, $duplicate_rc_id );
					
			case RecentChangesPartnerTable::errOK:
					break;
		}
		$compte = $rct->compte;
		$filtered_count = $rct->filtered_count;
		$updated_rows = $rct->affected_rows;
		
		if ($rct->almostInSync)
			$state = "'''normal'''";
		if ($rct->catchingUp)
			$state = "'''catching up'''";
		if ($rct->startup)
			$state = "'''startup'''";

		$this->successLog( $compte, $filtered_count, $updated_rows, $state );
		
		return true;
	}
	private function errorFetchingList()
	{
		// add an entry log.
		$this->updateLog( 'fetchfail', 'fetchfail-text1' );
		return false;
	}
	private function errorParsingList( $missing_rc_id, $duplicate_rc_id )
	{
		if ( $missing_rc_id )	$param1 = "Missing 'rc_id'.";
		if ( $duplicate_rc_id )	$param2 = "Duplicate 'rc_id'.";
		// add an entry log.	
		$this->updateLog( 'fetchfail', 'fetchfail-text2', $param1, $param2 );
		return false;		
	}
	private function listEmpty()
	{
		// add an entry log.	
		$this->updateLog( 'fetchok', 'fetchnc-text' );
		return false;		
	}
	/**
		Adds a log entry upon successful operation.
	 */
	private function successLog( $compte, $filtered, $updated, $state )
	{
		// were there any entries made?
		$msg = $compte==0 ? 'fetchnc-text' : 'fetchok-text';
		
		// add an entry log.
		$this->updateLog( 'fetchok', $msg, $compte, $filtered, $updated, $state );
		return true;
	}
	/**
		Actual logging takes place here.
	 */
	private function updateLog( $action, $msgid, $param1=null, $param2=null, $param3=null, $param4=null, $param5=null )
	{
		$message = wfMsgForContent( 'ftchrclog-'.$msgid, $param1, $param2, $param3, $param4, $param5 );
		
		$log = new LogPage( 'ftchrclog' );
		$log->addEntry( $action, $this->user->getUserPage(), $message );
	}
	
} // end class declaration
?>