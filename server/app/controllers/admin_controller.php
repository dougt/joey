<?php



class AdminController extends AppController
{

  var $name = 'Admin';

  var $components = array('Joey', 'Pagination', 'Session', 'Storage');

//@todo review these
    var $uses = array('Phone', 'Contentsource', 'Contentsourcetype', 'File', 'Upload','User');

    var $helpers = array('Number','Time');

    /**
     * Set in beforeFilter().  Will hold the session user data.
     */
    var $_user;

    /**
     * You can thank https://trac.cakephp.org/ticket/1589 for not letting us put this
     * in the constructor.  (Apparently that is not a valid scenario...)
     */
    function beforeFilter() {

        parent::beforeFilter();

        // Set the local user variable to the Session's User
        $this->_user = $this->Session->read('User');


        // disable query caching so devcp changes are visible immediately
        foreach ($this->uses as $_model) {
            $this->$_model->caching = false;
        }
    }

    function index() {
        $this->layout = null;
        $this->summary();
    }


    /**
    * Admin Summary
    */
    function summary() {

      if ($this->_user['administrator'] == 0)
      {
        $this->flash('Permission Denied.', '/uploads/index');
      }

  
      $_summary["user_count"] = $this->User->findCount();
      $_summary["upload_count"] = $this->Upload->findCOunt();


      //Last 24 hours
      $timestamp = date('Y-m-d H:i:s', (time() - 86400));

    //@todo queries go in the model.  These need to be moved.
      $result = $this->User->query("SELECT count(*) from users WHERE created >= '{$timestamp}';");
      $_summary["new_user_count"] = $result[0][0]["count(*)"];


      $result = $this->Upload->query("SELECT count(*) from uploads WHERE created >= '{$timestamp}'");
      $_summary["new_upload_count"] = $result[0][0]["count(*)"];


      $result = $this->User->query("SELECT count(*) from users WHERE confirmationcode is not NULL");
      $_summary["pending_users_count"] = $result[0][0]["count(*)"];

      $_summary["joeyd_stats"] = "Problem reading joeyd stat file!";
      
      if (is_readable(JOEYD_STAT_FILE) && is_file(JOEYD_STAT_FILE)) {
        $handle = fopen(JOEYD_STAT_FILE, "r");
        $result = fread($handle, filesize(JOEYD_STAT_FILE));

        $_summary["joeyd_stats"] = $result;
        
        fclose($handle);
      }

        $this->set('summary', $_summary);
        

    }
}
?>
