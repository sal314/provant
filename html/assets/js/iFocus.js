//
// iFocus.js
//

function toggleDisplay(div) {
	var d = document.getElementById(div);
	if (d) {
		if (d.style.display == 'none') {
			d.style.display = 'block';
		}
		else {
			d.style.display = 'none';
		}
	}
}
