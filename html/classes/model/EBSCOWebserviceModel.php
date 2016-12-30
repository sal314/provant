<?php

define("LIBRARYWS","http://webservices.epnet.com/healthinfo/library.wsdl");
define("HGWS","http://webservices.epnet.com/hgwebservice/HGWebservice.asmx?WSDL");
define("LWS","http://webservices.epnet.com/healthinfo/library.wsdl");
define("COLLECTIONSWS","http://webservices.epnet.com/hgcollection.asmx?WSDL");
define("CHUNKWS","http://webservices.epnet.com/hgchunk.asmx?WSDL");
define("HISTORYWS","http://webservices.epnet.com/hgonsitewebservice/GetHistory.asmx?WSDL");
define("TOKEN","180acf8a-4bd9-47bc-aeca-c5201a7468a0");

/**
 * This class is used to make call to EBSCO via a soap webservice call and return the requested html document
 * @author ejoyce
 *
 */
class EBSCOWebserviceModel extends SoapClient{
	
	/**
	 * Translate the xml chunk into a an html document
	 * @param string $XMLTxt
	 * @returns string html chunk
	 */
	public function translate($XMLTxt){
		$XML = new DOMDocument();
		$XML->loadXML( $XMLTxt); 
		$xslt = new XSLTProcessor();
		$XSL = new DOMDocument();
		$XSL->load( ROOT_DIR.'assets/xsl/HG-WS.xsl', LIBXML_NOCDATA);
		$xslt->importStylesheet( $XSL );
		return $xslt->transformToXML( $XML ); 
	}
	
	/**
	 * Translate an XML document into an html chunck 
	 * @param DomDocument $XMLDoc
	 * @returns string html chunk
	 */
	public function translateDoc($XMLDoc){		
		$xslt = new XSLTProcessor();
		$XSL = new DOMDocument();
		$XSL->load( ROOT_DIR.'assets/xsl/HG-WS.xsl', LIBXML_NOCDATA);
		$xslt->importStylesheet( $XSL );
		return $xslt->transformToXML( $XMLDoc ); 
	}
	
	/**
	 * Translate ConditionInjuryList page into a html document	 
	 * @param DomDocument $XMLDoc
	 * @returns string html chunk
	 * */
	public function translateConditionInjuryList($XMLTxt){
		$XML = new DOMDocument();
		
		$XML->loadXML( $XMLTxt);
		 
		$xslt = new XSLTProcessor();
		$XSL = new DOMDocument();
		$XSL->load( ROOT_DIR.'assets/xsl/HG-WS-ConditionInjuryList.xsl', LIBXML_NOCDATA);		
		$xslt->importStylesheet( $XSL );
		#PRINT
		return $xslt->transformToXML( $XML ); 
	}
	
}

?>
