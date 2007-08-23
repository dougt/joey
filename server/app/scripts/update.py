#!/usr/bin/python

import cse.Database
import cse.MySQLDatabase

import sys
import os
import traceback
import time
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

        Database.markUpdated(x)

    Database.commit()


#---------------------------------------------------------------------------------------------------
# Database Class
#---------------------------------------------------------------------------------------------------
class Database:

    def commit(self):
        database.commit()


    def markUpdated(self, data):
        
        query = """
           UPDATE
              uploads
           SET
              ever_updated = 1
           WHERE
             id = '%d' """ % (data.upload_id)

        database.executeSql(query)

    def setTitle(self, id, title):

        query = """
            UPDATE
                uploads
            SET
                title = '%s'
           WHERE
                id = '%d' """ % (title, id)

        database.executeSql(query)

    def changeFileNames(self, data, original, file, preview):

        # todo combined
        if original is not None:
            query =   "UPDATE files SET original_name = '%s' WHERE id='%d'" % ( original, data.file_id )
            database.executeSql(query)

        if file is not None:
            query =   "UPDATE files SET name = '%s' WHERE id='%d'" % ( file, data.file_id )
            database.executeSql(query)

        if preview is not None:
            query =   "UPDATE files SET preview_name = '%s' WHERE id='%d'" % ( preview, data.file_id )
            database.executeSql(query)

    def updateFileSizes(self, data):

        originalFile = "%s/%d/originals/%s" % (workingEnvironment['UploadDir'], data.user_id, data.original_name)
        previewFile  = "%s/%d/previews/%s" % (workingEnvironment['UploadDir'], data.user_id, data.preview_name)
        newFile      = "%s/%d/%s" % (workingEnvironment['UploadDir'], data.user_id, data.file_name)

        originalSize = 0
        previewSize = 0
        newSize = 0

        if os.path.isfile(originalFile):
            originalSize = int(os.path.getsize(originalFile))

        if os.path.isfile(previewFile):
            previewSize = int(os.path.getsize(previewFile))

        if os.path.isfile(newFile):
            newSize = int(os.path.getsize(newFile))

        query = """
            UPDATE
                files
            SET
                size = '%s',
                original_size = '%s',
                preview_size = '%s'
           WHERE
                files.id = '%s'
           LIMIT
                1"""

        database.executeManySql(query, [(newSize, originalSize, previewSize, data.file_id)])
        
    def updateFileTypes(self, data, type, originaltype, previewtype):

        # TODO Combined
        if type is not None:
            query = "UPDATE files SET type = '%s' WHERE id = '%d'" % (type, data.file_id)
            database.executeSql(query)

        if originaltype is not None:
            query = "UPDATE files SET original_type = '%s' WHERE id = '%d'" % (originaltype, data.file_id)
            database.executeSql(query)

        if previewtype is not None:
            query = "UPDATE files SET preview_type = '%s' WHERE id = '%d'" % (previewtype, data.file_id)
            database.executeSql(query)



