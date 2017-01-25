<?php
include 'dbInfo.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

global $db_password;
global $db_address;
global $db_user;
global $db_name;


//set timezone
date_default_timezone_set('UTC');

//connect to db
$mysqli = new mysqli($db_address, $db_user, $db_password, $db_name);
if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  die();
}

//check for POST data to determine function call
$postData = array();
if (isset($_POST))
{
  $postData = json_decode(file_get_contents('php://input'), true);
  if ($postData != NULL && $postData['action'] == "addPlayer")
  {
    addPlayer($postData, $mysqli);
    mysqli_close($mysqli);
    die('Player Added');
  }
  else if ($postData != NULL && $postData['action'] == "addTournament")
  {
    addTournament($postData, $mysqli);
    mysqli_close($mysqli);
    die('Tournament Added');
  }
  else if ($postData != NULL && $postData['action'] == "addSponsorship")
  {
    addSponsorship($postData, $mysqli);
    mysqli_close($mysqli);
    die('Sponsorship Added');
  }
  else if ($postData != NULL && $postData['action'] == "addSponsor")
  {
    addSponsor($postData, $mysqli);
    mysqli_close($mysqli);
    die('Sponsor Added');
  }
  else if ($postData != NULL && $postData['action'] == "addCountry")
  {
    addCountry($postData, $mysqli);
    mysqli_close($mysqli);
    die('Country Added');
  }
}


//initialize response array
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
$out_cid = NULL;

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

if (!($stmt = $mysqli->prepare("SELECT name FROM psa_countries ORDER BY name ASC;")))
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

if (!($stmt = $mysqli->prepare("SELECT name FROM psa_sponsors ORDER BY name ASC;")))
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

if (!($stmt = $mysqli->prepare("SELECT DISTINCT name FROM psa_tournaments ORDER BY name ASC;")))
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


//get full player list (for drop down menus)
$stmt = NULL;

if (!($stmt = $mysqli->prepare("SELECT fname, lname, pid FROM psa_players ORDER BY lname ASC")))
  echo "Prepared statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->execute())
  echo "Execute statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

$players = array();
$player = array();

$out_pid = NULL;
$out_fname = NULL;
$out_lname = NULL;

if (!$stmt->bind_result($out_fname, $out_lname, $out_pid))
  echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

while ($stmt->fetch())
{
  $player['id'] = $out_pid;
  $player['name'] = $out_fname . " " . $out_lname;

  array_push($players, $player);
}

$psaData['allPlayers'] = $players;


//get count of players in the DB
$stmt = NULL;
$out_count = NULL;

if (!($stmt = $mysqli->prepare("SELECT COUNT(*) FROM psa_players;")))
  echo "Prepared statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->execute())
  echo "Execute statement failed: (" . $mysqli->errno . ") " . $mysqli->error;

if (!$stmt->bind_result($out_count))
  echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

while ($stmt->fetch())
{
  //do nothing
}

//add player count to psaData
$psaData['count'] = $out_count;

//close connection and output JSON
mysqli_close($mysqli);
if (!$jsonStr = json_encode($psaData, JSON_FORCE_OBJECT))
  die('could not encode JSON');
else
  die($jsonStr);



