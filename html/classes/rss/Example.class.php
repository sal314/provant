<?php
require_once($_SERVER['DOCUMENT_ROOT']."/include/configuration.php");
require_once(LIB_ROOT."classes/common/Database.class.php");
require_once(LIB_ROOT."classes/datastruct/RssElement.class.php");

class Example{	
	private $dbObj;
	public function __construct(){
		$this->dbObj=Database::create();
	}
	
	public function populate(){
		
		//get the next 25 games
        $sql ="SELECT * FROM schedule 
               JOIN teams on teams.id=schedule.vs 
        	   WHERE home=9 LIMIT 0,25";				
		$results=$this->dbObj->query($sql);
		
		$elements=null;
		if($results){
			foreach($results as $record){
				$rec=array(); 
				$rec['link']="http://".$_SERVER['HTTP_HOST']."/";	//link to page it can be found at (the home page)
				$rec['title'] = "Game # ".htmlspecialchars(htmlentities($record['game_number']));//remove any html 
				$rec['description']="Vs. ".$record['city']." ".$record['name'];											
				$rec['author']="Buffer Overflows";
				$rec['guid']=$record['id'];	   //the guid is supposed to be a unique id for the article in the channel
				$elements[]= new RSSElement($rec);	//create the element from the array.							
			}
		}
		//the feed generator will use this array to populate the feed.		
		return $elements;
	}
	
}
