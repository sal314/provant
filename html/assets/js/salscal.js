//
// JavaScript to test out a new method of 'modal'
// Not really 'modal'.  It's just a div that gets
// built on the fly and then torn down.
//
//	WARNING.  In IE (6 & 7 ?) , if there are active-x controls in the
//	same space as this popup, they will appear on top of
//	the popup.  Workaround is to create a blank image
//	and set it as the backgound image of the main <div>
//	in a 'tiled' fashion.  Make sure the opacity is 100%.
//

dateDiv = null;			//The 'popup' <div> that holds it all
dateCal = null;			//The <div> that contains the calendar table
dateCtrl = null;		//The <div> that contains the calendar controls
dateSel = null;			//The <td> that holds the selected date (colored background)
dateYear = 0;			//The selected year
dateMonth = 0;			//The selected month (zero based)
dateDay = 0;			//The selected day
dateLimit = '';			//Selection limitations ('past', 'future', default='all')
dateLoc = null;			//Location to go when <update> button is clicked
dateVars = null;		//Name of target variables
dateYears = 0;			//Number of years in the dropdown
dateHiLite = "red";	//Highlight color

//
// Start here.  This creates the popup.
//
function showCalendarDialog(event, w, h, limit, loc, yrs, vars) {

	//
	// Get where the <click> happened and add some padding
	//
	e = event || window.event;

	var x = e.clientX + 10;
	var y = e.clientY + 10;
	if (window.innerWidth) {
		x = x + window.pageXOffset;
		y = y + window.pageYOffset;
	}
	else if (document.documentElement && document.documentElement.clientWidth) {
		x = x + document.documentElement.scrollLeft;
		y = y + document.documentElement.scrollTop;
	}
	else if (document.body.clientWidth) {
		x = x + document.body.scrollLeft;
		y = y + document.body.scrollTop;
	}
	else {
		x = 300;
		y = 500;
	}


	//	Only allow one popup at a time
	if (dateDiv) return;

	// Optional page (URL) to jump when date is selected
	if (loc) {
		dateLoc = loc;
	}

	// Optional years
	if (yrs) {
		dateYears = yrs;
	}
	else {
		dateYears = 1;
	}

	// Optional target variable name(s)
	if (vars) {
		dateVars = vars;
	}
	else {
		dateVars = 'date_entered';
	}

	// Limit selectable dates (past, future, or all)
	if (!limit) {
		limit = "all";
	}
	dateLimit = limit;

	// Use the highlight color
	var hi = document.getElementById('hilightColor');
	dateHiLite = hi.value;
	
	
	// The main <div>
	dateDiv = document.createElement("div");
	dateDiv.className = 'salcal';
	dateDiv.style.position = "absolute";
	dateDiv.style.backgroundColor = "#FFF";
	dateDiv.style.left = x+"px";
	dateDiv.style.top = y+"px";
	dateDiv.style.width = w+"px";
	//dateDiv.style.height = h+"px";
	dateDiv.style.border = "2px solid #000";
	dateDiv.id = "modal";
	//
	// Start out with today's date
	//
	var today=new Date();
	var cyear, cmonth, cday;
	if (!dateYear) {
		dateYear = today.getYear();
		if (dateYear < 1000) {
			dateYear += 1900;
		}
		dateMonth = today.getMonth();
		dateDay = today.getDate();
	}
	cyear = dateYear;
	cmonth = dateMonth;
	cday = dateDay;

	//
	// Build the calendar
	//
	build(cyear, cmonth, cday);

	var clearDiv = document.createElement("div");
	clearDiv.className = 'clear';
	dateDiv.appendChild(clearDiv);
	
	document.body.appendChild(dateDiv);
}

