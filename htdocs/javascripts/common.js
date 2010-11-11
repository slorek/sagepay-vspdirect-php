
// Function to add a load event.
function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      if (oldonload) {
        oldonload();
      }
      func();
    }
  }
}

// Function to get all elements by class name.
document.getElementsByClassName = function(cl) {
	var retnode = [];
	var myclass = new RegExp('\\b'+cl+'\\b');
	var elem = this.getElementsByTagName('*');
	for (var i = 0; i < elem.length; i++) {
		var classes = elem[i].className;
		if (myclass.test(classes)) retnode.push(elem[i]);
	}
	return retnode;
};


// Hides all transaction repeat forms on load.
addLoadEvent(function() {
	
	// Hide all the repeat forms by default.
	var elements = document.getElementsByClassName('authorise');
	
	for (var i=0; i < elements.length; i++) {
        elements[i].style.display = 'none';
	}
	
	// Add onclick events to show the authorise form for a single transaction.
	var transactions = document.getElementsByClassName('transaction');
	
	for (var i=0; i < transactions.length; i++) {
		
		var links = transactions[i].getElementsByTagName('a');
		
		for (var l=0; l < links.length; l++) {
			
			if (links[l].className == 'authorise_link') {
				
				links[l].onclick = function() {

					var elements = document.getElementsByClassName('authorise');

					for (var i=0; i < elements.length; i++) {
				        elements[i].style.display = 'none';
					}

					transactionId = this.id.substr(15);

					document.getElementById('authorise_' + transactionId).style.display = 'table-row';
				}
			}
		}
	}
});
