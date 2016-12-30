//
// CallLog.js
//

var list = null;
var factories = [
         		function() { return new XMLHttpRequest(); },
         		function() { return new ActiveXObject("Msxml2.XMLHTTP"); },
         		function() { return new ActiveXObject("Microsoft.XMLHTTP") }
         	];

var factory = null;

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


function getCompanyUsers(e, url) {
	var div = document.getElementById('UserList');
	var cid = document.getElementById('company_id');
	if (cid.value == "") {
		if (list) {
			div.removeChild(list);
			list = null;
		}
		return false;
	}
	var rqst = getNewRequest();
	rqst.open("POST", url, false);
	rqst.setRequestHeader("User-Agent", "XMLHttpRequest");
	rqst.setRequestHeader("Accept-Language", "en");
	rqst.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var param = "value=" + encodeURIComponent(cid.value).replace("/%20/g","+");
	rqst.send(param);
	if (rqst.status == 200) {
		var xmldoc = rqst.responseXML.documentElement;
		if (document.getElementById('UserTable')) {
			div.removeChild(document.getElementById('UserTable'));
		}
		list = document.createElement('div');
		list.id = 'UserTable';
		list.style.height = "500px";
		list.style.width = "15%";
		list.style.overflow = "scroll";
		var table = document.createElement('table');
		var hd = document.createElement('tr');
		var htd = document.createElement('td');
		htd.appendChild(document.createTextNode('Users:'));
		hd.appendChild(htd);
		table.appendChild(hd);
		list.appendChild(table);
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
		for (i = 0; i < data.length; i++) {
			if (data[i].name == "values") {
				var values = data[i].value;
				for (var j = 0; j < values.length; j++) {
					var disp = values[j].display;
					var val = values[j].value;
					var tr = document.createElement('tr');
					var td = document.createElement('td');
					var a = document.createElement('a');
					a.href = "javascript:selectUser("+val+");";
					a.appendChild(document.createTextNode(disp));
					td.appendChild(a);
					tr.appendChild(td);
					table.appendChild(tr);
				}
				div.appendChild(list);
			}
		}
	}
	else {
		alert ('Error requesting the user list');
	}
}

function selectUser(id) {
	var uid = document.getElementById('user_id');
	uid.value = id;
	var frm = document.forms[0];
	frm.submit();
}
