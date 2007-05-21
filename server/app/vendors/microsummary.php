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
  var $hintXPATH;
  var $hint;

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

    // read the hint in
    $hintelm = $xpath->query('/ms:generator/ms:hint')->item(0);

    if (!empty($hintelm->nodeValue))
      $this->hint = $this->fromXMLString($hintelm->nodeValue);
    else
      $this->hint = "";

    $this->hintXPATH = "";
  }

  function save()
  {
    if ($this->hintXPATH != "")
    {
      $valueof = $this->msdoc->getElementsByTagName("value-of");
      $valueof->item(0)->removeAttribute("select");
      $valueof->item(0)->setAttribute("select", $this->hintXPATH);
    }

    $result = $this->msdoc->saveXML();
    return $result;
  }

  function fromXMLString($in)
  {
    $out = str_replace ("&amp;", "&", $in);
    $out = str_replace ("&gt;",  ">", $out);
    $out = str_replace ("&lt;",  "<", $out);
    $out = str_replace ("&apos;","\'", $out);
    $out = str_replace ("&quot;","\"", $out);
    return $out;
  }

   function getNodePath($in_node)
   {
     if (!$in_node) {
       return null;
     }
     
     $buffer = '';
     $cur = $in_node;
     do {

       // print xpath as we go:
       //echo $buffer . "\n";
       
       $name = '';
       $sep = '/';
       $occur = 0;
       if (($type = $cur->nodeType) == XML_DOCUMENT_NODE) {
         if ($buffer[0] == '/') {
           break;
         }

       }
       else if ($type == XML_ATTRIBUTE_NODE) {
         $sep .= '@';
         $name = $cur->nodeName;
         $next = $cur->parentNode;
       }
       else {
         
         if ($type == XML_ELEMENT_NODE) {

           $id = $cur->getAttribute('id');
           if (!empty($id)) {
             
             if ($id == "microsummary_hint_id") {
               $ignore = true;
             }
             else {
               
               // Found a id.  Lets use that as the base reference.

               if (empty($buffer))
                 return "id('".$id."')";

               $buffer = "id('".$id."')" . $buffer;
               return $buffer;  // We are done.
             }
           }
         }
         
         $name = $cur->nodeName;
         $next = $cur->parentNode;
         
         // now figure out the index
         $tmp = $cur->previousSibling;
         while ($tmp != false) {
           if ($name == $tmp->nodeName) {
             $occur++;
           }
           $tmp = $tmp->previousSibling;
         }

         $occur++;
         
         if ($type == XML_ELEMENT_NODE) {
           $name = $name;  //???
         }
         
         // fix the names for those nodes where xpath query and dom node name don't match
         elseif ($type == XML_COMMENT_NODE) {
           $ignore = true;
           $name = 'comment()';
         }
         elseif ($type == XML_PI_NODE) {
           $ignore = true;
           $name = 'processing-instruction()';
         }
         elseif ($type == XML_TEXT_NODE) {
           $ignore = true;
           $name = 'text()';
         }
         // anything left here has not been coded yet (cdata is broken)
         else {
           $name = '';
           $sep = '';
           $occur = 0;
         }
       }
       if ($ignore == true) {
       }
       else if ($occur == 0) {
         $buffer = $sep . $name . $buffer;
       }
       else {
         $buffer = $sep . $name . '[' . $occur . ']' . $buffer;
       }
       $ignore = false;
       
       $cur = $next;
       
     } while ($cur != false);
     
     return $buffer;
   }

   function execute($applyTo, $useHint) {

    $rv = 1;

    // fetch
    if (!$str = $this->fetch($applyTo))
    {
       die("unable to fetch $applyTo");
    }

    //    $str = str_replace("<head>",
    //                       "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>", $str);

    //    $str = str_replace("charset=iso-8859-1", "charset=utf-8", $str);
    $str = str_replace("", "", $str);

    // load into new dom document
    $d = new DOMDocument();
    $d->preserveWhiteSpace = false;
    $d->resolveExternals = true; // for character entities
    //    $d->strictErrorChecking = false;
    @ $d->loadHTML($str);

    /*

    // This is the traditional way of creating a
    // microsummary.  However, the result is just text.
    // What we want is something richer.  For how, we are
    // simply going to get the XML/HTML pointed at by the
    // select XPATH.

    $xpath = new DOMXPath($this->msdoc);

    // register default ns
    $namespace = $xpath->evaluate('namespace-uri(//*)'); // returns the namespace uri
    $xpath->registerNamespace("ms", $namespace); // sets the prefix "ms" for the default namespace

    // register xsl namespace
    $xpath->registerNamespace("xsl", "http://www.w3.org/1999/XSL/Transform"); // sets the prefix "ms" for the default namespace

    // instantiate xsl processor
    $xslt = new xsltProcessor;
    $xslt->importStyleSheet($this->xsldoc);

    // execute xsl against it
    $summary = $xslt->transformToXML($d);

    */

    $valueof = $this->msdoc->getElementsByTagName("value-of");
    $select = $valueof->item(0)->getAttribute("select");

    //    $select = "id(\"cnn_t1hd\")";


    $docXpath = new DOMXPath($d);
    $docXpath->registerNamespace("html", "http://www.w3.org/1999/xhtml");
    $result = $docXpath->query($select);
    $summary = "";


    if ($result)
    {
      $node = $result->item(0);

      if ($node && $node->ownerDocument)
      {
        $summary = "<body>" . $node->ownerDocument->saveXML($node) . "</body>";
      }
    }

    if ($summary == "" && $useHint == true)
    {
      echo "Not Found:\n";
      echo $select;


      //      $f = fopen("/home/dougt/dump", "w");
      //      fwrite ($f, $str);
      //      fclose($f);
      

      return;  // Lets ignore the hint for now.

      $rv = 0;


      //$query = "//*[.='test']";  ??

      // need a smarter way from finding a hint (innerHTML)
      // in the page.

      if (!strstr($str, $this->hint))
      {
        // So there wasn't a direct match.  Lets try
        // checking to see if the hint needs to be massaged
        // into something more php friendly, or less like
        // Firefox.
        //
        // Currently we only need to kill any closing </li>
        //
        // TODO, we should make these tags optional in the
        // search.  
        
        $this->hint = str_replace("</li>", "", $this->hint);


        // TODO: We have to turn the hint into a pattern
        //        (escape it), then we can use something
        //        like:
        //  preg_match ( string pattern, string subject);


        // TODO: entities: &#39; for '


        if (!strstr($str, $this->hint))
        {
          // can't figure out what to do.  No updating; no microsummaries

          
          //          echo $this->hint;
          //          $f = fopen("/home/dougt/dump", w);
          //          fwrite ($f, $str);
          //          fclose($f);

          echo "failing here";
          return -1;
        }

      }

      //      echo $str;

      $str = str_ireplace ($this->hint,
                           "<div id=\"microsummary_hint_id\">" .
                           $this->hint .
                           "</div>", $str, $count);

      $d = new DOMDocument();
      @ $d->loadHTML($str);
      
      $child = $d->getElementById("microsummary_hint_id")->childNodes->item(0);
      $this->hintXPATH = $this->getNodePath($child);

      // Verify the xpath actually worked.

      echo $this->hintXPATH;
      $verify = new DOMXPath($d);
      $result = $verify->query($this->hintXPATH);

      if ($result == null) {
        echo "Generated XPATH could not be found in Document.";
      }
      
      $rv = 2;

      // TODO Refactor so that after an update, it is easy to get the summmary

      $me_serialized = $this->save();
      $ms = new microsummary();
      $ms->load($me_serialized);
      $ms->execute($applyTo, false);
      $summary = $ms->result;

    }

    $this->result = $summary;
    return $rv;
  }


  // curl utility function
  function fetch($url) {
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; FreeBSD i386; en-US; rv:1.2a) Gecko/20021021");
    curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset:utf-8')); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
      return false;
    }
    curl_close($ch);
    
    return $result;
  }

}

?>