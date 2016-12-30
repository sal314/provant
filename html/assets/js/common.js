$(function() {
	$("tbody tr:odd").addClass('odd');
	
	$(".interactive tbody tr").hover(
		function () {
			$(this).addClass("hover");
		},
		function () {
			$(this).removeClass("hover");
	});
	
	$(".call-log tbody tr").hover(
		function () {
			$(this).addClass("hover");
		},
		function () {
			$(this).removeClass("hover");
	});
	// AW: Control clicking of a container based on a link
	
	$(".click").live('mouseover mouseout', function(event) {
		if (event.type == 'mouseover') {
			$(this).addClass("hover");
		} else {
			$(this).removeClass("hover");
		}
	});

	$(".click").live('click', function() {
		window.location = $("a:first", this).attr("href");
		
		return false;
	});
		
	$("form .nextstep").click(function() {
		var ret = true;
		var frm = $(this).closest("form")[0];
		try {
			if (document.all) {                                    //For IE
				for (var i = 0; i < frm.attributes.length; i++) {    //Search thru form attributes
					if (frm.attributes[i].name == 'onsubmit') {        //If it's the 'onsubmit' attribute
						var func = frm.attributes[i].value;              //Get the string from the HTML form
						if (func != 'null') {
							func = func.replace ("return ", "");           //Strip the return if it's there
							func = func.replace ("this", "frm");           //Replace the 'this' with this form reference
							ret = eval(func);                              //Execute the onsubmit function
						}
						break;
					}
				}
			}
			else {
				if(frm.onsubmit) {
					ret = frm.onsubmit($(this).closest("form")[0]);
				}
			}
			if(ret) {
				$(this).closest("form")[0].submit();
			}
		} catch(e) {}
		
		return false;
	});
	
	$("form .login").click(function() {
		$(this).closest("form")[0].submit();
	});
	try {
		$("a.tt[title]").colorTip({color:'white'}); 
	} catch(e){}
});

/**
 * @author aware
 * @param percent : Number (this setting fills the
 * @notes This plugin will set the fill percentage and label for
 *		  a progress bar.
 */

(function($) {

	$.fn.progress = function(settings) {
	var config = {
				  'percent':0,
				  'totalSteps':0,
				  'currentStep':0
				  };
	
	if (settings) $.extend(config, settings);
	
	this.each(function() {
		
		var fillPercent = 0;
		
		if(settings.percent) {
			fillPercent = config.percent + '%';
			$('.percent span', this).text(fillPercent);
		}

		if(config.totalSteps != 0 && config.currentStep != 0) {
			fillPercent = Math.ceil((config.currentStep / config.totalSteps) * 100) + '%';
			
			$('.percent span', this).text('Step ' + config.currentStep + '/' + config.totalSteps);
		}
		
		$('.percent', this).width(fillPercent);
	});
	
	return this;

};

})(jQuery);