//
// Print.js
//

//
// Print the contents of a <div>
//	@param div - id of the <div> to print
//
function printMe(div) {
	var d2p = document.getElementById(div);
	if (d2p) {
		var Win = window.open('', 'PrintWindow', '');
		Win.document.writeln(d2p.innerHTML);
		Win.document.close();
		Win.focus();
		Win.print();
		Win.close();
	}
}
