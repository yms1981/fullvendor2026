$(document ).ready(function() {


	Inputmask("currency", {
		radixPoint: _radixPoint,
		groupSeparator: _groupSeparator,
		digits: _digits
	}).mask(".mask");

})