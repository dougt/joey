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
        print >>standardError, "Invalid input (%s): not a number" % uploadId
        sys.exit();

    #@todo - make sure files exist on disk
    for x in Upload.getDataById(uploadId):
        if x.source is None:
            Transcode.transcodeByUploadData(x)
        else:
            Update.updateByUploadData(x)

#---------------------------------------------------------------------------------------------------
# Transcode Class
#---------------------------------------------------------------------------------------------------
class Transcode:

    def transcodeByUploadData(self, data):
        print "Transcoding by upload data..."
# TODO: call _ functions depending on data.original_type
        
        return 0

    def _transcodeAudio(self, data):
        print "   Transcoding audio."
        return 0

    def _transcodeImage(self, data):
        print "   Transcoding image."
        return 0

    def _transcodeImageAndPreview(self, data):
        print "   Transcoding image and preview."
        return 0

    def _transcodeVideo(self, data):
        print "   Transcoding video."
        return 0


#---------------------------------------------------------------------------------------------------
# Update Class
#---------------------------------------------------------------------------------------------------
class Update:
    def updateByUploadData(self, data):
        print "Updating by upload data..."
        if (data.name == 'rss-source/text'):
            self._updateRssTypeFromUploadData(data)
        elif (data.name == 'microsummary/xml'):
            self._updateMicrosummaryTypeFromUploadData(data)
        elif (data.name == 'widget/joey'):
            self._updateJoeyWidgetTypeFromUploadData(data)
        else:
            print >>standardError, "Attempt to update unsupported type (%s)" % data.name

    def _updateRssTypeFromUploadData(self, data):
        print "update rss"
        return 0

    def _updateMicrosummaryTypeFromUploadData(self, data):
        print "update microsummary"
        return 0

    def _updateJoeyWidgetTypeFromUploadData(self, data):
        print "update joey widget"
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
                ('v',  'verbose', False, None, 'print status information as it runs to stderr'),
              ]
    
    workingEnvironment = cse.ConfigurationManager.ConfigurationManager(options)
    
  except cse.ConfigurationManager.ConfigurationManagerNotAnOption, x:
    print >>standardError, "m1 %s\n%s\nFor usage, try --help" % (version, x)
    sys.exit()
  
    
  try:
    useLogFile = open(workingEnvironment["logPathName"], "a")
    workingEnvironment.output(useLogFile)
    useLogFile.close()

    if "verbose" in workingEnvironment: 
      print >>standardError, "Beginning update version %s with options:" % (version)
      workingEnvironment.output(standardError)

    database = cse.MySQLDatabase.MySQLDatabase(workingEnvironment["DatabaseName"], workingEnvironment["ServerName"], 
                                                  workingEnvironment["UserName"], workingEnvironment["Password"])

    Transcode = Transcode();
    Update = Update();
    Upload = Upload();

    # Where stuff actually happens
    processByUploadId(2)

        
  except KeyboardInterrupt:
    print >>standardError, "Interrupted..."
    pass
  
  except Exception, x:
    print >>standardError, x
    traceback.print_exc(file=standardError)

  
  if "verbose" in workingEnvironment: print >>standardError, "done."
