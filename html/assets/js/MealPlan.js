//
// MealPlan.js
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
// Highlight either a Day or a Meal on the canned menu list
//
function highlightSource(ob) {
	var spn;
	var hid;
	var i;
	var selDay = document.getElementById('selectedDay');
	var selMeal = document.getElementById('selectedMeal');
	var seltype = document.getElementById('selectedSourceType');
	
	for (i = 1;; i++) {
		spn = document.getElementById('srcDayName'+i);
		if (spn == null) {
			break;
		}

		if (ob.type == 'day') {
			if (i == ob.day) {
				spn.style.backgroundColor = "blue";
				spn.style.color = "white";
				selDay.value = i;
				selMeal.value = "";
				seltype.value = ob.type;
			}
			else {
				spn.style.backgroundColor = "#efefef";
				spn.style.color = "#2D566E";
			}

			for (j = 1; j <= 6; j++) {
				spn = document.getElementById('srcMealName'+i+'_'+j);
				if (spn == null) {
					continue;
				}
				spn.style.backgroundColor = "#efefef";
				spn.style.color = "#444";
			}
		}
		else {
			spn.style.backgroundColor = "#efefef";
			spn.style.color = "#2D566E";
		}

		for (j = 1; j <= 6; j++) {
			spn = document.getElementById('srcMealName'+i+'_'+j);
			if (spn == null) {
				continue;
			}

			if (ob.type == 'meal') {
				if ((i == ob.day) && (j == ob.meal)) {
					spn.style.backgroundColor = "blue";
					spn.style.color = "white";
					selDay.value = i;
					selMeal.value = spn.innerText;
					seltype.value = ob.type;
				}
				else {
					spn.style.backgroundColor = "#efefef";
					spn.style.color = "#444";
				}
			}
		}
	}
}

