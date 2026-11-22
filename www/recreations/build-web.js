#!/usr/bin/env node
/*
 * Generates static reconstructions of the Joey server's pages (CakePHP 1.1 views
 * in server/app/views) as they would have rendered in 2007. The markup follows the
 * .thtml templates and layouts literally; only the PHP has been "executed" by hand.
 * Run: node build-web.js   (writes web-*.html next to this file)
 */
const fs = require('fs');
const path = require('path');
const out = (name, html) => fs.writeFileSync(path.join(__dirname, name), html);

/* ---------- layouts/default.thtml + green_banner / green_header / green_footer ---------- */
function defaultLayout({ title, user, flowContext, content }) {
  const header = user
    ? `<p id="getsoftware" ><a href="/users/getSoftware">GET THE SOFTWARE</a></p>`
    : '';
  const bar = user
    ? `<p><a href="/users/edit">${user}</a> ( <a href="/users/logout">Logout</a> )</p>`
    : flowContext === 'resetpassword' ? `<p>Reset password</p>` : `<p>Welcome!</p>`;
  return `<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" version="-//W3C//DTD XHTML 1.1//EN" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="assets/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="assets/css/green.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/joey.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/ff2-shim.css" />
	<title>${title}</title>
</head>


<body>

    <center>

    <div class="framecontent">

        <div id="topbanner"  >
          <p id="joeyserverlink" >
<a href="/">/JOEY.LABS.MOZILLA.COM/</a>
</p>
        </div>

        <div id="header">
	<div id="barleft">
    ${header}
	<div id="barmiddle">
	<div id="barright">
	</div>
    ${bar}
	</div>
	</div>
</div>

        <div id="innercontent">
        ${content}
        </div>

        <!-- start #footer -->
<div id="footer">
&copy; 2007 Mozilla Foundation -
<a href="/terms">Legal</a>
 -
<a href="https://labs.mozilla.com/forum/index.php?board=3.0">Support</a>
</div>
<!-- end #footer -->

    </div>

    </center>

</body>
</html>
`;
}

/* ---------- users/login.thtml ---------- */
const loginContent = `<style> h2 { margin-top:.5em } </style>
<div id="content">

<div id="joey-login-frame">

<div id="joey-login-content">

<table cellpadding="15" cellspacing="15"  >
<tr>
<td valign="top" >

Project Joey brings the Web content you need most to your
mobile phone.

<h2 style="margin:0px" >Send content to your phone</h2>

   Our Firefox extension allows you to select and upload text,
audio, videos, RSS, and other media.

<h2>Simple access to your mobile content</h2>

Using either your phones built-in browser, or our Joey
application, you quickly get to your data.

<h2>Try it now</h2>

<p>
Although we are in the early stages of development, much of
Joey is usable today. Feel free to give Joey a <a href="/users/register/">try now</a>.</p>


<h2>Learn more..</h2>
<p>Check out this hot video:</p>
<a href="http://www.youtube.com/watch?v=uH_S_9qYt5w" ><img border="0" src="assets/img/joeyplay.jpg" align="left" /></a>


</td>
<td valign="top">

<div class="joey-panel">

<h2>Login</h2>

<form action="" method="post" class="joey-form" >
    <div>
        <label class="joey-label-large" for="UserUsername">User Name:</label>
        <input name="data[User][username]" type="text" value="" id="UserUsername" />
    </div>

    <div>
        <label class="joey-label-large" for="UserPassword">Password:</label>
        <input name="data[User][password]" type="password" value="" id="UserPassword" />
    </div>

    <div class="button-box">
        <input type="submit" value="Login" class="joey-submit" />
    </div>
</form>

<div><b><a href="https://joey.labs.mozilla.com">Use Secure Login</a></b></div>


<div>
<a href="resetpasswordemail">Forgot Password?</a><br />
</div>


</div>

</td>

</tr>
</table>
</div>
</div>


<div id="footer-content">
</div>



</div>`;

