<?

/*
Copyright (C) 2012 Karen Naylor.

php-asterisk-cdr-view is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

# 0  "accountcode",            // Sets the Account Code for this call (if present)
# 1  "source",                 // The phone number that originated the call 
# 2  "destination",            // The phone number that was dialled 
# 3  "destination context",    // Context in which the call terminated
# 4  "callerid",               // The CallerID of the caller (if available)
# 5  "channel",                // What channel, if any, was used to connect the call
# 6  "destination channel" ,   // As above 
# 7  "last application",       // Last application run on the channel
# 8  "last app argument",      // Arguments passed to the above mentioned application 
# 9  "start time",             // The time at which the call was placed 
# 10 "answer time",  	       // The time at which the call was answered
# 11 "end time",	       // The time at which the call was cleared down 
# 12 duration,		       // Duration of the call including ringtime etc 
# 13 billable seconds,	       // How much calltime was billable (e.g. not ring time)
# 14 "disposition",            // What was the call  result - ANSWERED, NO ANSWER, BUSY
# 15 "amaflags",               // DOCUMENTATION, BILL, IGNORE etc - usually not set 
# 16 "uniqueid",               // Unique ID not usually useful unless recording calls 
# 17 "userfield"               // User field set in SetCDRUserField 

header("Expires: Sunday, 31-Jul-1983 22:44:00 GMT");
$title = "Call Records Report: " . date("l d/m/Y H:i:s");

$csv_file = "/var/log/asterisk/cdr-csv/Master.csv";
$hasrecord = 0; 
$recordings = "http://your.server.tld/path";

# If the user has set a number of records to be displayed use this number of records per page, else use 200
if ($_GET['numrecords'] > 0) {		 
	$numrecords = $_GET['numrecords']; 
} else {
	$numrecords = 200;
}

# For older/newer buttons
if ($_GET['offset'] > 0) {		 
	$offset = $_GET['offset'];
} else {
	$offset = 0;
}




$csv_array=array();
$row = 0;
if (($handle = fopen($csv_file, "r")) !== FALSE) {
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		$row++;
		$csv_array[] = $data;
	}
	fclose($handle);
}

if (($offset + $numrecords) > $row) {
	$numrecords = ($row - $offset);
}

if ($offset >= $row) {
	$offset = 0;
}


$csv_array = array_reverse($csv_array);
$csv_array = array_slice($csv_array, $offset, $numrecords);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?=$title?></title>
<style type="text/css">
.tbl       { margin-left: auto; margin-right: auto; background: #bbbbbb; }
.tbltitle  { font-size: large; font-weight: bold; }
.tblcell   { background: #ffffff; color: #333333; border: 2px solid #ffffff; text-align: center; }
.tblcella  { background: #bbeebb; color: #003300; border: 2px solid #bbeebb; text-align: center; }
.tblcellb  { background: #ccccee; color: #222266; border: 2px solid #ccccee; text-align: center; }
.tblcellc  { background: #eeeeaa; color: #555500; border: 2px solid #eeeeaa; text-align: center; }
.tblcelld  { background: #eeaaaa; color: #990000; border: 2px solid #eeaaaa; text-align: center; }
h1         { text-align: center }
</style>
</head>

<body>
<h1><?=$title?></h1>
<table align="center">
<tr>
<td>
<a href="http://<?=$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']?>?numrecords=<?=$numrecords?>&offset=<?=$offset - $numrecords?>">&lt;= Newer records</a>
</td>
<td>
Displaying last <?=$numrecords?> calls, starting at offset <?=$offset?>
</td>
<td>
<a href="http://<?=$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']?>?numrecords=<?=$numrecords?>&offset=<?=$offset + $numrecords?>">Older records =&gt;</a>
</td>
</tr>
</table>
<br /><br />




<table class="tbl">
<tr>
<th class="tbltitle">Caller ID</th>
<th class="tbltitle">From</th>
<th class="tbltitle">To</th>
<th class="tbltitle">Result</th>
<th class="tbltitle">Start Time</th>
<th class="tbltitle">End Time</th>
<th class="tbltitle">Duration</th>
<? if($hasrecord) { ?>
	<th class="tbltitle">Listen</th></tr>
<? } else { ?>
	</tr>
<? } ?>


<? 

foreach($csv_array as $cdrentry) {

# Start a new row
	print "<tr>\n";
	
	# Are these fields set to 0? i.e. no CallerID?
	if (($cdrentry[1] != "0") and ($cdrentry[4] != "0")) { 
		print "<td class=\"tblcell\">$cdrentry[4]</td>\n";

		# Internal call?
		if(preg_match('/^2\d\d/', $cdrentry[1])) {
			print "<td class=\"tblcella\">$cdrentry[1]</td>\n";
		} else {
			print "<td class=\"tblcellb\">$cdrentry[1]</td>\n";
		}
	} else {
		print "<td class=\"tblcelld\">Unavailable</td>\n";
		print "<td class=\"tblcelld\">External Caller</td>\n";
	}
	
	# Did this call go to the extension 's'? Probably an incoming call
	if ($cdrentry[2] != "s") { 
		if(preg_match('/^9\d*/', $cdrentry[2])) {
			print "<td class=\"tblcellb\">$cdrentry[2]</td>\n";
		} else {
			print "<td class=\"tblcella\">$cdrentry[2]</td>\n";
		}
	} else {
		$entry = explode("-", $cdrentry[6], 2);
		print "<td class=\"tblcellc\">Incoming Call - ".$entry[0]."</td>\n";
	}
	# Show which channel the call came through - can be useful
	#print "<td class=\"tblcell\">$cdrentry[6]</td>\n";
	# If call was answered colour it green, else colour it red
	if ($cdrentry[14] != "ANSWERED") {
		print "<td class=\"tblcelld\">$cdrentry[14]</td>\n";
	} else {
		print "<td class=\"tblcella\">$cdrentry[14]</td>\n";
	}
	print "<td class=\"tblcell\">$cdrentry[9]</td>\n";
	print "<td class=\"tblcell\">$cdrentry[11]</td>\n";

	$time = (int)($cdrentry[12] / 60);
	$secs = $cdrentry[12] - ($time * 60);
	if ($secs < 10) { $secs = "0".$secs; }
	# Convert duration from seconds to minutes:seconds
	print "<td class=\"tblcell\">$time:$secs</td>\n"; 
	
	# If recordings are enabled, print the 'Listen' column, and link to the .wav
	if ($hasrecord) { 	
		$temp = explode(' ', $cdrentry[9]);
		$sort = explode('-', $temp[0]);
		$temp[0] = $sort[2] . $sort[1] . $sort[0];
		$temp[1] = preg_replace('/-/g', ':', $temp[1]);
		$callfilename = "$temp[0]-$temp[1]-$cdrentry[16].wav";
	
		print "<td class=\"tblcell\"><a href='$recordings/$callfilename'>";
		print "Listen</a></td>\n</tr>\n";
	} else {
		# Otherwise, don't include links to non-existant recordings
		print "</tr>\n";
	}
} ?>

</table>

</body></html>