//
// Move a meal or a day's worth of meals from the
// canned menu to the custom menu
//
function moveMeal(tgday) {
	var selType = document.getElementById('selectedSourceType');
	var day = document.getElementById('selectedDay');
	var mName = document.getElementById('selectedMeal');
	var meal = 0;
	if (mName.value == "Breakfast") {
		meal = 1;
	}
	else if (mName.value == "Morning Snack") {
		meal = 2;
	}
	else if (mName.value == "Lunch") {
		meal = 3;
	}
	else if (mName.value == "Afternoon Snack") {
		meal = 4;
	}
	else if (mName.value == "Dinner") {
		meal = 5;
	}
	else if (mName.value == "Evening Snack") {
		meal = 6;
	}

	if (selType.value == 'day') {
		for (var i = 1; i <= 6; i++) {
			var mealDiv = document.getElementById('food'+tgday+'_'+i);
			if (mealDiv == null) {
				continue;
			}

			var tdiv = document.getElementById('target_'+tgday+'_'+i);
			if (tdiv != null) {
				mealDiv.removeChild(tdiv);
			}

			var div = document.createElement('div');
			div.id = 'target_'+tgday+'_'+i;
			div.style.backgroundColor = '#efefef';

			for (var j = 1;; j++) {
				var fid = document.getElementById('fid_'+day.value+'_'+i+'_'+j);
				if (fid == null) {
					break;
				}

				var tfid = document.createElement('input');
				tfid.name = 'targetFid'+tgday+'_'+i+'_'+j;
				tfid.type = "hidden";
				tfid.value = fid.value;

				var srv = document.getElementById('srv_'+day.value+'_'+i+'_'+j);
				var tsrv = document.createElement('input');
				tsrv.name = 'targetSrv'+tgday+'_'+i+'_'+j;
				tsrv.type = "hidden";
				tsrv.value = srv.value;

				var unt = document.getElementById('unt_'+day.value+'_'+i+'_'+j);
				var tunt = document.createElement('input');
				tunt.name = 'targetUnt'+tgday+'_'+i+'_'+j;
				tunt.type = "hidden";
				tunt.value = unt.value;

				div.appendChild(tfid);
				div.appendChild(tsrv);
				div.appendChild(tunt);

				var txt = document.getElementById('txt_'+day.value+'_'+i+'_'+j);
				var ttxt = document.createElement('span');
				ttxt.className = "foodList";
				ttxt.innerText = txt.innerText;
				div.appendChild(ttxt);

				var br = document.createElement('br');
				div.appendChild(br);
			}
			mealDiv.appendChild(div);
		}
	}

	else {
		var mealDiv = document.getElementById('food'+tgday+'_'+meal);
		var i = meal;

		var tdiv = document.getElementById('target_'+tgday+'_'+i);
		if (tdiv != null) {
			mealDiv.removeChild(tdiv);
		}

		var div = document.createElement('div');
		div.id = 'target_'+tgday+'_'+i;
		div.style.backgroundColor = '#efefef';

		for (j = 1;; j++) {
			var fid = document.getElementById('fid_'+day.value+'_'+i+'_'+j);
			if (fid == null) {
				break;
			}

			var tfid = document.createElement('input');
			tfid.name = 'targetFid'+tgday+'_'+i+'_'+j;
			tfid.type = "hidden";
			tfid.value = fid.value;

			var srv = document.getElementById('srv_'+day.value+'_'+i+'_'+j);
			var tsrv = document.createElement('input');
			tsrv.name = 'targetSrv'+tgday+'_'+i+'_'+j;
			tsrv.type = "hidden";
			tsrv.value = srv.value;

			var unt = document.getElementById('unt_'+day.value+'_'+i+'_'+j);
			var tunt = document.createElement('input');
			tunt.name = 'targetUnt'+tgday+'_'+i+'_'+j;
			tunt.type = "hidden";
			tunt.value = unt.value;

			div.appendChild(tfid);
			div.appendChild(tsrv);
			div.appendChild(tunt);

			var txt = document.getElementById('txt_'+day.value+'_'+i+'_'+j);
			var ttxt = document.createElement('span');
			ttxt.className = "foodList";
			ttxt.innerText = txt.innerText;
			div.appendChild(ttxt);

			var br = document.createElement('br');
			div.appendChild(br);
		}
		mealDiv.appendChild(div);
	}
}


