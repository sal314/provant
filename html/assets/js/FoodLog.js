//
// FoodLog.js
//
//	Scripts for the FoodLog application
//

var busy = false;
var factories = [
         		function() { return new XMLHttpRequest(); },
         		function() { return new ActiveXObject("Msxml2.XMLHTTP"); },
         		function() { return new ActiveXObject("Microsoft.XMLHTTP") }
         	];

var factory = null;
var table = null;
var div = null;

$(function() {

});

function getNewRequest() {
	if (factory != null) {
		return factory();
	}

	for (var i = 0; i < factories.length; i++) {
		try {
			var fac = factories[i];
			var request = fac();
			if (request != null) {
				factory = fac;
				return request;
			}
		}
		catch(e) {
			continue;
		}
	}

	factory = function () { throw new Error("XMLHttpRequest is not supported by this browser"); }
	factory();
}


function handleSearchBox(e, url) {
	if (busy) {
		return false;
	}

	var searchTxt = document.getElementById('search');
	if (searchTxt.value.length >= 3) {
		busy = true;
		var rqst = getNewRequest();
		rqst.onreadystatechange = function() {
				if (rqst.readyState == 4) {
					busy = false;
					if (rqst.status == 200) {
						var fl = $('#FoodList').html('');			
						// div = document.getElementById('FoodList');
						var xmldoc = rqst.responseXML.documentElement;
					
						fl.remove('#foodTable');
					
						var table_html = '<div id="foodTable" style="overflow:auto"><table><thead><tr><th>Matching Foods:</th></tr></thead><tbody>';

						var data;
						var messages;
						for (var i = 0; i < xmldoc.childNodes.length; i++) {
							if (xmldoc.childNodes[i].tagName == "messages") {
								messages = ZMEDIA.AJAX.parseMessageNodes(xmldoc.childNodes[i]);
							}
							else if (xmldoc.childNodes[i].tagName == "data") {
								data = ZMEDIA.AJAX.parseDataNodes(xmldoc.childNodes[i]);
							}
						}
						if (messages[0].name == "Error") {
							table_html += "<tr><td>Error searching for foods:</td></tr></tbody></table>";

							fl.append(table_html);
						} else {
												
							for (var i = 0; i < data.length; i++) {
								if (data[i].name == "values") {
									var values = data[i].value;
								
									try {

										for (var j = 0; j < values.length; j++) {

											var val = values[j].display;
											var arg = values[j].value;
											var food = values[j].selected;
											var serv = values[j].servingSize;
											var unit = values[j].UnitID;
											var cust = values[j].custom;
										
											table_html += '<tr><td><a href="#" onclick="selectFood(\'' + arg + '\',\'' + serv + '\',\'' + unit + '\',\'' + cust + '\'); return false;">' + val + '</a></td></tr>';
										
											// var tr = document.createElement('tr');
											// var td = document.createElement('td');
											// var a = document.createElement('a');
										
											// a.href = "javascript:selectFood("+arg+","+serv+","+unit+","+cust+");";
											// a.appendChild(document.createTextNode(val));
											// table.appendChild(a);
											// tr.appendChild(td);
											//tbl.appendChild(tr);
										}
									
										table_html += "</tbody></table>";
									
								
									} catch (e) {
										table_html += "<tr><td>Error searching for foods:</td></tr></tbody></table>";
									}
								
									fl.append(table_html);

								}
							}
						}
					}
					else {
						alert ("Failed: " + rqst.status);
					}
				}
		}
		rqst.open("POST", url, true);
		rqst.setRequestHeader("User-Agent", "XMLHttpRequest");
		rqst.setRequestHeader("Accept-Language", "en");
		rqst.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		var param = "value=" + encodeURIComponent(searchTxt.value).replace("/%20/g","+");
		rqst.send(param);
	}
	else {
		$('#foodList').remove('#foodTable');
		return false;
	}
}


