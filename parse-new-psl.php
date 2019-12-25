<?php
require_once "deps/vendor/autoload.php";

// import Punycode
use TrueBV\Punycode;
$puny = new Punycode();

// acquire data
$PSL = fopen("running/public_suffix_list.dat", "r");

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

echo "ICANN suffixes found: " . $countICANN . ", unique: " . $recountICANN . PHP_EOL;
echo "Private suffixes found: " . $countPrivate . ", unique: " . $recountPrivate . PHP_EOL;
echo "Discrepancy: " . (($countICANN + $countPrivate) - ($recountICANN + $recountPrivate)) . PHP_EOL;
echo "Obvious punycode errors: " . $parseErrors . PHP_EOL . PHP_EOL;

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

echo "Max ICANN suffix depth: " . $lvlMaxICANN . PHP_EOL;
echo "Max Private suffix depth: " . $lvlMaxPrivate . PHP_EOL . PHP_EOL;

// consolidation
$ICANNTLDs = [];
$PrivateTLDs = [];

foreach($ICANNex as $SFXex) {
	$walk = [];
	$recon = [];
	$pos = $ICANNTLDs;
	
	for($idx = 0; $idx < count($SFXex); $idx++) {
		if(!array_key_exists($SFXex[$idx], $pos)) {
			$pos[$SFXex[$idx]] = [];
		}
		$walk[$idx] = $pos;
		$recon[$idx] = $SFXex[$idx];
		$pos = $pos[$SFXex[$idx]];
	}
	
	for($idx = count($walk) - 1; $idx > 0; $idx--) {
		$addTo = $walk[$idx - 1];
		$addTo[$recon[$idx - 1]] = $walk[$idx];
		$walk[$idx - 1] = $addTo;
	}
	
	$ICANNTLDs = $walk[0];
}

foreach($Privateex as $SFXex) {
	$walk = [];
	$recon = [];
	$pos = $PrivateTLDs;
	
	for($idx = 0; $idx < count($SFXex); $idx++) {
		if(!array_key_exists($SFXex[$idx], $pos)) {
			$pos[$SFXex[$idx]] = [];
		}
		$walk[$idx] = $pos;
		$recon[$idx] = $SFXex[$idx];
		$pos = $pos[$SFXex[$idx]];
	}
	
	for($idx = count($walk) - 1; $idx > 0; $idx--) {
		$addTo = $walk[$idx - 1];
		$addTo[$recon[$idx - 1]] = $walk[$idx];
		$walk[$idx - 1] = $addTo;
	}
	
	$PrivateTLDs = $walk[0];
}

// debug
// var_dump($ICANNTLDs);
// var_dump($PrivateTLDs);

echo "ICANN TLDs: " . count($ICANNTLDs) . PHP_EOL;
echo "Private TLDs: " . count($PrivateTLDs) . PHP_EOL;

// encode
$ICANNJSON = json_encode($ICANNTLDs);
$PrivateJSON = json_encode($PrivateTLDs);

// write
file_put_contents("running/icann.json", $ICANNJSON);
file_put_contents("running/private.json", $PrivateJSON);
