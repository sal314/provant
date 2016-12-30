//
// ExPlan.js
//


//
// All the different ways to allocate a new AJAX request
//
var factories = [
         		function() { return new XMLHttpRequest(); },
         		function() { return new ActiveXObject("Msxml2.XMLHTTP"); },
         		function() { return new ActiveXObject("Microsoft.XMLHTTP") }
         	];

//
// The AJAX request allocation method that works is saved here
//
var factory = null;

//
// The busy flag
//
var busy = false;

//
// Get a new AJAX request object
//
function newRequest() {
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


//
// Attach an event to an object
//
function makeEvent(ele, func, param, ev) {
	function local() {
		return func(param);
	}

	if (ele.addEventListener) {
		ele.addEventListener(ev, local, false);
	}
	else {
		ele.attachEvent("on"+ev, local);
	}
}


//
// Retrieve (via AJAX) and display the details of a workout plan
//
function selectPlan(planID) {
	var rqst = newRequest();
	rqst.open("POST", "/WorkoutPlan/GetPlan", false);
	rqst.setRequestHeader("User-Agent", "XMLHttpRequest");
	rqst.setRequestHeader("Accept-Language", "en");
	rqst.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var param = "value=" + encodeURIComponent(planID).replace("/%20/g","+");
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
				var plan = data[i].value;
				var br;

				var mDiv = document.getElementById('srcPlan');
				if (mDiv != null) {
					var div = document.getElementById('CannedPlan');
					if (div != null) {
						mDiv.removeChild(div);
					}
				}
				cont = document.createElement('div');
				cont.id = "CannedPlan";
				tabs = document.createElement('div');
				tabs.className = "planTab";
				tabs.paddingTop = "2px";
				var nm = document.createElement('h3');
				nm.id = "srcPlanName";
				nm.innerText = plan.PlanName;
				cont.appendChild(nm);
				
				var days = plan.days;
				var astyle = "0 -56px";
				for (var d = 0; d < days.length; d++) {
					var pd = days[d];
					var tabdiv = document.createElement('div');
					if (document.all) {
						tabdiv.style.styleFloat = "left";
					}
					else {
						tabdiv.style.cssFloat = "left";
					}
					tabdiv.id = "PDay"+pd.day+"_tab";

					var taba = document.createElement('a')
					taba.href = "javascript:void(0)";
					taba.style.background = "url(/assets/media/images/meal-tab.png) 0 0 no-repeat";
					taba.style.width = "55px";
					taba.style.height = "28px";
					taba.style.display = "block";
					taba.style.textAlign = "center";
					taba.style.marginRight = "2px";
					taba.style.backgroundPosition = astyle;
					makeEvent(taba, displayPlanEx, pd.day, 'click');

					var aspn = document.createElement('span');
					aspn.className = "dayName";
					aspn.innerText = "Day " + pd.day;
					taba.appendChild(aspn)
					tabdiv.appendChild(taba);
					tabs.appendChild(tabdiv);

					astyle = "0 0";
				}
				cont.appendChild(tabs);

				div = document.createElement('div');
				div.className = "clear";
				cont.appendChild(div);

				div = document.createElement('div');
				div.id = "PlanContents";
				div.className = "border";
				br = document.createElement('br');
				div.appendChild(br);


				var display = "block";
				for (var d = 0; d < days.length; d++) {
					var day = days[d];

					var plandiv = document.createElement('div');
					plandiv.id = "PDay"+day.day+"_content";
					plandiv.style.display = display;

					var a = document.createElement('a');
					a.href = "javascript:void(0);";
					makeEvent(a, highlightSource, day.day, 'click');

					var spn = document.createElement('span');
					spn.id = "srcDayName"+day.day;
					spn.className = "dayName";
					spn.innerText = "Day " + day.day;
					a.appendChild(spn);
					plandiv.appendChild(a);

					var inp = document.createElement('input');
					inp.type = "hidden";
					inp.id = "srcDayHidden"+day.day;
					inp.value = day.day;
					plandiv.appendChild(inp);

					var cc = document.createElement('div');
					cc.className = "mealborder";

					var tbl = document.createElement('table');
					tbl.border = "0";
					
					var ex = day.ex;
					for (var e = 0; e < ex.length; e++) {
						var exercise = ex[e];
						var eidx = e + 1;

						var tr = document.createElement('tr');
						var td = document.createElement('td');

						var tgt = "td";
						if (exercise.description.length > 0) {
							a = document.createElement('a');
							a.href = "javascript:showWin('/WorkoutPlan/ExerciseDescription/" + exercise.id + "', '650px')";
							tgt = "a";
						}
						spn = document.createElement('span');
						spn.id = "txt_"+day.day+"_"+eidx;
						spn.className = "exerciseName";
						spn.innerText = exercise.name;
						if (tgt == 'a') {
							a.appendChild(spn);
							td.appendChild(a);
						}
						else {
							td.appendChild(spn);
						}
						tr.appendChild(td);

						if (exercise.sets > 0) {
							td = document.createElement('td');
							spn = document.createElement('span');
							spn.id = "sets_"+day.day+"_"+eidx;
							spn.className = "exerciseName";
							spn.innerText = "Sets: " + exercise.sets;
							td.appendChild(spn);
							tr.appendChild(td);

							td = document.createElement('td');
							spn = document.createElement('span');
							spn.id = "reps_"+day.day+"_"+eidx;
							spn.className = "exerciseName";
							spn.innerText = "Reps: " + exercise.reps;
							td.appendChild(spn);
							tr.appendChild(td);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srcSets"+day.day+"_"+eidx;
							inp.id = "srcSets"+day.day+"_"+eidx;
							inp.value = exercise.sets;
							cc.appendChild(inp);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srcReps"+day.day+"_"+eidx;
							inp.id = "srcReps"+day.day+"_"+eidx;
							inp.value = exercise.reps;
							cc.appendChild(inp);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srcDuration"+day.day+"_"+eidx;
							inp.id = "srcDuration"+day.day+"_"+eidx;
							inp.value = 0;
							cc.appendChild(inp);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srcMETs"+day.day+"_"+eidx;
							inp.id = "srcMETs"+day.day+"_"+eidx;
							inp.value = 0;
							cc.appendChild(inp);

						}
						else if (exercise.duration > 0) {
							td = document.createElement('td');
							spn = document.createElement('span');
							spn.id = "dur_"+day.day+"_"+eidx;
							spn.className = "exerciseName";
							spn.innerText = "Duration: " + exercise.duration;
							td.appendChild(spn);
							tr.appendChild(td);

//							td = document.createElement('td');
//							spn = document.createElement('span');
//							spn.id = "METs_"+day.day+"_"+eidx;
//							spn.className = "exerciseName";
//							spn.innerText = "METs: " + exercise.METs;
//							td.appendChild(spn);
//							tr.appendChild(td);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srcSets"+day.day+"_"+eidx;
							inp.id = "srcSets"+day.day+"_"+eidx;
							inp.value = 0;
							cc.appendChild(inp);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srcReps"+day.day+"_"+eidx;
							inp.id = "srcReps"+day.day+"_"+eidx;
							inp.value = 0;
							cc.appendChild(inp);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srcDuration"+day.day+"_"+eidx;
							inp.id = "srcDuration"+day.day+"_"+eidx;
							inp.value = exercise.duration;
							cc.appendChild(inp);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srcMETs"+day.day+"_"+eidx;
							inp.id = "srcMETs"+day.day+"_"+eidx;
							inp.value = exercise.METs;
							cc.appendChild(inp);
						}
						tbl.appendChild(tr);

//						br = document.createElement('br');
//						cc.appendChild(br);

						inp = document.createElement('input');
						inp.type = "hidden";
						inp.id = "eid_"+day.day+"_"+eidx;
						inp.value = exercise.id;
						cc.appendChild(inp);
					}
					cc.appendChild(tbl);
					plandiv.appendChild(cc);
					div.appendChild(plandiv);
					display = "none";
				}
				cont.appendChild(div);
				mDiv.appendChild(cont);
				highlightSource(1);
			}
		}
	}
}

