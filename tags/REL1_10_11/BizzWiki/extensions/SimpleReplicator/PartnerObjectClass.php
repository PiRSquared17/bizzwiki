<?php
/*<wikitext>
{| border=1
| <b>File</b> || PartnerObjectClass.php
|-
| <b>Revision</b> || $Id$
|-
| <b>Author</b> || Jean-Lou Dupont
|}<br/><br/>

== Notes ==
* Make sure that no entries from the 'partner' table get deleted
** RecentChanges
** Loging
* The partner table definition must include a field '*_status'


== Code ==
</wikitext>*/

abstract class PartnerObjectClass extends TableClass
{
	// Partner Machine related
	var $p_url;
	var $p_port;
	var $p_timeout;
	
	// Table Object related
	var $params;
	var $document_tag_field;

	// error codes.
	const errOK			 = 0;
	const errFetchingUrl = 1;
	const errListEmpty   = 2;
	const errParsing     = 3;
	
	// state variables
	var $missing_id;		// only valid if errParsing is returned by update()
	var $duplicate_id;		// only valid if errParsing is returned by update()
	var $filtered_count;	// only valid after 'filterList' method
	var $affected_rows;		// actual number of elements updated in the database
	var $fail_count;		// the number of rows that couldn't be fetched, even after a retry.
	var $compte;			// total number of elements
	var $startup;
	
	var $lowestFetchedId;
	var $highestFetchedId;