/* ---------- uploads/index.thtml ---------- */
const uploads = [
  { id: 41, fid: 41, title: 'Introduction to Joey - Video Tutorial', referrer: 'http://www.youtube.com/watch?v=uH_S_9qYt5w', type: 'video/flv', size: '1.2 MB', created: 'Sep 19th, 2007' },
  { id: 40, fid: 40, title: 'Mozilla Labs » Blog Archive » Introducing Joey', referrer: 'http://labs.mozilla.com/2007/06/introducing-joey/', type: 'text/plain', size: '2.4 KB', created: 'Sep 19th, 2007' },
  { id: 39, fid: 39, title: 'Planet Mozilla', referrer: 'http://planet.mozilla.org/rss20.xml', type: 'application/rss+xml', size: '38.6 KB', created: 'Sep 18th, 2007' },
  { id: 38, fid: 38, title: 'Golden Gate at dusk on Flickr - Photo Sharing!', referrer: 'http://www.flickr.com/photos/dougt/1399275104/', type: 'image/jpeg', size: '11.7 KB', created: 'Sep 18th, 2007', preview: true },
  { id: 37, fid: 37, title: 'Air Mozilla - Weekly Project Meeting (audio)', referrer: 'http://air.mozilla.com/', type: 'audio/mpeg', size: '3.9 MB', created: 'Sep 17th, 2007' },
  { id: 36, fid: 36, title: 'Current Tabs', referrer: 'about:CurrentTabs', type: 'browser/stuff', filetype: 'text/html', size: '1.1 KB', created: 'Sep 17th, 2007' },
  { id: 35, fid: 35, title: 'Mobile Firefox: Mozilla Wiki', referrer: 'http://wiki.mozilla.org/Mobile', type: 'text/plain', created: 'Sep 17th, 2007', pending: true },
];

function uploadRow(u) {
  const view = `/files/view/${u.fid}`;
  let thumb;
  if (u.type === 'video/flv') {
    thumb = `
              <div class="joey-upload-image-thumb">
                <div class="joey-mediaicon-video">
                    <a href="${view}/preview" title="${u.title}" alt="${u.title}" onclick="return false;" >
                    <div class="joey-mediaicon-video" style='background:transparent 50% 50% no-repeat;background-image:url(assets/img/preview-video.png);' >
                    </div>
        </a>
                </div>
              </div >`;
  } else if (u.type === 'audio/mpeg') {
    thumb = `
              <div class="joey-upload-image-thumb">
                <div style="float:right;margin-right:2px;margin-top:2px">
                  <!-- musicplayer.swf (Flash) sat here, 17x17 -->
                  <img src="assets/img/play.png" width="17" height="17" alt="" >
                </div>
                <div class="joey-mediaicon-audio">
                </div>
              </div>`;
  } else if (u.type === 'text/plain' || u.filetype === 'text/html') {
    thumb = `
          <div class="joey-upload-image-thumb">
		    <a href="${view}" title="${u.title}" alt="${u.title}" onclick="return false;" >
		    <div class="joey-mediaicon-text">
		    </div>
	</a>
          </div >`;
  } else if (u.type === 'application/rss+xml') {
    thumb = `
           <div class="joey-upload-image-thumb">
		    <a  href="${view}" title="${u.title}"  alt="${u.title}" onclick="return false" >
		     <div class="joey-mediaicon-rss">
		     </div>
</a>
           </div >`;
  } else if (u.preview) {
    thumb = `
          <div class="joey-upload-image-thumb">
            <a  href="${view}" title="Open image"  onclick="return false" id="iconhandler-${u.id}" >
		     <div class="joey-mediaicon-image" style="background-color:#656565;background-image:url(assets/img/preview-flickr.png);background-position: 50% 50%;background-repeat: no-repeat">
          </div >
            </a>
          </div >`;
  } else {
    thumb = `<a href="${view}"><img src="assets/img/na.png" alt="" border="0" /></a>`;
  }

  const info = u.pending
    ? `<li>Joey hasn't processed this yet</li>
                    <li>Uploaded ${u.created}</li>`
    : `<li>Size:  ${u.size}</li>
                    <li>Uploaded ${u.created}</li>
                    <li><a href="${view}/original">Original</a>`;

  let controls = '';
  if (u.type === 'video/flv') controls = `
                  <a href="javascript:" onclick="return false" id="joeyVideoPlayerController-${u.id}">play</a>`;
  if (u.type === 'application/rss+xml') controls = `
                  <a href="javascript:" onclick="return false" id="joeyPlayerController-${u.id}">open</a>
<span id='joeyPlayerCloseButton-${u.id}'></span>`;

  return `
	      <tr id="innerItem-${u.id}" >
	        <td class="joey-item-control" width="1" valign="top" >
		<div style='display:none'>${u.type}</div>
              <a href="/uploads/delete/${u.id}&previous=1&show=10&type=" onclick="return false;">Delete</a>
	        </td>

	        <td class="joey-item-thumb" width="1" valign="top" >${thumb}
	        </td>

	        <td  class="joey-item-information" valign="top">
              <span class="titleupload"><a href="${u.referrer}">${u.title}</a></span>
              <ul>
                    ${info}
		<span id='controlOptions-${u.id}' />${controls}
                </li>
              </ul>
              <div id="expandItem-${u.id}">
 </div>
	        </td>
          </tr>`;
}