//
// Filter the list of workout plans by category and level of intensity
//
function filterPlans() {
	var pt = document.getElementById('PlanCategory');
	var pl = document.getElementById('PlanLevel');

	var div = document.getElementById('PLContent');
	var plist = document.getElementById('planList');
	plist.removeChild(div);
	div = document.createElement('div');
	div.id = "PLContent";

	for (var i = 1;; i++) {
		var idx = document.getElementById('EPidx'+i);
		if (idx == null) {
			break;
		}

		if (pt.selectedIndex > 0) {
			var exT = document.getElementById('ExTyp'+idx.value);
			if (exT.value != pt.value) {
				continue;
			}
		}

		if (pl.selectedIndex > 0) {
			var exL = document.getElementById('ExLevel'+idx.value);
			if (exL.value != pl.value) {
				continue;
			}
		}

		var a = document.createElement('a');
		a.href = "javascript:void(0);";
		makeEvent(a, selectPlan, idx.value, 'click');
		var spn = document.createElement('span')
		var nm = document.getElementById('Exn'+idx.value);
		spn.innerText = nm.value;
		a.appendChild(spn);

		var br = document.createElement('br');
		div.appendChild(a);
		div.appendChild(br);
	}

	plist.appendChild(div);
	return false;
}

//
// Display the details of the canned plan for the input day
//
function displayPlanEx(day) {
	var tab;
	var taba;
	var div;
	for (var i = 1; i <= 7; i++) {
		tab = document.getElementById('PDay'+i+'_tab');
		div = document.getElementById('PDay'+i+'_content');
		if (tab) {
			taba = tab.lastChild;
			taba.style.backgroundPosition = "0 0";
			div.style.display = "none";
		}
	}
	
	try {
		tab = document.getElementById('PDay'+day+'_tab');
		taba = tab.lastChild;
		taba.style.backgroundPosition = "0 -56px";
		div = document.getElementById('PDay'+day+'_content');
		div.style.display = "block";		
	} catch(e) {}



	highlightSource(day);
}

