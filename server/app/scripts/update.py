#!/usr/bin/python

import cse.Database
import cse.MySQLDatabase

import sys
import os
import traceback

import re
import codecs
import urllib

import feedparser

version = "0.1"

standardError = sys.stderr

#---------------------------------------------------------------------------------------------------
# processByUploadId
#---------------------------------------------------------------------------------------------------
def processByUploadId (uploadId):
    if type(uploadId) != int:
        logMessage("processByUploadId: Invalid input (%s): not a number" % uploadId)
        return

    logMessage("Processing upload id (%d)..." % uploadId)

    for x in Upload.getDataById(uploadId):

        uploadDir = os.path.join(workingEnvironment['UploadDir'], str(x.user_id))

        if not os.access(uploadDir, os.R_OK|os.W_OK):
          logMessage("Upload directory (%s) is not readable or writable, failing.\n" % (uploadDir, uploadId),1)
          return

        if x.source is None:
            Transcode.transcodeByUploadData(x)
        else:
            Update.updateByUploadData(x)

#---------------------------------------------------------------------------------------------------
# Transcode Class
#---------------------------------------------------------------------------------------------------
class Transcode:

    def transcodeByUploadData(self, data):
        logMessage("transcoding...");

        if (data.original_type in ["audio/x-wav","audio/mpeg","audio/mid","audio/amr"]):
            self._transcodeAudio(data)
        elif (data.original_type in ["browser/stuff"]):
            self._transcodeBrowserStuff(data)
        elif (data.original_type in ["image/png","image/jpeg","image/tiff","image/bmp","image/gif"]):
            self._transcodeImage(data)
        elif (data.original_type in ["text/plain"]):
            self._transcodeText(data)
        elif (data.original_type in ["video/3gpp","video/flv","video/mpeg","video/avi","video/quicktime"]):
            self._transcodeVideo(data)
        else:
            logMessage("Attempt to transcode unsupported type (%s) for upload id (%d)" % (data.original_type, data.upload_id),1)
        
        return 0

    def _transcodeAudio(self, data):
        logMessage("type=audio...")
        logMessage("success.\n")
        return 0

    def _transcodeBrowserStuff(self, data):
        logMessage("type=browserstuff...")
        logMessage("success.\n")
        return 0

    def _transcodeImage(self, data):
        logMessage("type=image...")
        logMessage("success.\n")
        return 0

    def _transcodeImageAndPreview(self, data):
        print "   Transcoding image and preview."
        return 0

    def _transcodeText(self, data):
        logMessage("type=text...")
        originalFile = "%s/%d/originals/%s" % (workingEnvironment['UploadDir'], data.user_id, data.original_name)
        newFile      = "%s/%d/%s" % (workingEnvironment['UploadDir'], data.user_id, data.file_name)
        os.system("cp %s %s" % (originalFile, newFile))
        if not os.path.isfile(newFile): 
            logMessage("failure.\n")
            return 1
        logMessage("success.\n")
        return 0

    def _transcodeVideo(self, data):
        logMessage("type=video...")
        logMessage("success.\n")
        return 0


#---------------------------------------------------------------------------------------------------
# Update Class
#---------------------------------------------------------------------------------------------------
class Update:

    def _setTitleInDB(self, id, title):

        query = """
            UPDATE
                uploads
            SET
                title = '%s'
           WHERE
                id = '%d' """ % (title, id)

        database.executeSql(query)
        
        #todo move out of here
        database.commit()

    def _updateFileSizesInDB(self, data):

        originalFile = "%s/%d/originals/%s" % (workingEnvironment['UploadDir'], data.user_id, data.original_name)
        newFile      = "%s/%d/%s" % (workingEnvironment['UploadDir'], data.user_id, data.file_name)

        size = os.path.getsize(newFile)
        originalsize = os.path.getsize(originalFile)

        query = """
            UPDATE
                files
            SET
                size = %s,
                original_size = %s

           WHERE
                id = '%d' """ % (size, originalsize, data.file_id)

        database.executeSql(query)
        
        #todo move out of here
        database.commit()
        

    def _buildRssOutput(self, feed):
        
        output = "<h2>Channel Title: " +feed[ "channel" ][ "title" ]+ "</h2>"

        output = output + "<dl>"

        entries = feed.entries
        for entry in entries:
            if hasattr(entry, "link"):
                output = output + "<dt><a href=\""+entry['link']+"\">"+entry['title']+"</a></dt>"
            else:
                output = output + "<dt>"+entry['title']+"</dt>"

            output = output + "<dd>" + entry['description'] + "</dd>"
        
        output = output + "</dl>"

        return output

    def updateByUploadData(self, data):
        logMessage("updating...")

        if (data.contentsourcetype_name == 'rss-source/text'):
            self._updateRssTypeFromUploadData(data)
        elif (data.contentsourcetype_name == 'microsummary/xml'):
            self._updateMicrosummaryTypeFromUploadData(data)
        elif (data.contentsourcetype_name == 'widget/joey'):
            self._updateJoeyWidgetTypeFromUploadData(data)
        else:
            logMessage("Attempt to update unsupported type (%s) for upload id (%d)" % (data.contentsourcetype_name, data.upload_id) ,1)

    def _updateRssTypeFromUploadData(self, data):

        originalFile = "%s/%d/originals/%s" % (workingEnvironment['UploadDir'], data.user_id, data.original_name)
        newFile      = "%s/%d/%s" % (workingEnvironment['UploadDir'], data.user_id, data.file_name)

        #@todo, re needs to be able to just return the value in (.*).

        # parse out the rss url.
        rss_url = re.compile('rss=(.*)\r\n').search(data.source).group()
        rss_url = rss_url.replace('rss=', '')
        
        # parse out the optional icon url
        try:
            ico_url = re.compile('icon=(.*)\r\n').search(data.source).group()
            ico_url = ico_url.replace('icon=', '')
        except:
            # no url for the icon
            logMessage("no icon url to process for upload id (%d)" %(data.upload_id),1)


        # fetch the RSS Source
        file = urllib.urlopen(rss_url)
        source = file.read()

        # default error output.
        output = "RSS currently doesn't exist for this upload. Try again later.";

        d = feedparser.parse(source)

        # save / update the title of the upload.
        title = d['feed']['title'];
        self._setTitleInDB(data.upload_id, title)


        if hasattr(d.entries[0], "enclosures"):

            print "enclosures found"

        else:
            # generate the RSS output that we want to show people
            output = self._buildRssOutput(d)

            try:
                # copy the |source| to the original file
                out = open(originalFile, 'w+')
                out.write(source)
                out.close()

                # copy that to the actual file
                out = codecs.open(newFile, encoding='utf-8', mode='w+')
                out.write(output)
                out.close()

                self._updateFileSizesInDB(data)

            except:
                #look at this mess.  what happened to simply being able to get the exception passed to you?
                msg = "ERROR:\n" + traceback.format_tb(sys.exc_info()[2])[0] + "\nError Info:\n    " + str(sys.exc_type)+ ": " + str(sys.exc_value) + "\n"
                logMessage("Problem updating files on disk for upload id (%d):\n%s" %(data.upload_id, msg),1)

        print "updated rss"
        return 0

    def _updateMicrosummaryTypeFromUploadData(self, data):
        print "update microsummary"
        logMessage("type=microsummary...")
        logMessage("success.\n")
        return 0

    def _updateJoeyWidgetTypeFromUploadData(self, data):
        logMessage("type=widget...")
        logMessage("success.\n")
        return 0


