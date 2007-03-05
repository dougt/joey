
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
 * The Original Code is Joey Mozilla Project.
 *
 * The Initial Developer of the Original Code is
 * Doug Turner <dougt@meer.net>.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
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
 
// SAMPLE Configure File.

  include('microsummary.php');

  //connect to db
  include('config.php');

  $query = "SELECT id, owner, title, type, content, filename FROM uploads where type='microsummary/xml'";
  $result = mysql_query($query) or die('Error, query failed');

  if(mysql_num_rows($result) == 0)
  {
     echo "There are no Microsummaries to deal with.\n";
     exit;
  }
  
  while($fetched= @mysql_fetch_array($result))
  {
     echo "processing...\n";
     
     $ms_generator = base64_decode($fetched['content']);
     $tmpfname = tempnam ("/tmp", "microsummary");
     $fh = fopen($tmpfname, 'w') or die("can't open file");
     fwrite($fh, $ms_generator);
     fclose($fh);

    // echo $ms_generator;

     $ms = new microsummary();
     $ms->load($tmpfname);

     $ms->execute(base64_decode($fetched['title']));

     echo "Microsummary Result\n";
     echo $ms->result;
     echo ".\n";
     
     // Read in value, if value doesn't match, write out new value.  end.

     $filename = $fetched['filename'];

     if ($filename == "")
     {
       $filename = '/data/uploads/' . $fetched['owner'] . '/' . uniqid();

       $fh = fopen($filename, 'w') or die("can't open file");
       fwrite($fh, "");
       fclose($fh);

       $id = $fetched['id'];
       $updateQuery = "UPDATE uploads SET filename = '$filename' where id = '$id'";
       $result = mysql_query($updateQuery) or die('Error, query failed');
     }

     echo "Transcode file: " . $filename . "\n";

     $lastValue = "";
     $size = filesize($filename);
     
     if ($size > 0)
     {
       $fh = fopen($filename, 'r') or die("can't open transcode file");
       $lastValue = fread($fh, $size);
       fclose($fh);
     }

     if (strcmp($ms->result, $lastValue) != 0)
     {
       echo "UPDATING!\n";

       $fh = fopen($filename, 'w') or die("can't open transcode file");
       fwrite($fh, $ms->result) or die("can't write transcode file");
       fclose($fh);
       
       // notify the user of the change!
       $owner = $fetched['owner'];
       $emailQuery = "SELECT id, email from user where id='$owner'";
       $emailResult = mysql_query($emailQuery);
       
       $fetched= @mysql_fetch_array($emailResult);
       
       if(mysql_num_rows($emailResult) == 0)
       {
         echo "no email for user.\n";
         exit;
       }
       
       if (mail($fetched['email'], $ms->result, $ms->result)) 
       {
         echo("Message successfully sent!\n");
       } 
       else
       {
         echo("Message delivery failed...\n");
       }
     }
  }

  echo "done.";

?>