//
// Display the details of the user's plan for the input day
//
function displayUserPlan(day) {
	var tab;
	var div;
	for (var i = 1; i <= 7; i++) {
		tab = document.getElementById('UPDay'+i+'_tab');
		taba = tab.lastChild;
		div = document.getElementById('UPDay'+i+'_content');
		taba.style.backgroundPosition = "0 0";
		div.style.display = "none";
	}
	tab = document.getElementById('UPDay'+day+'_tab');
	taba = tab.lastChild;
	div = document.getElementById('UPDay'+day+'_content');
	taba.style.backgroundPosition = "0 -56px";
	div.style.display = "block";

	var hid = document.getElementById('UPDay');
	hid.value = day;
}

//
// Move the list of exercises from the canned plan to the user's plan
//
function moveExercises(tgday) {
	var day = document.getElementById('selectedDay');

	var planDiv = document.getElementById('exercise'+tgday);
	if (planDiv != null) {

		var tdiv = document.getElementById('target_'+tgday);
		if (tdiv != null) {
				planDiv.removeChild(tdiv);
		}
	}

	var div = document.createElement('div');
	div.id = 'target_'+tgday;
	div.style.backgroundColor = '#efefef';

	var tbl = document.createElement('table');
	tbl.border = "0";

	for (var j = 1;; j++) {
		var eid = document.getElementById('eid_'+day.value+'_'+j);
		if (eid == null) {
			break;
		}

		var sets = document.getElementById('srcSets'+day.value+'_'+j);
		var reps = document.getElementById('srcReps'+day.value+'_'+j);
		var dur = document.getElementById('srcDuration'+day.value+'_'+j);
		var mets = document.getElementById('srcMETs'+day.value+'_'+j);

		var teid = document.createElement('input');
		teid.name = 'targetEid'+tgday+'_'+j;
		teid.type = "hidden";
		teid.value = eid.value;
		div.appendChild(teid);

		var tsets = document.createElement('input');
		tsets.name = 'targetSets'+tgday+'_'+j;
		tsets.type = "hidden";
		if (sets != null) {
			tsets.value = sets.value;
		}
		else {
			tsets.value = 0;
		}
		div.appendChild(tsets);

		var treps = document.createElement('input');
		treps.name = 'targetReps'+tgday+'_'+j;
		treps.type = "hidden";
		if (reps != null) {
			treps.value = reps.value;
		}
		else {
			treps.value = 0;
		}
		div.appendChild(treps);

		var tdur = document.createElement('input');
		tdur.name = 'targetDuration'+tgday+'_'+j;
		tdur.type = "hidden";
		if (dur != null) {
			tdur.value = dur.value;
		}
		else {
			tdur.value = 0;
		}
		div.appendChild(tdur);

		var tmets = document.createElement('input');
		tmets.name = 'targetMETs'+tgday+'_'+j;
		tmets.type = "hidden";
		if (mets != null) {
			tmets.value = mets.value;
		}
		else {
			tmets.value = 0;
		}
		div.appendChild(tmets);

		var tr = document.createElement('tr');
		var td = document.createElement('td');

		var txt = document.getElementById('txt_'+day.value+'_'+j);
		var ttxt = document.createElement('span');
		ttxt.className = "ExList";
		ttxt.innerText = txt.innerText;
		td.appendChild(ttxt);
		tr.appendChild(td);

		if (tsets.value > 0) {
			td = document.createElement('td');
			tsets = document.createElement('span');
			tsets.className = "ExList";
			tsets.innerText = "Sets: " + sets.value;
			td.appendChild(tsets);
			tr.appendChild(td);

			td = document.createElement('td');
			treps = document.createElement('span');
			treps.className = "ExList";
			treps.innerText = "Reps: " + reps.value;
			td.appendChild(treps);
			tr.appendChild(td);
		}

		else if (tdur.value > 0) {
			td = document.createElement('td');
			tdur = document.createElement('span');
			tdur.className = "ExList";
			tdur.innerText = "Duration: " + dur.value;
			td.appendChild(tdur);
			tr.appendChild(td);

			td = document.createElement('td');
			tmets = document.createElement('span');
			tmets.className = "ExList";
			tmets.innerText = "METs: " + mets.value;
			td.appendChild(tmets);
			tr.appendChild(td);
		}

//		var br = document.createElement('br');
//		div.appendChild(br);
		tbl.appendChild(tr);
	}
	div.appendChild(tbl);
	planDiv.appendChild(div);
}