	public function __construct( $table_prefix, &$params, $tableFieldName, $indexFieldName, $timestampFieldName, 
								$documentTagField, $currentTimeFieldName ) 
	{ 
		parent::__construct( $table_prefix, $tableFieldName, $indexFieldName, $timestampFieldName, $currentTimeFieldName ); 
		
		$this->p_url	= PartnerMachine::$url;
		$this->p_port	= PartnerMachine::$url;
		$this->p_timeout= PartnerMachine::$timeout;
		
		$this->document_tag_field = $documentTagField;
		$this->params = $params;
		
		// some initialisation
		$this->filtered_count = 0;
		$this->affected_rows = 0;
		$this->fail_count = 0;
		$this->lowestFetchedId = null;
		$this->highestFetchedId = null;
	}
	/**
		Case 1: Startup
				Basically, the local table is empty i.e. 'first hole id == 1'
				==> just fetch a list to kick-start. 
					Depending how 'out of sync' the replicator pair is, this should either
					close the gap (i.e. Almost in Sync) or trigger 'Catching Up'
				
		Case 2: Almost in Sync
				The local replicator is almost in sync with the partner;
				no 'holes' are present. The local replicator just fetches
				updates on a regular basis.
				=> just fetch the 'newest' list from the partner.
				
		Case 3: Catching Up
				The local replicator found 'holes' in the local copy of the partner table.
				Make sure to look for 'valid' holes i.e.
					- statusEmpty
					- statusRetry
				
		
	 */
	public function update( )
	{
		$this->startup		= false;
		$this->almostInSync = false;		
		$this->catchingUp	= false;

		// let's check if there are any 'holes' in the local table.
		$holeid = $this->getFirstHole();
		
		// are we at the beginning of the table?
		// If yes, then we do not have a previous entry on which
		// to base a 'timestamp based retrieval'. Let's just
		// fetch an update from the partner to kick-start things.
		if ($holeid == 1)
		{
			$url = $this->p_url.$this->formatURL( '', null,$this->limit, 'newer' );
			$err = $this->getPartnerList( $url, $document );
			$this->startup = true;
		}
		else
		{
			// We already got a 'first hole'.
			// Now get the timestamp of the preceeding entry
			$bholeid = $this->getIdTsBeforeFirstHole( $holeid, $ts, true );
			// convert timestamp to the API's liking
			// The one returned by the database is in TS_MW format.
			$tsAPI = wfTimestamp(TS_ISO_8601, $ts );
			$url = $this->p_url.$this->formatURL( $tsAPI, null, $this->limit, 'newer' );
			$err = $this->getPartnerList( $url, $document );
		}
		if ($err !== CURLE_OK )
			return PartnerObjectClass::errFetchingUrl;
			
		// At this point, we have a document to parse.
		// RETURN IF THE LIST IS EMPTY OR INVALID
		// so that the rest of the process don't get confused i.e.
		// marking entries for 'retry' where we really can't do this now!
		$plist = $err= $this->parseDocument( $document, $this->params, $this->missing_id, $this->duplicate_id );
		if ($err === false)	return PartnerObjectClass::errParsing;
		if ($err === true)	return PartnerObjectClass::errListEmpty;
		
		// make sure we have the timestamp in the db format.
		$this->adjustCurTime( $plist );
		
		// Now we have a parsed document to process.
		// -----------------------------------------
		$lastid = $this->getLastId( $tsOfLastId );

		// From this point we can determine some status about the entries in the local table i.e.
		//  If we were expecting the fill some 'holes' and we didn't get the appropriate data, 
		//  then we must flags those.
		//  E.g. we were expecting entries starting at [$holeid] with for timestamp [$tsAPI]
		$this->fail_count = $this->processForHoles( $holeid, $plist );
		
		// If the last id recorded in the local table equals
		// that of the 'previous' hole, then we have ~ synchronized situation;
		// filter out all records that fall below $lastid.
		// Make sure we have some records in the db (i.e. $lastid !== null)
		if ( ( $lastid == $bholeid ) && ( $lastid !== null ))
		{
			$this->almostInSync = true;
			$flist = $this->filterList( $plist, $lastid+1, $this->filtered_count );
			
			// update the status field (e.g. rc_status)			
			$this->updateStatus( $flist );
			$this->compte = count( $flist );
			// update the table
			$this->affected_rows = $this->updateList( $flist );
			return PartnerObjectClass::errOK;
		}
		$this->filtered_count = 0;
			
		// At this point, just insert the records we got from the partner.
		// We are not really near 'synchronization': gather up as many records
		// as possible to catch up.
		$this->catchingUp = true;
		$this->compte = count( $plist );
		
		// update the status field (e.g. rc_status)
		$this->updateStatus( $plist );		
		$this->affected_rows = $this->updateList( $plist );
		return PartnerObjectClass::errOK;
		
	}
	/**
		Update the 'status' field of the entries.
		1) We need to get a snapshot of the local partner table 
		   to understand how to update the status of the entries.
		   
		2) Starting @ $holeid, 
		   
		- The missing entries must be created with a status 'statusRetry'
		- The entries with 
		
		IMPORTANT: we can only assess the status of the entries
				   within the bounds of the data-set we get from
				   the partner i.e. between [start id;end id]
				   of the list returned by the partner.
	 */
	private function processForHoles( $holeid, &$lst )	 
	{
		$fail_count = 0;
		
		$current_rows = $this->getIdList( $holeid, 500 );
		if (empty( $current_rows ))		
			return null;
			
		foreach( $current_rows as &$e )
		{
			// is there a corresponding id from the fetched list?
			// If YES, then you have nothing to do: the downstream
			// process will take care of this.
			// BUT if we do not have a corresponding id coming from
			// the partner, let's update the 'status' field.
			$id = $e[$this->indexName];
			
			// we got an entry from the partner: GOOD!
			if ( isset($lst[$id]) )
				continue;
			
			// we do not have an entry from the partner for $id
			// are we still within boundaries?
			// If YES, then update the status field.
			// If NO, then we do not much to do here... for now.
			if ( ( $id < $this->lowestFetchedId ) || ( $id > $this->highestFetchedId ) )
				continue;								
			
			// at this point, we are missing an id from the fetched list
			// AND we the said id is within the boundaries of the fetched list.
			// Let's update the local status of the entry.
			$status = $e[$this->statusName];
			$new_status = null;
			switch( $status )
			{
				case self::statusEmpty:
					$new_status = self::statusRetry;
					break;
				
				// If OK, we are still OK!
				// If fail, still fail.
				case self::statusFail:
				case self::statusOK:
					$new_status = $status;
					break;
					
				case self::statusRetry:
					$new_status = self::statusFail;
					$fail_count++;
					break;

				default:
					throw new MWException( __METHOD__.': received invalid status code.' );
			}//end switch

			$e[$this->statusName] = $new_status;
		
		}//end foreach
		
		return $fail_count;
	}// end method
	