const uploadsContent = `<div id="content">

<div id="joey-upload-frame">


<h1>Joey! Uploads </h1>

<div>
  <a href="/uploads/index?type=all">all</a>
  <a href="/uploads/index?type=videos">video</a>
  <a href="/uploads/index?type=audio">audio</a>
  <a href="/uploads/index?type=images">images</a>
  <a href="/uploads/index?type=rss">rss</a>
  <a href="/uploads/index?type=microsummaries">microsummaries</a>
  <a href="/uploads/index?type=text">text</a>
</div>

<div class="joey-warning" >
<div style="font-style: italic;">
    <p>Did you know that you can access your Joey with your phone's web browser?  Just <a href="/users/getSoftware/sendJoeyURL">click here</a> and we will send your phone a url.</p>
</div>
</div>

<div id="messagesBar" > </div><div id="loading" class="joey-message"  style="display:none" >Joey is thinking...</div>

<table cellspacing="0"  cellpadding="0" >
  <tr>
    <td valign="top">
      <div class="joey-upload" >

        <table class="joey-upload-list" cellspacing="0"  cellpadding="0" border="0" width="100%">
          ${uploads.map(uploadRow).join('\n')}
        </table>
      </div>
    </td>
    <td valign="top" >
    </td>
  </tr>
</table>
</div>

<div id="footer-content">
<div id='pagination'>

  <div id='pagination-pagesize'>
    Page size: <a href="/uploads/index?show=5">5</a> <em>10</em> <a href="/uploads/index?show=20">20</a> <a href="/uploads/index?show=50">50</a>
  </div>

  Results: 1-7 of 7<br/>Go to page: <em>1</em><br/>
</div>
</div>

</div>`;

/* ---------- users/register.thtml ---------- */
const operators = ['Not Sure','AT&amp;T','Cellular One','Cingular','Metro PCS','Nextel','Orange','O2','T-Mobile','Verizon PCS','Sprint PCS','Alaska Communications Systems','Alltel Wireless','Bell Mobility &amp; Solo Mobile','AT&amp;T Wireless','Boost Mobile','Centennial Wireless','Comcel','Cricket','CTI','Emtel','Globalstar','Fido','Helio','Presidents Choice','Movicom','Movistar','Qwest','Rogers','7-11 Speakout','Suncom','Tigo','Telus Mobility','Tracfone','US Cellular','YCC','Virgin Mobile','Vodacom','WEBTEXT','IPIPI.COM','CLARO'];
const phones = ['Not Sure','Generic/Midp2Cldc11','Generic/JtwiCldc11','HTC/TyTN','Motorola/Q','Motorola/Motorazr_V3x','Nokia/6630','Nokia/N95','Nokia/E61','Sony-Ericsson/K750i','BlackBerry/8100'];
const opts = (arr) => arr.map((o, i) => `<option value="${i + 1}">${o}</option>`).join('\n');

const registerContent = `<div id="content">

<div id="joey-general-frame">

<div id="joey-registration-content">

<h2>Register</h2>

<p>All fields are required.</p>

<form action="" method="post" class="joey-form" >

<div>
    <label class="joey-label-large" for="UserUsername">User Name:</label>
    <input name="data[User][username]" type="text" value="" id="UserUsername" />
</div>

<div>
    <label class="joey-label-large" for="UserEmail">Email Address:</label>
    <input name="data[User][email]" type="text" value="" id="UserEmail" />
</div>

<div>
    <label class="joey-label-large" for="UserPassword">Password:</label>
    <input name="data[User][password]" type="password" value="" id="UserPassword" />
</div>

<div>
    <label class="joey-label-large" for="UserPassword">Confirm Password:</label>
    <input name="data[User][confirmpassword]" type="password" value="" id="UserConfirmpassword" />
</div>

<div>
    <label class="joey-label-large" for="UserPhonenumber">Phone number:</label>
    <input name="data[User][phonenumber]" type="text" value="" id="UserPhonenumber" />
</div>

<div>
    <label class="joey-label-large" for="PhoneName">What type of phone do you have?</label>
    <select name="data[Phone][name]" id="PhoneName">
${opts(phones)}
</select>
</div>

<div>
    <label class="joey-label-large" for="OperatorProvider">Who is your provider?</label>
    <select name="data[Operator][provider]" id="OperatorProvider">
${opts(operators)}
</select>
</div>

<div>
<i> Don't see your phone or cell operator? </i> <br />
<br>
If your phone isn't there, it means that we do not have
software available for your phone.  This doesn't mean you
can't use Joey.
Instead, try accessing Joey in your phone's browser.
Join the conversation on the <a href="https://labs.mozilla.com/forum/index.php?board=3.0">Joey Forum</a>.
</div>

<div>
        <label for="LegalStuff" style="font-weight:bold;" >Important Legal Stuff:</label>
<br />
<br />    <a href="https://joey.labs.mozilla.com/terms/termsofservice">Terms of Use</a>
<br />    <a href="https://joey.labs.mozilla.com/terms/EULA">End User License Agreement</a>
<br />    <a href="https://joey.labs.mozilla.com/terms/privacypolicy">Privacy Policy</a>
</div>

<div>
By clicking to accept this Agreement or by downloading the associated software, you acknowledge and agree that use of a Beta Release may exhibit sporadic disruptions that have the potential to disrupt your use of the Software and potential to affect the handset operations in general. You also acknowledge and agree that a Beta Release may not protect you in blocking unacceptable digital content, meaning digital content showing profanity in word or symbol format.  You further acknowledge and agree that the Beta Release will send information to your telecommunications device and that Mozilla is unable to guarantee that information you send to your telecommunications device will be secure or private.  Mozilla specifically disclaims all damages resulting from your use of the Beta Release.
</div>

<div class="button-box">
    <input type="submit" value="Accept and Register" class="joey-submit" />
</div>

</form>

</div>
</div>
<div id="footer-content">
</div>

</div>`;

