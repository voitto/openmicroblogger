<?xml version="1.0" encoding="UTF-8"?>
<package packagerversion="1.7.2" version="2.0" xmlns="http://pear.php.net/dtd/package-2.0" xmlns:tasks="http://pear.php.net/dtd/tasks-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0     http://pear.php.net/dtd/tasks-1.0.xsd     http://pear.php.net/dtd/package-2.0     http://pear.php.net/dtd/package-2.0.xsd">
 <name>Services_Facebook</name>
 <channel>pear.php.net</channel>
 <summary>PHP interface to Facebook&apos;s API</summary>
 <description>An interface for accessing Facebook&apos;s web services API at http://api.facebook.com</description>
 <lead>
  <name>Jeff Hodsdon</name>
  <user>jeffhodsdon</user>
  <email>jeffhodsdon@gmail.com</email>
  <active>yes</active>
 </lead>
 <lead>
  <name>Joe Stump</name>
  <user>jstump</user>
  <email>joe@joestump.net</email>
  <active>yes</active>
 </lead>
 <developer>
  <name>Travis Swicegood</name>
  <user>tswicegood</user>
  <email>development@domain51.com</email>
  <active>yes</active>
 </developer>
 <date>2009-02-18</date>
 <time>00:07:58</time>
 <version>
  <release>0.2.5</release>
  <api>0.2.0</api>
 </version>
 <stability>
  <release>alpha</release>
  <api>alpha</api>
 </stability>
 <license uri="http://www.opensource.org/licenses/bsd-license.php">New BSD License</license>
 <notes>* Added action link support for feeds.registerTemplateBundle</notes>
 <contents>
  <dir baseinstalldir="/" name="/">
   <file baseinstalldir="/" md5sum="07b6c722fb6b36a117f06e7a6c00bed2" name="Services/Facebook.php" role="php">
    <tasks:replace from="@package-version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="9b1498927bd1698a2986dd281fbcaa80" name="Services/Facebook/Admin.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="9c3d1aa3b8bd3aff55eefc34bdccc4a3" name="Services/Facebook/Application.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="c89d9ebf9b51544b5a426dc82008878c" name="Services/Facebook/Auth.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="7dd96a24bebad392080951d11154c0ac" name="Services/Facebook/Batch.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="ae02b6802d1d862b1e08bb494454d3c4" name="Services/Facebook/Common.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="faa401e3aed8cd826c79553771772847" name="Services/Facebook/Connect.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="a90514bbe06be2fb96c18a89f20d646a" name="Services/Facebook/Data.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="4bb2cc0e2d2dbdf0cec8476486806633" name="Services/Facebook/Events.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="a6204a032314b3b8ed593305a15b77dc" name="Services/Facebook/Exception.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="fdf764cd959859e54135e2eeea865dbf" name="Services/Facebook/FBML.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="0bf199e06f3e77427e950cc4e0235b4e" name="Services/Facebook/Feed.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="19705ff5b19cf26e6645a2c529175307" name="Services/Facebook/FQL.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="e6d4f232c60f265d4b8a69fa81af30c7" name="Services/Facebook/Friends.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="6eb3a4530f415bd60ed42158f07fd03c" name="Services/Facebook/Groups.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="33e3059714adf13f9c664ecdb1c6eb1f" name="Services/Facebook/MarketPlace.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="2012bb8d5273d3ec8d367c2bf79e2a04" name="Services/Facebook/Notifications.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="6033491f62ae903f6a352b78c69e1254" name="Services/Facebook/Pages.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="3d56bace9ff73e354d772ea6c3fd84cd" name="Services/Facebook/Photos.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="d21a8903e72843be1cadfb88aae635be" name="Services/Facebook/Profile.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="c2bfc77b124c7ffacccab8fca0e3e4e2" name="Services/Facebook/Share.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="e5835fdf6c02fe4f2d863ac5e49af514" name="Services/Facebook/Users.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="c328b6dea969aa4e7197f4c6af9bcd00" name="Services/Facebook/MarketPlace/Listing.php" role="php">
    <tasks:replace from="@package_version@" to="version" type="package-info" />
   </file>
   <file baseinstalldir="/" md5sum="b16cec82a3bd0158f5f4c459d363143d" name="tests/AllTests.php" role="test" />
   <file baseinstalldir="/" md5sum="47c031385084d5f13abca5281b475c50" name="tests/Services/AllTests.php" role="test" />
   <file baseinstalldir="/" md5sum="ceea493ae9a86378b68a7152076bc3c6" name="tests/Services/Facebook/AdminTest.php" role="test" />
   <file baseinstalldir="/" md5sum="d845ea238396fb671fc30131059e1120" name="tests/Services/Facebook/AllTests.php" role="test" />
   <file baseinstalldir="/" md5sum="033d0856fc12c879b5274909410f6845" name="tests/Services/Facebook/ApplicationTest.php" role="test" />
   <file baseinstalldir="/" md5sum="7f737d33f8d619b7cde065045acf11fe" name="tests/Services/Facebook/AuthTest.php" role="test" />
   <file baseinstalldir="/" md5sum="cb1c8cd1333368d5a78cadb1ce3d7dc9" name="tests/Services/Facebook/BatchTest.php" role="test" />
   <file baseinstalldir="/" md5sum="b12b6666305f9bc9d4bd31c3b5b8d4a5" name="tests/Services/Facebook/CommonTest.php" role="test" />
   <file baseinstalldir="/" md5sum="75cb5cf0b24086fc4b0447a02bb5a010" name="tests/Services/Facebook/ConnectTest.php" role="test" />
   <file baseinstalldir="/" md5sum="c6f8c19605bbb64f3135eaf07cb5d26f" name="tests/Services/Facebook/EventsTest.php" role="test" />
   <file baseinstalldir="/" md5sum="b4a50769855d9af403cbf1158b7ef8d3" name="tests/Services/Facebook/FBMLTest.php" role="test" />
   <file baseinstalldir="/" md5sum="f453a81186c7a9dcd7c51184c4133927" name="tests/Services/Facebook/FeedTest.php" role="test" />
   <file baseinstalldir="/" md5sum="30bf8014f1cb6cbc8acafd503cd3274e" name="tests/Services/Facebook/FQLTest.php" role="test" />
   <file baseinstalldir="/" md5sum="e9b80ab476942dc99592d6a9e66f2fd9" name="tests/Services/Facebook/FriendsTest.php" role="test" />
   <file baseinstalldir="/" md5sum="a9c654ae1191f37207be5ed6b2089f4a" name="tests/Services/Facebook/GroupsTest.php" role="test" />
   <file baseinstalldir="/" md5sum="42713fc63885ce58cfc9191e0b51bb58" name="tests/Services/Facebook/MarketPlaceTest.php" role="test" />
   <file baseinstalldir="/" md5sum="391cdfebbcf6e1304ffd4baf31e0b4cc" name="tests/Services/Facebook/NotificationsTest.php" role="test" />
   <file baseinstalldir="/" md5sum="e76c443a523cbd6074fa91f875b29831" name="tests/Services/Facebook/PagesTest.php" role="test" />
   <file baseinstalldir="/" md5sum="53180cb44ceec8a2060e5c7c8ec9bbee" name="tests/Services/Facebook/PhotosTest.php" role="test" />
   <file baseinstalldir="/" md5sum="e6c8d2ef3a265c3d79fc46088d0d8bed" name="tests/Services/Facebook/ProfileTest.php" role="test" />
   <file baseinstalldir="/" md5sum="d529c76c54b33ddc3e15cbf7d3cc7c15" name="tests/Services/Facebook/ShareTest.php" role="test" />
   <file baseinstalldir="/" md5sum="0bb3e929a25f0073e4e796c4c1a692f7" name="tests/Services/Facebook/skel.php" role="test" />
   <file baseinstalldir="/" md5sum="d3196b2d79a70dff755be6d31ad750dc" name="tests/Services/Facebook/UnitTestCommon.php" role="test" />
   <file baseinstalldir="/" md5sum="22a87616d257c2c0363622813a3d6c4f" name="tests/Services/Facebook/UsersTest.php" role="test" />
  </dir>
 </contents>
 <dependencies>
  <required>
   <php>
    <min>5.2.0</min>
   </php>
   <pearinstaller>
    <min>1.4.0</min>
   </pearinstaller>
  </required>
 </dependencies>
 <phprelease />
 <changelog>
  <release>
   <version>
    <release>0.2.0</release>
    <api>0.2.0</api>
   </version>
   <stability>
    <release>alpha</release>
    <api>alpha</api>
   </stability>
   <date>2009-01-22</date>
   <license uri="http://www.opensource.org/licenses/bsd-license.php">New BSD License</license>
   <notes>* Batching support