	/**
		Update the 'status' field of each record.
		The status helps the replicator know what to do
		with each row:
		- retry to fetch the record
		- abandon retries
		etc.
		
		The entries we fetched from the partner
		and we are about to commit locally get a 'statusOK' code.
		Make sure not to set the entries already touched by an 
		upstream process!
	 */
	private function updateStatus( &$lst )	
	{
		foreach( $lst as $index => &$e )
			if (!isset( $e[ $this->statusName ] ))
				$e[ $this->statusName ] = self::statusOK;
	}
	/**
		Adjust the 'current timestamp' field of the table, if any.
	 */
	private function adjustCurTime( &$lst )
	{
		if (empty( $this->currentTimestampName ))
			return;
			
		// no need to be that precise in the timestamp
		$cur_time = wfTimestamp( TS_MW );
		foreach( $lst as $index => &$e )
			$e[$this->currentTimestampName] = $cur_time;
	}

	/**
		Filters the list for records falling below $next_expected_id
	 */
	private function filterList( &$lst, $next_expected_id, &$filtered_count )
	{
		$filtered_count = 0;
		
		// Because of a bug in php v5 wrt to arrays passed by reference,
		// we need to make a copy of the records we are going to carry forward.
		$flist = null;
		
		foreach( $lst as $index => $e )
			if ( $next_expected_id > $e[$this->indexName] )
				$filtered_count++;
			else
				$flist[$e[$this->indexName]] = $e;	// copy here

		if (!empty( $flist ))
			ksort( $flist );

		return $flist;	
	}
	/**
		Parse the received XML formatted document.
	 */
	private function parseDocument( &$document, &$paramsList, &$missing_id, &$duplicate_id )
	{
		$missing_id = null;
		$duplicate_id = null;
		
		if (empty( $document ))
			return true;	// the document was empty, hence no problem.
		
		$p = null;
		
		// start by loading the document	
		$x = new DOMDocument();
		@$x->loadXML( $document );

#		var_dump( htmlspecialchars($document) );
#		echo 'tag: '.$this->document_tag_field;
		
		// next, extract the relevant elements
		$llist = $x->getElementsByTagName($this->document_tag_field);

		// place the elements in a PHP friendly array
		foreach( $llist as $e )
		{
			$a = null;
			foreach( $paramsList as $param => $dbkey )
			{
				$value = $e->getAttribute( $param );
				
				// must adjust TIMESTAMP field
				if ( $param == 'timestamp' )
					$value = wfTimestamp( TS_MW, $value );
				$a[ $dbkey ] = $value;
			}
			#wfVarDump( $a );

			// make sure we have an 'id' present
			if (!isset( $a[$this->indexName] ))
				{ $missing_id = true; $p=null; break; }
				
			$id = $a[$this->indexName];
			// now make sure we didn't encounter this 'id' yet in the transaction
			if (isset( $p[$id] ))
				{ $duplicate_id = $id; $p=null; break; }
				
			// everything looks ok... for this row
			$p[ $id ] = $a; 
		}

		// if the document was not empty and we end up
		// with an empty array, something is wrong.
		if ( ($missing_id != null) || ($duplicate_id != null) )
			return false;

#		wfVarDump( $p );

		// document empty? special return code.		
		if (empty( $p ))
			return true;

		// sort the list for convenience
		ksort( $p );

		// get the lowest & highest id's
		reset( $p );
		$this->lowestFetchedId  = key( $p );
		end( $p );
		$this->highestFetchedId = key( $p );

 		return $p;
	}
	/**
		Use the Mediawiki API to retrieve a 'document' from the partner replication node.
	 */
	private function getPartnerList( $url, &$document )
	{
		$ch = curl_init();    									// initialize curl handle

		curl_setopt($ch, CURLOPT_URL, $url);					// set url to post to
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);				// Fail on errors
		#curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   		// allow redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 			// return into a variable
		curl_setopt($ch, CURLOPT_PORT, $this->p_port); 			//Set the port number
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->p_timeout);	// times out after 15s
		#curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		
		$document = curl_exec($ch);
		
		$error = curl_errno($ch);
		curl_close($ch);
		
		// CURLE_OK if everything OK.
		return $error;
	}


} // end class declaration

?>