<?php

session_start();

include 'config.php';
global $connection, $folder, $defaultPic;

if(isset($_GET['editRoom'])){
    $roomId = $_GET['editRoom'];
    $roomQuery = "SELECT * FROM voting_room WHERE room_id = '$roomId'";
    $roomResult = mysqli_query($connection, $roomQuery) or die("Error Retrieving Room: " . mysqli_error($connection));
    if(mysqli_num_rows($roomResult) > 0){
        if($roomRow = mysqli_fetch_array($roomResult)){
            // Checks if the voting room was closed and will redirect the website to the voting result page
            if($roomRow['room_status'] == 2){
                header("Location: VotingPage.php?room_id=$roomId");
            }
        }
    }
}

// Notes:
//      - Once the room is created then the user should not be able to edit it (add/delete candidate)

// If we close the voting room, it is updated in the database and the website will be redirected to the result page
if(isset($_POST['closeRoom'])){
    $closeRoomQuery = "UPDATE voting_room SET room_status = 2 WHERE room_id = '{$_POST['roomId']}'";
    $closeRoomResult = mysqli_query($connection, $closeRoomQuery) or die("Error Closing Voting Room: " . mysqli_error($connection));
    if($closeRoomResult){
        header("Location: VotingPage.php?room_id={$_POST['roomId']}");
    }else{
        echo '<script type="text/javascript">alert("Closing Room Failed")</script>';
        header("Location: {$_SESSION['lastPage']}");
    }
}

// Runs when we edit an already created Room
if(isset($_POST['save']) && isset($_POST['roomId'])){
    //  Updates the room's title if the room is already created
    $roomTitle = clean($_POST['roomTitle'], 200);
    $roomId = $_POST['roomId'];
    $updateRoomQuery = "UPDATE voting_room SET room_title = '$roomTitle' WHERE room_id = '$roomId'";
    $updateRoomResult = mysqli_query($connection, $updateRoomQuery)
    or die("Error Updating Room Title");
    if($updateRoomResult){
        // Updates each of the candidate in the database
        for($i = 0; $i < count($_POST['names']); $i++){
            $candidateName = clean($_POST['names'][$i], 100);
            $candidateBio = clean($_POST['bios'][$i], 5000);
            // Checks if the image was updated and uploads the image
            if($_FILES['pictures']['name'][$i] == ''){
                $updateCandidateQuery = "UPDATE candidate SET candidate_name = '$candidateName', 
                     candidate_bio = '$candidateBio' WHERE candidate_room = '$roomId' AND candidate_id = {$_POST['ids'][$i]}";
                // Continue from here(create mysqli_query and the query in case the user changed the photo)
                $updateCandidateResult = mysqli_query($connection, $updateCandidateQuery)
                or die("Error Updating Candidate: " . mysqli_error($connection));
                if($updateCandidateResult){
//                    echo "Candidate Updated";
                }
            }else{
                $path = $folder . $_FILES['pictures']['name'][$i];
                if(move_uploaded_file($_FILES['pictures']['tmp_name'][$i], $path)){
                    $updateCandidateQuery = "UPDATE candidate SET candidate_name = '$candidateName', 
                     candidate_bio = '$candidateBio', candidate_photo = '$path' 
                        WHERE candidate_room = '$roomId' AND candidate_id = $i";
                    $updateCandidateResult = mysqli_query($connection, $updateCandidateQuery)
                    or die("Error Updating Candidate: " . mysqli_error($connection));
                    if($updateCandidateResult){
//                        echo "Candidate Updated";
                    }
                }
            }
        }
        header("Location: MainPage.php");
    }
}

