//
// Colors.js
//

var color = "";

function setColorTag() {
	var sel = document.getElementById('picker');
	color = sel.value;

	var r = document.getElementById(color+'_r');
	var g = document.getElementById(color+'_g');
	var b = document.getElementById(color+'_b');

	var red = document.getElementById('red');
	var grn = document.getElementById('green');
	var blu = document.getElementById('blue');

	red.value = r.value;
	grn.value = g.value;
	blu.value = b.value;
}


function adjustColor(shade, direction, jump) {
	if (color == "") {
		color = 'background';
	}
	var cb;
	if (shade == 'r') {
		cb = document.getElementById('red');
	}
	else if (shade == 'g') {
		cb = document.getElementById('green');
	}
	else if (shade == 'b') {
		cb = document.getElementById('blue');
	}
	else {
		return;
	}
	var val = document.getElementById(color+'_'+shade);
	var num = val.value - 0;
	if (direction == "up") {
		if (num == 255) {
			return;
		}
		num = num + jump;
		if (num > 255) {
			num = 255;
		}
		val.value = num;
		cb.value = num;
	}
	else if (direction == "down") {
		if (num == 0) {
			return;
		}
		var num = num - jump;
		if (num < 0) {
			num = 0;
		}
		val.value = num;
		cb.value = num;
	}

	var red = document.getElementById(color+'_r');
	var grn = document.getElementById(color+'_g');
	var blu = document.getElementById(color+'_b');
	var clr = "rgb(" + red.value + ", " + grn.value + ", " + blu.value + ")";
	if (color == "background") {
		var bck = document.getElementById('testpage');
		bck.style.backgroundColor = clr;
		var txt = document.getElementById('hdrtxt');
		txt.style.color = clr;
	}
	else if (color == "tab1") {
		var t1 = document.getElementById('tab1');
		t1.style.backgroundColor = clr;
	}
	else if (color == "tab2") {
		var t2 = document.getElementById('tab2');
		t2.style.backgroundColor = clr;
	}
	else if (color == "tab3") {
		var t3 = document.getElementById('tab3');
		t3.style.backgroundColor = clr;
	}
	else if (color == "tab4") {
		var t4 = document.getElementById('tab4');
		t4.style.backgroundColor = clr;
	}
	else if (color == "articles") {
		var art = document.getElementById('articles_1');
		art.style.backgroundColor = clr;
		art = document.getElementById('articles_2');
		art.style.backgroundColor = clr;
		art = document.getElementById('articles_3');
		art.style.backgroundColor = clr;
	}
	else if (color == "color1") {
		var nav;
		for (var i = 1; i < 9; i += 2) {
			nav = document.getElementById('nav' + i);
			nav.style.backgroundColor = clr;
		}
	}
	else if (color == "color2") {
		var nav;
		for (var i = 2; i < 9; i += 2) {
			nav = document.getElementById('nav' + i);
			nav.style.backgroundColor = clr;
		}
	}
}

