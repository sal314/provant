function validateiFocus(f){
	try {
 	  var currentRadioGroup="";
 	  var success=true;
 		for(var x=0;x<f.elements.length;x++){
 			if(f.elements[x].type=="radio"){
 			  if(currentRadioGroup!=f.elements[x].name){
 			  	if(currentRadioGroup){ 			  		
 			  		success=false;
 			  		var tmp = document.getElementById("q"+currentRadioGroup);
 			  		if (tmp != null) {
	 			  		document.getElementById("q"+currentRadioGroup).className=" missingResponse ";
	 			  	}
 			  	}
 			  	currentRadioGroup=f.elements[x].name;
 			  }
 			  
 			  if(f.elements[x].checked){
 			  	var crg = document.getElementById("q"+currentRadioGroup);
 			  	if (crg != null) {
	 			  	var cn=document.getElementById("q"+currentRadioGroup).className;
	 			  	document.getElementById("q"+currentRadioGroup).className=cn.replace("missingResponse","");
	 			  	while(f.elements[x] && f.elements[x].name==currentRadioGroup){
 				  		x++;
 				  	}
 				  	currentRadioGroup=null;
	 			  	x--;
 				  }
 				}
 			}else if(f.elements[x].type=="hidden"){
 				if(f.elements[x].name=="q11") continue;//Anual Exam date does not need to be answered
 				if(f.elements[x].name=="q14") continue;//Papsmear date date does not need to be answered
 			}
 		}
 		if(currentRadioGroup){ 			  		
 			success=false;
 			tmp = document.getElementById("q"+currentRadioGroup);
 			if (tmp != null) {
	 			document.getElementById("q"+currentRadioGroup).className=" missingResponse";
	 		}
 		}
 		if(!success) alert("Please answer the question highlighted in red"); 
 		return success;
	} catch (e) {
		return false;
	}
 }

function showWin(pUrl,pWidth,pHeight){
	if(!pWidth) pWidth="680px";
	if(!pHeight) pHeight="635px";
    var props={
    	'width':pWidth,
    	'className':'lw_window',
    	titlebar:{
    		'height':'35px',
    		'className':'lw_titlebar',   
    		'button':{     	        
    			'src':"/assets/media/close.jpg",
    			'className':'lw_close_btn_img',
    				'text':{
					'value':'',
					'location':'before',
					'className':'lw_close_btn_text'
				}					
    	    },
    	    'title':{
    	    	'content':'',
    	    	'className':'lw_titlebar_text'
    	   	}
    	 },
    	window:{
    		'height':pHeight,
    		'className':'lw_content',
    		'iframe':true,
    		'url':pUrl,
    		'content':null
    	}        
    }
    ZMEDIA.LightBox.openWindow(props);      
}
