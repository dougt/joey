<?php

/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Microsummary Engine.
 *
 * The Initial Developer of the Original Code is
 * Dietrich Ayala.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Doug Turner <dougt@meer.net>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

//error_reporting(E_ERROR);
//ini_set('display_errors', true);

class microsummary {
  var $msdoc; // microsummary dom document
  var $xsldoc; // dom document of stylesheet embedded in the generator
  var $result;

  // load and parse a microsummary generator file
  function load($generator) {
  
    $this->msdoc = new DOMDocument();
    $this->msdoc->loadXML($generator);

    // get pages
    $xpath = new DOMXPath($this->msdoc);
    // register default ns
    $namespace = $xpath->evaluate('namespace-uri(//*)'); // returns the namespace uri
    $xpath->registerNamespace('ms', $namespace); // sets the prefix "ms" for the default namespace

    // register xsl namespace
    $xpath->registerNamespace("xsl", "http://www.w3.org/1999/XSL/Transform"); // sets the prefix "ms" for the default namespace

    // get template node
    $templateNode = $xpath->query('/ms:generator/ms:template/xsl:transform')->item(0);

    // import stylesheet
    $this->xsldoc = DOMDocument::loadXML($this->msdoc->saveXML($templateNode));
  }

  function execute($applyTo) {

    $xpath = new DOMXPath($this->msdoc);

    // register default ns
    $namespace = $xpath->evaluate('namespace-uri(//*)'); // returns the namespace uri
    $xpath->registerNamespace("ms", $namespace); // sets the prefix "ms" for the default namespace

    // register xsl namespace
    $xpath->registerNamespace("xsl", "http://www.w3.org/1999/XSL/Transform"); // sets the prefix "ms" for the default namespace

    // instantiate xsl processor
    $xslt = new xsltProcessor;
    $xslt->importStyleSheet($this->xsldoc);

    // fetch
    if (!$str = fetch($applyTo))
       die("unable to fetch $applyTo");

    // load into new dom document
    $d = new DOMDocument();
    @ $d->loadHTML($str);

    // execute xsl against it
    $summary = $xslt->transformToXML($d);

    $this->updateResultForURI($applyTo, $summary);
  }

  // save updated results back to file
  // if changed, update db and notify consumers
  function updateResultForURI($uri, $summary) {
  	$this->result = $summary;
  }
}

// curl utility function
function fetch($url) {

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  $result = curl_exec($ch);
  if (curl_errno($ch)) {
    print curl_error($ch);
    return false;
  }
  curl_close($ch);

  return $result;
}

?>