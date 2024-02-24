<?php

/**
 * Database connection variable
 */
$connection = mysqli_connect("localhost", "id21733760_root", "voteup-426WebAdvanced", "id21733760_votingdb")
or die("Error Connection To The Database: " . mysqli_connect_error());

/**
 * Name of the uploads folder
 */
$folder = 'uploads/';
/**
 * Name of the default picture
 */
$defaultPic = 'placeholderImage.jpg';

/**
 * Checks a string for illegal characters
 * @param $input - The string to be checked
 * @param $maxlength - The maximum length of the string allowed
 * @return string - The checked string
 */
function clean($input, $maxlength)
{
    $input = substr($input,0,$maxlength);
    $input = escapeshellarg($input);
    $input = str_replace("'","", $input);
    return htmlspecialchars($input,ENT_QUOTES);
}

/**
 * Retrieves the number of votes in a room
 * @param $roomId - The id of the room to get the number of votes from
 * @return int|void - The number of votes
 */
function getVoteCount($roomId){
    global $connection;
    $countQuery = "SELECT COUNT(voter_id) AS number_of_votes, (SELECT COUNT(*) FROM anonymous_user_vote WHERE voted_room = '$roomId') AS anon_total FROM user_vote WHERE voted_room = '$roomId';";
    $countResult = mysqli_query($connection, $countQuery) or die("Error Counting Votes: " . mysqli_error($connection));

    if($countRow = mysqli_fetch_array($countResult)){
        $total = (int)$countRow['anon_total'] + (int)$countRow['number_of_votes'];
        return $total;
    }
    return 0;
}

/**
 * Retrieves the voting room owner's name from the database
 * @param $roomId - The id of the voting room to get the owner from
 * @return mixed|string|void - The voting room's owner name
 */
function getRoomOwner($roomId){
    global $connection;
    $selectQuery = "SELECT user_name FROM id21733760_votingdb.user, voting_room WHERE room_owner = user_id 
                                    AND room_id = '$roomId'";
    $selectResult = mysqli_query($connection, $selectQuery)
    or die("Error Retrieving Room Owner: " . mysqli_error($connection));
    if(mysqli_num_rows($selectResult) > 0){
        if($row = mysqli_fetch_array($selectResult)){
            return $row['user_name'];
        }
    }
    return '';
}

/**
 * Randomly generates a 10 digits id for anonymous voter
 * @param $roomId <p>The id of the room to check if the id already used</p>
 * @return string|void - The randomly generated 10 digits id
 */

function generateAnonymousId($roomId){
    global $connection;
    $id = rand(1, 9);
    for($i = 0; $i < 7; $i++){
        $id *= 10;
        $id += rand(1, 9);
    }
    $checkQuery = "SELECT * FROM anonymous_user_vote WHERE voter_id = $id AND voted_room = '$roomId'";
    $checkResult = mysqli_query($connection, $checkQuery)
    or die("Error Checking Generated Id: " . mysqli_error($connection));
    if(mysqli_num_rows($checkResult) > 0){
        return generateAnonymousId($roomId);
    }
    return ''.$id;
}

/**
 * Checks the status of the voting room and if the user has already voted inside of it
 * @param $roomId <p>The code of the voting room to be checked</p>
 * @param $userId <p>The id of the user to check if they voted</p>
 * @return string|void The status of the room (Voted/Open/Close)
 */
function getRoomStatus($roomId, $userId){
    global $connection;

    $checkRoom = "SELECT room_status FROM voting_room WHERE room_id = '$roomId'";
    $checkRoomResult = mysqli_query($connection, $checkRoom)
    or die("Error Checking Room Status" . mysqli_error($connection));

    if(mysqli_num_rows($checkRoomResult) > 0){
        if($code = mysqli_fetch_array($checkRoomResult)){
            if($code['room_status'] == '2'){
                return "Closed";
            }else{
                $checkVote = "SELECT * FROM user_vote WHERE voter_id = $userId AND voted_room = '$roomId'";
                $checkVoteResult = mysqli_query($connection, $checkVote)
                or die("Error Checking Room Status" . mysqli_error($connection));

                if(mysqli_num_rows($checkVoteResult) > 0){
                    return "Voted";
                }else{
                    return "Open";
                }
            }
        }
    }
}
?>