//
// Builds a calendar for the specified year, month, day
//
function build (cyear, cmonth, cday) {
	var firstDay=new Date(cyear,cmonth,1).getDay();
	var setDay=new Date(cyear,cmonth,cday);

	var daysinmon = new Array(31,28,31,30,31,30,31,31,30,31,30,31);
	var months = new Array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

	var days = daysinmon[cmonth];
	if (cmonth == 1) {
		if ((cyear % 4) == 0) {
			if ((cyear % 100) == 0) {
				if ((cyear % 400) == 0) {
					days++;
				}
			}
			else {
				days++;
			}
		}
	}

	var t = new Date();
	var y = t.getYear();
	if (y < 1000) {
		y += 1900;
	}
	var m = t.getMonth();
	if (m < 10) {
		m = "0" + m;
	}
	var d = t.getDate();
	if (d < 10) {
		d = "0" + d;
	}
	var today = y + "-" + m + "-" + d;

	y = cyear;
	if (y < 1000) {
		y += 1900;
	}
	m = cmonth;
	if (m < 10) {
		m = "0" + m;
	}
	var compdate = y + '-' + m + '-';
	var compday;

	var count=1;
	var idx=0;
	var table,thead,tbody,th,td,a,txt;

	if (dateCal) {
		dateDiv.removeChild(document.getElementById('calendar'));
		dateDiv.removeChild(document.getElementById('crtlBox'));
		dateDiv.removeChild(document.getElementById('can'));
		dateDiv.removeChild(document.getElementById('upd'));
		dateCal = null;
		dateCtrl = null;
	}
	var crtl = document.createElement("div");
/*	crtl.style.position = "absolute";
	crtl.style.left = "5px";
	crtl.style.top = "5px";
	crtl.style.height = "40px;"*/
	dateDiv.appendChild(crtl);
	crtl.id = "crtlBox";
	crtl.className="ctls";
	dateCtrl = crtl;

	var box = document.createElement("div");
/*	box.style.position = "absolute";
	box.style.left = "5px";
	box.style.top = "30px";*/
	box.id = "calendar";
	dateDiv.appendChild(box);
	dateCal = box;

	//dropdown box
	var calmon = document.createElement("div");
	calmon.style.position = "absolute";
	calmon.style.left = "30px";
	calmon.style.top = "8px";
	calmon.style.width = "130px";

	var sel = document.createElement("select")
	sel.onchange = updateCal;
	sel.id = 'select_month';
	calmon.appendChild(sel);
	crtl.appendChild(calmon);

	var my = months[cmonth] + ' ' + cyear;
	var strtmon = t.getMonth();
	var strtyr = t.getYear();
	if ((dateLimit == "past") || (dateLimit == "all")) {
		strtyr -= dateYears;
	}
	var n = (dateYears * 12) + 1;
	if (dateLimit == "all") {
		n = (dateYears * 24) + 1;
	}
	if (strtyr < 1000) {
		strtyr += 1900;
	}
	var start = months[strtmon] + ' ' + strtyr;
	for(var x = 0; x < n; x++){
		var dstr = months[strtmon] + ' ' + strtyr;
		sel.options.add(new Option(dstr, dstr));
		if(dstr == my) {
			sel.selectedIndex = x;
		}
		strtmon += 1;
		if (strtmon > 11) {
			strtmon = 0;
			strtyr += 1;
		}
	}
	var end = dstr;

	var la = document.createElement("div");
	la.style.position = "absolute";
	la.style.left = "10px";
	la.style.top = "10px";
	la.style.width = "10px";
	if (my == start) {
		la.appendChild(document.createTextNode(" "));
	}
	else {
		a = document.createElement("A");
		a.className = "arrow";
		a.href="javascript:lastMonth("+cmonth+",1,"+cyear+");";
//		a.appendChild(document.createTextNode("<"));
		a.style.marginRight="7px";
		var limg = document.createElement("img");
		limg.src = "/assets/media/images/cal_left_arrow.png";
		a.appendChild(limg);
		la.appendChild(a);
	}
	crtl.appendChild(la);

/*
	var txt = document.createElement("input");
	txt.value = cyear;
	txt.id = "current_year";
	txt.size = 4;			
	txt.onkeyup = updateCalYear;
	txt.style.marginLeft="7px";
	crtl.appendChild(txt);
*/

	var ra = document.createElement("div");
	ra.style.position = "absolute";
	ra.style.left = "175px";
	ra.style.top = "10px";
	ra.style.width = "10px";
	if (my == end) {
		ra.appendChild(document.createTextNode(" "));
	}
	else {
		a=document.createElement("A");
		a.className="arrow";
		a.href="javascript:nextMonth("+cmonth+",1,"+cyear+")";
//		a.appendChild(document.createTextNode(">"));
		a.style.marginLeft="7px";
		var rimg = document.createElement("img");
		rimg.src = "/assets/media/images/cal_right_arrow.png";
		a.appendChild(rimg);
		ra.appendChild(a);
	}
	crtl.appendChild(ra);

/*	var br = document.createElement("br");
	crtl.appendChild(br);

	var title = document.createElement("div");
	var str = document.createTextNode(months[cmonth] + ' ' + cyear);
	title.appendChild(str);
	title.style.marginTop = "10px";
	title.style.marginLeft = "auto";
	title.style.marginRight = "auto";
	title.style.textAlign = "center";
	title.style.backgroundColor = "#ace";
	crtl.appendChild(title);
*/
	table = document.createElement("TABLE");
	table.border = 1;
	//table.style.margin = "0px";
	table.className = 'calTable';
	box.appendChild(table);

	thead = document.createElement("THEAD");
	thead.className="calHead";
	table.appendChild(thead);
//	table.style.width = "250px";

		tr = document.createElement("TR");
		thead.appendChild(tr);
			
			td = document.createElement("TD");
			//td.style.width = "14.29%";
			tr.appendChild(td);
			td.appendChild(document.createTextNode("Sun"));

			td = document.createElement("TD");
			//td.style.width = "14.29%";
			tr.appendChild(td);
			td.appendChild(document.createTextNode("Mon"));

			td = document.createElement("TD");
			//td.style.width = "14.29%";
			tr.appendChild(td);
			td.appendChild(document.createTextNode("Tue"));

			td = document.createElement("TD");
			//td.style.width = "14.29%";
			tr.appendChild(td);
			td.appendChild(document.createTextNode("Wed"));

			td = document.createElement("TD");
			//td.style.width = "14.29%";
			tr.appendChild(td);		
			td.appendChild(document.createTextNode("Thu"));

			td = document.createElement("TD");
			//td.style.width = "14.29%";
			tr.appendChild(td);
			td.appendChild(document.createTextNode(" Fri  "));

			td = document.createElement("TD");
			//td.style.width = "14.29%";
			tr.appendChild(td);
			td.appendChild(document.createTextNode("Sat"));
			
	tbody = document.createElement("TBODY");
	tbody.className = 'calBody';
	table.appendChild(tbody);

		tr = document.createElement("TR");
		tbody.appendChild(tr);

		for(idx = 0; idx < firstDay; idx++){
			td = document.createElement("TD");
			tr.appendChild(td);
			td.appendChild(document.createTextNode(String.fromCharCode(160)));
		}

		for (idx = firstDay; idx <= 6; idx++){
			td = document.createElement("TD");
			tr.appendChild(td);
			if (count < 10) {
				compday = "0" + count;
			}
			else {
				compday = count;
			}

			if ((dateLimit == "all") ||
				((dateLimit == "past") && (today >= compdate+compday)) ||
				((dateLimit == "future") && (today <= compdatae+compday))) {

				a=document.createElement("A");
				td.appendChild(a);
					a.href="javascript:void(0)";
//					a.onclick=function(event) {setDate(event, cmonth, cyear);};
					ob = new Object;
					ob.month = cmonth;
					ob.year = cyear;
					attachEvent(a, setDate, ob, 'click');

					a.appendChild(document.createTextNode(count));
					if(count == cday){
						dateSel = td;
						td.style.backgroundColor = dateHiLite;
					}
			}
			else {
				td.appendChild(document.createTextNode(count));
			}
			count++;
		}
			
		tr = document.createElement("TR");
		tbody.appendChild(tr);

		while (count <= days) {
			for (idx = 0; idx < 7; idx++) {
				if (count > days) {
					td = document.createElement("TD");
					tr.appendChild(td);
					td.appendChild(document.createTextNode(String.fromCharCode(160)));
				}
				else {
					td = document.createElement("TD");
						tr.appendChild(td);
					if (count < 10) {
						compday = "0" + count;
					}
					else {
						compday = count;
					}

					if ((dateLimit == "all") ||
						((dateLimit == "past") && (today >= compdate+compday)) ||
						((dateLimit == "future") && (today <= compdate+compday))) {

						a = document.createElement("A");
						td.appendChild(a);
							a.href = "javascript:void(0)";
//							a.onclick = function(event) {setDate(event, cmonth, cyear);};
					ob = new Object;
					ob.month = cmonth;
					ob.year = cyear;
					attachEvent(a, setDate, ob, 'click');
							a.appendChild(document.createTextNode(count));
						if (count == cday){
							dateSel = td;
							td.style.backgroundColor = dateHiLite;
						}
					}
					else {
						td.appendChild(document.createTextNode(count));
					}
				}
				count++;
			}

			tr = document.createElement("TR");
			tbody.appendChild(tr);								
		}

/*
** commented out this extra row
**
		if (tbody.rows.length < 7){
			tr = document.createElement("TR");
			for (var x = 0; x < 7; x++) {
				td = document.createElement("td");
				td.appendChild(document.createTextNode(String.fromCharCode(160)));
				tr.appendChild(td);
			}
			tbody.appendChild(tr);
		}
*/

		//txt.focus();

		//
		// The close button
		//
		var cancel = document.createElement("a");
		cancel.className = 'ctrl_button';
		//cancel.type="button";
		cancel.id="can";
		cancel.href="javascript:void(0)";
//		cancel.onclick=rm;
		attachEvent(cancel, rm, null, 'click');
		//cancel.style.position = "absolute";
		//cancel.style.left = "5px";
		//cancel.style.top = "230px";
		var closeimg = document.createElement("img");
		closeimg.src = "/assets/media/images/calendar_close.png";
		cancel.appendChild(closeimg);

		//
		// The update button
		//
		var upd = document.createElement("a");
		upd.className = 'ctrl_button';
		//upd.type="button";
		upd.id="upd";
		upd.href="javascript:void(0)";
//		upd.onclick=update;
		attachEvent(upd, update, null, 'click');
		//upd.style.position = "absolute";
		//upd.style.right = "5px";
		//upd.style.top = "230px";
		var updateimg = document.createElement("img");
		updateimg.src = "/assets/media/images/calendar_update.png";
		upd.appendChild(updateimg);
		


		//
		// The reset button
		//
	/*
		var rset = document.createElement("input");
		rset.className = 'ctrl_button';
		rset.type="button";
		rset.id="rset";
//		rset.onclick=reset;
		attachEvent(rset, reset, null, 'click');
		rset.value="Reset";
		rset.style.position = "absolute";
		rset.style.left = (w / 2) - 27 + "px";
		rset.style.top = "250px";
	*/

		//
		// Attach the buttons to the <div>
		// and attache the <div> to the document body
		//
		dateDiv.appendChild(cancel);
		dateDiv.appendChild(upd);
		//dateDiv.appendChild(rset);
}

