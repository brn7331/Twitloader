<?php

/** Set access tokens here - see: https://dev.twitter.com/apps/ **/
$settings = array('oauth_access_token' => "...",
		  'oauth_access_token_secret' => "...",
		  'consumer_key' => "...",
		  'consumer_secret' => "...");

 
header( 'Content-type: text/html; charset=utf-8' );
echo '
<html>
<head>
<title>Twitter Downloader</title>
</head>';

if (empty($_GET)){
echo '
<body>
<center><b>Twitter Downloaader</b></center>
<form id="searchfrm" action="" method="get">

Number of query (max 180 every 15 minutes) <input type="text" name="query"> (default 10, over 1300 query usually crash)<br><br>

Number of tweets returned in each query (max 100) <input type="text" name="num"> (leave blank for maximum)<br><br>

Delay between request in seconds (max < 5) <input type="text" name="del"> (default 3, to avoid duplicate, 180 queries takes 2 minutes with 0 delay)<br><br>

Language (en, ru, it, ...) <input type="text" name="lan"> (leave blank for italian)<br><br>

Text to search <input type="text" name="text"> (leave blank to search everything, about...) <br><br>

Debug <input type="checkbox" name="debug" value=0 /> <br><br><br>

<input type="submit" value="Start">	        
</form>
';

}else{


if( ($_GET["query"]>=1) && ($_GET["query"]<=2000) ){
	$query=$_GET["query"];
}else{
	$query=10;
}

// check number of request integrity
if( ($_GET["num"]>=1) && ($_GET["num"]<=100) ){
	$request=$_GET["num"];
}else{
	$request=100;
}

// check delay integrity
if( ($_GET["del"]!=null) && ($_GET["del"]>=0) && ($_GET["del"]<5) ){
	$delay=$_GET["del"];
}else{
	$delay=3;
}

// check language
if(strlen($_GET["lan"])==2){
	$language=$_GET["lan"];
}else{
	$language="it";
}

// text
if (trim($_GET["text"])!=""){
	$text=trim($_GET["text"]);
	$null=false;
}else{
	$text="e";
	$null=true;
}

// debug
$debug=!(empty($_GET["debug"]));





ini_set('display_errors', 1);
require_once('TwitterAPIExchange.php');


// uncomment for POST request
// /** URL for REST request, see: https://dev.twitter.com/docs/api/1.1/ **/
// $url = 'https://stream.twitter.com/1.1/statuses/filter.json';
// $requestMethod = 'POST';
// 
// /** POST fields required by the URL above. See relevant docs as above **/
// $postfields = array(
//     'track' => 'twitter', 
// );
// 
// 
// /** Perform a POST request and echo the response **/
// $twitter = new TwitterAPIExchange($settings);
// echo $twitter->buildOauth($url, $requestMethod)
//              ->setPostfields($postfields)
//              ->performRequest();

// info
echo "Start: ";
echo date("g:i a");
echo "\t\t\tDuration: ";

$num=($query/180)*15;

if(round($num)!=$num){
	echo (round($num/15)+1)*15;
}else{
	echo round($num);
}

echo " minutes ( about ";
echo round($num/60);
//echo")";

echo" hours)
<br>
Number of query: $query<br>
Number of request: $request<br>
Language: $language<br>";

if (($debug==true)||($null==false)){
	echo" Searched text: $text<br>";
}





$start = microtime();
$rand = rand(0, 9999);
$myFile = "tweets-$rand.txt";
$fh = fopen($myFile, 'w') or die("can't open file");


echo"
File:  <a href='./$myFile' target='_blank'>$myFile</a> <br>


<br>";






$max = $query;
for ($i = 1; $i <= $max ; $i++) {
	sleep($delay);
	$pausa=false;
	echo "Invio richiesta a twitter $i/$max<br>";
	$url = 'https://api.twitter.com/1.1/search/tweets.json';
	$getfield = "?q=$text&count=$request&lang=$language";
	$requestMethod = 'GET';
	$twitter = new TwitterAPIExchange($settings);
	$output = $twitter -> setGetfield($getfield) -> buildOauth($url, $requestMethod) -> performRequest();

	// never append             
	if (strpos($output, 'Rate limit exceeded') !== false) {
		echo '<br><b>Rate limit exceeded<br>';
		$end = microtime();
		$time = $end - $start;
		$time=round($time);
		$wait = 15*60-$time+10;
		echo "Il processo ricomincera' tra $wait secondi ( ";
		echo round($wait / 60);
		echo " minuti )<br>Ora di interruzione: ";
		echo date("g:i a");
		echo "</b><br><br>";
		
		flush();
		
		usleep($wait*1000000);
		echo "Ora di ripresa: ";
		echo date("g:i a");
		$pausa = true;
		$start = microtime();
		//fclose($fh);
		//exit(0);
	}
	
	if ($pausa == false) {
		echo "Filtraggio dei dati ricevuti<br>";
		preg_match_all('/,"text":.*?source/', $output, $out);
		$tot = count($out[0]);
		$tot--;
		$messaggi;
		$rt = 0;
		$chk = 0;
		for ($p = 0; $p <= $tot; $p++) {
			$messaggi[$chk] = substr($out[0][$p], 9, -9);

			$begin_with_zero = preg_match("/^RT @/", $messaggi[$chk]);
			if ($begin_with_zero) {
				$rt++;
				$chk--;
				//$p--;
				//$tot--;
			}
			$chk++;
		}
		echo "Retweet: $rt<br>";

		$tot2 = count($messaggi);
		$tot2--;
		echo "Scrittura dei dati sul file $myFile<br>";
		for ($j = 0; $j <= $tot2; $j++) {
			//$txt = "$messaggi[$j]\n";
			$txt = json_decode('"'.$messaggi[$j].'"');
			$txt=html_entity_decode($txt);
			echo "&nbsp;&nbsp;&nbsp;&nbsp;---";
			echo $txt;
			echo"---<br>";
			$txt2 = trim(preg_replace('/\s+/', ' ', $txt));
			$txt2="$txt2\n";
			fwrite($fh, $txt2);
		}
		//sleep(2);
		//echo"<br>$output<br>";
		echo "Scrittura numero $i/$max effettuata con successo<br><br>";
		flush();
	}else {
			//$pausa = false;
			$i--;
		}

	}
	fclose($fh);



	//delete duplicate lines
	$filename = $myFile;
	$text = array_unique(file($filename));
	$f = @fopen($filename, 'w+');
	if ($f) {
		fputs($f, join('', $text));
		fclose($f);
	}



	//oltre i 13 mega si riscontrano problemi:
	
	// Fatal error: Maximum execution time of 30 seconds exceeded in /Applications/MAMP/htdocs/twitter/twitter-api-php-master/index.php on line 103
	//si impalla a  scrivere nel file => splittarlo
	
	// Fatal error: Maximum execution time of 30 seconds exceeded in /Applications/MAMP/htdocs/twitter/twitter-api-php-master/TwitterAPIExchange.php on line 213
	//probabilmente dopo un certo traffico twitter applica un blocco temporaneo, effettuare ulteriori test...



	//se è un retweet all'inizio viene scritto RT @persona:
	//echo"<br><br><br><br><br><br>$output";
	
	}
echo"</html>";
	?>