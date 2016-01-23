<?php

//imports
require_once 'includes/sqlconn.php';
require_once 'includes/functions.php';

//constants
$protocolVersion = 19;

//get headers
$header = getallheaders();
$toprint = array();

//read post data
$postdata = file_get_contents('php://input');

//default player data
$userName = 'nouser';

//TODO: implement site, if ua is not 'osu!'

header('cho-token: '.'yomama');	//TODO: implement tokens
header('cho-protocol: '.$protocolVersion);

if (!isset($header['osu-token'])) {
	//parse post data
	$lines = explode("\n", $postdata);
	if (count($lines) != 4) {
		DieError(-5);
	} else {
		//do checks
		$userName = $lines[0];
		$passHash = $lines[1];

		$user = GetUserByName($userName);
		if (CheckUserPassword($user, $passHash) === false) DieError(-1);
		$userId = $user['id'];
		$userRank = $user['status'];
		$userName = $user['username'];	//in case casing is different
		if ($userRank == 0) ActivateUser($userId);
		if ($userRank == -1) DieError(-3);	//banned

		//TODO: implement these things
		$pp = 0;					//TODO: implement pp
		$globalRank = 0;
		$totalScore = GetTotalScoreFromId($user['id']);
		$accuracy = GetAccuracyFromId($user['id']);
		$playCount = GetPlaycountFromId($user['id']);
		//5,000 / 3 * (4n^3 - 3n^2 - n) + 1.25 * 1.8^(n - 60)
		$experience = 0;//$totalscore;	//ACTUALLY? NVM

		$toprint = array_merge(
			CreatePacket(92, 0),	//ban status/time

			CreatePacket(5, $userId),	//user id
			CreatePacket(75, $protocolVersion),	//bancho protocol version
			CreatePacket(71, $userRank),	//user rank (supporter etc)
			CreatePacket(72, array(1, 2)),	//friend list
			CreatePacket(83, array(	//local player
				'id' => $userId,
				'playerName' => $userName,
				'utcOffset' => 0 + 24,
				'country' => 0,
				'playerRank' => $userRank,
				'longitude' => 0,
				'latitude' => 0,
				'globalRank' => $globalRank,
				)),
			CreatePacket(11, array(		//more local player data
				'id' => $userId,
					'bStatus' => 0,		//byte
					'string0' => '',	//String
					'string1' => '',	//string
					'mods' => 0,		//int
					'playmode' => 0,	//byte
					'int0' => 0,		//int
				'score' => $totalScore,			//long 	score
				'accuracy' => $accuracy,	//float accuracy
				'playcount' => $playCount,			//int playcount
				'experience' => $experience,			//long 	experience
				'int1' => $globalRank,	//int 	global rank?
				'pp' => $pp,			//short	pp 				if set, will use?
				)),	//*/
			CreatePacket(83, array(	//bancho bob
				'id' => 3,
				'playerName' => GetUserById(3)['username'],
				'utcOffset' => 0 + 24,
				'country' => 1,
				'playerRank' => 0,
				'longitude' => 0,
				'latitude' => 0,
				'globalRank' => 0,
				)),
			CreatePacket(96, array(0, $userId)),	//TODO: list of players

			CreatePacket(89, null),
			//foreach player online, packet 12 or 95
			CreatePacket(64, '#osu'),	//main channel
			CreatePacket(64, '#news'),
			CreatePacket(65, array('#osu', 'Main channel', 2147483647 - 1)),	//secondary channel
			CreatePacket(65, array('#news', 'This will contain announcements and info, while beta lasts.', 1)),
			CreatePacket(65, array('#kfc', 'Kawaii friends club', 0)),	//secondary channel
			CreatePacket(65, array('#aqn', 'cuz fuck yeah', 1337)),
			//CreatePacket(105, "HoLLy_HaCKeR eyes have awakened.\nGo pray to your mommy, banhammer is on it's way.")	//scary msg

			//players
			//GetOnlinePlayersPackets(),

			CreatePacket(07, array('BanchoBob', 'This is a test message! First step to getting chat working!', '#osu', 3))
		);
	}


} else {
	//Get shit from database i guess
	$userId = 1;
	$userName = "HoLLy_HaCKeR";
	$userRank = 15;
	$globalRank = 1;

	//TODO: implement other packets
	//TODO: create packets where currenttime-lastlogintime < 60?
	$toprint = array_merge(
			CreatePacket(83, array(	//local player
				'id' => $userId,
				'playerName' => $userName,
				'utcOffset' => 0 + 24,
				'country' => 0,
				'playerRank' => $userRank,
				'longitude' => 0,
				'latitude' => 0,
				'globalRank' => $globalRank,
				))
			);
}
SetLastActiveForId($userId);
echo implode(array_map("chr", $toprint));