#---------------------------------------------------------------------------------------------------
# Transcode Class
#---------------------------------------------------------------------------------------------------
class Transcode:

    def transcodeByUploadData(self, data):
        logMessage("transcoding...");

        _phone_data = User.getPhoneDataByUserId(int(data.user_id))

        # Newly uploaded files do not have their
        # preview_name nor file_name filed in either in the
        # db or on disk. we need to prefill the name
        # correctly
        #
        # TODO we might want to change the upload controller
        # to deal with this

        if (data.preview_name == None or data.preview_name == ""):
            data.preview_name = data.original_name
            Database.changeFileNames(data, None, None, data.preview_name)

        if (data.file_name == None or data.file_name == ""):
            data.file_name = data.original_name
            Database.changeFileNames(data, None, data.file_name, None)

        fromFile    = os.path.join(workingEnvironment['UploadDir'], str(data.user_id), 'originals', data.original_name)
        toFile      = os.path.join(workingEnvironment['UploadDir'], str(data.user_id), data.file_name)
        previewFile = os.path.join(workingEnvironment['UploadDir'], str(data.user_id), 'previews' , data.preview_name)

        #TODO we need to write these file basename's to the DB

        if (data.original_type in ["audio/x-wav","audio/mpeg","audio/mid","audio/amr"]):
            self._transcodeAudio(data)
        elif (data.original_type in ["browser/stuff"]):
            self._transcodeBrowserStuff(data, fromFile, toFile, previewFile)
        elif (data.original_type in ["image/png","image/jpeg","image/tiff","image/bmp","image/gif"]):
            self._transcodeImageAndPreview(fromFile, toFile, previewFile) #@TODO width/height from _phone_data
        elif (data.original_type in ["text/plain"]):
            self._transcodeText(fromFile, toFile)
        elif (data.original_type in ["video/3gpp","video/flv","video/mpeg","video/avi","video/quicktime"]):
            self._transcodeVideo(data)
        else:
            logMessage("Attempt to transcode unsupported type (%s) for upload id (%d)" % (data.original_type, data.upload_id),1)
        
        Database.updateFileSizes(data)

        return 0

    def _transcodeAudio(self, fromFile, toFile):
        logMessage("type=audio...")

        if not os.path.isfile(fromFile):
            logMessage("failure (file not found)\n")
            return 1

        # Encode the file, wait for the command to return
        if not os.spawnlp(os.P_WAIT, workingEnvironment['FfmpegCmd'], os.path.basename(workingEnvironment['FfmpegCmd']), '-y', '-i', fromFile, '-ar', '8000', '-ac', '1', '-ab', '7400', '-f', 'amr', toFile) == 0:
            logMessage("failure.\n")
            return 1

        if not os.path.isfile(toFile): 
            logMessage("failure.\n")
            return 1
        logMessage("success.\n")
        return 0

    def _transcodeBrowserStuff(self, data, fromFile, toFile, preview):
        logMessage("type=browserstuff...")

        if not os.path.isfile(fromFile):
            logMessage("failure (file not found)\n")
            return 1

        # Copy the file, wait for the command to return
        if not os.spawnlp(os.P_WAIT, 'cp', 'cp', fromFile, toFile) == 0:
            logMessage("failure.\n")
            return 1

        if not os.path.isfile(toFile): 
            logMessage("failure.\n")
            return 1

        # Copy the file to preview, wait for the command to return
        if not os.spawnlp(os.P_WAIT, 'cp', 'cp', fromFile, preview) == 0:
            logMessage("failure.\n")
            return 1

        if not os.path.isfile(preview): 
            logMessage("failure.\n")
            return 1

        Database.updateFileTypes(data, "text/html", "browser/stuff", None)

        logMessage("success.\n")
        return 0

    def _transcodeImage(self, fromFile, toFile, width=100, height=100):
        logMessage("type=image...")

        if not os.path.isfile(fromFile):
            logMessage("failure (file not found)\n")
            return 1

        # Encode the file, wait for the command to return
        if not os.spawnlp(os.P_WAIT, workingEnvironment['ConvertCmd'], os.path.basename(workingEnvironment['ConvertCmd']), '-geometry', ("%sx%s"% (width, height)), fromFile, toFile) == 0:
            logMessage("failure.\n")
            return 1

        if not os.path.isfile(toFile): 
            logMessage("failure.\n")
            return 1

        logMessage("success.\n")
        return 0

    def _transcodeImageAndPreview(self, fromFile, toFile, previewFile, width=100, height=100):
        # TODO logMessage isn't really going to work for this method since it's nested with _transcodeImage, but since we have to redo the logging to deal with the
        # multithreading I'm just going to skip this function for now

        if not self._transcodeImage(fromFile, toFile, width, height) == 0:
            return 1

        if not self._transcodeImage(toFile, previewFile, width/2, height/2) == 0:
            return 1

        return 0

    def _transcodeText(self, fromFile, toFile):
        logMessage("type=text...")

        if not os.path.isfile(fromFile):
            logMessage("failure (file not found)\n")
            return 1

        # Copy the file, wait for the command to return
        if not os.spawnlp(os.P_WAIT, 'cp', 'cp', fromFile, toFile) == 0:
            logMessage("failure.\n")
            return 1

        if not os.path.isfile(toFile): 
            logMessage("failure.\n")
            return 1

        logMessage("success.\n")
        return 0

    def _transcodeVideo(self, data):
        logMessage("type=video...")
        #@TODO
        logMessage("success.\n")
        return 0


