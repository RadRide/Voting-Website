<?php

session_start();

include 'config.php';

global $connection, $folder, $defaultPic;

// If the user entered the vote anonymously, a cookie containing a randomly generated id is created to identify the user
//if(!isset($_SESSION['user']) && !isset($_COOKIE['user'])){
//    setcookie('user', generateAnonymousId($_GET['room_id']), 2147483647);
//}

/**
 * defines if the user already voted for a candidate 
 */
$isVoted = false;

// After pressing the vote button the user's vote is added to the database
if(isset($_GET['voteBtn'])){
    $roomId = $_GET['room_id'];
    $vote = $_GET['vote'];

    // Checking if the user has an account or has voted anonymously
    if(isset($_COOKIE['user']) && !isset($_SESSION['user'])){
        $tableName = 'anonymous_user_vote';
        $userId = $_COOKIE['user'];
    }else{
        $tableName = 'user_vote';
        $userId = $_SESSION['user']['id'];
    }

    $insertVoteQuery = "INSERT INTO $tableName VALUES ($userId, '$roomId', $vote)";
    $insertVoteResult = mysqli_query($connection, $insertVoteQuery) or die("Error Inserting Vote: " . mysqli_error($connection));

    if($insertVoteResult){
        echo '<script type="text/javascript">alert("Vote Successful")</script>';
    }else{
        echo '<script type="text/javascript">alert("Vote Failed")</script>';
    }
    $isVoted = true;
}

