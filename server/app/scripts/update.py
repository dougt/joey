#!/usr/bin/python

import cse.Database
import cse.MySQLDatabase

import sys
import os
import traceback

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
            logMessage("Attempt to transcode unsupported type (%s) for upload id (%d)" % (data.original_type, data.id),1)
        
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
        newFile      = "%s/%d/%s" % (workingEnvironment['UploadDir'], data.user_id, data.name)
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
    def updateByUploadData(self, data):
        logMessage("updating...")
        if (data.name == 'rss-source/text'):
            self._updateRssTypeFromUploadData(data)
        elif (data.name == 'microsummary/xml'):
            self._updateMicrosummaryTypeFromUploadData(data)
        elif (data.name == 'widget/joey'):
            self._updateJoeyWidgetTypeFromUploadData(data)
        else:
            logMessage("Attempt to update unsupported type (%s) for upload id (%d)" % (data.name, data.id),1)

    def _updateRssTypeFromUploadData(self, data):
        logMessage("type=rss...")
        logMessage("success.\n")
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
        query = """
            SELECT * FROM 
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

