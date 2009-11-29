<?php

// database settings

define(       "DB_NAME", ""      ); // name of database
define(       "DB_USER", ""      ); // user name
define(   "DB_PASSWORD", ""      ); // user password

define( "STANDARD_LANG", "eng"   ); // ger, eng

// options

define(      "INTRANET", "0"     ); // change to 1 for password login
define(          "PING", "1"     ); // change to 0 for silent operation
define( "REALTIME_HOST", ""      ); // host for comet push
define( "REALTIME_PORT", ""      ); // port for comet push
define(     "MEMCACHED", "0"     ); // memcached cache duration
define(    "TWEET_SIZE", "140"   );

// more database settings

define(       "DB_HOST", ""      );
define(    "DB_CHARSET", "utf8"  );
define(    "DB_COLLATE", ""      );

// pretty URLs setup
// example value: "http://twitteronia.com"
// you must also copy resource/prettyurls/.htaccess
// to your omb folder & edit lines 2 and 3 of the file

define("PRETTY_URL_BASE", ""     );

