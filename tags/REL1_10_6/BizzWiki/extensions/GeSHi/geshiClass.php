<?php
/*<wikitext>
</wikitext>*/
class geshiClass extends ExtensionClass
{
	// constants.
	const thisName = 'geshiClass';
	const thisType = 'other';
	const id       = '$Id$';	
		  
	public static function &singleton()
	{ return parent::singleton( );	}
	
	function geshiClass( $mgwords = null, $passingStyle = self::mw_style, $depth = 1 )
	{
		parent::__construct( );

		global $wgExtensionCredits;
		$wgExtensionCredits[self::thisType][] = array( 
			'name'        => self::thisName, 
			'version'     => self::getRevisionId( self::id ),
			'author'      => 'Jean-Lou Dupont', 
			'description' => 'Handles generic syntax highlighting',
			'url' => self::getFullUrl(__FILE__),			
		);
	}
	public function setup() 
	{ 
		parent::setup();
			
		global $wgParser;
		$wgParser->setHook( 'geshi',  array( $this, 'execute' ) );
		$wgParser->setHook( 'source', array( $this, 'execute' ) );  // align with some other extensions
																	// providing the ~same functionality
		$wgParser->setHook( 'php', array( $this, 'executePHP' ) );
	}
	public function executePHP( &$text, &$argv, &$parser )
	{
		$this->extractArgs( $argv, $lang, $lines, $source );
		return $this->executeMain( $text, 'php', $lines, $source );	
	}
	public function execute( &$text, &$argv, &$parser )
	{
		$this->extractArgs( $argv, $lang, $lines, $source );
		return $this->executeMain( $text, $lang, $lines, $source );	
	}
	public function extractArgs( &$argv, &$lang, &$lines, &$source )
	{
		if (isset( $argv['lang'] ))  $lang = $argv['lang'];
		if (isset( $argv['lines']) ) $lines = $argv['lines'];
		if (isset( $argv['source'])) $source = $argv['source']; 
	}
	public function executeMain( &$text, $lang, $lines, $source )
	{
		switch( $source )
		{
			case 'page':
			    $title   = Title::newFromText( $text );
			    $article = new Article( $title );
				$text = $article->getContent();
				break;
				
			case 'file':				
				$text = $this->getFileText( $text, $result );
				if ( !$result )  
					return $text;
				break;
		}
		
		return $this->highlight( $text, $lang, $lines );			
	}
	private function highlight( &$text, $lang='php', $lines=false )
	{
        $geshi = new GeSHi($text, $lang );	

        $geshi->enable_classes(); 
        $geshi->set_header_type(GESHI_HEADER_PRE); 
        $geshi->set_overall_class("code"); 
        $geshi->set_encoding("utf-8"); 

		if (($lines == true) or ($lines==1) or ($lines=='1'))
			$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
		else
			$geshi->enable_line_numbers(GESHI_NO_LINE_NUMBERS);
		
        return "<style>".$geshi->get_stylesheet()."</style>".$geshi->parse_code();        
	}

	private function getFileText( $file_name, &$result )
	{
		global $IP;
		
		# Reference home of wiki installation
		$file_name=$IP."/".$file_name;
		
		$result = false;
		
		if (strtolower(basename($file_name))=="localsettings.php")
			return "The file <i>LocalSettings.php</i> can not be highlighted due to security issue.";
		if (strtolower(basename($file_name))=="adminsettings.php")
			return "The file <i>AdminSettings.php</i> can not be highlighted due to security issue.";
	
		
	        //Process the file
	        if (is_readable($file_name)) 
			{          
                $handle = fopen($file_name, "r");
                $contents = fread($handle, filesize($file_name));
                fclose($handle);
				$result = true;
	        } 
			else
                $contents = "File not Found! ($file_name)";

		return $contents;
	}

	public function hSyntaxHighlight( &$text, $lang, $lines, &$result )
	{
		$result = $this->highlight( $text, $lang, $lines );

		return true; // be nice with other possible extensions.
	}
	
} // END CLASS DEFINITION

?>