//
// Highlight the canned exercise plan
//
function highlightSource(day) {
	var spn;
	var hid;
	var i;
	var selDay = document.getElementById('selectedDay');
	
	for (i = 1;; i++) {
		spn = document.getElementById('srcDayName'+i);
		if (spn == null) {
			break;
		}

		if (i == day) {
			spn.style.backgroundColor = "blue";
			spn.style.color = "white";
			selDay.value = i;
		}
		else {
			spn.style.backgroundColor = "#efefef";
			spn.style.color = "#2D566E";
		}
	}
}

//
// Submit the details of a workout plan to be saved
//
function submitPlanForm() {
	var pnam = document.getElementById('UserPlanName');
	if (pnam.value.length == 0) {
		alert ('Please enter a name for your exercise plan');
		pnam.focus();
		return false;
	}
	var pn = document.getElementById('PlanName');
	pn.value = pnam.value;

	frm = document.getElementById('PlanForm');
	frm.submit();
}

//
// Submit to create a new exercise plan
//
function createExPlan() {
	var frm = document.getElementById('CreatePlanForm');
	var txt = document.getElementById('UserExPlanName');
	if (txt.value.length == 0) {
		alert('Please enter a name for your exercise plan');
		txt.focus();
		return false;
	}
	frm.submit();
	return true;
}

//
// Submit to display details of an exercise plan
//
function getExPlanDetails() {
	var frm = document.getElementById('SelectPlanForm');
	var pln = document.getElementById('ExPlanID');
	if (pln.value == 0) {
		alert('Please select one of your exercise plans from the dropdown');
		pln.focus();
		return false;
	}
	frm.submit();
	return true;
}

//
// Log an exercise from the exercise plan
//
function addToExLog(day, exID, supp, cat, p1, p2) {
	var chk = document.getElementById('check'+day+'_'+exID+'_'+supp);
	if (!chk.checked) {
		chk.checked = true;
		return;
	}

	var rqst = newRequest();
	rqst.open("POST", "/WorkoutPlan/LogExercise", false);
	rqst.setRequestHeader("User-Agent", "XMLHttpRequest");
	rqst.setRequestHeader("Accept-Language", "en");
	rqst.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var param = "exID=" + encodeURIComponent(exID).replace("/%20/g","+") +
								"&supp=" + encodeURIComponent(supp).replace("/%20/g","+") +
								"&cat=" + encodeURIComponent(cat).replace("/%20/g","+") +
								"&p1=" + encodeURIComponent(p1).replace("/%20/g","+") +
								"&p2=" + encodeURIComponent(p2).replace("/%20/g","+");
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
				var stat = data[i].value;
				if (stat == "Success") {
					var chk = document.getElementById('check'+day+'_'+exID+'_'+supp);
					if (chk != null) {
						chk.checked = true;
					}
					var add = document.getElementById('added'+day+'_'+exID+'_'+supp);
					add.innerHTML = "added";
				}
				else {
					alert ('An error occured when adding to the exercise log');
				}
			}
		}
	}
	else {
		alert ('A network error occured');
	}
}


