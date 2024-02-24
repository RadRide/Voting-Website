<?php
// Starting a session to be able to send user data to other webpages
session_start();

// Resetting the user's information session values
$_SESSION['user'] = null;

$_SESSION['lastPage'] = 'index.php';

// Access the config.php file where the connection and clean() are written
include 'config.php';

global $connection;

// Run if we press the login button
if(isset($_POST['loginBtn'])){
    // Checks if any input field was left empty
    if($_POST['email'] == '' || $_POST['password'] == ''){
        echo "<script type='text/javascript'>
                alert('All Fields Must Be Filled');
              </script>";
    }else{
        // Cleans the email and password from any unwanted characters and illegal phrases
        $email = clean($_POST['email'], 300);
        $password = clean($_POST['password'], 100);

        // Hashes(encrypts) the password
        $password = hash('sha256', $password);

        // Selects account from database with the same email and password
        $query = "SELECT * FROM id21733760_votingdb.user WHERE user.user_email = '$email' AND user.user_password = '$password';";
        $queryResult = mysqli_query($connection, $query)
        or die('Error Login In: ' . mysqli_error($connection));

        // Checks is the database has an account with the same email and password
        if(mysqli_num_rows($queryResult) >= 1){
            if($row = mysqli_fetch_array($queryResult)) {
                $user = array('id' => $row['user_id'],
                    'name' => $row['user_name'],
                    'password' => $password,
                    'dob' => $row['user_birth_date'],
                    'email' => $email,
                    'picture' => $row['user_picture']
                );
                $_SESSION['user'] = $user;

                header("Location: MainPage.php");
            }
        }else{
            // Prints an error if the account is not correct or does not exist in the database
            echo "<script type='text/javascript'>
                alert('Wrong Email Or Password');
              </script>";
        }
    }
}
// Run if we press the anonymous vote button
else if(isset($_POST['anonBtn'])){
    if($_POST['code'] == ''){
        echo "<script type='text/javascript'>
                alert('Voting Room Code Cannot Be Empty');
              </script>";
    }else{
        // Cleans(removes) the room's code from any unwanted characters and illegal phrases
        $code = clean($_POST['code'], 50);

        // Selects room from database with the same code
        $query = "SELECT * FROM voting_room WHERE room_id = '$code';";
        $queryResult = mysqli_query($connection, $query)
        or die('Error Checking chamber number: ' . mysqli_error($connection));

        // Checks if any room with same code was found in the database
        if(mysqli_num_rows($queryResult) > 0){
            if($row = mysqli_fetch_array($queryResult)){
                // Checks if room's status isn't only for verified accounts(it should not be 1)
                if($row['room_status'] == 0 || $row['room_status'] == 2){
                    $_SESSION['roomCode'] = $code;
                    if(!isset($_COOKIE['user'])){
                        setcookie('user', generateAnonymousId($code), 2147483647);
                    }
                    header("Location: VotingPage.php?room_id=$code");
                }else{
                    echo "<script type='text/javascript'>
                        alert('Wrong Code Or Open For Verified Accounts Only');
                      </script>";
                }
            }
        }else{
            echo "<script type='text/javascript'>
                        alert('Room Does Not Exist');
                      </script>";
        }
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
    <title>VoteUp Welcome</title>

    <link rel="icon" type="image/x-icon" href="assets/icons/VoteUp-icon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

    <link rel="stylesheet" href="Styles/WelcomePageStyles.css">
</head>

<?php


?>


<body>

<div class="container main">
    <div class="h2 text-uppercase welcomeText">Welcome</div>
    <div class="h5 text-muted text-capitalize">Enter Credentials to continue</div>
    <form action="index.php" method="post" enctype="multipart/form-data">
        <input type="email" placeholder="Email" name="email" class="form-control nameField fields"> <br>
        <input type="password" placeholder="Password" name="password" class="form-control fields">
        <div class="row btnRow">
            <div class="col">
                <input type="submit" value="Login" name="loginBtn" class="buttons">
            </div>
            <div class="col">
                <input type="button" value="Sign Up" class="buttons" id="signup">
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 leftButtonRow">
                <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModal" class="buttons">Anonymous Vote</button>
            </div>
        </div>
    </form>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Anonymous Voting</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="text" placeholder="Voting Room Code" name="code" class="form-control nameField fields">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn anonButton" name="anonBtn" value="1">Enter Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    document.getElementById('signup').onclick = (event) => window.location.assign('CreateAccount.php');
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</body>
</html>



<?php

?>