//*********
// Changes to the following two routines to change the meal selection from
// a list of <a> tags to a <select> drop down.  The commented out with "//**" is
// what used to be here.  Those lines with that comment at the end are the new ones.
//*********

function selectFood(id,serv,unid,cust) {

	var searchTxt = $('#search');
		searchTxt.val('');

	var hid = $('#foodID');
		hid.val(id);
		hid = $('#servingSize');
		hid.val(serv);
		hid = $('#UnitID');
		hid.val(unid);
		hid = $('#custom');
		hid.val(cust);

		var fl = $('#FoodList').html('');
//**			fl.append('<div id="mealList"><h3>Select which meal:</h3></div>');
		fl.append('<div id="mealList"></div>');                                  //**
		$("#mealList").append('<select id="mealdrop" onchange="setMeal();"><option value="0">- Select a meal -</option>');   //**

	for (var i = 1;; i++) {
		var mid = document.getElementById('meal' + i);
		if (mid == undefined) {
			break;
		}
		var mn = $('#mname' + i);
			
//**		$("#mealList").append('<a href="#" onclick="setMeal(' + mid.value + ');">' + mn.val() + '</a><br />');
		$("#mealdrop").append('<option value="' + mid.value + '">' + mn.val() + '</option>');  //**
	}

	var div = document.getElementById('FLMeals');
	div.style.display = 'block';

	//document.body.appendChild(pop);
}

//**function setMeal(id) {
function setMeal() {
	var sm = document.getElementById('selected_meal');
  var drop = document.getElementById('mealdrop');                          //**
  var id = drop.value;                                             //**
	sm.value = id;

	var frm = document.forms[0];
	frm.submit();
}

function checkEntry() {
	var fid = document.getElementById('foodID');
	if (fid > 0) {
		return true;
	}
	else {
		return false;
	}
}

function UpdQty(id) {
	var qty = document.getElementById('qty'+id);
	var post = document.getElementById('qty');
	post.value = qty.value;
	var fid = document.getElementById('foodID');
	fid.value = id;
	
	var frm = document.forms[0];
	frm.action = "/FoodLog/UpdateFoodLog";
	frm.submit();
}

function DelFood(id) {
	var fid = document.getElementById('foodID');
	fid.value = id;

	var frm = document.forms[0];
	frm.action = "/FoodLog/DeleteFoodLog";
	frm.submit();
}

//
// Functions for the custom food section
//
function GetClasses(e, url) {
	var major = document.getElementById('major_class');
	if (major.value == "") {
		return;
	}
	var minor = document.getElementById('food_class');
	var sz = minor.options.length;
	for(var i = 1; i < sz; i++) {
		minor.options[1] = null;
	}

	var rqst = getNewRequest();
	rqst.open("POST", url, false);
	rqst.setRequestHeader("User-Agent", "XMLHttpRequest");
	rqst.setRequestHeader("Accept-Language", "en");
	rqst.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var param = "value=" + encodeURIComponent(major.value).replace("/%20/g","+");
	rqst.send(param);
	if (rqst.status == 200) {
		var xmldoc = rqst.responseXML.documentElement;
		var messages;
		for (var i = 0; i < xmldoc.childNodes.length; i++) {
			if (xmldoc.childNodes[i].tagName == "messages") {
				messages = ZMEDIA.AJAX.parseMessageNodes(xmldoc.childNodes[i]);
			}
			else if (xmldoc.childNodes[i].tagName == "data") {
				data = ZMEDIA.AJAX.parseDataNodes(xmldoc.childNodes[i]);
			}
		}
		for (i = 0; i < data.length; i++) {
			if (data[i].name == "values") {
				var values = data[i].value;
				for (var j = 0; j < values.length; j++) {
					var opt = new Option(values[j].display,
					                     values[j].value,
					                     false,false);
					minor.options[j+1] = opt;
				}
			}
		}
	}
	else {
		alert ('Error from AJAX = ' + rqst.status);
	}
}



	