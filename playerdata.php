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

//check for POST data
$postData = array();
if (isset($_POST))
{
  $postData = json_decode(file_get_contents('php://input'), true);
}

//connect to db
$mysqli = new mysqli($db_address, $db_user, $db_password, $db_name);
if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  die();
}

$psaData = array();

//establish base selection query
$select_statement = "SELECT p.pid, p.fname, p.lname, p.rank, p.dob, c.name FROM psa_players p inner JOIN
  psa_countries c ON p.country=c.cid";

//flags
$sponsor_filter = false;
$country_filter = false;
$tournament_filter= false;

//check if filtering by sponsor
if (isset($_GET['sponsor']) && ($_GET['sponsor'] != "undefined") && ($_GET['sponsor'] != "All"))
{
  $select_statement .= " INNER JOIN psa_player_sponsor ps ON ps.pid=p.pid INNER JOIN psa_sponsors s ON s.sid=ps.sid";
  $sponsor_filter = true;
}

//check if we're filtering by tournament
if (isset($_GET['tournament']) && ($_GET['tournament'] != "undefined") && ($_GET['tournament'] != "All"))
{
  $select_statement = str_replace("SELECT", "SELECT DISTINCT", $select_statement);
  $select_statement .= " INNER JOIN psa_tournaments t ON (p.pid=t.winner OR p.pid=t.runner_up)";
  $tournament_filter = true;
}

//apply sponsor and/or tournament WHERE clauses
$select_statement .= ($sponsor_filter) ? " WHERE s.name=" . "'" . $_GET['sponsor'] . "'" : '';
$select_statement .= ($sponsor_filter && $tournament_filter) ? " AND" : '';
$select_statement .= ($tournament_filter && !$sponsor_filter) ? " WHERE" : '';
$select_statement .= ($tournament_filter) ? " t.name=" . "'" . $_GET['tournament'] . "'" : '';


//check if we're filtering by country
if (isset($_GET['country']) && ($_GET['country'] != "undefined") && ($_GET['country'] != "All"))
{
  $select_statement .= ($sponsor_filter || $tournament_filter) ? " AND" : " WHERE";
  $select_statement .= " c.name=" . "'" . $_GET['country'] . "'";
  $country_filter = true;
}

//set sorting order
$select_statement .= " ORDER BY p.rank ASC";


//get player list
if (!($stmt = $mysqli->prepare($select_statement . ";")))
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

  if ($player['rank'] == 999)
    $player['rank'] = "retired";

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

$psaData['players'] = $players;

//get full country list
$stmt = NULL;
$countries = array("All");
$out_country = NULL;

if (!($stmt = $mysqli->prepare("SELECT name FROM psa_countries;")))
  echo "Prepared statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->execute())
  echo "Execute statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->bind_result($out_country))
  echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

while ($stmt->fetch())
{
  array_push($countries, $out_country);
}

//add countries to psaData
$psaData['countries'] = $countries;

//get full sponsor list
$stmt = NULL;
$sponsors = array("All");
$out_sponsor = NULL;

if (!($stmt = $mysqli->prepare("SELECT name FROM psa_sponsors;")))
  echo "Prepared statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->execute())
  echo "Execute statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->bind_result($out_sponsor))
  echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

while ($stmt->fetch())
{
  array_push($sponsors, $out_sponsor);
}

//add sponsors to psaData
$psaData['sponsors'] = $sponsors;

//get full tournament list
$stmt = NULL;
$tournaments = array("All");
$out_tournament = NULL;

if (!($stmt = $mysqli->prepare("SELECT DISTINCT name FROM psa_tournaments;")))
  echo "Prepared statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->execute())
  echo "Execute statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->bind_result($out_tournament))
  echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

while ($stmt->fetch())
{
  array_push($tournaments, $out_tournament);
}

//add tournaments to psaData
$psaData['tournaments'] = $tournaments;


mysqli_close($mysqli);

if (!$jsonStr = json_encode($psaData, JSON_FORCE_OBJECT))
  die('could not encode JSON');
else
  die($jsonStr);
