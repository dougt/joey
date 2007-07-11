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
 *
 * The Initial Developer of the Original Code is
 * The Mozilla Foundation.
 * Portions created by the Initial Developer are Copyright (C) 2006
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Wil Clouser <clouserw@mozilla.com>
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

vendor('microsummary');
vendor('joeywidget');
vendor('magpierss/rss_fetch.inc');

uses('sanitize');

/**
 * A component for anything relating to transcoding files
 */
class UpdateComponent extends Object
{
    /**
     * Will hold cake's sanitize object
     * @var object
     * @access private
     */
    var $_sanitize;

    var $components = array('Storage', 'Transcode');

  /**
   * Save a reference to the controller on startup
   * @param object &$controller the controller using this component
   */
  function startup(&$controller) {
      $this->controller =& $controller;

      $this->_sanitize = new Sanitize();
  }

  /**
   * A private function which will build HTML output when given a MagPieRSS
   * object, prefilled with data.
   *
   * @todo Use a view for this?
   * @param object MagPieRSS object with data fetched
   * @return string html output of RSS feed
   */
  function _buildRssOutput($rss) {

      $_title = $this->_sanitize->html($rss->channel['title']);

      $_output = "<h2>Channel Title: {$_title}</h2>";
      $_output .= "<dl>";

      foreach ($rss->items as $item) {
          $_title = $this->_sanitize->html($item['title']);
          $_link = $this->_sanitize->html($item['link']);
          $_description = $this->_sanitize->html($item['description']);

          if (!empty($_link)) {
              $_output .= "<dt><a href=\"{$_link}\">{$_title}</a></dt>";
          } else {
              $_output .= "<dt>{$_title}</dt>";
          }

          $_output .= "<dd>{$_description}</dd>";
      }

      $_output .= '</dl>';

      return $_output;
  }

  /**
   * Retrieves the contents of a URL.  Note: No error handling.  
   *
   * @param string URL to retrieve
   * @return string contents of URL
   */
  function fetchURL($url)
  {
      $useragent = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.4) Gecko/20070515 Firefox/2.0.0.4";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
      curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 

      $result = curl_exec($ch);

