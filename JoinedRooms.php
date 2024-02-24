<?php

session_start();

$_SESSION['lastPage'] = 'JoinedRooms.php';

include "config.php";

global $connection;

// When the delete button is pressed, the row will be deleted from the database
if(isset($_GET['delete'])){
    $deleteQuery = "DELETE FROM joined_room WHERE joined_room.joined_room = '{$_GET['delete']}' 
                          AND user_id = {$_SESSION['user']['id']};";
    $deleteResult = mysqli_query($connection, $deleteQuery);

    if($deleteResult){
        echo "<script type='text/javascript'>alert('Room Deleted Successfully')</script>";
    }else{
        echo "<script type='text/javascript'>alert('Room Deletion Failed')</script>";
    }
}

if(isset($_GET['joinRoom'])){
    // Removing any illegal characters or statements from entered code
    $roomId = clean($_GET['code'], 50);

    // Checking if the entered code is valid
    $checkRoomQuery = "SELECT * FROM voting_room WHERE room_id = '$roomId';";
    $checkRoomResult = mysqli_query($connection, $checkRoomQuery)
    or die("Error Checking Room: " . mysqli_error($connection));

    // If the code is valid, the room is added to the joined rooms and the website will be redirected to the Voting page
    if(mysqli_num_rows($checkRoomResult) > 0){
        $joinRoomQuery = "INSERT INTO joined_room VALUES ('{$_SESSION['user']['id']}', '$roomId');";
        $joinRoomResult = mysqli_query($connection, $joinRoomQuery)
        or die("Error Adding Room To The Joined Rooms: " . mysqli_error($connection));

        if($joinRoomResult){
            header("Location: VotingPage.php?room_id=$roomId");
        }else{
            echo "<script type='text/javascript'>alert('Error Joining Voting Room')</script>";
        }
    }else{
        echo "<script type='text/javascript'>alert('Voting Room Code Invalid')</script>";
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
    <title>VoteUp</title>

    <link rel="icon" type="image/x-icon" href="assets/icons/VoteUp-icon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

    <link rel="stylesheet" href="Styles/MainPageStyles.css">
</head>
<body>


<nav class="navbar navbar-expand-lg bg-light">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-capitalize" aria-current="page" href="MainPage.php">my rooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active text-capitalize" href="#">joined rooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-capitalize logoutBtn" href="index.php">Log out</a>
                </li>
            </ul>
        </div>
        <a class="navbar-brand text-capitalize d-flex flex-row-reverse" href="#">
            <img src="<?php echo $_SESSION['user']['picture']?>" alt="" class="d-inline-block align-text-bottom" width="35" height="35" style="border-radius: 50%; margin-left: 10px">
            <?php echo $_SESSION['user']['name']?>
        </a>
    </div>
</nav>

<div class="container tableContainer">
    <div class="table-responsive">
            <?php

            // Selecting all the info of the joined rooms
            $selectQuery = "SELECT room_id, room_title, creation_date FROM voting_room, user, joined_room 
                            WHERE joined_room.user_id = id21733760_votingdb.user.user_id AND joined_room.joined_room = voting_room.room_id
                            AND joined_room.user_id = '{$_SESSION['user']['id']}'";
            $selectResult = mysqli_query($connection, $selectQuery)
            or die("Error Retrieving Joined Rooms: " . mysqli_error($connection));

            if(mysqli_num_rows($selectResult) > 0){
                // Inserting the joined voting rooms in the webpage
                echo '<table class="table">
                        <thead align="center" >
                        <tr>
                            <th scope="col">CODE</th>
                            <th scope="col">Title</th>
                            <th scope="col">Room Owner</th>
                            <th scope="col">Creation Date</th>
                            <th scope="col">Number Of Votes</th>
                            <th scope="col">Room Status</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody align="center">';
                while ($row = mysqli_fetch_array($selectResult)){
                    echo '<tr class="tableRow">
                                <th scope="row">' . $row['room_id'] . '</th>
                                <td>' . $row['room_title'] . '</td>
                                <td>' . getRoomOwner($row['room_id']) . '</td>
                                <td>' . $row['creation_date'] . '</td>
                                <td>' . getVoteCount($row['room_id']) . '</td>
                                <td>'. getRoomStatus($row["room_id"], $_SESSION["user"]["id"]) .'</td>
                                <td>
                                    <form action="VotingPage.php" method="get">
                                        <button class="tableButton" name="room_id" value="' . $row['room_id'] . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Open Room">
                                            <svg class="icon" width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="icon" d="M5 12V6C5 5.44772 5.44772 5 6 5H18C18.5523 5 19 5.44772 19 6V18C19 18.5523 18.5523 19 18 19H12M8.11111 12H12M12 12V15.8889M12 12L5 19" stroke="#6e77ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <form action="JoinedRooms.php" method="get">
                                        <button class="tableButton" name="delete" value="' . $row['room_id'] . '" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete Room">
                                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="icon" d="M10 12V17" stroke="#6e77ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path class="icon" d="M14 12V17" stroke="#6e77ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path class="icon" d="M4 7H20" stroke="#6e77ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path class="icon" d="M6 10V18C6 19.6569 7.34315 21 9 21H15C16.6569 21 18 19.6569 18 18V10" stroke="#6e77ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path class="icon" d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5V7H9V5Z" stroke="#6e77ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>';
                }
                echo '</tbody>
                    </table>';
            }else{
                echo 'Press The + Button To Join a Voting Room';
            }

            ?>

    </div>
</div>

<button class="btnAdd btn" data-bs-toggle="modal" data-bs-target="#exampleModal">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path class="addIcon" d="M7 12L12 12M12 12L17 12M12 12V7M12 12L12 17" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Anonymous Voting</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="JoinedRooms.php" method="get">
                <div class="modal-body">
                    <input type="text" placeholder="Voting Room Code" name="code" class="form-control nameField fields">
                </div>
                <div class="modal-footer">
                    <button type="submit"  name="joinRoom" class="btn anonButton">Enter Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<script type="text/javascript">
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
</script>
</body>
</html>