//
//removed the popup
//
function rm() {
	document.body.removeChild(document.getElementById("modal"));
	dateDiv = null;
	dateCal = null;
	dateCtrl = null;
	dateSel = null;
	dateLimit = "";
}

//
//update the date to the selected date
//
function update() {
	var spn = document.getElementById(dateVars+'_disp');
	spn.innerText = getDateString();
	var hid = document.getElementById(dateVars);
	hid.value = getDateValue();
	rm();
	if (dateLoc != null) {
		var frm = document.forms[0];
		frm.action = dateLoc;
		dateLoc = null;
		frm.submit();
	}
}

//
//reset the date to today's date
//
function reset() {
	var today = new Date();
	dateYear = today.getYear()
	if (dateYear < 1000) {
		dateYear += 1900;
	}
	dateMonth = today.getMonth();
	dateDay = today.getDate();

	var spn = document.getElementById(dateVars+'_disp');
	spn.innerText = getDateString();
	var hid = document.getElementById(dateVars);
	hid.value = getDateValue();
	rm();
}

//
//Format the stored date into a string (mmm dd, yyyy)
//
function getDateString() {
	var y = dateYear;
	var m = dateMonth;
	var d = dateDay;

	m++;
	if (m < 10) {
		m = "0" + m;
	}
	if (d < 10) {
		d = "0" + d;
	}
	var dte = new Date(m+"/"+d+"/"+y);
	var str = dte.toDateString();				//Format: weekday month day year
	var arr = str.split(" ");
	if (arr[2] < 10) {									//Some browsers return "01" for day
		if (arr[2].length > 1) {
			arr[2] = arr[2].substr(1,1);
		}
	}
	return arr[1] + " " + arr[2] + ", " + arr[3];
}

