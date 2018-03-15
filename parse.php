<?php
require_once "deps/vendor/autoload.php";

// import Punycode
use TrueBV\Punycode;
$puny = new Punycode();

// acquire data
$PSL = fopen("data/public_suffix_list.dat", "r");

// punycoding and rule trimming
$endICANN = false;
$ICANN = [];
$countICANN = 0;
$endPrivate = false;
$Private = [];
$countPrivate = 0;
$parseErrors = 0;

while($endICANN == false || $endPrivate == false) {
	// get a line
	$holster = fgets($PSL, 65535);
	
	// verify line is not the terminus of ICANN suffixes
	if(strcmp($holster, "// ===END ICANN DOMAINS===\n") === 0) {
		$endICANN = true;
	} elseif(strcmp($holster, "// ===END PRIVATE DOMAINS===\n") === 0) {
		$endPrivate = true;
	} else {
		// sanitize
		$holster = trim($holster, " \t\n\r\0\x0B");
		
		// must not be a comment, or blank
		if(strcmp(substr($holster, 0, 2), "//") !== 0 && strlen($holster) > 0) {
			
			// remove exemption rules
			if(strpos($holster, "!") !== false) {
				$holster = explode(".", $holster);
				unset($holster[0]);
				$holster = implode(".", $holster);
			}
			
			// remove supercookie rulesets and errata which are less important for our use case
			for($i = 0; $i < 255; $i++) {
				$holster = ltrim($holster, "*");
				$holster = trim($holster, ".");
			}
			
			// punycode it
			$small = $puny->encode($holster);
			// echo $holster . " <-> " . $small . " <-> " . $puny->decode($small) . "\n"; // testing
			
			if(!strcmp($holster, $puny->decode($small)) === 0) {
				$parseErrors++;
			}
			
			if($endICANN == false) {
				$ICANN[] = $small;
				$countICANN++;
			} else {
				$Private[] = $small;
				$countPrivate++;
			}
		}
	}
}

// status checking before split operation
$ICANN = array_unique($ICANN);
$recountICANN = count($ICANN);
$Private = array_unique($Private);
$recountPrivate = count($Private);

echo "ICANN suffixes found: " . $countICANN . "   Private suffixes found: " . $countPrivate . PHP_EOL;
echo "Unique ICANN suffixes: " . $recountICANN . "   Unique Private suffixes: " . $recountPrivate . PHP_EOL;
echo "Discrepancy: " . (($countICANN + $countPrivate) - ($recountICANN + $recountPrivate)) . PHP_EOL;
echo "Obvious punycode errors: " . $parseErrors . PHP_EOL;

// splitting
$ICANNex = [];
$lvlMaxICANN = 0;
$Privateex = [];
$lvlMaxPrivate = 0;

foreach($ICANN as $SFX) {
	$SFXex = explode(".", $SFX);
	
	$tempCtr = count($SFXex);
	if($tempCtr > $lvlMaxICANN) {
		$lvlMaxICANN = $tempCtr;
	}
	
	$SFXex = array_reverse($SFXex);
	ksort($SFXex);
	$SFXex[] = "%";
	
	$ICANNex[] = $SFXex;
}

foreach($Private as $SFX) {
	$SFXex = explode(".", $SFX);
	
	$tempCtr = count($SFXex);
	if($tempCtr > $lvlMaxPrivate) {
		$lvlMaxPrivate = $tempCtr;
	}
	
	$SFXex = array_reverse($SFXex);
	ksort($SFXex);
	$SFXex[] = "%";
	
	$Privateex[] = $SFXex;
}

// debug
// var_dump($ICANNex);
// var_dump($Privateex);

echo "Max ICANN suffix depth: " . $lvlMaxICANN . "   Max Private suffix depth: " . $lvlMaxPrivate . PHP_EOL;

// consolidation
$ICANNTLDs = [];
$PrivateTLDs = [];

foreach($ICANNex as $SFXex) {
	if(!array_key_exists($SFXex[0], $ICANNTLDs)) {
		$ICANNTLDs[$SFXex[0]] = [];
	}
	
	$SFXm = [];
	for($i = 1; $i < count($SFXex); $i++) {
		$SFXm[$i - 1] = $SFXex[$i];
	}
	
	$ICANNTLDs[$SFXex[0]] = $SFXm;
}

foreach($Privateex as $SFXex) {
	if(!array_key_exists($SFXex[0], $PrivateTLDs)) {
		$PrivateTLDs[$SFXex[0]] = [];
	}
	
	$SFXm = [];
	for($i = 1; $i < count($SFXex); $i++) {
		$SFXm[$i - 1] = $SFXex[$i];
	}
	
	$PrivateTLDs[$SFXex[0]][] = $SFXm;
}

// debug
// var_dump($ICANNTLDs);
// var_dump($PrivateTLDs);

echo "ICANN TLDs: " . count($ICANNTLDs) . "   Private TLDs: " . count($PrivateTLDs) . PHP_EOL;

// encode
$ICANNJSON = json_encode($ICANNTLDs);
$PrivateJSON = json_encode($PrivateTLDs);

// write
file_put_contents("data/icann.json", $ICANNJSON);
file_put_contents("data/private.json", $PrivateJSON);

echo "Data written to suffixer/data" . PHP_EOL;