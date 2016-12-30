//
// incentiveProgram.js
//	These are some javascript routines for the Incentive Program
//	pages for the administrator.
//
//	@author  Scott LePage scott@shazamm.net
//	@version 1.1
//	@package assets.js
//

//
// automatically checks the checkbox when any the
// of the text boxes change
//
function checkTheBox () {
	var chk = document.getElementById('id_20');
	chk.checked=true;
	return true;
}

//
// clear out the redeem points fields when
// cancel button is pushed
//
function clearRedeem () {

	var email = document.getElementById('redeemEmail');
	email.value = "";
	var points = document.getElementById('redeemPoints');
	points.value = "";

	return true;
}

//
// check that values were entered in the email
// address and the points fields
//
function checkRedeem () {
	var email = document.getElementById('redeemEmail');
	if (email.value.length == 0) {
		alert ("Enter a user's email address");
		email.focus();
		return false;
	}
	var points = document.getElementById('redeemPoints');
	if (points.value.length == 0) {
		alert ("Enter how many points are being redeemed");
		points.focus();
		return false;
	}
    var num = parseInt(points.value);
    if (isNaN(num)) {
    	alert("Redeemed points must be a number");
    	points.value = "";
    	points.focus();
    	return false;
    }

	return true;
}
