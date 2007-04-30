
function getJoeyServerURL()
{
    var psvc = Components.classes["@mozilla.org/preferences-service;1"]
                         .getService(Components.interfaces.nsIPrefBranch);

    if (psvc.prefHasUserValue("joey.service_url"))
        return psvc.getCharPref("joey.service_url");

    return "https://joey.labs.mozilla.com";
}

function clearLoginData()
{
    
    // sometimes the password manager remembers username
    // and password that are wrong or have changed.  If
    // we failed to login, lets purge this data.  I hate
    // that this is such a PITA to do.
    
    var pwmgr = Components.classes["@mozilla.org/passwordmanager;1"]
                          .getService(Components.interfaces.nsIPasswordManager);
    var e = pwmgr.enumerator;
    
    var passwds = [];
    
    while (e.hasMoreElements()) {
        var passwd = e.getNext().QueryInterface(Components.interfaces.nsIPassword);
        passwds.push(passwd);
    }
    
    var server = getJoeyServerURL();
    for (var i = 0; i < passwds.length; ++i)
    {
        if (passwds[i].host == server)
            pwmgr.removeUser(passwds[i].host, passwds[i].user);
    }
}

