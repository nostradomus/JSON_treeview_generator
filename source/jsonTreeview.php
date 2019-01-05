#!/usr/bin/php
<?php
declare(strict_types=1);

/*******************************************************
*  Tool to convert json data into a text-based treeview
*  31-12-2018
*  Copyright 2018
*  v1.0.0.0 : initial version
*******************************************************/

define("version", "1.0.0.0");
define("versiondate", "31-12-2018");
define("appname", "jsonTreeview");

$version = version;                    // variables ($-notation) containing constants for integration in HereDoc
$versiondate = versiondate;
$appname = 'JSON TREEVIEW GENERATOR';

// application help in HereDoc format/syntax (will be echo'd with -h -H --help arguments)
$man = <<<MAN

$appname (1)            General Commands Manual            $appname (1)

NAME
       jsonTreeview - generate a text-based treeview for json-data originating from file or web

SYNOPSIS
       jsonTreeview [-h]
       jsonTreeview [-v]
       jsonTreeview [-f file] {--valueonly}
       jsonTreeview [-u url] {--valueonly}

DESCRIPTION
       jsonTreeview is a tool to generate text-based treeviews for json-data originating
       from either a file or a web-API.

       Options -f and -u, followed by either a path/filename or a web-address, will allow
       the tool to go get the json-data to transform into a text-based treeview, as
       displayed below :

       http://www.thewebaddress/data.json or /pathname/filename.json
             |_firstelementkey
                |_firstdatalinekey : 1234
                |_seconddatalinekey : abcde
                |_...
             |_secondelementkey
                |_firstdatalinekey : 5678
                |_seconddatalinekey : fghij
                |_...
             |_...

       The root tag of the treeview will refer to the origin of the json-data.
       Collection headers will display the key as label.
       Branch ends will display key/value pairs.

       When adding the --valueonly option to the commandline, a treeiew with only element values
       will be generated (no key labels). Collection headers will be labeled ARRAY.

       The -h option will display this help text.

       The -v option will display the tools software version number.

OPTIONS
       -h -H --help           display this help message
       -v -V --version        display this application's version
       -f -F --file           generate a treeview from the json-data in the file
       -u -U --url            generate a treeview from the json-data retrieved from the url
             --valueonly      generate a treeview with values only (no key labels)

DIAGNOSTICS
       Informative messages will be displayed when the application is called in
       an incorrect way (i.e. missing/unkown/invalid parameters)

AUTHOR
       This tool was created by The Nostradomus Engineering Team, for quick analysis
       of json-data from web-APIs. We can be contacted through
       http://nostradomus.ddns.net/contactform.html for any questions.

Version : $version                        $versiondate                       $appname (1)

MAN;

// recursive iterator to build a text-based treeview with key/value pairs
function iterateArray($dict,$level) {
    $spaces = '';                                        // leading spaces depending on the branch level
    for ($i = 0; $i <= ($level*3); $i++) {
        $spaces = $spaces.' ';
      }
    $branch = '';
    foreach($dict as $key => $value) {
        if (!(is_array($value))) {                       // add an end element (key/value) to the tree
          $indent = '';
          for ($i = 0; $i <= (strlen($key)+3); $i++) {   // leading spaces in case of a multiline value
              $indent = $indent.' ';
            }
          $value = str_replace("\n","\n ".$spaces.$indent,$value);
          $branch = $branch.$spaces."|_".$key." : ".$value.PHP_EOL;
          } else {                                       // add a branch title for a next level to the tree
              $next = iterateArray($value,($level+1));
              $branch = $branch.$spaces."|_".$key.PHP_EOL.$next;
             }
    }
    return $branch;
  }

// value-only treeview generator based on language built-in iterators
function iterateValues($dict) {
    $tree = '';
    $json2list = new RecursiveArrayIterator($dict);
    $list2tree = new RecursiveTreeIterator($json2list);

    foreach( $list2tree as $key => $value ) {
        $tree = $tree.$value.PHP_EOL;
    }
    return $tree;
  }

// check if the script was launched with arguments, if not, show help
if (!isset($argv[1])) {
    $argv[1] = '-h';
  }

$argvcount = count($argv);
$fullTreeview = true;           // by default, a treeview with key/value pairs is generated

// check the presence of a value-only request for the treeview, to set the concerning flag
if (($argvcount == 4) && ($argv[3] == "--valueonly")) {
    $fullTreeview = false;
  }

// main application loop
switch ($argv[1]) {
    case "-H":
    case "-h":
    case "--help":
        echo $man.PHP_EOL;
        break;
    case "-V":
    case "-v":
    case "--version":
        echo "VERSION : ".version.", from ".versiondate.PHP_EOL.PHP_EOL;
        break;
    case "-F":
    case "-f":
    case "--file":                                                        // create the json tree from file
        if ($argvcount >= 3) {
            $file = $argv[2];                                             // get the path/filename from the arguments list
            $json = file_get_contents($file);                             // get the json-data from the file
            $json = json_decode($json, true, 512, JSON_OBJECT_AS_ARRAY);  // decode the json-data to an array
            $jsonTree = "$file".PHP_EOL;                                  // create the root of the json tree
            if ($fullTreeview) {
                $jsonTree = $jsonTree.iterateArray($json,1);              // iterate through the tree, and append all elements in branches (keys and values)
              } else {
                  $jsonTree = $jsonTree.iterateValues($json);             // iterate through the tree, and append all elements in branches (values only)
                }
            echo $jsonTree;                                               // send the result to the output
          } else {                                                        // inform user on missing arguments
              echo "File/path to process is missing. Check help (-h) for correct usage".PHP_EOL;
            }
        break;
    case "-U":
    case "-u":
    case "--url":                                                         // create the json tree from data to be retrieved from the web
        if ($argvcount >= 3) {
            $url = $argv[2];                                              // get the url-address from the arguments list
            $epochtime = time();                                          // gets time as epoch-count
            $URL = "$url?relevance=$epochtime";                           // an extra argument (relevance) is added to the http-request for caching-issues
            $json = file_get_contents($URL);                              // get the json-data from the web(-api)
            $json = json_decode($json, true, 512, JSON_OBJECT_AS_ARRAY);  // decode the json-data to an array
            $jsonTree = "$url".PHP_EOL;                                   // create the root of the json tree
            if ($fullTreeview) {
                $jsonTree = $jsonTree.iterateArray($json,1);              // iterate through the tree, and append all elements in branches (keys and values)
              } else {
                  $jsonTree = $jsonTree.iterateValues($json);             // iterate through the tree, and append all elements in branches (values only)
                }
            echo $jsonTree;                                               // send the result to the output
          } else {                                                        // inform user on missing arguments
              echo "URL to process is missing. Check help (-h) for correct usage".PHP_EOL;
            }
        break;
    default:                                                             // refer to help in case of invalid arguments
        echo "Unknown or invalid argument(s).".PHP_EOL."  please check the help for proper usage".PHP_EOL."  type : ".appname.".php --help".PHP_EOL.PHP_EOL;
  }

