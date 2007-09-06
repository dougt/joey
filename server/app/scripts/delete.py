#!/usr/bin/python


""" 

delete

takes an upload id, and removes it from the db and fs.  This
does not mark the upload item as deleted, but rather it
erases it completely from memory.

"""
import cse.Database
import cse.MySQLDatabase

import os
import sys
import traceback


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
                    (None, 'logPathName', True, "./joeyd.log", 'a progressive log of all runs of the update script'),
                    (None, 'statPathName', True, "./joeyd_stat.log", 'a snapshot of the current state of the update script'),
                    (None, 'UploadDir', True, "", 'Where are all the uploads stored?'),
                    ('t',  'threadcount', True, 100, 'Number of threads that should be in our thread pool.'),
                    (None, 'listenAddress', True, 'localhost', 'Address to listen on'),
                    (None, 'listenPort', True, 8777, 'Port to listen on'),
                    (None, 'ConvertCmd', True, "/usr/bin/convert", 'Where is your convert executable?'),
                    (None, 'FfmpegCmd', True, "", 'Where is your ffmpeg executable?'),
                    ('v',  'verbose', False, None, 'print status information as it runs to stderr'),
                    ('d',  'debug', False, None, 'print status information as it runs to stdout'),
                    ('l',  'listen', False, None, 'listen on the listening address and port for incoming requests'),

                    # the only item that differs from joey.py
                    ('u',  'UploadID', True, None, 'specify the upload id of the doomed upload'),

                    ]
        
        workingEnvironment = cse.ConfigurationManager.ConfigurationManager(options)

        joey_db = cse.MySQLDatabase.MySQLDatabase(workingEnvironment["DatabaseName"],
                                                  workingEnvironment["ServerName"], 
                                                  workingEnvironment["UserName"],
                                                  workingEnvironment["Password"])

        query = """
            SELECT
                uploads_users.user_id,
                Upload.id as upload_id,
                File.id as file_id,
                File.name as file_name,
                File.original_name,
                File.preview_name,
                Contentsource.id as content_id,
                Contentsourcetype.name as contentsourcetype_name
            FROM 
            uploads_users
            JOIN uploads as Upload ON uploads_users.upload_id = Upload.id
            LEFT JOIN files as File ON Upload.id = File.upload_id
            LEFT JOIN contentsources as Contentsource ON File.id = Contentsource.file_id
            LEFT JOIN contentsourcetypes as Contentsourcetype ON Contentsource.contentsourcetype_id = Contentsourcetype.id
            WHERE uploads_users.upload_id = %s """ % workingEnvironment['UploadID']

        result = joey_db.executeSql(query)
        
        for data in result:
            
            print "clearing db rows related to the upload id"
            
            if data.content_id is not None:
                joey_db.executeSql("DELETE FROM contentsources where id = %s" %(data.content_id))

            if data.file_id is not None:
                joey_db.executeSql("DELETE FROM files where id = %s" %(data.file_id))

            joey_db.executeSql("DELETE FROM uploads where id = %s" %(workingEnvironment['UploadID']))

            joey_db.commit()

            originalFile = "%s/%d/originals/%s" % (workingEnvironment['UploadDir'], data.user_id, data.original_name)
            previewFile  = "%s/%d/previews/%s" % (workingEnvironment['UploadDir'], data.user_id, data.preview_name)
            newFile      = "%s/%d/%s" % (workingEnvironment['UploadDir'], data.user_id, data.file_name)

            print "deleting previewFile"
            if os.path.isfile(previewFile):
                os.remove(previewFile)

            print "deleting originalFile"
            if os.path.isfile(originalFile):
                os.remove(originalFile)

            print "deleting newFile"
            if os.path.isfile(newFile):
                os.remove(newFile)



    except cse.ConfigurationManager.ConfigurationManagerNotAnOption, x:
        print >>sys.stderr, "m1 %s\nFor usage, try --help" % (x)
        sys.exit(1)
    
    except KeyboardInterrupt:
        print >>sys.stderr, "Interrupted..."
        sys.exit(1)
        pass

    except Exception, x:
        print >>sys.stderr, x
        traceback.print_exc(file=sys.stderr)
        sys.exit(1)
        pass