//
// Update the details of an exercise in the exercise log
//
function UpdEx(eid, supp) {
	var ExID = document.getElementById('ExID');
	ExID.value = eid;

	var s = document.getElementById('Sets');
	var r = document.getElementById('Reps');
	var w = document.getElementById('Weight');
	var d = document.getElementById('Dur');
	var c = document.getElementById('Category');
	var z = document.getElementById('Custom');

	var cat = document.getElementById('cat'+eid+'_'+supp);
  if (cat.value == 'cardio') {
  	z.value = supp;
  	c.value = cat.value;

		s.value = 0;
		r.value = 0;
		w.value = 0;
		var dur = document.getElementById('dur'+eid+'_'+supp);
		d.value = dur.value;
  }
  else {
		z.value = supp
		c.value = cat.value;

		d.value = 0;
		var sets = document.getElementById('sets'+eid+'_'+supp);
		s.value = sets.value;
		var reps = document.getElementById('reps'+eid+'_'+supp);
		r.value = reps.value;
		var wt = document.getElementById('weight'+eid+'_'+supp);
		if (wt != null) {
			w.value = wt.value;
		}
		else {
			w.value = 0;
		}
	}

	var frm = document.forms[0];
	frm.action = "/WorkoutPlan/UpdExerciseLog";
	frm.submit();
}

//
// Delete an exercise from the exercise log
//
function DelEx(eid, supp) {
	var ExID = document.getElementById('ExID');
	ExID.value = eid;
	var z = document.getElementById('Custom');
	z.value = supp;

	var frm = document.forms[0];
	frm.action = "/WorkoutPlan/DelExerciseLog";
	frm.submit();
}

