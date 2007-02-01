<?php

class WebTestHelper extends WebTestCase {
    /* Compute protocol and hostname prefix, no trailing slash. */
    function hostPrefix() {
        $http = (!empty($_SERVER["HTTP_MOZ_REQ_METHOD"]) && $_SERVER["HTTP_MOZ_REQ_METHOD"] == 'HTTPS') ? 'https://' : 'http://';
        $uriBase = $http . $_SERVER['HTTP_HOST'];
        return $uriBase;
    }

    /* Compute the URI for the given action, accounting for us possibly not
     * being at the root of the web space.
     */
    function actionURI($action) {
        /**
            If HTTP_MOZ_REQ_METHOD indicates this was requested via https://,
            use that, otherwise default to http:// 
        */
        return $this->hostPrefix() . $this->actionPath($action);
    }

    /* As above, but just the local path and not a complete URI. */
    function actionPath($action) {
        return preg_replace('/\/tests.*/', $action, setUri());
    }

    /* Make a GET for the given action, accounting for us possibly not being at
     * the root of the web space.
     */
    function getAction($action) {
        $this->get($this->actionURI($action));
    }
    
    /* GET a fully-specified local URI path (needs to include site prefix if any). */
    function getPath($path) {
        $this->get($this->hostPrefix() . $path);
    }
    
   /**
    * Logs in with test account info.
    */
    function login() {
        $username = 'nobody@mozilla.org';
        $password = 'test';
        
        $path = $this->actionURI('/users/login');
        $data = array(
                    'data[Login][email]' => $username,
                    'data[Login][password]' => $password
                );
        
        $this->post($path, $data);
        $this->assertNoUnwantedText(_('error_username_or_pw_wrong'), 'Logged in with test account');        
    }

    /**
     * Check if the retrieved XML document is well-formed/trivially parsable
     * (no DTD validity for now)
     */
    function checkXML() {
        $browser = $this->getBrowser();
        $data = $browser->getContent();
        $xmlparser = xml_parser_create();
        return (xml_parse($xmlparser, $data, true) == 1);;
    }
}

?>
