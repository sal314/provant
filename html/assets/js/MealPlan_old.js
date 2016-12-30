//
// MealPlan.js
//

function highlightTarget(itemType, itemId) {
	var spn;
	var i;
	var sel = document.getElementById('selectedTarget');
	var seltype = document.getElementById('selectedTargetType');
	
	if (itemType == 'day') {
		for (i = 1;; i++) {
			spn = document.getElementById('targDayName'+i);
			if (spn == null) {
				break;
			}
			hid = document.getElementById('targDayHidden'+i);
			if (i == itemId) {
				spn.style.backgroundColor = "blue";
				spn.style.color = "white";
				sel.value = hid.value;
				seltype.value = itemType;
			}
			else {
				spn.style.backgroundColor = "#e1e1e1";
				spn.style.color = "#2D568E";
			}
		}

		for (i = 1;; i++) {
			spn = document.getElementById('targMealName'+i);
			if (spn == null) {
				break;
			}
			spn.style.backgroundColor = "#e1e1e1";
			spn.style.color = "#444";
		}
	}
	else if (itemType == "meal") {
		for (i = 1;; i++) {
			spn = document.getElementById('targMealName'+i);
			if (spn == null) {
				break;
			}
			hid = document.getElementById('targMealHidden'+i);
			if (i == itemId) {
				spn.style.backgroundColor = "blue";
				spn.style.color = "white";
				sel.value = hid.value;
				seltype.value = itemType;
			}
			else {
				spn.style.backgroundColor = "#e1e1e1";
				spn.style.color = "#444";
			}
		}

		for (i = 1;; i++) {
			spn = document.getElementById('targDayName'+i);
			if (spn == null) {
				break;
			}
			spn.style.backgroundColor = "#e1e1e1";
			spn.style.color = "#2D566E";
		}
	}
}

function highlightSource(itemType, itemId) {
	var spn;
	var hid;
	var i;
	var sel = document.getElementById('selectedSource');
	var seltype = document.getElementById('selectedSourceType');
	
	if (itemType == 'day') {
		for (i = 1;; i++) {
			spn = document.getElementById('srcDayName'+i);
			if (spn == null) {
				break;
			}
			hid = document.getElementById('srcDayHidden'+i);
			if (i == itemId) {
				spn.style.backgroundColor = "blue";
				spn.style.color = "white";
				sel.value = hid.value;
				seltype.value = itemType;
			}
			else {
				spn.style.backgroundColor = "#efefef";
				spn.style.color = "#2D566E";
			}
		}
		for (i = 1;; i++) {
			spn = document.getElementById('srcMealName'+i);
			if (spn == null) {
				break;
			}
			spn.style.backgroundColor = "#efefef";
			spn.style.color = "#444";
		}
	}
	else if (itemType == "meal") {
		for (i = 1;; i++) {
			spn = document.getElementById('srcMealName'+i);
			if (spn == null) {
				break;
			}
			hid = document.getElementById('srcMealHidden'+i);
			if (i == itemId) {
				spn.style.backgroundColor = "blue";
				spn.style.color = "white";
				sel.value = hid.value;
				seltype.value = itemType;
			}
			else {
				spn.style.backgroundColor = "#efefef";
				spn.style.color = "#444";
			}
		}
		for (i = 1;; i++) {
			spn = document.getElementById('srcDayName'+i);
			if (spn == null) {
				break;
			}
			spn.style.backgroundColor = "#efefef";
			spn.style.color = "#2D566E";
		}
	}
}

function checkMenuMove() {
	var srcType = document.getElementById('selectedSourceType');
	var targType = document.getElementById('selectedTargetType');
	if (srcType.value != targType.value) {
		alert('The selected menu items must match on each side.  Currently the suggested menu (source) has a ' + srcType + ' selected and the custom menu (target) has a ' + targType + ' selected.  These must be the same type.');
		return false;
	}
	return true;
}

function selectMenu(menuID) {
	var ml = document.getElementById('menuList');
	var sm = document.createElement('input');
	sm.type = "hidden";
	sm.name = "SrcMenu";
	sm.value = menuID;
	ml.appendChild(sm);
	var frm = document.getElementById('filterForm');
	frm.submit();
}