#---------------------------------------------------------------------------------------------------
# Update Class
#---------------------------------------------------------------------------------------------------
class Update:


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
            logMessage("no icon url to process for upload id (%d)" %(data.upload_id))


        # fetch the RSS Source
        file = urllib.urlopen(rss_url)
        source = file.read()

        d = feedparser.parse(source)

        # save / update the title of the upload.
        title = d['feed']['title'];
        Database.setTitle(data.upload_id, title)

        try:

            if hasattr(d.entries[0], "enclosures"):

                last_entry = d.entries[0]
                
                for entry in d.entries:
                    if (entry.updated_parsed > last_entry.updated_parsed):
                        last_entry = entry
                        
                # check to see if on disk the last modification
                # time matches the updated_parsed date.  If so,
                # than ignore this update.
                info = os.stat(originalFile)

                last_entry_time = int(time.mktime(last_entry.updated_parsed))

                # [8] is the last mod date.  I wonder if
                # there is a "define" for this.  This is a
                # magic number i do not like.

                if (last_entry_time <= info[8]):
                    # do nothing
                    return 1

                # todo we should be able to process other types of podcasts
                        
                for enclosure in last_entry.enclosures:
                    if (enclosure.type == "audio/mpeg"):

                        #todo -- shouldn't we worry here about disk space?
                        #print urllib.urlretrieve(enclosure.href, originalFile)

                        f = urllib.urlopen(enclosure.href)
                        media = f.read()
                        
                        # check to see if the originalFile already has the right extension
                        if (originalFile.find(".mp3") == -1):
                            originalFile = originalFile + ".mp3"

                            #update the table with this new file name.
                            Database.changeFileNames(data, None, os.path.basename(originalFile), None)

                        out = open(originalFile, 'w+')
                        out.write(media)
                        out.close()

                        # make sure that the file time matches the feed updated time so that we can ignore updates if the dates match

                        modtime = int(time.mktime(last_entry.updated_parsed))
                        os.utime(originalFile, (modtime, modtime))

                        media = ""

                        # todo transcode audio (Can I simply call Transcode.transcodeAudio?)
                        if not os.spawnlp(os.P_WAIT, workingEnvironment['FfmpegCmd'], os.path.basename(workingEnvironment['FfmpegCmd']), '-y', '-i', originalFile, '-ar', '8000', '-ac', '1', '-ab', '7400', '-f', 'amr', newFile) == 0:
                            logMessage("failure.\n")
                            return 1
                        
                        if not os.path.isfile(newFile): 
                            logMessage("failure.\n")
                            return 1
                        logMessage("success.\n")

                        # update the file types.
                        Database.updateFileTypes(data, "audio/amr", enclosure.type, None)
                                
            else:
                # generate the RSS output that we want to show people
                output = self._buildRssOutput(d)
                
                # copy the |source| to the original file
                out = open(originalFile, 'w+')
                out.write(source)
                out.close()
                
                # copy that to the actual file -- utf8.
                out = codecs.open(newFile, encoding='utf-8', mode='w+')
                out.write(output)
                out.close()
                
                Database.updateFileTypes(data, "text/html", "application/rss+xml", None)

            Database.updateFileSizes(data)
    
        except:
            #look at this mess.  what happened to simply being able to get the exception passed to you?
            msg = "ERROR:\n" + traceback.format_tb(sys.exc_info()[2])[0] + "\nError Info:\n    " + str(sys.exc_type)+ ": " + str(sys.exc_value) + "\n"
            logMessage("Problem updating files on disk for upload id (%d):\n%s" %(data.upload_id, msg),1)
            return 1

        return 0

    def _updateMicrosummaryTypeFromUploadData(self, data):

        originalFile = "%s/%d/originals/%s" % (workingEnvironment['UploadDir'], data.user_id, data.original_name)
        newFile      = "%s/%d/%s" % (workingEnvironment['UploadDir'], data.user_id, data.file_name)

        # this is a hack.  we spent a bunch of time making
        # the DOM between FF and PHP work when using XPATHS.
        # In order to save this work until we investigate
        # using the PYTHON DOM, we are going to kick off php
        # here.

        # save out the data.source so that PHP can see it
        out = open(originalFile, 'w+')
        out.write(data.source)
        out.close()

        #TODO  -- are we absolutely sure that os.spawnlp escapes params?  This is a huge hole if not.  
        
        if not os.spawnlp(os.P_WAIT, "php", "", '-f', '../vendors/microsummary.php', originalFile, data.upload_referrer) == 0:
            logMessage("failure.\n")

        # copy it over to the new file.
        os.system("cp %s %s" % (originalFile, newFile))
        if not os.path.isfile(newFile): 
            logMessage("failure.\n")
            return 1

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
            logMessage("Invalid input (%s): not a number" % id, 1)

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

class User:
    def getPhoneDataByUserId(self, id):
        if type(id) != int:
            logMessage("Invalid input (%s): not a number" % id, 1)

        query = """
            SELECT
                Phone.*
            FROM phones as Phone
            JOIN users as User on User.phone_id = Phone.id
            WHERE User.id = '%d' """ % id 

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
                ('c',  'config', True, './joeyd.conf', 'specify the location and name of the config file'),
                (None, 'DatabaseName', True, "", 'the name of the database within the server'),
                (None, 'ServerName', True, "", 'the name of the database server'),
                (None, 'UserName', True, "", 'the name of the user in the database'),
                (None, 'Password', True, "", 'the password for the user in the database'),
                (None, 'logPathName', True, "./update.log", 'a progressive log of all runs of the update script'),
                (None, 'UploadDir', True, "", 'Where are all the uploads stored?'),
                (None, 'ConvertCmd', True, "/usr/bin/convert", 'Where is your convert executable?'),
                (None, 'FfmpegCmd', True, "", 'Where is your ffmpeg executable?'),
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
    User = User();
    Database = Database();

    # Where stuff actually happens
    processByUploadId(710)
    processByUploadId(722)
    processByUploadId(727)
    processByUploadId(728)
    processByUploadId(729)

  except KeyboardInterrupt:
    print >>standardError, "Interrupted..."
    pass
  
  except Exception, x:
    print >>standardError, x
    traceback.print_exc(file=standardError)