      curl_close($ch);
      return $result;
  }

  /**
   * Given an upload id, this will update the file from it's associated content
   * source.  (Obviously, this only works if there is a contentsource for the
   * upload).
   *
   * @param int ID of the Upload that is associated with the file to update
   * @param boolean force an update?
   * @return boolean true on success, false on failure
   */
    function updateContentSourceByUploadId($id, $force) {

        $_upload = $this->controller->Upload->FindDataById($id);

        // If the upload doesn't have a contentsource, this function is useless
        if (empty($_upload['Contentsourcetype']['name']) || empty($_upload['Contentsource']['source'])) {
            return false;
        }

        // If a file doesn't exist yet, we'll create one so we can update it
        if (empty($_upload['File'])) {
            if ($this->createFileForUploadId($id, $_upload['Contentsourcetype']['name']) == false) {
                return false;
            }
      
            $this->controller->Upload->cacheQueries = false;
            $_upload = $this->controller->Upload->FindDataById($id);
            $this->controller->Upload->cacheQueries = true;
        }

        // If we're not forcing an update, check if enough time has passed
        if (!$force) {
            $_expire_time = strtotime($_upload['File']['modified'] . " + " . CONTENTSOURCE_REFRESH_TIME . " minutes");

            if (($_expire_time === false) || $_expire_time > strtotime('now')) {
                return true; //  don't process anything just yet.
            }
        }

        switch ($_upload['Contentsourcetype']['name']) {
            case 'rss-source/text':
                if (!$this->_updateRssType($_upload)) {
                    return false;
                }
                break;

            case 'microsummary/xml':
                if (!$this->_updateMicrosummaryType($_upload)) {
                    return false;
                }
                break;
            case 'widget/joey':
                if (!$this->_updateJoeyWidgetType($_upload)) {
                    return false;
                }
                break;

            // We don't support whatever they're trying to update.  :(
            default:
                return false;
        }

        return true;
        
    }

    /**
     * A private function for updating a file which is an RSS type
     * @param array a complete array from Upload::FindDataById()
     * @return boolean true on success, false on failure
     */
    function _updateRssType($upload) {

        $_owner = $this->controller->Upload->findOwnerDataFromUploadId($upload['Upload']['id']);
        $_filename = UPLOAD_DIR."/{$_owner['User']['id']}/{$upload['File']['name']}";
        $_originalname = UPLOAD_DIR."/{$_owner['User']['id']}/originals/{$upload['File']['original_name']}";
        $_previewname = UPLOAD_DIR."/{$_owner['User']['id']}/previews/{$upload['File']['preview_name']}";

        // Parse out the two parts of an rss content source: the rss url, and the icon url.
        preg_match("/rss=(.*)\r\n/", $upload['Contentsource']['source'], $_rss_url);
        preg_match("/icon=(.*)\r\n/", $upload['Contentsource']['source'], $_icon_url);

        $_rss_url = $_rss_url[1];

        // the icon url is optional.
        if (!empty($_icon_url))
            $_icon_url = $_icon_url[1];
        else
            unset($_icon_url);

        if (($result = $this->fetchURL($_rss_url)) == false) {
            return false;
        }

        // default error output.
        $output = "RSS currently doesn't exist for this upload. Try again later.";

        $rss = new MagpieRSS( $result );
        if ( !$rss or $rss->ERROR)
            return false;

        $_title = $rss->channel['title'];

        $this->controller->Upload->id = $upload['Upload']['id'];
        $this->controller->Upload->saveField('title', $_title);

        // Okay, check to see if this is a podcast (something
        // that contains a audio enclosure).  If it is, then we
        // treat it as updatable media file.
        if ($rss->incontent == "enclosure"){
            $this->updateRssEnclosureForUpload($upload, $rss);
        }

        if (!file_put_contents($_originalname, $result)) {
            $this->controller->Error->addError("Failed to write original file ({$_originalname})", 'general', false, true);
            return false;
        }

        if (!file_put_contents($_filename, $this->_buildRssOutput($rss))) {
            $this->controller->Error->addError("Failed to write file ({$_filename})", 'general', false, true);
            return false;
        }

        // If there is no preview, make one
        if (!file_exists($_previewname)) {
            if (empty($_icon_url)) {
                $_icon_url = $this->getIconUrlForUrl($_rss_url);
            }

            $this->savePreviewForUploadFromUrl($upload, $_icon_url);
        }

        $this->controller->File->id = $upload['File']['id'];
        $this->controller->File->saveField('size',filesize($_filename));
        $this->controller->File->saveField('original_size',filesize($_originalname));

        return true;
    }

    /**
     * Given the URL for a webpage, this will attempt to find a favicon for
     * the site.  If found, it will return a URL to the favicon.  If not
     * found, it will return an empty string.
     *
     * @param string URL to look for preview
     * @return string image data or empty
     */
    function getIconUrlForUrl($url) {

        $_href = '';

        if (empty($url)) {
            return $_href;
        }

        $_guess = parse_url($url, PHP_URL_HOST);
        $_result = $this->fetchURL($_guess);

        // load into new dom document, and look for something that looks like:
        // <link rel="icon" href="/favicon.ico"
        $_d = new DOMDocument();
        $_d->preserveWhiteSpace = false;
        $_d->resolveExternals = true; // for character entities

        $_d->loadHTML($_result);
        
        $_links = $_d->getElementsByTagName('link');

        foreach ($_links as $_link) {
            if ($_link->getAttribute('rel')) {
                $_href = $_guess . $_link->getAttribute('href');
            }
        }

        return $_href;
    }

    /**
     * Given an upload array and a URL, this function will retrieve whatever
     * is at the URL and attempt to make it into a preview image.
     *
     * @param array a complete array from Upload::FindDataById()
     * @param string URL that contains preview data
     * @return boolean true on success, false on failure
     */
    function savePreviewForUploadFromUrl($upload, $url) {

        $_owner = $this->controller->Upload->findOwnerDataFromUploadId($upload['Upload']['id']);
        $_previewname = UPLOAD_DIR."/{$_owner['User']['id']}/previews/{$upload['File']['preview_name']}";

        // Grab the icon content.
        if (($_result = $this->fetchURL($url)) == false) {
            return false;
        }

        // figure out the extension of the favicon
        $_extension = pathinfo($url, PATHINFO_EXTENSION);
        $_extension = empty($_extension) ? 'tmp' : $_extension;

        $_tempname = UPLOAD_DIR."/cache/{$upload['File']['preview_name']}.{$_extension}";

        if (!file_put_contents($_tempname, $_result)) {
            $this->controller->Error->addError("Failed to write temp file ({$_tempname})", 'general', false, true);
            return false;
        }

        if (!$this->Transcode->transcodeImage("{$_extension}:{$_tempname}", $_previewname, 16, 16)) {
            unlink($_tempname);
            return false;
        }

        unlink($_tempname);

        $this->controller->File->id = $upload['File']['id'];
        $this->controller->File->saveField('preview_size',filesize($_previewname));

        return true;

    }

    /**
     * Given an upload array and a MagPieRSS Object that is an enclosure, this
     * will retrieve the enclosed file for the upload. (eg. a podcast contains
     * audio enclosures)
     *
     * @todo this function assumes the enclosure is audio (bug 387729)
     * @param array a complete array from Upload::FindDataById()
     * @return boolean true on success, false on failure OR no update
     */
    function updateRssEnclosureForUpload($upload, $rss) {

        $_owner = $this->controller->Upload->findOwnerDataFromUploadId($upload['Upload']['id']);
        $_filename = UPLOAD_DIR."/{$_owner['User']['id']}/{$upload['File']['name']}";
        $_originalname = UPLOAD_DIR."/{$_owner['User']['id']}/originals/{$upload['File']['original_name']}";

        if (!$rss->incontent == 'enclosure') {
            return false;
        }

        $_lastpublished = 0;

        // Find the most recent date
        foreach ($rss->items as $item) {
            // the RSS pubDate must be an RFC-822 date-time
            $_pubdate = strtotime($item['pubdate']);

            if ($_pubdate === false) {
                return false;
            }

            if ($_pubdate > $_lastpublished || $_lastpublished == 0) {
                $_lastpublished = $_pubdate;
                $_podcast = $item['enclosure'][0];
            }
        }

        // If no change since last update, we're done
        if (file_exists($_filename) && filemtime($_filename) < $_lastpublished) {
            return false;
        }

        if (($_output = $this->fetchURL($_podcast['url'])) == false) {
            return false;
        }

        if (!array_key_exists($_podcast['url'], $this->Storage->suffix)) {
            $this->controller->Error->addError("Attempt to save unsupported RSS enclosure type ({$_podcast['url']})", 'general', false, true);
            return false;
        }

        if (!file_put_contents($_originalname, $_output)) {
            $this->controller->Error->addError("Failed to write original file ({$_originalname})", 'general', false, true);
        }

        // @todo - I don't see why this is useful, so I'm leaving it commented out.  Should probably revisit this after testing. :)
        //$_orignalname = str_replace(".png", "." . $this->suffix[ $podcast['type']], $_orignalname);

        // Remove the old file and s/rss/amr/ for the new filename
        unlink($_filename);
        $_filename = str_replace(".rss", ".amr", $_filename);

        if (!$this->Transcode->transcodeAudio($_originalname, $_filename)) {
            return false;
        }

        $this->controller->File->id = $upload['File']['id'];

        $this->controller->File->saveField('size', filesize($_filename));
        $this->controller->File->saveField('name', basename($_filename));
        $this->controller->File->saveField('type',"audio/amr");

        $this->controller->File->saveField('original_size',filesize($_orignalname));
        $this->controller->File->saveField('original_name',basename($_orignalname));
        $this->controller->File->saveField('original_type', $podcast['type']);

        $this->controller->File->saveField('preview_name', "");      

        return true;
    }

    /**
     * A private function for updating a file which is a Microsummary type
     * @param array a complete array from Upload::FindDataById()
     * @return boolean true on success, false on failure
     */
    function _updateMicrosummaryType($upload) {
        $_ms = new microsummary();
        $_ms->load($upload['Contentsource']['source']);
        $_rv = $_ms->execute($upload['Upload']['referrer'], true);

        $_owner = $this->controller->Upload->findOwnerDataFromUploadId($upload['Upload']['id']);
        $_filename = UPLOAD_DIR."/{$_owner['User']['id']}/{$upload['File']['name']}";

        if ($_rv == 2)
        {
            // The XPATH has been updated based on the
            // hint passed.  Lets save this new content
            // source to the database

            $updated_source =  $_ms->save();

            // save in db:

            $this->controller->Contentsource->id = $upload['Contentsource']['id'];
            $this->controller->Contentsource->saveField('source', $updated_source);

        }

        if (empty($_ms->result)) {
            $_ms->result = "XPATH is broken..  this feature doesn't work for the content you have selected. ";
            $this->controller->Error->addError("Microsummary ({$upload['Contentsource']['id']}) does not have an xpath result");
        }

        // does the user have enough space to proceed
        if (!$this->controller->User->hasAvailableSpace($_owner['User']['id'], strlen($_ms->result) - filesize($_filename))) {
            $this->controller->Error->addError('Not enough space to update');
            return false;
        }

        // write the file.
        if (!file_put_contents($_filename, $_ms->result)) {
            $this->controller->Error->addError("Failed to write file ({$_filename})", 'general', false, true);
            return false;
        }

        // need to update the size and date in the db.
        $this->controller->File->id = $upload['File']['id'];
        $this->controller->File->saveField('size',filesize($_filename));
        return true;
    }

    /**
     * A private function for updating a file which is a Joey Widget type
     * @param array a complete array from Upload::FindDataById()
     * @return boolean true on success, false on failure
     */
    function _updateJoeyWidgetType($upload) {

        $_jw = new joeywidget();
        $_jw->load($upload['Contentsource']['source']);

        $_owner = $this->controller->Upload->findOwnerDataFromUploadId($upload['Upload']['id']);
        $_filename = UPLOAD_DIR."/{$_owner['User']['id']}/{$upload['File']['name']}";
        $_previewname = UPLOAD_DIR."/{$_owner['User']['id']}/previews/{$upload['File']['preview_name']}";

        if (!$this->controller->User->hasAvailableSpace($_owner['User']['id'], strlen($_jw->content) + strlen($_jw->preview) - filesize($_filename) - filesize($_previewname))) {
            $this->controller->Error->addError('Not enough space to update');
        }

        // write the file.
        if (!file_put_contents($_filename, $_jw->content)) {
            $this->controller->Error->addError("Failed to write file ({$_filename})", 'general', false, true);
            return false;
        }

        if (!file_put_contents($_previewname, $_jw->preview)) {
            $this->controller->Error->addError("Failed to write preview file ({$_previewname})", 'general', false, true);
            return false;
        }

        // need to update the size and date in the db.
        $this->controller->File->id = $upload['File']['id'];
        $this->controller->File->saveField('size',filesize($_filename));
        $this->controller->File->saveField('preview_size',filesize($_previewname));

        return true;
    }

}
?>
