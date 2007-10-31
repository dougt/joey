/////
////
/// Joey Client library - used so far with the Welcome page 
//  

function button(refObject) {
	refObject.parentNode.style.background="url(/img/welcome/button3.gif) no-repeat 0 0 ";
}
function buttonOut(refObject) {
	refObject.parentNode.style.background="url(/img/welcome/button2.gif) no-repeat 0 0";

}

var allPanels = ['panel1','panel2','panel3'];

function panel(refPanel) {

	for( var i=0;i<3;i++) {
		var currentPanel = allPanels[i];
		if(currentPanel==refPanel) {

			document.getElementById(currentPanel).style.display="block";

		} else {
			document.getElementById(currentPanel).style.display="none";

		}

	}
}


document.body.addEventListener("DOMNodeInserted",joeyMutation,false);

function joeyMutation(e) {

       var eventNode = e.target;

	var currentCommand= eventNode.getAttribute("class").toString();

	if( currentCommand.indexOf("joeyCallback#joeyIsHere")>-1) {
		showGuidedPanel();
		sniffSelection();
	}

	if( currentCommand.indexOf("joeyCallback#node=")>-1) {
		showCongrats();
	}
	if( currentCommand.indexOf("joeyCallback#action=help")>-1) {
		showHelp();
	}

}

function showGuidedPanel() {

	panel("panel1");
	document.getElementById("button-guided").style.display="block";

}

function showCongrats() {

        document.getElementById("guidedTour").style.display="none";
        document.getElementById("guidedTourPass").style.display="block";

}

function showHelp() {

        document.getElementById("guidedTour").style.display="none";
        document.getElementById("guidedTourHelp").style.display="block";

}

var gPassed1 = false;

function sniffSelection() {


	var probeStr = document.getSelection();
	var checkStr = "** Send me to Joey **";

	try { 

		if(probeStr!="") {
		if(checkStr.indexOf(probeStr)>-1) {

			pass2();
			gPassed1=true;

		}
		}

	} catch(i) {} 
	
	if(!gPassed1) {
		setTimeout("sniffSelection()",3000);
	}

}

function pass2() {

	document.getElementById("pass1").style.display="block";

	var joeyTalkBit = document.createElement("div");

	document.body.appendChild(joeyTalkBit);

	joeyTalkBit.innerHTML="<div class='joeyAction#joeySelected?id=123'></div>";


}

gCurrentPanel="pass0";
gFadeCounter = 0;

function fadeIn() {

	if(gFadeCounter<100) {


	}

}