//
// Retrieve (via AJAX) and display the details of a canned menu
//
function selectMenu(menuID) {
	var rqst = newRequest();
	rqst.open("POST", "/MealPlan/GetMenu", false);
	rqst.setRequestHeader("User-Agent", "XMLHttpRequest");
	rqst.setRequestHeader("Accept-Language", "en");
	rqst.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var param = "value=" + encodeURIComponent(menuID).replace("/%20/g","+");
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
				var menu = data[i].value;
				var br;

				var mDiv = document.getElementById('srcMenu');
				if (mDiv != null) {
					var div = document.getElementById('CannedMenu');
					if (div != null) {
						mDiv.removeChild(div);
					}
				}
				cont = document.createElement('div');
				cont.id = "CannedMenu";
				tabs = document.createElement('div');
				tabs.className = "menuTab";
				tabs.paddingTop = "2px";
				var nm = document.createElement('h3');
				nm.id = "srcMenuName";
				nm.innerText = menu.menu.Calories + " Calorie " + menu.menu.Days + " Day " + menu.menu.Category + " Menu";
				cont.appendChild(nm);
				
				var days = menu.days;
				var astyle = "0 -56px";
				for (var d = 0; d < days.length; d++) {
					var md = days[d];
					var tabdiv = document.createElement('div');
					if (document.all) {
						tabdiv.style.styleFloat = "left";
					}
					else {
						tabdiv.style.cssFloat = "left";
					}
					tabdiv.id = "MDay"+md.id+"_tab";

					var taba = document.createElement('a')
					taba.href = "javascript:void(0)";
					taba.style.background = "url(/assets/media/images/meal-tab.png) 0 0 no-repeat";
					taba.style.width = "55px";
					taba.style.height = "28px";
					taba.style.display = "block";
					taba.style.textAlign = "center";
					taba.style.marginRight = "2px";
					taba.style.backgroundPosition = astyle;
					makeEvent(taba, displayMenuMeal, md.id, 'click');
//					if (document.all) {
//						taba.onclick = function () { displayMenuMeal(md.id) };
//					}
//					else {
//						var aatt = document.createAttribute('onclick');
//						aatt.nodeValue = "displayMenuMeal("+md.id+")";
//						taba.setAttributeNode(aatt);
//					}
					var aspn = document.createElement('span');
					aspn.className = "dayName";
					aspn.innerText = md.name;
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
				div.id = "MenuContents";
				div.className = "border";
				br = document.createElement('br');
				div.appendChild(br);


				var display = "block";
				for (var d = 0; d < days.length; d++) {
					var day = days[d];

					var menudiv = document.createElement('div');
					menudiv.id = "MDay"+day.id+"_content";
					menudiv.style.display = display;

					var a = document.createElement('a');
					a.href = "javascript:void(0);";
					var obj = new Object;
					obj.type = "day";
					obj.day = day.id;
					obj.meal = 0;
					
					makeEvent(a, highlightSource, obj, 'click');
//					if (document.all) {
//						a.onclick = function() { highlightSource('day', day.id, 0) };
//					}
//					else {
//						var attr = document.createAttribute('onclick');
//						attr.nodeValue = "highlightSource('day', "+day.id+", 0)";
//						a.setAttributeNode(attr);
//					}
					var spn = document.createElement('span');
					spn.id = "srcDayName"+day.id;
					spn.className = "dayName";
					spn.innerText = day.name;
					a.appendChild(spn);
					menudiv.appendChild(a);

					var inp = document.createElement('input');
					inp.type = "hidden";
					inp.id = "srcDayHidden"+day.id;
					inp.value = day.id;
					menudiv.appendChild(inp);

					var cc = document.createElement('div');
					cc.className = "mealborder";

					var meals = day.meals;
					for (var m = 0; m < meals.length; m++) {
						var meal = meals[m];

						a = document.createElement('a');
						a.href = "javascript:void(0);";
						var ob = new Object;
						ob.type = "meal";
						ob.day = day.id;
						ob.meal = meal.order;
						makeEvent(a, highlightSource, ob, 'click');
//						if (document.all) {
//							a.onclick = function() {highlightSource('meal', day.id, meal.order) };
//						}
//						else {
//							attr = document.createAttribute('onclick');
//							attr.nodeValue = "highlightSource('meal', "+day.id+", "+meal.order+");";
//							a.setAttributeNode(attr);
//						}
						spn = document.createElement('span');
						spn.id = "srcMealName"+day.id+"_"+meal.order;
						spn.className = "mealName";
						spn.innerText = meal.name;
						a.appendChild(spn);
						cc.appendChild(a);
						br = document.createElement('br');
						cc.appendChild(br);

						inp = document.createElement('input');
						inp.type = "hidden";
						inp.id = "srcMealHidden"+day.id+"_"+meal.order;
						inp.value = meal.id;
						cc.appendChild(inp);

						var foods = meal.foods;
						for (var f = 0; f < foods.length; f++) {
							food = foods[f];
							var fidx = f + 1;

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "fid_"+day.id+"_"+meal.order+"_"+fidx;
							inp.id = "fid_"+day.id+"_"+meal.order+"_"+fidx;
							inp.value = food.FoodID;
							cc.appendChild(inp);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "srv_"+day.id+"_"+meal.order+"_"+fidx;
							inp.id = "srv_"+day.id+"_"+meal.order+"_"+fidx;
							inp.value = food.ServingSize;
							cc.appendChild(inp);

							inp = document.createElement('input');
							inp.type = "hidden";
							inp.name = "unt_"+day.id+"_"+meal.order+"_"+fidx;
							inp.id = "unt_"+day.id+"_"+meal.order+"_"+fidx;
							inp.value = food.UnitID;
							cc.appendChild(inp);

							var tgt = "div";
							if (food.Recipe == 1) {
								a = document.createElement('a');
								a.href = "javascript:showWin('/MealPlan/ShowRecipe/" + food.FoodID + "')";
								tgt = "a";
							}
							spn = document.createElement('span');
							spn.className = "foodList"
							spn.id = "txt_"+day.id+"_"+meal.order+"_"+fidx;
							spn.innerText = food.Text;
							if (tgt == "a") {
								a.appendChild(spn);
								cc.appendChild(a);
							}
							else {
								cc.appendChild(spn);
							}
							br = document.createElement('br');
							cc.appendChild(br);
						}
					}
					menudiv.appendChild(cc);
					div.appendChild(menudiv);
					display = "none";
				}
				cont.appendChild(div);
				mDiv.appendChild(cont);
				ob = new Object;
				ob.type = "day";
				ob.day = 1;
				ob.meal = 0;
				highlightSource(ob);
			}
		}
	}
}

