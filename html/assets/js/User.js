//
// User.js
//
//	Function to validate and submit the opt-in form for health coaching
//

function validateForm() {
	var agree = document.getElementById('agree');
	if (!agree.checked) {
		alert("You must agree to the terms and conditions to receive Health Coaching");
		return false;
	}

	formatPhone('day');
	var phone = document.getElementById('dayphone');
	if (phone.value.length == 0) {
		alert ("Daytime phone number is required");
		phone.focus();
		return false;
	}

	var frm = document.forms[0];
	frm.submit();
}


function formatPhone(ph) {
	var numbers = new String();
	if (ph == "day") {
		var num = document.getElementById('dayphone');
	}
	else if (ph == "eve") {
		var num = document.getElementById('evephone');
	}
	if (num.value.length == 0) {
		return;
	}
	numbers.value = "";
	for (var i = 0; i < num.value.length; i++) {
		var n = num.value.substr(i, 1);
		if (n >= '0' && n <= '9') {
			numbers.value = numbers.value + n;
		}
	}
	num.value = numbers.value;
	if (numbers.value.length == 10) {
		num.value = numbers.value.substr(0,3) + "-" + numbers.value.substr(3,3) + "-" + numbers.value.substr(6);
	}
	else if (numbers.value.length == 7) {
		num.value = numbers.value.substr(0,3) + "-" + numbers.value.substr(3);
	}
	else {
		alert ('Phone number should have either 7 or 10 digits');
		num.value = "";
		num.focus();
	}
	return;
}
