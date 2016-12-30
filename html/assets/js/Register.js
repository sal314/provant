//
// Register.js
//

function setDays() {
	var m = document.getElementById('dob_month');
	var d = document.getElementById('dob_day');
	var idx = d.selectedIndex;
	while (d.options.length > 1) {
		d.options[1] = null;
	}

	var nd = 0;
	switch(m.value) {
		case "0":
			return;
			break;
		case "1":
		case "3":
		case "5":
		case "7":
		case "8":
		case "10":
		case "12":
			nd = 31;
			break;

		case "4":
		case "6":
		case "9":
		case "11":
			nd = 30;
			break;

		case "2":
			nd = 28;
			break;
	}

	var yr = document.getElementById('dob_year');
	
// If month is February and it's a Leap Year, add a day
	if (yr.value != 0) {
		if (m.value == 2) {
			if (isLeap(yr.value)) {
				nd += 1;
			}
		}
	}

	for (var i = 1; i <= nd; i++) {
		var opt = new Option(i, i, false, false);
		d.options[i] = opt;
	}
	if (idx < nd) {
		d.selectedIndex = idx;
	}
	else {
		d.selectedIndex = nd;
	}
}


//
// Leap Year check
//
//	If the year is divisible by 4 it's a leap year,
//	Unless it is divisible by 100, then it's not a leap year,
//	Unless it is divisible by 400, then it is a leap year.
//

function isLeap(year) {
	if ((parseInt(year) % 4) == 0) {
		if ((parseInt(year) % 100) == 0) {
			if ((parseInt(year) % 400) != 0) {
				return false;
			}
			else {
				return true;
			}
		}
		else {
			return true;
		}
	}
	else {
		return false;
	}
}