// Runs if we created a new room
else if(isset($_POST['save'])){
    // Creating a new voting room
    $roomTitle = clean($_POST['roomTitle'], 200);
    $roomId = generateId();
    $creationDate = date("Y-m-d");
    // Checking if the verified vote checkbox was checked or not
    if(isset($_POST['verifiedVote'])){
        $verified = 1;
    }else{
        $verified = 0;
    }

    $newRoomQuery = "INSERT INTO voting_room VALUES ('$roomId', '$roomTitle', '$creationDate','$verified', '{$_SESSION['user']['id']}')";
    $result = mysqli_query($connection, $newRoomQuery) or die("Error Creating New Voting Room: " . mysqli_error($connection));

    // After creating the new voting room in the database, we can add the candidates and links them to it
    if($result){
        $candidates = array();
        for($i = 0; $i < count($_POST['names']); $i++){
            $candidates[$i]['id'] = $i;
            $candidates[$i]['name'] = clean($_POST['names'][$i], 100);
            $candidates[$i]['bio'] = clean($_POST['bios'][$i], 5000);
            if($_FILES['pictures']['name'][$i] == ''){
                $path = $folder . $defaultPic;
            }else{
                $path = $folder . $_FILES['pictures']['name'][$i];
                if(move_uploaded_file($_FILES['pictures']['tmp_name'][$i], $path)){
//                    echo 'Picture uploaded';
                }
            }
            $candidates[$i]['picture'] = $path;
        }

        for($i = 0; $i < count($candidates); $i++){
//            echo "{$candidates[$i]['id']} => $roomId<br>";
            $insertQuery = "INSERT INTO candidate (candidate_id,candidate_room, candidate_name, candidate_bio, candidate_photo)
                        VALUES ({$candidates[$i]['id']}, '$roomId', '{$candidates[$i]['name']}', '{$candidates[$i]['bio']}', '{$candidates[$i]['picture']}');";
            $insertResult = mysqli_query($connection, $insertQuery) or die("Error Inserting Candidate: " . mysqli_error($connection));
//            if($insertQuery){
//                echo 'User Created Successfully';
//            }else{
//                echo 'User Creation Failed';
//            }
        }
        header("Location: MainPage.php");
    }else{
        echo "<script type='text/javascript'>alert('Error Creating Voting Room');</script>";
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
    <title>Edit Room</title>

    <link rel="icon" type="image/x-icon" href="assets/icons/VoteUp-icon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
          rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

    <link rel="stylesheet" href="Styles/RoomEditorStyles.css">
</head>
<body>


<nav class="navbar navbar-expand-lg bg-light">
    <div class="container-fluid">
        <a class="backBtn" href="<?php echo $_SESSION['lastPage']; ?>">
            <svg width="30" height="30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none">
                <path stroke="#000000" stroke-linecap="round"
                      stroke-linejoin="round" stroke-width="2" d="M11 18h3.75a5.25 5.25 0 100-10.5H5M7.5 4L4 7.5 7.5 11"/>
            </svg>
        </a>
        <a class="navbar-brand text-capitalize d-flex flex-row-reverse" href="#">
            <img src="<?php echo $_SESSION['user']['picture']?>" alt="" class="d-inline-block align-text-bottom" width="35" height="35" style="border-radius: 50%; margin-left: 10px">
            <?php echo $_SESSION['user']['name']?>
        </a>
    </div>
</nav>

<?php

if(isset($_GET['editRoom'])){
    $roomId = $_GET['editRoom'];
    $roomQuery = "SELECT * FROM voting_room WHERE room_id = '$roomId'";
    $roomResult = mysqli_query($connection, $roomQuery) or die("Error Retrieving Room: " . mysqli_error($connection));
    if(mysqli_num_rows($roomResult) > 0){
        if($roomRow = mysqli_fetch_array($roomResult)){
            // Checks if the voting room was closed and will redirect the website to the voting result page
            if($roomRow['room_status'] == 2){
                header("Location: VotedPage.php?roomId=$roomId");
            }else{
                // If the
                $selectQuery = "SELECT * FROM candidate WHERE candidate_room = '$roomId' ORDER BY candidate_id";
                $selectResult = mysqli_query($connection, $selectQuery)  or die("Error Retrieving Candidates: " . mysqli_error($connection));

                if(mysqli_num_rows($selectResult) > 0){
                    echo '<div class="container">
                            <form action="RoomEditor.php" method="post" enctype="multipart/form-data">
                                <input type="text" name="roomId" value="' . $roomId. '" style="display: none" required>
                                <div class="input-group titleField">
                                    <label for="title" class="input-group-text">Title</label>
                                    <input id="title" class="form-control" type="text" name="roomTitle" value="' . $roomRow["room_title"] . '">
                                </div>
                        
                                <div class="candidateContainer" id="candidateContainer">';
                    $counter = 0;
                    while ($row = mysqli_fetch_array($selectResult)){
                        echo'<div class="card candidateCard" style="width: 18rem;">
                                        <label for="pictureChooser' . $counter . '"><img src="' . $row['candidate_photo'] . '" class="card-img-top" alt="Candidate Picture"
                                                                        height="250px" style="object-fit: cover; cursor: pointer"></label>
                                        <input type="file" class="fileInput" id="pictureChooser' . $counter . '" name="pictures[]" style="display: none" accept=".jpg, .jpeg, .png">
                                        <input type="text" name="ids[]" value="' . $row['candidate_id'] . '" style="display: none">
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item">
                                                    <div class="input-group fields">
                                                        <label for="title" class="input-group-text">Name</label>
                                                        <input id="title" class="form-control" type="text" name="names[]" value="' . $row["candidate_name"] . '" required>
                                                    </div>
                                                </li>
                                                <li class="list-group-item">
                                                    <div class="input-group fields">
                                                        <label for="title" class="input-group-text">Bio</label>
                                                        <textarea id="title" class="form-control" name="bios[]" required>' . $row["candidate_bio"] . '</textarea>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>';
                        $counter++;
                    }
                    echo'</div>
                                <input type="submit" class="submitButton" name="save" value="Save">
                                <input type="submit" class="submitButton" name="closeRoom" value="Close Room">
                            </form>
                         </div>';
                }
            }
        }
    }
}else{
    echo '<button class="btnAdd btn" id="btnAdd" data-bs-toggle="modal" data-bs-target="#exampleModal">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path class="addIcon" d="M7 12L12 12M12 12L17 12M12 12V7M12 12L12 17"
              stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    </button>';

    echo '<div class="container">
    <form action="RoomEditor.php" method="post" enctype="multipart/form-data">
        <div class="input-group titleField">
            <label for="title" class="input-group-text">Title</label>
            <input id="title" class="form-control" type="text" name="roomTitle" required>
        </div>

        <div class="candidateContainer" id="candidateContainer">
            <div class="card candidateCard" style="width: 18rem;">
                <label for="pictureChooser0"><img src="assets/placeholderImage.jpg" class="card-img-top" alt="Candidate Picture"
                                                height="250px" style="object-fit: cover; cursor: pointer"></label>
                <input type="file" class="fileInput" id="pictureChooser0" name="pictures[]" style="display: none" accept=".jpg, .jpeg, .png">
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <div class="input-group fields">
                                <label for="title" class="input-group-text">Name</label>
                                <input id="title" class="form-control" type="text" name="names[]" required>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="input-group fields">
                                <label for="title" class="input-group-text">Bio</label>
                                <textarea id="title" class="form-control" name="bios[]" required></textarea>
                            </div>
                        </li>
                    </ul>
                    <button class="buttons deleteButton" type="button">Remove Candidate</button>
                </div>
            </div>
        </div>
        <div class="form-check form-switch switchButton">
            <label class="form-check-label switchLabel" for="flexSwitchCheckDefault">Verified Only Votes</label>
            <input class="form-check-input switch" type="checkbox" role="switch" name="verifiedVote" id="flexSwitchCheckDefault">
        </div>
        <input type="submit" class="submitButton" name="save" value="Save">
    </form>
    </div>';
}

?>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>
<script type="text/javascript">

    /**
     * Selects all the delete buttons and set it so when they are pressed it will delete their cards
     * @type {HTMLCollectionOf<Element>}
     */
    function initButton(){
        var buttons = document.getElementsByClassName('deleteButton');
        for(var i = 0; i < buttons.length; i++){
            buttons[i].addEventListener('click', function (){
                this.closest('.candidateCard').remove();
                counter--;
            });
        }
    }

    /**
     * Adds a listener for the picture chooser so then when I choose an image it is going to be displayed in the img html tag
     */
    function initFileInput(){
        var fileInputs = document.getElementsByClassName('fileInput');
        for(var i = 0; i < fileInputs.length; i++){
            fileInputs[i].addEventListener('change', function (){
                var file = this.files[0];
                var reader = new FileReader();
                reader.onloadend = function () {
                    this.closest('.candidateCard').querySelector('img').src = reader.result;
                }.bind(this);
                reader.readAsDataURL(file);
            });
        }
    }

    initButton();

    initFileInput()

    var addBtn = document.getElementById('btnAdd');

    let counter = document.getElementsByClassName('candidateCard').length;

    /**
     * Implements the ability to add a new candidate card to the btnAdd button
     */
    addBtn.addEventListener('click', function (){
        var div = document.getElementById('candidateContainer');
        div.insertAdjacentHTML('beforeend', `<div class="card candidateCard" style="width: 18rem;">
                <label for="pictureChooser${counter}"><img src="assets/placeholderImage.jpg" class="card-img-top" alt="Candidate Picture"
                                                height="250px" style="object-fit: cover; cursor: pointer"></label>
                <input type="file" class="fileInput" id="pictureChooser${counter}" name="pictures[]" style="display: none" accept=".jpg, .jpeg, .png">
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <div class="input-group fields">
                                <label for="title" class="input-group-text">Name</label>
                                <input id="title" class="form-control" type="text" name="names[]" required>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="input-group fields">
                                <label for="title" class="input-group-text">Bio</label>
                                <textarea id="title" class="form-control" name="bios[]" required></textarea>
                            </div>
                        </li>
                    </ul>
                    <button class="buttons deleteButton" type="button">Remove Candidate</button>
                </div>
            </div>`);

        counter++;
        initButton();
        initFileInput();
    });

</script>
</body>
</html>



<?php
/**
 * Generates a random code(id) for the room and checks if the
 * generated code is not already available in the database
 * @return string|void - The generated ID
 */
function generateId(){
    global $connection;
    $id = '';
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $length = strlen($alphabet) - 1;
    for($i = 0; $i < 10; $i++){
        $id = $id . $alphabet[rand(0, $length)];
    }
    $query = "SELECT room_id FROM voting_room WHERE room_id = '$id'";
    $queryResult = mysqli_query($connection, $query) or die("Error Generating Id: " . mysqli_error($connection));
    if(mysqli_num_rows($queryResult) > 0){
        return generateId();
    }
    return $id;
}
?>
