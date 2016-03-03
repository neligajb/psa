<?php
include 'dbInfo.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

global $db_password;
global $db_address;
global $db_user;
global $db_name;

//if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
//  die('Bad POST request.');

//set timezone
date_default_timezone_set('UTC');

//connect to db

$mysqli = new mysqli($db_address, $db_user, $db_password, $db_name);
if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  die();
}


//get player list
if (!($stmt = $mysqli->prepare("SELECT p.pid, p.fname, p.lname, p.rank, p.dob, c.name FROM psa_players p inner JOIN
  psa_countries c ON p.country=c.cid;")))
  echo "Prepared statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->execute())
  echo "Execute statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

$players = array();
$player = array();

$out_pid = NULL;
$out_fname = NULL;
$out_lname = NULL;
$out_rank = NULL;
$out_dob = NULL;
$out_country = NULL;

if (!$stmt->bind_result($out_pid, $out_fname, $out_lname, $out_rank, $out_dob, $out_country))
  echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

while ($stmt->fetch())
{
  $player['id'] = $out_pid;
  $player['name'] = $out_fname . " " . $out_lname;
  $player['rank'] = $out_rank;
  $player['age'] = date_diff(date_create($out_dob), date_create('today'))->y;
  $player['country'] = $out_country;

  array_push($players, $player);
}


//get each player's sponsor list
foreach ($players as $key => $player)
{
  $stmt = NULL;
  $sponsors = array();
  $out_sponsor = NULL;
  $pid = $player['id'];

  if (!($stmt = $mysqli->prepare("SELECT s.name FROM psa_sponsors s INNER JOIN psa_player_sponsor ps ON s.sid=ps.sid
  WHERE ps.pid=?;")))
    echo "Prepared statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

  if (!$stmt->bind_param("i", $pid))
    echo "Binding output params failed: (" . $stmt->errno . ") " . $stmt->error;
  
  if (!$stmt->execute())
    echo "Execute statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

  if (!$stmt->bind_result($out_sponsor))
    echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

  while ($stmt->fetch())
  {
    array_push($sponsors, $out_sponsor);
  }

  //add sponsors to players object
  $players[$key]['sponsors'] = $sponsors;
}


mysqli_close($mysqli);

if (!$jsonStr = json_encode($players, JSON_FORCE_OBJECT))
  die('could not encode JSON');
else
  die($jsonStr);
