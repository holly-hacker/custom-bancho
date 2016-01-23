<?php
$host = 'localhost';
$dbusername = 'root';
$dbpassword = '';
$database = 'osuserver';


try {
  $db = new PDO("mysql:dbname=$database;host=$host", $dbusername, $dbpassword);
} catch (Exception $e) {
  die($e->getMessage());
}

require_once $_SERVER['DOCUMENT_ROOT'].'/includes/functions.php';

function AddUser($username, $hash, $salt) {
  $query = 'INSERT INTO users (username, passhash, passsalt)
            VALUES (:username, :hash, :salt)';

  global $db;
  $prepared = $db->prepare($query);
  $prepared->bindParam(':username', $username, PDO::PARAM_STR);
  $prepared->bindParam(':hash', $hash, PDO::PARAM_STR);
  $prepared->bindParam(':salt', $salt, PDO::PARAM_STR);
  return $prepared->execute();
}

function ActivateUser($id) {
  $query = 'UPDATE `users` SET `status`=1 WHERE `id`=:id';

  global $db;
  $prepared = $db->prepare($query);
  $prepared->bindParam(':id', $id, PDO::PARAM_INT);
  return $prepared->execute();
}

function CheckPassword($id, $hashed) {
  $user = GetUserById($id);
  return CheckUserPassword($user, $hashed);
}
function CheckUserPassword($user, $hashed) {
  if ($user == null) return false;
  if ($user['passhash'] != HashWithSalt($hashed, $user['passsalt'])) return false;
  return true;
}

function GetUser($input) {
  //TODO, not yet inplemented, to do when implementing userpages
}

function GetUserById($id) {
  $query = 'SELECT * FROM `users` WHERE `id` = :id';

  global $db;
  $prepared = $db->prepare($query);
  $prepared->bindParam(':id', $id, PDO::PARAM_INT);
  $prepared->execute();
  return $prepared->fetch(PDO::FETCH_ASSOC);
}
function GetUserByName($name) {
  $query = 'SELECT * FROM `users` WHERE `username` = :user';

  global $db;
  $prepared = $db->prepare($query);
  $prepared->bindParam(':user', $name, PDO::PARAM_STR);
  $prepared->execute();
  return $prepared->fetch(PDO::FETCH_ASSOC);
}

function GetScores($hash) {
  $query = 'SELECT * FROM `scores` WHERE `beatmapHash` = :hash';

  global $db;
  $prepared = $db->prepare($query);
  $prepared->bindParam(':hash', $hash, PDO::PARAM_STR);
  $prepared->execute();
  return $prepared->fetchAll(PDO::FETCH_ASSOC);
}
function GetScoreOfPlayer($hash, $id) {
  //TODO
}

function GetTotalScoreFromId($userid) {
  global $db;
  $prepared = $db->prepare('SELECT SUM(score) FROM `scores` WHERE `playerID` = :id');
  $prepared->bindParam(':id', $userid, PDO::PARAM_STR);
  $prepared->execute();

  return $prepared->fetch()[0];
}
function GetPlaycountFromId($userid) {
  global $db;
  $prepared = $db->prepare('SELECT * FROM `scores` WHERE `playerID` = :id');
  $prepared->bindParam(':id', $userid, PDO::PARAM_INT);
  $prepared->execute();

  $rows =  $prepared->fetchAll();
  return count($rows);
}
function GetAccuracyFromId($userid) {
  global $db;
  $prepared = $db->prepare('SELECT (SUM(count300)*6 + SUM(count100)*2 + SUM(count50)*1)/((SUM(count300)+SUM(count100)+SUM(count50)+SUM(countMiss))*6) FROM `scores` WHERE `playerID` = :id');
  $prepared->bindParam(':id', $userid, PDO::PARAM_STR);
  $prepared->execute();

  return $prepared->fetch()[0];
}

function GetOnlinePlayers() {
  $query = 'SELECT * FROM `users` WHERE 1 = 1'; //TODO use lastlogin

  global $db;
  $prepared = $db->prepare($query);
  $prepared->execute();
  return $prepared->fetchAll(PDO::FETCH_ASSOC);
}

function SetLastActiveForId($id) {
  $query = 'UPDATE `users` SET `lastactive`=GETDATE() WHERE `id`=:id';

  global $db;
  $prepared = $db->prepare($query);
  $prepared->bindParam(':id', $id, PDO::PARAM_INT);
  return $prepared->execute();
}
?>