* PHPUnit tests</notes>
  </release>
  <release>
   <version>
    <release>0.2.1</release>
    <api>0.2.0</api>
   </version>
   <stability>
    <release>alpha</release>
    <api>alpha</api>
   </stability>
   <date>2009-01-22</date>
   <license uri="http://www.opensource.org/licenses/bsd-license.php">New BSD License</license>
   <notes>* Fixed bug with Notifications</notes>
  </release>
  <release>
   <version>
    <release>0.2.2</release>
    <api>0.2.0</api>
   </version>
   <stability>
    <release>alpha</release>
    <api>alpha</api>
   </stability>
   <date>2009-01-27</date>
   <license uri="http://www.opensource.org/licenses/bsd-license.php">New BSD License</license>
   <notes>* Fixed curl timeout. Switched from Connection timeout to global request timeout.</notes>
  </release>
  <release>
   <version>
    <release>0.2.3</release>
    <api>0.2.0</api>
   </version>
   <stability>
    <release>alpha</release>
    <api>alpha</api>
   </stability>
   <date>2009-01-30</date>
   <license uri="http://www.opensource.org/licenses/bsd-license.php">New BSD License</license>
   <notes>* Created static property, Services_Facebook::$apiURL, that will be applied to every constructed driver.  Services_Facebook_Common::setAPI() will still function the same.</notes>
  </release>
  <release>
   <version>
    <release>0.2.4</release>
    <api>0.2.0</api>
   </version>
   <stability>
    <release>alpha</release>
    <api>alpha</api>
   </stability>
   <date>2009-02-02</date>
   <license uri="http://www.opensource.org/licenses/bsd-license.php">New BSD License</license>
   <notes>* Added users.isAppUser</notes>
  </release>
  <release>
   <version>
    <release>0.2.5</release>
    <api>0.2.0</api>
   </version>
   <stability>
    <release>alpha</release>
    <api>alpha</api>
   </stability>
   <date>2009-02-18</date>
   <license uri="http://www.opensource.org/licenses/bsd-license.php">New BSD License</license>
   <notes>* Added action link support for feeds.registerTemplateBundle</notes>
  </release>
 </changelog>
</package>
