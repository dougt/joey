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

    data = getDataById(uploadId);
    data.printByColumn()

    for x in getDataById(uploadId):
        if x.source is None:
            transcodeByUploadData(x)
        else:
            updateByUploadData(x)

def transcodeByUploadData(data):
    return 0

def updateByUploadData(data):
    if (data.name == 'rss-source/text'):
        updateRssTypeFromUploadData(data)
    elif (data.name == 'microsummary/xml'):
        updateMicrosummaryTypeFromUploadData(data)
    elif (data.name == 'widget/joey'):
        updateJoeyWidgetTypeFromUploadData(data)
    else:
        print >>standardError, "Attempt to upload unsupported type (%s)" % data.name

def updateRssTypeFromUploadData(data):
    print "update rss"
    return 0

def updateMicrosummaryTypeFromUploadData(data):
    print "update microsummary"
    return 0

def updateJoeyWidgetTypeFromUploadData(data):
    print "update joey widget"
    return 0

def getDataById(id):
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


    # Where stuff actually happens
    processByUploadId(10)
        
  except KeyboardInterrupt:
    print >>standardError, "Interrupted..."
    pass
  
  except Exception, x:
    print >>standardError, x
    traceback.print_exc(file=standardError)

  
  if "verbose" in workingEnvironment: print >>standardError, "done."
