# Youtube XSS using Flash and Ads

Report Details<br />
Email Subject: XSS in https://www.youtube.com/v/[VIDEO_ID]<br />
Date: 13/06/2016<br />

Hello Google Security Team,<br />

There is a XSS vulnerability in www.youtube.com via Flash.<br />
In the Youtube AS3 Api, the file ad3.swf (ads module) contains Security.allowDomain(getDomain(adManagerUrl)) [with getDomain(adManagerUrl) = "mssl.fwmrm.net"]. This means that if an attacker has access to mssl.fwmrm.net, he can have access to ytimg.com/.../ad3.swf. Because the Youtube AS3 api flashvar "afv_ad_tag" is not properly sanitized, it is possible to load a vulnerable swf such as "https://mssl.fwmrm.net/p/fox_live/FreeWheelPDKPlugin.swf" which allows an attacker to execute AS3 code from mssl.fwmrm.net and have access to ytimg.com/.../ad3.swf. It is then possible for an attacker to execute AS3 code on ytimg.com/.../ad3.swf and then also on https://www.youtube.com/v/[VIDEO_ID] leading to XSS on youtube.com and Cross Site Flashing on Google Drive/Docs.

----------
Proof of Concept

POC link : 
http://opnsec.com/google/YoutubeXSS.php

POC requirements :
- Flash must be active
- Any ads blocker must be desactivated
- Optionally you may be logged in with a testing Google Account with Google Drive files

Steps to reproduce:
1. Open the POC link and wait a few seconds (10-12 sec)
2. Your Google Drive/Docs info will show if you are logged in Google
3. The javascript payload will execute on www.youtube.com

Browser/OS: Tested on Windows 8.1/10 with Chrome 51, Firefox 47 and IE Edge (XSS only with IE 11) with Flash and without any ads blocker

Attack scenario:<br />
The vulnerability leads to XSS. Because the XSS is triggered from a Flash file, it is possible to embed it in an iframe like in the POC making it even more undetectable for the victim who opens the evil page like the POC page. The vulnerability also leads to Cross Site Flashing on any domain that serves a Crossdomain.xml file allowing ytimg.com (like drive.google.com, docs.google.com, clients6.google.com,...). This means that the attacker can read the source code on these domains and get private info and CSRF tokens from these domains.

---------
Technical details :

The vulnerability exploit uses multiple flaws :<br />
The "afv_ad_tag" parameter is not properly sanitized in ytimg.com/.../ad3.swf [when "videoData.isDataReady()" is true in ytimg.com/.../watch_as3.swf meaning that getVideoInfo() will not be called and the video info will be used from the flashvars]<br />
The "afv_ad_tag" parameter is used to define an XML file which contains the parameters for the ads including the link to the ads media file (image, video or flash file)<br />
An attacker can point afv_ad_tag to his own XML file (in the POC https://elire.fr/google/vpaidnonlinear.xml) which will point the ads media file to a evil swf (in the POC https://elire.fr/google/vpaid2.swf)<br />
The evil swf (vpaid2.swf) will then load https://mssl.fwmrm.net/p/fox_live/FreeWheelPDKPlugin.swf and will execute AS3 code on mssl.fwmrm.net (thanks to SecurityDomain.currentDomain in FreeWheelPDKPlugin.swf). mssl.fwmrm.net has access to ytimg.com/.../ad3.swf (thanks to Security.allowDomain(getDomain(adManagerUrl)) in ad3.swf) so the attacker has access to ad3.swf.<br />
The attacker can then call the "ModuleBase.guardedCall()" function on ad3.swf which is an equivalent to eval() in the sense that the code sent to guardedCall will be executed in the context of ytimg.com/.../ad3.swf.<br />
At this point the attacker can perform Cross Site Flashing from ytimg.com to Google Drive/Docs and other domains.<br />
Furthermore, the attacker can access ad3.swf parent watch_as3.swf (wich is on the same SecurityDomain) and its parent www.youtube.com/v/[VIDEO_ID] (thanks to security.allowdomain("*") on the youtube.com file). The attacker can import the class com.google.utils.ExternalInterfaceSecureWrapper from www.youtube.com/v/[VIDEO_ID] and then call the function ExternalInterfaceSecureWrapper.call that will execute ExternalInterface.call from the context of www.youtube.com/v/[VIDEO_ID]. This means that the attacker is able to execute any javascript code in the domain www.youtube.com.

--------
Vulnerability mitigation :

To solve this issue, the "afv_ad_tag" parameter should be properly sanitized to only access trusted domains. in addition, the XML file that afv_ad_tag is pointing to should be sanitized to only contains trusted image/video/flash files as ads. This way an attacker will not be able to load a malicious swf file from ytimg.com/.../ad3.swf. You should also check if it is possible to remove Security.allowDomain(getDomain(adManagerUrl)) and use another way to communicate with mssl.fwmrm.net for example loading the mssl.fwmrm.net swf files with SecurityDomain.currentDomain.

-------
I hope that my explanations and the POC are clear enough because it is not simple to follow all the steps ! I would be happy to send you by email the source code of the files used in the POC if needed.<br />
Here is a video of the POC in action. [![Youtube XSS video](http://img.youtube.com/vi/14GUwFZtPfg/0.jpg)](https://www.youtube.com/watch?v=14GUwFZtPfg)

Regards,

Enguerran Gillier