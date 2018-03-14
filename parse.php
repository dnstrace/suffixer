<?php
require_once "deps/vendor/autoload.php";

// import Punycode
use TrueBV\Punycode;

$puny = new Punycode();

$PSL = fopen("public_suffix_list.dat", "r");

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
	
	// verify line is not the terminus of ICANN TLDs
	if(strcmp($holster, "// ===END ICANN DOMAINS===\n") === 0) {
		$endICANN = true;
	} elseif(strcmp($holster, "// ===END PRIVATE DOMAINS===\n") === 0) {
		$endPrivate = true;
	} else {
		// sanitize
		$holster = trim($holster, " \t\n\r\0\x0B");
		
		// must not be a comment, or blank
		if(strcmp(substr($holster, 0, 2), "//") !== 0 && strlen($holster) > 0) {
			
			// remove rulesets which are less important for our use case
			$holster = ltrim($holster, "*!");
			$holster = trim($holster, ".");
			
			// punycode it
			$small = $puny->encode($holster);
			echo $holster . " <-> " . $small . " <-> " . $puny->decode($small) . "\n"; // testing
			
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

echo "ICANN TLDs found: " . $countICANN . "   Private TLDs found: " . $countPrivate . PHP_EOL;
echo "Unique ICANN TLDs: " . $recountICANN . "   Unique Private TLDs: " . $recountPrivate . PHP_EOL;
echo "Discrepancy: " . (($countICANN + $countPrivate) - ($recountICANN + $recountPrivate)) . PHP_EOL;
echo "Obvious punycode errors: " . $parseErrors . PHP_EOL;

// splitting into usable format
$ICANNex = [];
foreach($ICANN as $TLD) {
	$TLDex = explode(".", $TLD);
	$TLDex = array_reverse($TLDex);
	ksort($TLDex);
	$ICANNex[] = $TLDex;
}

$Privateex = [];
foreach($Private as $TLD) {
	$TLDex = explode(".", $TLD);
	$TLDex = array_reverse($TLDex);
	ksort($TLDex);
	$Privateex[] = $TLDex;
}

// debug
// var_dump($ICANNex);
// var_dump($Privateex);