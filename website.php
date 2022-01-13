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
    $abs_path = "user_folders/".$username."/";
    $query_check_file = "SELECT name FROM user_files WHERE owner = '$username' AND name = '$fname'";
    $query_check_file_rows = $db->query($query_check_file)->numRows();
    
    if (isset($_POST["delete"]) && $query_check_file_rows > 0) {
        delete($abs_path.$fname, $fname, $username);
    } else if (isset($_POST["download"]) && $query_check_file_rows > 0) {
        download($abs_path.$fname);
    }
}

if (isset($_GET["file"])) {
    
    $fname = $_GET["file"];
    
    $form = '<form method="post" action="website.php" class="dialog_container" style="display: flex; align-items: center;">
                    <label style="color: black; font-family: Arial; margin-left: 5px;" class="fontsize">
                         What would you like to do with <b>'.htmlspecialchars($fname).'</b>?
                    </label>
                    <input type="hidden" name="file" value="'.htmlspecialchars($fname).'"/>
                    <button type="submit" name="delete" class="button-delete">Delete</button>
                    <button type="submit" name="download" class="button-login" style="width: 100px; margin-top: auto; margin-bottom: 10px; margin-right: 10px"
                    onclick='."setTimeout(function(){window.location.href='website.php';},1000)".'>Download</button>
              </form>';
}

if (isset($_POST["submit_upload"])) {
    
    $upload = upload();
}

$query_spaceused = "SELECT spaceused_b FROM user_accounts WHERE username = '$username'";
$spaceused = $db->query($query_spaceused)->fetchArray();

$spaceused_gb = round($spaceused["spaceused_b"] * 0.000000001, 2);
$spacemax_gb = round(MAX_STORAGE * 0.000000001);

$spaceused_graph = '<div class="spaceused">
                        <label class="fontsize" style="font-family: Arial;">'.$spaceused_gb.' GB / '.$spacemax_gb.' GB</label>
                            <progress max="'.MAX_STORAGE.'" value="'.$spaceused["spaceused_b"].'"></progress>
                    </div>';

?>

<!-- When page is refreshed if a file was uploaded it will be uploaded again-->
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

<html lang="en-us">

<head>
    <title>Cloudio</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
</head>

<body style="margin: 0 0 0 0;">

<div class="navbar_container_top">
    <form action="website.php" method="post" enctype="multipart/form-data">
        <button class="button-signup" style="width: 100px; margin: 10px 10px 10px 10px; float: right;" name="logout">
            Log out
        </button>
        <label class="button-login"
               style="width: 100px; height: 35px; margin: 10px 10px 10px 10px; float: left; text-align: center; display: block">
            Upload
            <input type="file" name="upload[]" multiple class="button-login"
                   style="font-size: 10px; margin: auto; text-align-last: center; height: 10px;">
        </label>
        <input type="submit" class="button-login" name="submit_upload" value="Submit"
               style="width: 100px; float: left; text-align: center; margin-top: 10px;">
    </form>
    <?php
    echo $spaceused_graph; ?>
</div>

<div style="margin-top: calc(var(--navbar-height) + var(--space-used-height) - 10px); padding-bottom: var(--dialog-height)">
    
    <?php
    
    printAllFiles($username);
    
    echo $form;
    echo $upload;
    
    ?>
</div>
</body>
</html>