//
// Filter the list of canned menus by Category, Days and/or Calories
//
function filterMenus() {

	var typ = document.getElementById('MenuType');
	var day = document.getElementById('MenuDays');
	var cal = document.getElementById('MenuCalories');

	var div = document.getElementById('MLContent');
	var mlist = document.getElementById('menuList');
	mlist.removeChild(div);
	div = document.createElement('div');
	div.id = "MLContent";

	var out = new Array();
	for (var i = 1;; i++) {
		var idx = document.getElementById('Midx'+i);
		if (idx == null) {
			break;
		}

		if (typ.selectedIndex > 0) {
			var mT = document.getElementById('Mtc'+idx.value);
			if (mT.value != typ.value) {
				continue;
			}
		}

		if (day.selectedIndex > 0) {
			var mD = document.getElementById('Mtd'+idx.value);
			if (mD.value != day.value) {
				continue;
			}
		}

		if (cal.selectedIndex > 0) {
			var mC = document.getElementById('Mtk'+idx.value);
			if (mC.value != cal.value) {
				continue;
			}
		}

		var a = document.createElement('a');
		a.href = "javascript:void(0);";
		makeEvent(a, selectMenu, idx.value, 'click');
//		if (document.all) {
//			a.onclick = function(idx.value) { selectMenu(idx.value) };
//		}
//		else {
//			var attr = document.createAttribute('onclick');
//			attr.nodeValue = "selectMenu(" + idx.value + "); return false;";
//			a.setAttributeNode(attr);
//		}
		var spn = document.createElement('span')
		var nm = document.getElementById('Mtn'+idx.value);
		spn.innerText = nm.value;
		a.appendChild(spn);

		var br = document.createElement('br');
		div.appendChild(a);
		div.appendChild(br);
	}

	mlist.appendChild(div);
	return false;
}

//
// When custom menu is complete - submit it for storage
//
function submitMenuForm() {
	var nm = document.getElementById('UserMenuName');
	if (nm.value.length == 0) {
		alert ('Please enter a name for your custom menu');
		nm.focus();
		return false;
	}
	var frm = document.getElementById('MenuForm');
	frm.submit();
}

//
// Get the details of a custom menu
//
function getMenuDetails() {
	var frm = document.getElementById('SelectMenuForm');
	var menu = document.getElementById('MenuID');
	if (menu.value == 0) {
		alert('You must select one of your menus from the dropdown');
		menu.focus();
		return false;
	}
	frm.submit();
	return false;
}

//
// Create a new user menu
//
function createMenu() {
	var frm = document.getElementById('CreateMenuForm');
	var umn = document.getElementById('UserMenuName');
	if (umn.value.length == 0) {
		alert ('Please enter a name for your menu');
		umn.focus();
		return false;
	}
	frm.submit();
	return false;
}