//
//Formate the stored date into a string (yyyy-mm-dd)
//
function getDateValue() {
	var y = dateYear;
	var m = dateMonth;
	var d = dateDay;

	m++;
	if (m < 10) {
		m = "0" + m;
	}
	if (d < 10) {
		d = "0" + d;
	}

	return y + "-" + m + "-" + d;
}
//
//Set the date if a number in the table was clicked
//
function setDate (e, dobj) {
	var t = null;
	if (document.all) {
		e = window.event;
		t = e.srcElement;
	}
	else {
		t = e.target;
	}

	var p = t.parentElement;
	var d = t.childNodes[0].nodeValue;

	dateMonth = dobj.month;
	dateDay = d;
	dateYear = dobj.year;

	if (dateSel) {
		if (!document.all) {
			dateSel.style.backgroundColor = null;
		}
		else {
			dateSel.style.removeAttribute("backgroundColor");
		}
	}
	p.style.backgroundColor = dateHiLite;
	dateSel = p;
}


//
// Function to decrement the month.  Attached to the "<" anchor
//
function lastMonth(m, d, y) {

	if (m == 0) {
		dateMonth = 11;
		dateYear = y - 1;
	}
	else {
		dateMonth = m - 1;
		dateYear = y;
	}

	dateDay = d;

	build(dateYear, dateMonth, dateDay);
}

