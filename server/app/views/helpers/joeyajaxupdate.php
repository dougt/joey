<?php 
/**
 *  Marcio updater helper 
 */
class JoeyajaxupdateHelper extends Helper 
{
	var $ajaxLinkOptions = array();
	var $style = 'html';
	var $paramStyle = 'get';

	var $helpers = Array("Html","Ajax","Javascript" );


    function renderMarkup($pagelimit,$pagetype,$pagepage,$command) {

		return "<div id='joeyEvent' style='display:none'  >$pagepage,$pagelimit,$pagetype,$command</div>";
    }

}
?>
