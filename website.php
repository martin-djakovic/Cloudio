<?php

require_once "mysql_connect.php";
require_once "functions.php";

global $db;

$form = "";

session_start();

if (!isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION["user"];

if (isset($_POST["logout"])) {
    unset($_SESSION["user"]);
    header("Location: index.php");
    exit();
}

if (isset($_POST["file"])) {

    $fname = $_POST["file"];
    $abs_path = USER_FOLDERS_PATH . $username . "/";

    $query_check_file = "SELECT name FROM user_files WHERE owner = '$username' AND name = '$fname'";
    $query_check_file_rows = $db->query($query_check_file)->numRows();

    if (isset($_POST["delete"]) && $query_check_file_rows > 0) {
        delete($abs_path . $fname, $fname, $username);
    } else {
        if (isset($_POST["download"]) && $query_check_file_rows > 0) {
            download($abs_path . $fname);
        }
    }
}

if (isset($_GET["file"])) {

    $fname = $_GET["file"];

    $form = '<form method="post" action="website.php" class="dialog-container" style="display: flex; align-items: center;">
                    <label style="color: black; font-family: Arial; margin-left: 5px;" class="fontsize">
                         What would you like to do with <b>' . htmlspecialchars($fname) . '</b>?
                    </label>
                    <input type="hidden" name="file" value="' . htmlspecialchars($fname) . '"/>
                    <button type="submit" name="delete" class="button-delete">Delete</button>
                    <button type="submit" name="download" class="button-login" style="width: 100px; margin-top: auto; margin-bottom: 10px; margin-right: 10px"
                    onclick=' . "setTimeout(function(){window.location.href='website.php';},1000)" . '>Download</button>
              </form>';
}

if (isset($_POST["submit_upload"])) {

    $upload = upload();
}

$query_spaceused = "SELECT spaceused_b FROM user_accounts WHERE username = '$username'";
$spaceused = $db->query($query_spaceused)->fetchArray();

$spaceused_gb = round($spaceused["spaceused_b"] * 0.000000001, 2);
$spacemax_gb = round(MAX_STORAGE * 0.000000001);

$spaceused_graph = '<div class="spaceused" style="padding-top: 30px;">
                        <label class="fontsize" style="font-family: Arial;">' . $spaceused_gb . ' GB / ' . $spacemax_gb . ' GB</label>
                        <progress style="width: 100%;" max="' . MAX_STORAGE . '" value="' . $spaceused["spaceused_b"] . '"></progress>
                    </div>';

?>

<!-- When page is refreshed if a file was uploaded it will be uploaded again, this fixes it -->
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

<script>
    window.addEventListener("resize", () => {
        /*if (window.innerWidth > 620){
            document.querySelector(':root').style.setProperty('--navbar-ht', '70px');
            document.querySelector(':root').style.setProperty('--text-no-files-size', '25px');
        }*/
    });
</script>

<html lang="en-us">

<head>
    <title>Cloudio</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
    <script src="ui.js"></script>
</head>

<body style="margin: 0 0 0 0; overflow-y: scroll; overflow-x: hidden;">

<!-- RIGHT NAVBAR -->
<div class="navbar-container-right">
    <form action="website.php" method="post" enctype="multipart/form-data">
        <label class="button-login"
               style="height: 35px; margin: var(--navbar-ht) 10px 10px 10px; float: left; text-align: center;
               display: block; width: calc(100% - 20px)">
            Upload
            <input type="file" name="upload[]" multiple class="button-login"
                   style="font-size: 10px; margin: auto; text-align-last: center; height: 10px;">
        </label>
        <input type="submit" class="button-login" name="submit_upload" value="Submit"
               style="float: left; text-align: center; width: calc(100% - 20px); margin-left: 10px;
               margin-right: 10px; margin-top: 0;">
    </form>
    <?php
    echo $spaceused_graph;
    ?>
</div>

<!-- TOP NAVBAR -->
<div class="navbar-container-top">

    <button class="dropdown-btn" onclick="dropdown();">
        <img src="img/dropdown.svg" style="width: 40px; height: 40px; opacity: 90%;" alt="MENU BUTTON">
    </button>

    <form method="post">
        <button class="button-signup" style="width: 100px; margin: 10px 10px 10px 10px; float: right;"
                name="logout">
            Log out
        </button>
    </form>

    <form method="post" enctype="multipart/form-data" style="margin: 0 0 0 0;">
        <label class="button-login"
               style="height: 35px; margin: 100px 10px 10px 10px; float: left; text-align: center;
               display: block; width: calc(100% - 20px)">
            Upload
            <input type="file" name="upload[]" multiple class="button-login"
                   style="font-size: 10px; margin: auto; text-align-last: center; height: 10px;">
        </label>
        <input type="submit" class="button-login" name="submit_upload" value="Submit"
               style="float: left; text-align: center; width: calc(100% - 20px); margin-left: 10px;
               margin-right: 10px; margin-top: 0;">
    </form>

    <?php
    echo $spaceused_graph;
    ?>

</div>

<div class="file-container">

    <?php

    $file_count = $db->query("SELECT * FROM user_files WHERE owner = '$username'")->numRows();

    printAllFiles($username);

    echo $form;
    echo $upload;

    if ($file_count <= 0) {
        echo '<p class="text-no-files">Oops!<br>It seems you have no files!</p>';
    }

    ?>
</div>
</body>
</html>