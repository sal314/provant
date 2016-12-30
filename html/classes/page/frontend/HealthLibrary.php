<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/EBSCOWebserviceModel.php");

class HealthLibrary  extends PageBase{
	//Search Page
	public function __construct(){
  		parent::__construct();
  		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}
		//load the ebsco.css for all requests
		$h=HeaderInclude::getInstance();
		$h->addTag("link","rel='stylesheet' type='text/css' media='screen' href='/assets/css/ebsco.css'");
  	}
	public function Index($param){
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
		$html=$this->GetEntryPoint($param,false);
		$template->addVar("html",$html);
		return $template;
	}

	/**
	 * Get the ConditionInjuryList Page
	 * @param char $param 1st letter of term
	 * @returns template instance
	 */
	public function ConditionInjuryList($param){
		$name=(isset($param[0]))?$param[0]:"A";

	 	$id="33341#".$name;
	 	$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		$soapRequest->ChunkIID=$id;

		try{
			$soapClient=new EBSCOWebserviceModel(HGWS);
			$ws = $soapClient->GetHgXML($soapRequest);
			//$html=$soapClient->translateConditionInjuryList($ws->GetHgXMLResult->any,$name);
			$html=$soapClient->translate($ws->GetHgXMLResult->any,$name);

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
	public function GetEntryPoint($param,$loadTemplate=true){
		$id=isset($param[0])?$param[0]:null;
		$soapRequest=new stdClass();
		$soapRequest->Token=TOKEN;
		$soapRequest->ChunkIID=$id;

		try{
			$soapClient=new EBSCOWebserviceModel(HGWS);
			$ws = $soapClient->GetHgEntryPoint($soapRequest);
			$html=$soapClient->translate($ws->GetHgEntryPointResult->any);
//			$html = $this->parseContent($html);
		}//catch (SoapFault $e) {
		catch (Exception $e) {
			throw $e;
		}

		if($loadTemplate){
			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/EBSCO/search.tpt");
			$template->addVar("html",$html);
			return $template;
		}
		return 	$html;
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
		$soapRequest->SetNumber=(($page-1)*$soapRequest->NumHits)+1;

		$soapRequest->CollectionIIDsToSearch="2 578 722";
		if($query){
			try{
				$soapClient=new EBSCOWebserviceModel(LIBRARYWS);

				$ws = $soapClient->GetSearchResults($soapRequest);

				$XMLDoc = new DOMDocument();
				$XMLDoc->loadXML( $ws->GetSearchResultsResult->any);

				$results=$XMLDoc->getElementsByTagName("results");
				$resultItem=$results->item(0);


				if($resultItem && $resultItem->hasAttributes()){
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
		}else{
			$pager=null;
			$html="";
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
			$txt=$ws->GetArticleResult->any;

			$html=$soapClient->translate($txt);

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
			print_r($ws);die();
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

	/**
	 * parseContent()
	 *	@param - $recd - html returned from EBSCO
	 *	returns the same html but formatted differently
	 *
	 *	This function is for the inital load of the EBSCO page.  It contains a list
	 *	of links and the format should have been a list of link and possible lists
	 *	or sub-lists.  This code keys in on the dash (-) as a delimiter between the
	 *	main list and the sub-list.
	 *
	 *		**********************************************
	 *		*                                            *
	 *		* THIS IS PRONE TO BREAKAGE IF EBSCO CHANGES *
	 *		* THE FORMAT OR ORDER OF THEIR LIST.         *
	 *		*                                            *
	 *		**********************************************
	 */
	private function parseContent($recd) {

		$link = array();
		$href = array();
		$off = strpos($recd, '<div class="ListHeader">');

		$front = substr($recd, 0, strpos($recd, '<ul>', $off));

		$header = false;
		$head = "";
		while ($pos = strpos($recd, '<a class="link"', $off)) {
			$off = $pos + 1;
			$txt = strpos($recd, '>', $pos) + 1;
			$size = $txt - $pos;
			$hr = substr($recd, $pos, $size);
			$eot = strpos($recd, '<', $txt);
			$size = $eot - $txt;
			$text = substr($recd, $txt, $size);
			$dash = strpos($text, " - ");

			if ($dash) {
				if ($header) {
					if ($head == substr($text, 0, $dash)) {
						array_push ($link[$head], substr($text, $dash+3));
						array_push ($href[$head], $hr);
					}
					else {
						$head = substr($text, 0, $dash);
						$link[$head] = array(substr($text, $dash+3));
						$href[$head] = array($hr);
					}
				}
				else {
					$header = true;
					$head = substr($text, 0, $dash);
					$link[$head] = array(substr($text, $dash+3));
					$href[$head] = array($hr);
				}
			}
			else {
				$header = false;
				$link[$text] = array($text);
				$href[$text] = array($hr);
			}
		}

		$bpos = strpos($recd, '</ul>', $off);
		$back = substr($recd, $bpos+5);


		$retHTML = $front . "\n";
		$retHTML .= "<ul>\n";
		foreach ($link as $l => $v) {

			if (count($v) > 1) {
				$retHTML .= "<li><span style=\"font-size:14px; font-weight:bold\">" . $l . "</span></li>\n<li><ul>\n";
				for ($i = 0; $i < count($v); $i++) {

					$retHTML .= "<li style=\"margin-left:40px\">" . $href[$l][$i] . $link[$l][$i] . "</a></li>\n";
				}
				$retHTML .= "</ul></li>\n";
			}
			else {
				if ($l != $v[0]) {

					$retHTML .= "<li><span style=\"font-size:14px; font-weight:bold\">" . $l . "</span></li>\n<li><ul>\n<li style=\"margin-left:40px\">" . $href[$l][0] . $v[0] . "</a></li>\n</ul></li>\n";
				}
				else {
					$retHTML .= "<li>" . $href[$l][0] . $link[$l][0] . "</a></li>\n";
				}
			}
		}

		$retHTML .= "</ul>\n" . $back;
		return $retHTML;
	}
}