//
// Send (via AJAX) a request to save a food item to the food log
//
function addToFoodLog(day, meal, food, srv, unit) {
	var chk = document.getElementById('check'+day+'_'+meal+'_'+food);
	if (!chk.checked) {
		chk.checked = true;
		return;
	}

	var rqst = newRequest();
	rqst.open("POST", "/MealPlan/LogFood", false);
	rqst.setRequestHeader("User-Agent", "XMLHttpRequest");
	rqst.setRequestHeader("Accept-Language", "en");
	rqst.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var param = "mealID=" + encodeURIComponent(meal).replace("/%20/g","+") +
								"&foodID=" + encodeURIComponent(food).replace("/%20/g","+") +
								"&srv=" + encodeURIComponent(srv).replace("/%20/g","+") +
								"&unit=" + encodeURIComponent(unit).replace("/%20/g","+");
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
//					alert ("Successfully added to today's food log");
					chk = document.getElementById('check'+day+'_'+meal+'_'+food);
					if (chk != null) {
						chk.checked = true;
					}
					var add = document.getElementById('added'+day+'_'+meal+'_'+food);
					add.className = 'added';
					add.innerHTML = 'added';
				}
				else {
					alert ('An error occured when adding to the food log');
				}
			}
		}
	}
	else {
		alert ('A network error occured');
	}
}

//
// Keeps a checkbox checked
//
function keepChecked(day, meal, food) {
	var chk = document.getElementById('check'+day+'_'+meal+'_'+food);
	if (!chk.checked) {
		chk.checked = true;
		return;
	}
}

	
//
// For the custom menu, change the day tab to be displayed
//
function displayUserMeal(day) {
	var tab;
	var div;
	for (var i = 1; i <= 7; i++) {
		tab = document.getElementById('UMDay'+i+'_tab');
		if(tab) {
			taba = tab.lastChild;
		}
		div = document.getElementById('UMDay'+i+'_content');
		if(taba) {
			taba.style.backgroundPosition = "";
		}
		div.style.display = "none";
	}
	tab = document.getElementById('UMDay'+day+'_tab');
	taba = tab.lastChild;
	div = document.getElementById('UMDay'+day+'_content');
	taba.style.backgroundPosition = "0 -56px";
	div.style.display = "block";

	var hid = document.getElementById('UMDay');
	hid.value = day;
}

//
// For the canned menu, change the day tab to be displayed
//
function displayMenuMeal(day) {
	var tab;
	var taba;
	var div;
	for (var i = 1; i <= 7; i++) {
		tab = document.getElementById('MDay'+i+'_tab');
		div = document.getElementById('MDay'+i+'_content');
		if (tab) {
			taba = tab.lastChild;
			taba.style.backgroundPosition = "0 0";
			div.style.display = "none";
		}
	}
	tab = document.getElementById('MDay'+day+'_tab');
	taba = tab.lastChild;
	div = document.getElementById('MDay'+day+'_content');
	taba.style.backgroundPosition = "0 -56px";
	div.style.display = "block";

	var ob = new Object;
	ob.type = "day";
	ob.day = day;
	ob.meal = 0;
	highlightSource(ob);
}

var gAutoPrint = true;

function processPrint(){

if (document.getElementById != null){

var html = '<HTML>\n<HEAD>\n';

if (document.getElementsByTagName != null){

var headTags = document.getElementsByTagName("head");

if (headTags.length > 0) html += headTags[0].innerHTML;

}

html += '\n</HE' + 'AD>\n<BODY>\n';

var printReadyElem = document.getElementById("printMe");

if (printReadyElem != null) html += printReadyElem.innerHTML;

else{

alert("Error, no contents.");

return;

}

html += '\n</BO' + 'DY>\n</HT' + 'ML>';

var printWin = window.open("","processPrint");

printWin.document.open();

printWin.document.write(html);

printWin.document.close();

if (gAutoPrint) printWin.print();

} else alert("Browser not supported.");

}