/* ---------- users/get_software.thtml ---------- */
const getSoftwareContent = `<div id="content">

<div id="joey-general-frame">

<div id="joey-registration-content">


<h2>Get the software</h2>

<div>
<h3> Joey! Addon for Firefox </h3>
<p> You will want to get this so that you can grab stuff from the web and send it to Joe.... </p>
<br><a href="http://joey.labs.mozilla.com/app/webroot/ff/joey.xpi">Download Joey Addon for Firefox</a>
</div>

<div>


<h3> Joey! Addon for your phone </h3>

<div>
<p>You can access your Joey on your phone using the built-in browser, but if you want to try out our experimental application... here ya go! </p>
</div>
<br><p>Pressing this button will send an email to your phone.  This might cost you something, but you already know that.</p>


<form action="" method="post" class="joey-form">
   <input type="hidden" name="joey" value="joey"/>
   <div class="button-box">
     <input type="submit" value="Send SMS to your phone with this download link" class="joey-submit" />
    </div>
</form>


<div>
<p> If, for some reason, you want to download the jar directly, here it is.... </p>
<br><a href="http://joey.labs.mozilla.com/app/webroot/ff/HTC-TyTN-en_US-joey.jar">Download Joey Addon for your phone</a>
</div>

</div>


</div>
</div>
<div id="footer-content">
</div>

</div>`;

/* ---------- layouts/joeyclient.thtml + version/index.thtml + release_notes/0.3.0.0 ---------- */
const releaseNotes = fs.readFileSync(path.join(__dirname, '../../server/app/webroot/release_notes/0.3.0.0'), 'utf8')
  // Guided Tour button becomes visible once the add-on fires joeyCallback#joeyIsHere
  .replace('<div class="button" style="display:none" id="button-guided">', '<div class="button" id="button-guided">')
  // panel1 shown (showGuidedPanel()); panel2/3 hidden
  .replace('<div id="panel1" class="panel">', '<div id="panel1" class="panel" style="display:block">')
  .replace('<div id="panel2">', '<div id="panel2" class="panel" style="display:none">')
  .replace('<div id="panel3" class="panel">', '<div id="panel3" class="panel" style="display:none">')
  // the user has selected the text and Joey replied: pass1 revealed
  .replace('<div id="pass1" style="display:none">', '<div id="pass1">')
  // static stand-in for the YouTube Flash embed
  .replace(/-moz-border-radius:9px;/g, '-moz-border-radius:9px;border-radius:9px;')
  .replace(/<object width="425" height="350">[\s\S]*?<\/object>/, '<img src="assets/img/joeyplay.jpg" width="259" height="222" alt="Joey video (Flash embed)" />');

const welcomeHtml = `<html lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="shortcut icon" href="assets/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="assets/css/joeyclient.css" />
	<title>Joey! Welcome</title>
<style>
/* selection highlight as left by the guided tour step */
::selection { background:#3875d7; color:white; }
</style>
</head>


<body>

    <center>

        <div id="topbanner"  >
        </div>

    <div class="framecontent">


	<div id="header" >
	</div>

        <div id="innercontent">

        <div id="content">


${releaseNotes}



<div id="footer-content">
</div>



</div>

        </div>

        <!-- start #footer -->
<div id="footer">
&copy; 2007 Mozilla Foundation -
<a href="/terms">Legal</a>
 -
<a href="https://labs.mozilla.com/forum/index.php?board=3.0">Support</a>
</div>
<!-- end #footer -->

    </div>

    </center>

</body>
</html>
`;

