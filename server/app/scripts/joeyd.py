#!/usr/bin/python


""" 

joeyd

This is an implementation of a webservice that processes
joey uploads offline.  Once started up, we will listen to a
high port waiting for incoming requests in the form of
/<upload_id>.  When we get a request, we toss the request
into a thread pool for processing.

if "/now" is append on a request, we make an attempt to put
the new request at the top of the queue for processing.

"""

import sys
import os
import traceback

import thread
import threading
import time

from time import sleep
 
import urllib

from BaseHTTPServer import HTTPServer, BaseHTTPRequestHandler

import cse.Database
import cse.MySQLDatabase


#---------------------------------------------------------------------------------------------------
# Configuration options
#---------------------------------------------------------------------------------------------------

version = "0.1"

standardError = sys.stderr


#---------------------------------------------------------------------------------------------------
# Thread pool implemenation
#---------------------------------------------------------------------------------------------------

""" Threading code from Python Cookbook Recipe """
class ThreadPool:

    """Flexible thread pool class.  Creates a pool of threads, then
    accepts tasks that will be dispatched to the next available
    thread."""
    
    def __init__(self, numThreads):

        """Initialize the thread pool with numThreads workers."""
        
        self.__threads = []
        self.__resizeLock = threading.Condition(threading.Lock())
        self.__taskLock = threading.Condition(threading.Lock())
        self.__tasks = []
        self.__isJoining = False
        self.setThreadCount(numThreads)

    def setThreadCount(self, newNumThreads):

        """ External method to set the current pool size.  Acquires
        the resizing lock, then calls the internal version to do real
        work."""
        
        # Can't change the thread count if we're shutting down the pool!
        if self.__isJoining:
            return False
        
        self.__resizeLock.acquire()
        try:
            self.__setThreadCountNolock(newNumThreads)
        finally:
            self.__resizeLock.release()
        return True

    def __setThreadCountNolock(self, newNumThreads):
        
        """Set the current pool size, spawning or terminating threads
        if necessary.  Internal use only; assumes the resizing lock is
        held."""
        
        # If we need to grow the pool, do so
        while newNumThreads > len(self.__threads):
            newThread = ThreadPoolThread(self)
            self.__threads.append(newThread)
            newThread.start()
        # If we need to shrink the pool, do so
        while newNumThreads < len(self.__threads):
            self.__threads[0].goAway()
            del self.__threads[0]

    def getThreadCount(self):

        """Return the number of threads in the pool."""
        
        self.__resizeLock.acquire()
        try:
            return len(self.__threads)
        finally:
            self.__resizeLock.release()

    def queueTask(self, task, args=None, taskCallback=None, forceToTop=0):

        """Insert a task into the queue.  task must be callable;
        args and taskCallback can be None."""
        
        if self.__isJoining == True:
            return False
        if not callable(task):
            return False
        
        self.__taskLock.acquire()
        try:

            print self.__tasks;

            if forceToTop == 1:
                self.__tasks.insert(0, (task, args, taskCallback))
            else:
                self.__tasks.append((task, args, taskCallback))
            return True
        finally:
            self.__taskLock.release()

    def getNextTask(self):

        """ Retrieve the next task from the task queue.  For use
        only by ThreadPoolThread objects contained in the pool."""
        
        self.__taskLock.acquire()
        try:
            if self.__tasks == []:
                return (None, None, None)
            else:
                return self.__tasks.pop(0)
        finally:
            self.__taskLock.release()
    
    def joinAll(self, waitForTasks = True, waitForThreads = True):

        """ Clear the task queue and terminate all pooled threads,
        optionally allowing the tasks and threads to finish."""
        
        # Mark the pool as joining to prevent any more task queueing
        self.__isJoining = True

        # Wait for tasks to finish
        if waitForTasks:
            while self.__tasks != []:
                sleep(.1)

        # Tell all the threads to quit
        self.__resizeLock.acquire()
        try:
            self.__setThreadCountNolock(0)
            self.__isJoining = True

            # Wait until all threads have exited
            if waitForThreads:
                for t in self.__threads:
                    t.join()
                    del t

            # Reset the pool for potential reuse
            self.__isJoining = False
        finally:
            self.__resizeLock.release()


        
class ThreadPoolThread(threading.Thread):

    """ Pooled thread class. """
    
    threadSleepTime = 0.1

    def __init__(self, pool):

        """ Initialize the thread and remember the pool. """
        
        threading.Thread.__init__(self)
        self.__pool = pool
        self.__isDying = False
        
    def run(self):

        """ Until told to quit, retrieve the next task and execute
        it, calling the callback if any.  """
        
        while self.__isDying == False:
            cmd, args, callback = self.__pool.getNextTask()
            # If there's nothing to do, just sleep a bit
            if cmd is None:
                sleep(ThreadPoolThread.threadSleepTime)
            elif callback is None:
                cmd(args)
            else:
                callback(cmd(args))
    
    def goAway(self):

        """ Exit the run loop next time through."""
        
        self.__isDying = True


