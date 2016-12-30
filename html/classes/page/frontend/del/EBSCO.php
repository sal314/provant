<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/EBSCOWebserviceModel.php");

class EBSCO  extends PageBase{
	//Search Page
	public function Index($param){
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}

		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		return $template;
	}
	
	/**
	 * Get the ConditionInjuryList Page 
	 * @param char $param 1st letter of term
	 * @returns template instance
	 */
	public function ConditionInjuryList($param){
	 	$id="33341#";//.$param[0];
	 	$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		$soapRequest->ChunkIID=$id;

		try{
			$soapClient=new EBSCOWebserviceModel(HGWS);
			$ws = $soapClient->GetHgXML($soapRequest);			
			$html=$soapClient->translateConditionInjuryList($ws->GetHgXMLResult->any,$param[0]);
						
		}//catch (SoapFault $e) {
		catch (Exception $e) {
			throw $e;
		}        

		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		$template->addVar("html",$html);
		return $template;
		
	}
	
	/**
	 * Get EBSO landing page 
	 * @param unknown_type $param
	 * @returns template instance
	 */
	public function GetEntryPoint($param){
		$id=isset($param[0])?$param[0]:null;
		$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		$soapRequest->ChunkIID=$id;

		try{
			$soapClient=new EBSCOWebserviceModel(HGWS);
			$ws = $soapClient->GetHgEntryPoint($soapRequest);
			$html=$soapClient->translate($ws->GetHgEntryPointResult->any);			
		}//catch (SoapFault $e) {
		catch (Exception $e) {
			throw $e;
		}        

		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		$template->addVar("html",$html);
		return $template;		
	}
/**
	 * Get EBSO landing page 
	 * @param unknown_type $param
	 * @returns template instance
	 */
	public function GetEntryPoint2($param){
		//$id=isset($param[0])?$param[0]:null;
		$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		//$soapRequest->ChunkIID=$id;

		try{
			$soapClient=new EBSCOWebserviceModel(HGWS);
			$ws = $soapClient->GetContent($soapRequest);
			$html=$soapClient->translate($ws->GetHgEntryPointResult->any);			
		}//catch (SoapFault $e) {
		catch (Exception $e) {
			throw $e;
		}        

		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		$template->addVar("html",$html);
		return $template;		
	}
	
	/**
	 * Search databases for key word, paginate results
	 * @param unknown_type $param
	 * @returns template instance
	 */
	public function search($param){
		$query=isset($_GET['query'])?$_GET['query']:null;
		$page=isset($_GET['page'])?$_GET['page']:1;
		$collections=isset($_GET['collections'])?$_GET['collections']:1;
		
		$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		$soapRequest->SearchTerm=$query;
		$soapRequest->NumHits=10;		
		$soapRequest->SetNumber=($page*$soapRequest->NumHits)+1;
		
		$soapRequest->CollectionIIDsToSearch="2 578 722";
		try{
			$soapClient=new EBSCOWebserviceModel(LIBRARYWS);
			$ws = $soapClient->GetSearchResults($soapRequest);		
			
			$XMLDoc = new DOMDocument();
			$XMLDoc->loadXML( $ws->GetSearchResultsResult->any);
			
			$results=$XMLDoc->getElementsByTagName("results");
			$resultItem=$results->item(0); 
			
			if($resultItem->hasAttributes()){
				$attribs=$resultItem->attributes;
				$first=$attribs->getNamedItem("first")->textContent;
				$last=$attribs->getNamedItem("last")->textContent;
				$total=$attribs->getNamedItem("total")->textContent;									
			}else{
				$first=$last=$total=0;
			}
			$last=$total_pages=floor($total/$soapRequest->NumHits);					
									
			$saveStart=$page;
			$over=null;
		
			$start=$page;
			if($start+$soapRequest->NumHits>$last){
				$over=($start+$soapRequest->NumHits)-$last-1;
				$start=$start-$over;
				if($start<1) $start=1;
				$end=$last;
			}else{
				$end=$start+$soapRequest->NumHits-1;
			}

			$pager=array();
			$pager['current_page']=$saveStart;
			$pager['first_page']=1;
			if($over>1){
				if($saveStart-1>0){ //dont' show if no previous page exists.
					$pager['previous_page']=$saveStart-1;
				}				
			}	
			for($x=$start;$x<=$end;$x++){
		   		$pager['page'.($x-$start+1)]=$x;
			}
			if($saveStart<$last){ //only show if the current page is not the last page
				$pager['next_page']=$saveStart+1;
			}					
			$pager['last_page']=$last;
		
			$html=$soapClient->translateDoc($XMLDoc);
			
		}//catch (SoapFault $e) {
		catch (Exception $e) {
			throw $e;
		}        
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		$template->addVar("results",true);
		$template->addVar("GET",$_GET);
		$template->addVar("pager",$pager);
		$template->addVar("html",$html);
		return $template;
	}
	
	
	
	/**
	 * Get an article from a search result by its ChunkIId
	 * @param string  $param  article ChunkIID
	 * @returns template instance
	 */
	
	public function GetResultArticle($param){
		$id=isset($param[0])?$param[0]:null;
		$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		$soapRequest->ChunkIID=$id;

		try{
			$soapClient=new EBSCOWebserviceModel(LIBRARYWS);
			$ws = $soapClient->GetArticle($soapRequest);
			$html=$soapClient->translate($ws->GetArticleResult->any);			
		}//catch (SoapFault $e) {
		catch (Exception $e) {
			throw $e;
		}        
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		$template->addVar("html",$html);
		return $template;
	}
	
	/**
	 * Get list of all collections Provant has subscribed to at EBSCO
	 * @param unknown_type $param
	 * @returns template instance
	 */
	
	public function getAllCoreCollections($param){		
		$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		try{
			$soapClient=new EBSCOWebserviceModel(COLLECTIONSWS);
			$ws = $soapClient->GetAllCoreCollections($soapRequest);	
			$html=$soapClient->translate($ws->GetAllCoreCollectionsResult->any);				
		}//catch (SoapFault $e) {
		catch (Exception $e) {
			throw $e;
		}        
		//print_r($ws);
		//print_r($html);
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		$template->addVar("html",$html);
		return $template;		
	}	
	
	/**
	 * Get an article by its ChunkIId
	 * @param string  $param  article ChunkIID
	 * @returns template instance
	 */
	public function GetArticle($param){
		$id=isset($param[0])?$param[0]:null;
		$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		$soapRequest->ChunkIID=$id;

		try{
			$soapClient=new EBSCOWebserviceModel(HGWS);
			$ws = $soapClient->GetHgXML($soapRequest);
			$html=$soapClient->translate($ws->GetHgXMLResult->any);			
		}//catch (SoapFault $e) {
		catch (Exception $e) {
			throw $e;
		}        
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		$template->addVar("html",$html);
		return $template;
	}
	
	/**
	 * Alias to the GetAtricle method 
	 * @deprecated
	 * @param string $param CinkIId
	 */
	public function getChunk($param){
		return $this->GetArticle($param);
	}	
}