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

import threading

import MySQLdb

from time import sleep

from BaseHTTPServer import HTTPServer, BaseHTTPRequestHandler


""" What address should with listen to """
joeyd_address = ('localhost', 8777)

""" How many threads should be in our thread pool """
joeyd_threadcount = 20

""" Database login info """
joey_db_server = "localhost"
joey_db_user   = "root"
joey_db_pw     = "wil is my hero"
joey_db_name   = "joey"



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
        
""" Add upload handling code here """
def processUpload(id):
    """ need to validate this as an id """
    print "upload %s processing" % id
    sleep(10)
    print "upload %s done" % id
    return 0;


print "joeyd started."


# connect
joey_db = MySQLdb.connect(host=joey_db_server, user=joey_db_user, passwd=joey_db_pw, db=joey_db_name)

"""
cursor = joey_db.cursor()
cursor.execute("SELECT * FROM uploads")
result = cursor.fetchall()

for record in result:
    print "Upload ID: %d Title: $s" % record[0]
    print "Upload Title: %s" % record[2]

"""

print "Connected on: %s:%s" % joeyd_address

joeyd_threadpool = ThreadPool(joeyd_threadcount)
joeyd_server     = HTTPServer(joeyd_address, RequestHandler)

joeyd_server.serve_forever() 

joeyd_threadpool.joinAll()