#---------------------------------------------------------------------------------------------------
# HTTP Request Handler
#   Called when a GET request is made
#---------------------------------------------------------------------------------------------------

class RequestHandler(BaseHTTPRequestHandler):
 
    def do_GET(self):

        """ 
        The path needs to look like
            /<upload_id>[/now]
        """
        path = self.path.split("/");
        upload_id = path[1];
        

        if (len(path) == 3):
            if path[2] == "now":
                print "Processing NOW event"
                joeyd_threadpool.queueTask(processUpload, upload_id, None, 1)
        else:
            joeyd_threadpool.queueTask(processUpload, upload_id, None)

        self.send_response(200)
        self.send_header('Content-type', 'text/html')
        self.end_headers()


#---------------------------------------------------------------------------------------------------
# Timer implemenation
#---------------------------------------------------------------------------------------------------

class Timer:

    # Create Timer Object
    def __init__(self, interval, function, *args, **kwargs):
        self.__lock = thread.allocate_lock()
        self.__interval = interval
        self.__function = function
        self.__args = args
        self.__kwargs = kwargs
        self.__loop = False
        self.__alive = False

    # Start Timer Object
    def start(self):
        self.__lock.acquire()
        if not self.__alive:
            self.__loop = True
            self.__alive = True
            thread.start_new_thread(self.__run, ())
        self.__lock.release()

    # Stop Timer Object
    def stop(self):
        self.__lock.acquire()
        self.__loop = False
        self.__lock.release()

    # Private Thread Function
    def __run(self):
        while self.__loop:
            self.__function(*self.__args, **self.__kwargs)
            sleep(self.__interval)
        self.__alive = False

        
#---------------------------------------------------------------------------------------------------
# Adding processing code here TODO
#---------------------------------------------------------------------------------------------------
""" Add upload handling code here """
def processUpload(id):
    """ need to validate this as an id """
    print "upload %s processing" % id
    sleep(10)
    print "upload %s done" % id
    return 0;



#---------------------------------------------------------------------------------------------------
# Joey refreshing timer
#---------------------------------------------------------------------------------------------------
def joeyd_refresher_timeout():

#todo in addtion, we should only query for files with a
#modification date greater than 15 min (or whatever refresh
#time we care about)

    query = """
        SELECT * FROM 
        uploads_users 
        JOIN uploads as Upload ON uploads_users.upload_id = Upload.id
        LEFT JOIN files as File ON Upload.id = File.upload_id
        LEFT JOIN contentsources as Contentsource ON File.id = Contentsource.file_id
        WHERE Contentsource.source IS NOT NULL """
    
    result = joey_db.executeSql(query)
    
    for x in result:
        joeyd_threadpool.queueTask(processUpload, x.id, None)


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
                    ('t',  'threadcount', True, 20, 'Number of threads that should be in our thread pool.'),
                    (None, 'listenAddress', True, '', 'Address to listen on'),
                    (None, 'listenPort', True, 87277, 'Port to listen on'),
                    ('v',  'verbose', False, None, 'print status information as it runs to stderr'),
                    ]
        
        workingEnvironment = cse.ConfigurationManager.ConfigurationManager(options)
        
    except cse.ConfigurationManager.ConfigurationManagerNotAnOption, x:
        print >>standardError, "m1 %s\n%s\nFor usage, try --help" % (version, x)
        sys.exit()
    

print "joeyd starting."


joey_db = cse.MySQLDatabase.MySQLDatabase(workingEnvironment["DatabaseName"],
                                          workingEnvironment["ServerName"], 
                                          workingEnvironment["UserName"],
                                          workingEnvironment["Password"])
print "joeyd db setup."




joeyd_threadpool = ThreadPool(workingEnvironment["threadcount"])
print "joeyd threadpool setup."



# every 30 seconds is going to kill us.  throttle back when we go online.
joeyd_refresher_timer = Timer(30.0, joeyd_refresher_timeout)
joeyd_refresher_timer.start()
print "joeyd timer setup."


joeyd_server     = HTTPServer((workingEnvironment["listenAddress"], workingEnvironment["listenPort"]), RequestHandler)
print "Connected on: %s:%s" % (workingEnvironment["listenAddress"], workingEnvironment["listenPort"])




# cleanup when we die.
joeyd_server.serve_forever() 
joeyd_threadpool.joinAll()
