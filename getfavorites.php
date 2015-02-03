<?php

//Twitter Details
$user = "username";
$pwd = "password";

//Get number of favorites
$xml=file_get_contents('http://twitter.com/users/show.xml?screen_name='.$user.'');
if (preg_match('/favourites_count>(.*)</',$xml,$match)!=0) {
	$tw['count'] = $match[1];
}
$favs = $tw['count'];

//Twitter API returns max 20 favorites per page
$maxperpage = 20;
//Divide the total number of favorites by max per page
$pages = ceil($favs / $maxperpage);

function multiRequest($data,$options = array()) {

  // array of curl handles
  $curly = array();
  // data to be returned
  $result = array();

  // multi handle
  $mh = curl_multi_init();

  // loop through $data and create curl handles
  // then add them to the multi-handle
  foreach ($data as $id => $d) {

    $curly[$id] = curl_init();

    $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
    curl_setopt($curly[$id], CURLOPT_URL,            $url);
    curl_setopt($curly[$id], CURLOPT_HEADER,         0);
    curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);


    // post?
    if (is_array($d)) {
      if (!empty($d['post'])) {
        curl_setopt($curly[$id], CURLOPT_POST,       1);
        curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
      }
    }

    // extra options?
    if (!empty($options)) {
      curl_setopt_array($curly[$id], $options);
    }

    curl_multi_add_handle($mh, $curly[$id]);
  }

  // execute the handles
  $running = null;
  do {
    curl_multi_exec($mh, $running);
  } while($running > 0);

  // get content and remove handles
  foreach($curly as $id => $c) {
    $result[$id] = curl_multi_getcontent($c);
    curl_multi_remove_handle($mh, $c);
  }

  // all done
  curl_multi_close($mh);

  return $result;
}


//Get the pages and URL

$url = "http://http://twitter.com/favorites.rss?page=%s";

$i=1;

$url=array();

while($i<=$pages) {
    $url[]="http://twitter.com/favorites.atom?page=".$i ;
    $i++;
}

//Fetch the data from URLS

$data = $url;

$r = multiRequest($data, array(CURLOPT_USERPWD => "$user:$pwd"));

//Output contents of Array 

//var_dump($r);

//Array to 1 XML 

$xml = implode('', $r); 
$xml = str_replace(array('<?xml version="1.0" encoding="UTF-8"?>',
	'<statuses type="array">',
	'</statuses>'), '', $xml); 

$xml = '<?xml version="1.0" encoding="UTF-8"?>'
     . '<statuses type="array">'
     . $xml
     . '</statuses>';

$favs = simplexml_load_string($xml);

//echo '<pre>';
//var_dump($favs);
//echo '</pre>';

//Convert plain text to links
function makeClickableLinks($text) {
        $text = html_entity_decode($text);
        $text = " ".$text;
        $text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
                '<a href="\\1" target=_blank>\\1</a>', $text);
        $text = eregi_replace('(((f|ht){1}tps://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
                '<a href="\\1" target=_blank>\\1</a>', $text);
        $text = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
        '\\1<a href="http://\\2" target=_blank>\\2</a>', $text);
        $text = eregi_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})',
        '<a href="mailto:\\1" target=_blank>\\1</a>', $text);
        return $text;
}