//function definitions
function addPlayer($data, $db_connection)
{
  //get country id
  if (!($stmt = $db_connection->prepare("SELECT cid FROM psa_countries WHERE name = ?;")))
    echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->bind_param('s', $data['country']))
    echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->execute())
    echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->store_result())
    echo "Store result failed: (" . $db_connection->errno . ") " . $db_connection->error;

  $country_id = NULL;

  if (!$stmt->bind_result($country_id))
    echo "Binding result failed: (" . $db_connection->errno . ") " . $db_connection->error;

  while($stmt->fetch())
  {
    if (!($stmt2 = $db_connection->prepare("INSERT INTO psa_players (fname, lname, rank, country, dob) 
    VALUES (?, ?, ?, ?, ?)")))
    {
      echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;
    }
    
    if (!$stmt2->bind_param('ssiis', $data['fname'], $data['lname'], $data['rank'], $country_id, $data['dob']))
          echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;
    
    if (!$stmt2->execute())
      echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

    if (!$stmt2->store_result())
      echo "Store result failed: (" . $db_connection->errno . ") " . $db_connection->error;
    
    $stmt2->close();
  }
  return;
}


function addTournament ($data, $db_connection)
{
  if (!($stmt = $db_connection->prepare("SELECT cid FROM psa_countries WHERE name = ?;")))
    echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->bind_param('s', $data['country']))
    echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;
  
  if (!$stmt->execute())
    echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->store_result())
    echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

  $country_id = NULL;

  if (!$stmt->bind_result($country_id))
    echo "Binding result failed: (" . $db_connection->errno . ") " . $db_connection->error;

  while($stmt->fetch()) {
    if (!($stmt2 = $db_connection->prepare("INSERT INTO psa_tournaments 
      (name, winner, runner_up, country, city, prize_money, year)  
       VALUES (?, ?, ?, ?, ?, ?, ?)"))
    ) {
      echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;
    }

    if (!$stmt2->bind_param('siiisis', $data['name'], $data['winner'], $data['runnerup'], $country_id, $data['city'],
        $data['prize_money'], $data['year']))
    {
      echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;
    }

    if (!$stmt2->execute())
      echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

    if (!$stmt2->store_result())
      echo "Store result failed: (" . $db_connection->errno . ") " . $db_connection->error;

    $stmt2->close();
  }
  return;
}


function addSponsorship ($data, $db_connection)
{
  if (!($stmt = $db_connection->prepare("SELECT sid FROM psa_sponsors WHERE name = ?;")))
    echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->bind_param('s', $data['sponsor']))
    echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->execute())
    echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->store_result())
    echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

  $sponsor_id = NULL;

  if (!$stmt->bind_result($sponsor_id))
    echo "Binding result failed: (" . $db_connection->errno . ") " . $db_connection->error;

  while($stmt->fetch()) {
    if (!($stmt2 = $db_connection->prepare("INSERT INTO psa_player_sponsor (pid, sid) VALUES (?, ?)")))
      echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

    if (!$stmt2->bind_param('ii', $data['player'], $sponsor_id))
      echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;

    if (!$stmt2->execute())
      echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

    if (!$stmt2->store_result())
      echo "Store result failed: (" . $db_connection->errno . ") " . $db_connection->error;

    $stmt2->close();
  }
  return;
}


function addSponsor ($data, $db_connection)
{
  if (!($stmt = $db_connection->prepare("SELECT cid FROM psa_countries WHERE name = ?;")))
    echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->bind_param('s', $data['country']))
    echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->execute())
    echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->store_result())
    echo "Binding result failed: (" . $stmt->errno . ") " . $stmt->error;

  $country_id = NULL;

  if (!$stmt->bind_result($country_id))
    echo "Binding result failed: (" . $db_connection->errno . ") " . $db_connection->error;

  while($stmt->fetch()) {
    if (!($stmt2 = $db_connection->prepare("INSERT INTO psa_sponsors (name, country) VALUES (?, ?)")))
      echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

    if (!$stmt2->bind_param('si', $data['name'], $country_id))
      echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;

    if (!$stmt2->execute())
      echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

    if (!$stmt2->store_result())
      echo "Store result failed: (" . $db_connection->errno . ") " . $db_connection->error;

    $stmt2->close();
  }
  return;
}


function addCountry ($data, $db_connection)
{
  if (!($stmt = $db_connection->prepare("INSERT INTO psa_countries (name, population) VALUES (?, ?)")))
    echo "Prepared statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->bind_param('si', $data['name'], $data['population']))
    echo "Binding param failed: (" . $db_connection->errno . ") " . $db_connection->error;

  if (!$stmt->execute())
    echo "Execute statement failed: (" . $db_connection->errno . ") " . $db_connection->error;

  $stmt->close();

  return;
}