//
// Search via AJAX for a matching exercise
//
function handleSearchBox(e, url) {
	if (busy) {
		return false;
	}
	var searchTxt = document.getElementById('search');
	if (searchTxt.value.length >= 3) {
		busy = true;
		var rqst = newRequest();
		rqst.onreadystatechange = function() {

			if (rqst.readyState == 4) {
				busy = false;
				if (rqst.status == 200) {
					var el = $('#ExerciseList').html('');			
					var xmldoc = rqst.responseXML.documentElement;
					
					el.remove('#ExTable');
					
					var table_html = '<div id="ExTable" style="overflow:auto"><table><thead><tr><th>Matching Exercises:</th></tr></thead><tbody>';

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
						table_html += "<tr><td>Error searching for exercises:</td></tr></tbody></table>";

						el.append(table_html);
					} else {
												
						for (var i = 0; i < data.length; i++) {
							if (data[i].name == "values") {
								var values = data[i].value;
								
								try {

									for (var j = 0; j < values.length; j++) {

										//
										// parameters p1, p2, p3 are dependant on category
										//	if category == 'cardio' p1 = duration, p2 = 0
										//	otherwise p1 = sets, p2 = reps
										//
										var val = values[j].name;
										var arg = values[j].id;
										var ex = values[j].selected;
										var cat = values[j].category;
										if (cat.value == 'cardio') {
											p1 = values[j].duration;
											p2 = 0;
										}
										else {
											p1 = values[j].sets;
											p2 = values[j].reps;
										}
										var cust = values[j].custom;
										
										table_html += '<tr><td><a href="#" onclick="selectExercise(\'' + arg + '\',\'' + cat + '\',\'' + p1 + '\',\'' + p2 + '\',\'' + cust + '\'); return false;">' + val + '</a></td></tr>';
									}
									
									table_html += "</tbody></table>";
									
								
								} catch (e) {
									table_html += "<tr><td>Error searching for exercises:</td></tr></tbody></table>";
								}
								
								el.append(table_html);

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
		$('#ExerciseList').remove('#ExTable');
		return false;
	}
}


//
// Add the selected exercise to the exercise log
//
function selectExercise(eid, cat, p1, p2, cust) {
	var searchTxt = $('#search');
	searchTxt.val('');

	var hid = $('#ExID');
	hid.val(eid);
	hid = $('#Category');
	hid.val(cat);
	hid = $('#Custom');
	hid.val(cust);

	if (cat == 'cardio') {
		hid = $('#Dur');
		hid.val(p1);
		hid = $('#Sets');
		hid.val(0);
		hid = $('#Reps');
		hid.val(0);
	}
	else {
		hid = $('#Dur');
		hid.val(0);
		hid = $('#Sets');
		hid.val(p1);
		hid = $('#Reps');
		hid.val(p2);
	}

	var frm = document.forms[0];
	frm.submit();
}

function custChgCat() {
	var sel = document.getElementById('category');
	var cardio = document.getElementById('cardio');
	var str = document.getElementById('strength');
	var butt = document.getElementById('button');

	if (sel.selectedIndex == 0) {
		cardio.style.display = "none";
		str.style.display = "none";
		butt.style.display = "none";
	}
	else if (sel.value == "cardio") {
		cardio.style.display = "block";
		str.style.display = "none";
		butt.style.display = "block";
	}
	else {
		cardio.style.display = "none";
		str.style.display = "block";
		butt.style.display = "block";
	}
}



function submitAddCustom() {
	var nm = document.getElementById('name');
	if (nm.value.length == 0) {
		alert('You must select a name for your exercise');
		nm.focus();
		return false;
	}

	var cat = document.getElementById('category');
	if (cat.selectedIndex == 0) {
		alert('You must select an exercise category');
		cat.focus();
		return false;
	}

  var res;
	if (cat.selectedIndex == 1) {
		var dur = document.getElementById('duration');
		if (dur.value.length == 0) {
			alert('You must have duration value in minutes for a cardio exercise');
			dur.focus();
			return false;
		}
		else {
			var dpatt = new RegExp("^\\d+$");
			res = dpatt.exec(dur.value);
			if (res == null) {
				alert('Duration must be a number of minutes');
				dur.value = "";
				dur.focus();
				return false;
			}
		}
		var M = document.getElementById('METs');
		if (M.value.length == 0) {
			alert('You must have a value for metabolic equivalent for a cadio exercise');
			M.focus();
			return false;
		}
		else {
			var mpatt = new RegExp("^\\d+\\.{0,1}\\d*$");
			res = mpatt.exec(M.value);
			if (res == null) {
				alert("Metabolic Equivalent is expressed as numeric value (mm.nn)");
				M.value = "";
				M.focus();
				return false;
			}
		}

		var sets = document.getElementById('sets');
		sets.value = 0;

		var reps = document.getElementById('reps');
		reps.value = 0;

		var wt = document.getElementById('weight');
		wt.value = 0;
	
	}
	else {
		var sets = document.getElementById('sets');
		if (sets.value.length == 0) {
			alert('You must have a number of sets for a strength exercise');
			sets.focus();
			return false;
		}
		else {
			var spatt = new RegExp("^\\d+$");
			res = spatt.exec(sets.value);
			if (res == null) {
				alert('Sets must be expressed as a number');
				sets.value = "";
				sets.focus();
				return false;
			}
		}

		var reps = document.getElementById('reps');
		if (reps.value.length == 0) {
			alert('You must have a number of repetitions for a strength exercise');
			reps.focus();
			return false;
		}
		else {
			var rpatt = new RegExp("^\\d+$");
			res = rpatt.exec(reps.value);
			if (res == null) {
				alert('Repetitions must be expressed as a number');
				reps.value = "";
				reps.focus();
				return false;
			}
		}

		var wt = document.getElementById('weight');
		if (wt.value.length > 0) {
			var wpatt = new RegExp("^\\d+$");
			res = wpatt.exec(wt.value);
			if (res == null) {
				alert('Weight must be expressed as a number');
				wt.value = "";
				wt.focus();
				return false;
			}
		}
		else {
			wt.value = 0;
		}

		var dur = document.getElementById('duration');
		dur.value = 0;

		var M = document.getElementById('METs');
		M.value = 0;
	}

	frm = document.getElementById('customExerciseForm');
	frm.submit();
	return true;
}