/* ---------- layouts/iphone.thtml + uploads/iphone_index.thtml ---------- */
const iphoneHtml = `<html xmlns="http://www.w3.org/1999/xhtml" version="-//W3C//DTD XHTML 1.1//EN" xml:lang="en" lang="en" dir="ltr">
<head>
    <meta name="viewport" content="width=320;initial-scale=1.0;maximum-scale=1.0;user-scalable=0;"/>
    <style type="text/css" media="screen">
      @import "assets/vendor/iui/iui.css";</style>
	<title>Joey! Uploads</title>
</head>

<body>
<div title="Login" class="toolbar">
  <h1 id="pageTitle">Main</h1>
  <a id="backButton" class="button" href="#"></a>
</div>


<ul id="home" title="Main"  selected="true">
  <li><a href="#browser">Browser stuff</a></li>
  <li><a href="#videos">Videos</a></li>
  <li><a href="#images">Images</a></li>
  <li><a href="#audio">Audio</a></li>
  <li><a href="#rss">RSS</a></li>
  <li><a href="#text">Text</a></li>
</ul>


<ul id="browser" title="Firefox stuff">
<li><a href="/files/wrapper/36">Current Tabs</a></li>
</ul>

<ul id="videos" title="Videos">
<li><a href="/files/wrapper/41">Introduction to Joey - Video Tutorial</a></li>
</ul>

<ul id="audio" title="Audio">
<li><a href="#i37">Air Mozilla - Weekly Project Meeting (audio)</a></li>
</ul>

<ul id="images" title="Images">
<li><a href="/files/wrapper/38">Golden Gate at dusk on Flickr - Photo Sharing!</a></li>
</ul>

<ul id="rss" title="RSS">
<li><a href="/files/view/39">Planet Mozilla</a></li>
</ul>

<ul id="text" title="Text">
<li><a href="/files/wrapper/40">Mozilla Labs » Blog Archive » Introducing Joey</a></li>
</ul>
</body>
</html>
`;

/* ---------- layouts/mp.thtml + uploads/mp_index.thtml ---------- */
const wapRows = [
  { id: 41, title: 'Introduction to Joey - Video Tutorial' },
  { id: 40, title: 'Mozilla Labs » Blog Archive » Introducing Joey' },
  { id: 39, title: 'Planet Mozilla' },
  { id: 38, title: 'Golden Gate at dusk on Flickr - Photo Sharing!', image: true },
  { id: 37, title: 'Air Mozilla - Weekly Project Meeting (audio)' },
];
const wapHtml = `<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <link rel="stylesheet" type="text/css" href="assets/css/mp_joey.css" />
    <title>Joey! Uploads</title>
  </head>

  <body>
<p>
dougt [<a href="/uploads/add">Upload</a> | <a href="/users/logout">Logout</a>]</p>

${wapRows.map((r, i) => {
  const cls = ((i + 1) % 2 === 0) ? 'oddrow' : 'evenrow';
  const body = r.image
    ? `
        <br/>
        <a href="../files/view/${r.id}"><img src="assets/img/preview-flickr.png"/></a>
`
    : `
        <span class="view">[<a href="../files/view/${r.id}">View</a>]</span>
`;
  return `<div class="${cls}">
${body}
${r.title}

<span class="delete">[<a href="../uploads/delete/${r.id}">Delete</a>]</span>


</div>
`;
}).join('\n')}
<br/>
<div>
Go to page: <em>1</em><br/>
</div>

<!-- end #container -->
<!-- start #footer -->
<!-- end #footer -->
  </body>
</html>
`;

/* ---------- emit ---------- */
out('web-login.html', defaultLayout({ title: 'Joey! Login', user: null, content: loginContent }));
out('web-uploads.html', defaultLayout({ title: 'Joey! Uploads', user: 'dougt', content: uploadsContent }));
out('web-register.html', defaultLayout({ title: 'Joey! Register', user: null, content: registerContent }));
out('web-get-software.html', defaultLayout({ title: 'Joey! Get the software', user: 'dougt', content: getSoftwareContent }));
out('web-welcome.html', welcomeHtml);
out('web-iphone.html', iphoneHtml);
out('web-wap.html', wapHtml);
console.log('wrote 7 reconstructions');