//
// Function to increment the month.  Attached to the ">" anchor
//
function nextMonth(m, d, y) {

	if (m == 11) {
		dateMonth = 0;
		dateYear = y + 1;
	}
	else {
		dateMonth = m + 1;
		dateYear = y;
	}

	dateDay = 1;
	build (dateYear, dateMonth, dateDay);
}

//
// Function to change the month.  Attached to the month select box (onchange)
//
function updateCal() {
	var months = new Array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

	var select_month = document.getElementById("select_month");
	var my = new String(select_month.options[select_month.selectedIndex].value);
	var dte = my.split(/\s+/);
	dateYear = dte[1];
	for (var i = 0; i < 12; i++) {
		if (dte[0] == months[i]) {
			dateMonth = i;
			break;
		}
	}
	dateDay = 1;
	build(dateYear, dateMonth, 1);
}

//
// Function to change the year.  Attached to the year text box (onkeyup)
//
/*
function updateCalYear(e) {
  	if (!e) { e=window.event; }
  	if (e.type=="keyup") {
  		if (e.keyCode && e.keyCode != 13) {
  			if (e.target.value.length < 4){
  				return true ;
  			}
  		}
  		if (e.keyCode == 0){ return true;}
  	}
	var year = document.getElementById("current_year");
	if (isNaN(year.value) || year.value < 1){
		alert("Year is invalid. Please Correct.");
		year.focus();
		return;
	}
	var select_month = document.getElementById("select_month");
	var month = select_month.options[select_month.selectedIndex].value;

	dateYear = year.value;
	dateMonth = month;
	dateDay = 1;
	build(year.value, month, 1);
}
*/

//
// Function to attach an event to a function
//
function attachEvent (obj, func, param, evt) {
	function local (e) {
		return func(e, param);
	}

	if (obj.addEventListener) {
		obj.addEventListener(evt, local, false);
	}
	else {
		obj.attachEvent("on"+evt, local);
	}
}
