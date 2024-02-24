<?php
//session_abort();
session_start();

include 'config.php';

global $connection;

// $folder is the uploads folder path and $defaultPic is the name of the default picture
$folder = 'uploads/';
$defaultPic = 'placeholderImage.jpg';

if(isset($_POST['signupBtn'])){
    // Checks if any input field was left empty
    if($_POST['name'] == '' || $_POST['dob'] == '' || $_POST['email'] == ''
        || $_POST['password'] == '' || $_POST['confirmPassword'] == '' ){
        echo "<script type='text/javascript'>
                alert('All Fields Must Be Filled');
              </script>";
    }else{
        // Checks if the password and confirm password are identical
        if($_POST['password'] == $_POST['confirmPassword']){
            $name = clean($_POST['name'], 100);
            $dob = clean($_POST['dob'], 20);
            $email = clean($_POST['email'], 300);
            $password = clean($_POST['password'], 100);

            // Hashes(encrypts) the password
            $password = hash('sha256', $password);

            $checkEmail = "SELECT * FROM id21733760_votingdb.user WHERE user_email = '$email';";
            $checkEmailResult = mysqli_query($connection, $checkEmail)
            or die("Error Checking Email: " . mysqli_error($connection));

            if(mysqli_num_rows($checkEmailResult) > 0){
                echo "<script type='text/javascript'>
                alert('Email Already Exist In Database');
              </script>";
            }else{
                // Checks if we choose a picture and uploads it. Else the default picture is used
                if($_FILES['profilePicture']['name'] == ""){
                    $picture = $folder . $defaultPic;
                }else{
                    $picture = $folder . $_FILES['profilePicture']['name'];
                    if(move_uploaded_file($_FILES['profilePicture']['tmp_name'], $picture)){
                        // echo "Upload Successful";
                    }else{
                        echo "Error uploading file: " . $_FILES['profilePicture']['error'];
                    }
                }

                // Inserting the user's info into the database
                $query = "INSERT INTO id21733760_votingdb.user (user_name, user_birth_date, user_email, user_password, user_picture)
                    VALUES ('$name', '$dob', '$email', '$password', '$picture')";
                $queryResult = mysqli_query($connection, $query) or die("Error Creating User: " . mysqli_error($connection));

                if($queryResult){
                    // Retrieves the newly created user's id
                    $query = "SELECT user_id FROM id21733760_votingdb.user WHERE user.user_email = '$email' AND user.user_password = '$password'
                        AND user.user_name = '$name';";
                    $queryResult = mysqli_query($connection, $query)
                    or die('Error Login In: ' . mysqli_error($connection));

                    // Checks is the database has an account with the same email and password
                    if(mysqli_num_rows($queryResult) >= 1) {
                        if ($row = mysqli_fetch_array($queryResult)) {
                            $_SESSION['user'] = array(
                                'id' => $row['user_id'],
                                'name' => $name,
                                'password' => $password,
                                'dob' => $dob,
                                'email' => $email,
                                'picture' => $picture
                            );

                            // Redirects the website to the Main Page
                            header("Location: MainPage.php");
                        }
                    }
                }else{
                    echo "<script type='text/javascript'>
                alert('Error With Database');
              </script>";
                }
            }
        }else{
            echo "<script type='text/javascript'>
                alert('Confirm Password Not Correct');
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
        <title>Create New Account</title>

        <link rel="icon" type="image/x-icon" href="assets/icons/VoteUp-icon.png">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

        <link rel="stylesheet" href="Styles/WelcomePageStyles.css">
    </head>
    <body>

    <!--<nav class="navbar bg-light">-->
    <!--    <div class="container">-->
    <!--        <a class="navbar-brand" href="#">-->
    <!--            <img src="assets/TestImage.jpg" alt="Bootstrap" width="30" height="30" style="border-radius: 50%">-->
    <!--        </a>-->
    <!--    </div>-->
    <!--</nav>-->

    <div class="container main">
        <div class="h2 text-uppercase welcomeText">Sign up</div>
        <div class="h5 text-muted text-capitalize">Create new account</div>
        <form action="CreateAccount.php" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <label for="picturePicker" class="imageLabel">
                        <img src="uploads/placeholderImage.jpg" class="profileContainer" alt="Candidate Picture"
                             height="250px" width="250px" style="object-fit: cover; cursor: pointer; border-radius: 50%">
                    </label>
                    <input type="file" class="form-control nameField" name="profilePicture"
                           id="picturePicker" accept=".jpg, .jpeg, .png" style="display: none">
                </div>
                <div class="col-md-6">
                    <input type="text" placeholder="Enter Your Name" name="name" class="form-control nameField fields"> <br>
                    <input type="date" name="dob" class="form-control fields">
                    <input type="text" placeholder="Enter Your Email" name="email" class="form-control nameField fields"> <br>
                    <input type="password" placeholder="Enter Your Password" name="password" class="form-control fields"> <br>
                    <input type="password" placeholder="Confirm Your Password" name="confirmPassword" class="form-control fields">
                </div>
            </div>
            <div class="row ">
                <div class="col-sm-12 leftButtonRow">
                    <input type="submit" value="Sign Up" name="signupBtn" class="buttons">
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
            crossorigin="anonymous"></script>
    <script type="text/javascript">
        /**
         * Lets us choose a picture from device to use as profile picture
         * @type {HTMLElement}
         */
        const picChooser = document.getElementById('picturePicker');
        picChooser.addEventListener('change', function (){
            var file = this.files[0];
            var reader = new FileReader();
            reader.onloadend = function (){
                this.parentElement.querySelector('img').src = reader.result;
            }.bind(this);
            reader.readAsDataURL(file);
        });
    </script>
    </body>
    </html>

<?php

?>