function GetOnlinePlayersPackets() {
	$players = GetOnlinePlayers();
	$return = array();
	foreach ($players as $player) {
    	$newarray = ConvertPlayer($player);
		$return = array_merge($toreturn, CreatePacket(83, $newarray));
	}
	return $toreturn;
}
function ConvertPlayer($player) {
	return array(
		'id' => $player['id'],
		'playerName' => $player['username'],
		'utcOffset' => 0 + 24,
		'country' => 1,
		'playerRank' => $player['status'],
		'longitude' => 0,
		'latitude' => 0,
		'globalRank' => 0,
	);
}

function DieError($err) {
	die(implode(array_map("chr", CreatePacket(5, $err))));
}

function GetLongBytes($long) {
	$value = $long;
	$highMap = 0xffffffff00000000;
	$lowMap = 0x00000000ffffffff;
	$higher = ($value & $highMap) >>32;
	$lower = $value & $lowMap;
	$packed = pack('NN', $higher, $lower);
	return array_reverse(unpack('C*', $packed));
}

function ULeb128($string) {	//TODO: use proper ULEB128
	if ($string == '') return array(0);
	$toreturn = array();
	$toreturn = array_merge(
			array(11, strlen($string)),
			unpack('C*', $string));
	//var_dump($toreturn);
	return $toreturn;
}

function CreatePacket($type, $data = null) {
	$toreturn = '';
	$length = 0;
	switch ($type) {
		//string
		case 24:	//show custom, orange notification
		case 64:	//Main channel
		case 66:	//remove channel?
		case 105:	//show scary msg
			$toreturn = ULeb128($data);
			break;
		//empty
		case 23:
		case 50:	//something with match-confirm
		case 59:	//something with chat channels?
		case 80:	//Sneaky Shizzle
			$toreturn = array();
			break;
		//Class17 (player data 02)
		case 83:	//local player
			$toreturn = array();
			$toreturn = array_merge(
				unpack('C*', pack('L*', $data['id'])),
				ULeb128($data['playerName']),				//TODO: fix names
				unpack('C*', pack('C*', $data['utcOffset'])),
				unpack('C*', pack('C*', $data['country'])),
				unpack('C*', pack('C*', $data['playerRank'])),
				unpack('C*', pack('f*', $data['longitude'])),
				unpack('C*', pack('f*', $data['latitude'])),
				unpack('C*', pack('L*', $data['globalRank']))
				);
			break;
		//Class19 (player data 01)
		case 11:	//some player thing
			$toreturn = array_merge(
				unpack('C*', pack('L*', $data['id'])),
					unpack('C*', pack('C*', $data['bStatus'])),
					ULeb128($data['string0']),
					ULeb128($data['string1']),
					unpack('C*', pack('L*', $data['mods'])),
					unpack('C*', pack('C*', $data['playmode'])),
					unpack('C*', pack('L*', $data['int0'])),
				GetLongBytes($data['score']),
				unpack('C*', pack('f*', $data['accuracy'])),
				unpack('C*', pack('L*', $data['playcount'])),
				GetLongBytes($data['experience']),
				unpack('C*', pack('L*', $data['int1'])),
				unpack('C*', pack('S*', $data['pp']))
				);
			break;
		//Class20 (string, string, short)
		case 65: 	//chat channel with title
			$toreturn = array_merge(
			ULeb128($data[0]),
			ULeb128($data[1]),
			unpack('C*', pack('S*', $data[2]))
		);
			break;
		//chat Message
		case 07:
			$toreturn = array_merge(
			ULeb128($data[0]),
			ULeb128($data[1]),
			ULeb128($data[2]),
			unpack('C*', pack('I', $data[3]))
		);
			break;
		//int[] (short length, int[length])
		case 72:	//friend list, int[]
		case 96:	//list of online players
			$l1 = unpack('C*', pack('S', sizeof($data)));
			$toreturn = array();
			foreach ($data as $key => $value) {
				$toreturn = array_merge($toreturn, unpack('C*', pack('I', $value)) );
			}
			$toreturn = array_merge($l1, $toreturn);
			break;
		//int32
		case 5:		//user id
		case 71:	//user rank
		case 75: 	//cho protocol
		case 92:	//ban status
		default:
			$toreturn = unpack('C*', pack('L*', $data));
			break;
	}

	return array_merge(
					unpack('S*', pack("L*", $type)),			//type
					array(0),									//unused byte
					unpack('C*', pack('L', sizeof($toreturn))),	//length
					$toreturn									//data
				);
}
?>