#---------------------------------------------------------------------------------------------------
# Upload Class
#---------------------------------------------------------------------------------------------------
class Upload:
    def getDataById(self, id):
        if type(id) != int:
            print >>standardError, "Invalid input (%s): not a number" % id
            sys.exit();
        # CSE has a known issue about selecting columns with the same name across multiple tables.  We'll have to
        # manually specify the column names we need.
        query = """
            SELECT
                uploads_users.user_id,
                Upload.id as upload_id,
                Upload.title as upload_title,
                Upload.referrer as upload_referrer,
                Upload.deleted as upload_deleted,
                Upload.ever_updated,
                File.id as file_id,
                File.name as file_name,
                File.size as file_size,
                File.type as file_type,
                File.original_name,
                File.original_size,
                File.original_type,
                File.preview_name,
                File.preview_size,
                File.preview_type,
                File.modified as file_modified,
                Contentsource.source,
                Contentsourcetype.name as contentsourcetype_name
            FROM 
            uploads_users
            JOIN uploads as Upload ON uploads_users.upload_id = Upload.id
            LEFT JOIN files as File ON Upload.id = File.upload_id
            LEFT JOIN contentsources as Contentsource ON File.id = Contentsource.file_id
            LEFT JOIN contentsourcetypes as Contentsourcetype ON Contentsource.contentsourcetype_id = Contentsourcetype.id
            WHERE uploads_users.upload_id = '%d' """ % id

        return database.executeSql(query)


#---------------------------------------------------------------------------------------------------
# logMessage: Helper function for logging
#---------------------------------------------------------------------------------------------------
def logMessage (msg, error=0):
    if "verbose" in workingEnvironment:
        LogFile = open(workingEnvironment["logPathName"], "a")
        LogFile.write(msg)
        LogFile.close()

    if not error == 0:
        print >>standardError, msg


#===========================================================================================================
# main
#===========================================================================================================
if __name__ == "__main__":

  import cse.ConfigurationManager
  
  try:
  
    options = [ ('?',  'help', False, None, 'print this message'), 
                ('c',  'config', True, './update.conf', 'specify the location and name of the config file'),
                (None, 'DatabaseName', True, "", 'the name of the database within the server'),
                (None, 'ServerName', True, "", 'the name of the database server'),
                (None, 'UserName', True, "", 'the name of the user in the database'),
                (None, 'Password', True, "", 'the password for the user in the database'),
                (None, 'logPathName', True, "./update.log", 'a progressive log of all runs of the update script'),
                (None, 'UploadDir', True, "", 'Where are all the uploads stored?'),
                ('v',  'verbose', False, None, 'print status information as it runs to stderr'),
              ]
    
    workingEnvironment = cse.ConfigurationManager.ConfigurationManager(options)
    
  except cse.ConfigurationManager.ConfigurationManagerNotAnOption, x:
    print >>standardError, "m1 %s\n%s\nFor usage, try --help" % (version, x)
    sys.exit()
  
    
  try:

    logMessage("Beginning update version %s\n" % (version))

    if not os.access(workingEnvironment["UploadDir"], os.R_OK|os.W_OK):
      logMessage("Upload directory (%s) is not readable or writable, exiting.\n" % workingEnvironment['UploadDir'],1)
      sys.exit()

    database = cse.MySQLDatabase.MySQLDatabase(workingEnvironment["DatabaseName"], workingEnvironment["ServerName"], 
                                                  workingEnvironment["UserName"], workingEnvironment["Password"])

    Transcode = Transcode();
    Update = Update();
    Upload = Upload();

    # Where stuff actually happens
    processByUploadId(18)

        
  except KeyboardInterrupt:
    print >>standardError, "Interrupted..."
    pass
  
  except Exception, x:
    print >>standardError, x
    traceback.print_exc(file=standardError)