// Checking is the user has already submitted a vote in the voting room
if(!$isVoted){
    $roomId = $_GET['room_id'];

    if(isset($_COOKIE['user']) && !isset($_SESSION['user'])){
        $tableName = 'anonymous_user_vote';
        $userId = $_COOKIE['user'];
    }else{
        $tableName = 'user_vote';
        $userId = $_SESSION['user']['id'];
    }

    $checkVoteQuery = "SELECT * FROM $tableName WHERE voter_id = $userId AND voted_room = '$roomId';";
    $checkVoteResult = mysqli_query($connection, $checkVoteQuery)
    or die("Error Checking User Vote: " . mysqli_error($connection));
    if(mysqli_num_rows($checkVoteResult) > 0){
        $isVoted = true;
    }else{
        $isVoted = false;
    }
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Voting Page</title>

    <link rel="icon" type="image/x-icon" href="assets/icons/VoteUp-icon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
          rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

    <link rel="stylesheet" href="Styles/VotingPageStyles.css">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-light">
    <div class="container-fluid">
        <a class="backBtn" href="<?php echo $_SESSION['lastPage']?>">
            <svg width="30" height="30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none">
                <path stroke="#000000" stroke-linecap="round"
                      stroke-linejoin="round" stroke-width="2" d="M11 18h3.75a5.25 5.25 0 100-10.5H5M7.5 4L4 7.5 7.5 11"/>
            </svg>
        </a>
        <a class="navbar-brand text-capitalize d-flex flex-row-reverse" href="#">
            <img src="<?php echo isset($_SESSION['user'])?$_SESSION['user']['picture'] : $folder.$defaultPic; ?>" alt="Candidate Picture" class="d-inline-block align-text-bottom" width="35" height="35" style="border-radius: 50%; margin-left: 10px">
            <?php echo isset($_SESSION['user'])?$_SESSION['user']['name'] : 'USER' . $_COOKIE['user']; ?>
        </a>
    </div>
</nav>

<div class="container">
    <?php

    if(isset($_GET['room_id'])){
        $roomQuery = "SELECT * FROM voting_room WHERE room_id = '{$_GET['room_id']}'";
        $roomResult = mysqli_query($connection, $roomQuery)
        or die("Error Retrieving Voting Room Information: " . mysqli_error($connection));

        if(mysqli_num_rows($roomResult) > 0){
            if($row = mysqli_fetch_array($roomResult)){
                $roomStatus = (int)$row['room_status'];
                $roomId = $_GET['room_id'];
                // If the Voting Room is for verified accounts only and the user tried to join anonymously,
                // it will display an error and will redirect the website to the login page
                if($roomStatus == 1 && !isset($_SESSION['user'])){
                    echo '<script type="text/javascript">alert("Only Verified Accounts Can Vote In This Room")</script>';
                    header("Location: {$_SESSION['lastPage']}");
                }else{ // Else it will display either the voting choices or the result depending on the voting room's status
                    if($roomStatus == 2 || $isVoted){ // If the voting room was closed or the user has already voted, it will display the results
                        $totalVotes = getVoteCount($roomId);
                        $candidates = getCandidates($roomId);
                        $winner = $candidates[0];
                        for($i = 1; $i < count($candidates); $i++){
                            if($winner['votes'] < $candidates[$i]['votes']){
                                $winner = $candidates[$i];
                            }
                        }
                        if($roomStatus == 2){
                            echo "<h1 align='center' class='roomTitle text-capitalize'>{$row['room_title']}<br>Winner: {$winner['name']}</h1>";
                        }else{
                            echo "<h1 align='center' class='roomTitle text-capitalize'>{$row['room_title']}</h1>";
                        }
                        echo '<form action="VotingPage.php" method="get">
                                <div class="candidateContainer">';
                        for ($i = 0; $i < count($candidates); $i++){
                            $votePercentage = $totalVotes == 0? 0 : ($candidates[$i]['votes'] * 100) / $totalVotes;
                            echo '<div class="card" style="width: 18rem;">
                                    <img src="'. $candidates[$i]['picture'] .'" class="card-img-top" alt="Candidate Picture"
                                         height="250px" style="object-fit: cover;">
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item text-capitalize">
                                                <h5 class="card-title">'. $candidates[$i]['name'] .'</h5>
                                            </li>
                                            <li class="list-group-item">
                                                <p class="card-text">'. $candidates[$i]['bio'] .'</p>
                                            </li>
                                        </ul>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" aria-label="Example with label"
                                                 style="width: ' . $votePercentage . '%;" aria-valuenow="'. $candidates[$i]['votes'] .'" 
                                                 aria-valuemin="0" aria-valuemax="'. $totalVotes .'">'. $candidates[$i]['votes'] .' Votes</div>
                                        </div>
                                    </div>
                                  </div>';
                        }
                        echo '</div>
                            </form>';
                        if($roomStatus == 2 && isset($_SESSION['user']) && (int)$row['room_owner'] == (int)$_SESSION['user']['id']){
                            echo "<button type='button' class='voteBtn' data-bs-toggle='modal' data-bs-target='#votersDetails'>Show Voters</button>";

                            echo '<div class="modal fade" id="votersDetails" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="exampleModalLabel">Anonymous Voting</h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                                <div class="modal-body">';
                            $voterQuery = "SELECT
    u.user_name,
    u.user_email,
    u.user_picture,
    c.candidate_name,
    uv.voted_room
FROM
    id21733760_votingdb.user AS u
INNER JOIN
    user_vote AS uv ON u.user_id = uv.voter_id
INNER JOIN
    candidate AS c ON uv.voted_candidate = c.candidate_id
WHERE
    uv.voted_room = '$roomId'
    AND uv.voted_room = c.candidate_room;";
                            $voterResult = mysqli_query($connection, $voterQuery)
                            or die("Error Retrieving Voters: " . mysqli_error($connection));

                            if(mysqli_num_rows($voterResult) > 0){
                                while ($voterRow = mysqli_fetch_array($voterResult)){
                                    echo "<div style='display: flex; justify-content: space-between; margin-bottom: 5px;'>
                                            <div>
                                            <img src='{$voterRow["user_picture"]}' height='35' style='border-radius: 50%; margin-left: 10px'>
                                            <span>{$voterRow["user_name"]}</span>
                                            </div>
                                            <span>{$voterRow["user_email"]}</span>
                                            <span class='text-capitalize'>{$voterRow["candidate_name"]}</span>
                                          </div>";
                                }
                            }

                            $voterQuery = "SELECT
    c.candidate_name,
    auv.voter_id
FROM
    candidate AS c
INNER JOIN
    anonymous_user_vote AS auv ON auv.voted_candidate = c.candidate_id
                                AND auv.voted_room = c.candidate_room
WHERE
    auv.voted_room = '$roomId';";
                            $voterResult = mysqli_query($connection, $voterQuery)
                            or die("Error Retrieving Voters: " . mysqli_error($connection));

                            if(mysqli_num_rows($voterResult) > 0){
                                while ($voterRow = mysqli_fetch_array($voterResult)){
                                    echo "<div style='display: flex; justify-content: space-between; margin-bottom: 5px;'>
                                            <div>
                                            <img src='uploads/placeholderImage.jpg' height='35' style='border-radius: 50%; margin-left: 10px'>
                                            <span>USER{$voterRow["voter_id"]}</span>
                                            </div>
                                            <span class='text-capitalize'>{$voterRow["candidate_name"]}</span>
                                          </div>";
                                }
                            }
                            echo '</div>
                                        </div>
                                    </div>
                                </div>';
                        }
                    }else{
                        echo "<h1 align='center' class='roomTitle text-capitalize'>{$row['room_title']}</h1>";
                        echo '<form action="VotingPage.php" method="get">
                                <input type="text" name="room_id" value="'. $roomId .'" style="display: none">
                                <div class="candidateContainer">';
                        $candidates = getCandidates($roomId);
                        for($i = 0; $i < count($candidates); $i++){
                            echo '<div class="card" style="width: 18rem;">
                                    <img src="'. $candidates[$i]['picture'] .'" class="card-img-top" alt="Candidate Picture"
                                         height="250px" style="object-fit: cover; cursor: pointer">
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item text-capitalize">
                                                <h5 class="card-title">'. $candidates[$i]['name'] .'</h5>
                                            </li>
                                            <li class="list-group-item">
                                                <p class="card-text">'. $candidates[$i]['bio'] .'</p>
                                            </li>
                                        </ul>
                                        <div class="radioContainer">
                                            <input type="radio" name="vote" value="'. $candidates[$i]['id'] .'" class="form-check-input radio">
                                        </div>
                                    </div>
                                </div>';
                        }
                        echo '</div>
                              <input type="submit" value="Vote" name="voteBtn" disabled class="voteBtn" id="voteBtn">
                            </form>';

                    }
                }
            }
        }else{
            echo '<script type="text/javascript">alert("Room Id Incorrect")</script>';
            header("Location: {$_SESSION['lastPage']}");
        }
    }

    ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

<script type="text/javascript">
    // Checks if any candidate was selected and disables the vote button if no, else it enables it
    const voteBtn = document.getElementById('voteBtn');
    let voteRadio = document.getElementsByClassName('radio');
    for(let i = 0; i < voteRadio.length; i++){
        voteRadio[i].addEventListener("change", function (){
            voteBtn.removeAttribute('disabled');
        });
    }
</script>
</body>
</html>

<?php

/**
 * Retrieves the candidates from a specific voting room
 * @param $roomId - The id of the voting room
 * @return array|void - An array of the candidates information
 */
function getCandidates($roomId){
    global $connection;
    $candidates = array();
    // Selecting the candidates information and their total number of votes
    $selectQuery = "SELECT candidate_id, candidate_name, candidate_bio, candidate_photo, 
       (SELECT COUNT(*) FROM user_vote WHERE voted_room = '$roomId' AND voted_candidate = candidate.candidate_id) AS total_votes, 
       (SELECT COUNT(*) FROM anonymous_user_vote WHERE voted_room = '$roomId' AND voted_candidate = candidate.candidate_id) AS anonymous_total_votes
        FROM candidate WHERE candidate_room = '$roomId' ORDER BY candidate_id;";
    $selectResult = mysqli_query($connection, $selectQuery) or die("Error Retrieving Candidates: " . mysqli_error($connection));

    if(mysqli_num_rows($selectResult) > 0){
        $counter = 0;
        while ($row = mysqli_fetch_array($selectResult)){
            $candidates[$counter]['id'] = $row['candidate_id'];
            $candidates[$counter]['name'] = $row['candidate_name'];
            $candidates[$counter]['bio'] = $row['candidate_bio'];
            $candidates[$counter]['picture'] = $row['candidate_photo'];
            $candidates[$counter]['votes'] = (int)$row['total_votes'] + (int)$row['anonymous_total_votes'];

//            // Selecting the anonymous votes for each candidate
//            $votesQuery = "SELECT COUNT(*) AS anonymous_total_votes FROM anonymous_user_vote
//                                         WHERE voted_room = '$roomId' AND voted_candidate = {$row['candidate_id']}";
//            $votesResult = mysqli_query($connection, $votesQuery);
//            if(mysqli_num_rows($votesResult) > 0){
//                if($anonRow = mysqli_fetch_array($votesResult)){
//                    $anonymousVotes = (int)$anonRow['anonymous_total_votes'];
//                }else{
//                    $anonymousVotes = 0;
//                }
//            }else{
//                $anonymousVotes = 0;
//            }

            $counter++;
        }
        return $candidates;
    }
    return $candidates;
}

?>