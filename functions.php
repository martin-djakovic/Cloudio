<?php

require_once "mysql_connect.php";

# SYMBOL EXCLUSION REGEX
# Regex characters, such as / must be ESCAPED with \\
const USERNAME_ALLOWED_SYMBOLS = "/[^0-9a-zA-Z_]/";
# 1 GB
const MAX_UPLOAD_SIZE = 1000000000;
const MAX_UPLOAD_COUNT = 50;
# 10 GB
const MAX_STORAGE = 10000000000;
const USER_FOLDERS_PATH = "user_folders/";

# LOGIN FUNCTIONS

# Checks if data entered is valid for creating an account
# Returns bool or string
# False return means user account can be created
# String return means the data provided is not valid (username is too long, username contains symbols, etc.)
function checkSignup()
{

    if (isset($_POST["submit_signup"])) {

        global $db;

        $error = false;
        $username_exists = false;
        $empty_username = empty($_POST["text_username_signup"]);
        $empty_password = empty($_POST["text_password_signup"]);
        $empty_confirm_password = empty($_POST["text_confirm_password"]);
        $password_confirmed = $_POST["text_password_signup"] == $_POST["text_confirm_password"];
        $username = $_POST["text_username_signup"];
        $password = $_POST["text_password_signup"];
        $username_len_checks = strlen($username) <= 16;
        $password_len_checks = strlen($password) <= 72;

        $username_contains_symbols = preg_match(USERNAME_ALLOWED_SYMBOLS, $username);

        $username = hash("sha256", $_POST["text_username_signup"]);

        # Check if entered username is already in database
        # If $query_result > 0 the username provided is already in use
        $query_username_exists = "SELECT username FROM user_accounts WHERE username = '$username'";
        $query_result = $db->query($query_username_exists)->numRows();

        if ($query_result != 0) {
            $username_exists = true;
        }

        if ($empty_username) {
            $error = "Username is empty";
        } else {
            if ($username_contains_symbols) {
                $error = "Username can't contain symbols";
            } else {
                if ($username_exists) {
                    $error = "Username is already in use";
                } else {
                    if ($empty_password) {
                        $error = "Password is empty";
                    } else {
                        if ($empty_confirm_password) {
                            $error = "Password wasn't confirmed";
                        } else {
                            if (!$password_confirmed) {
                                $error = "Confirm password doesn't match";
                            } else {
                                if (!$username_len_checks) {
                                    $error = "Username can't be longer than 16 characters";
                                } else {
                                    if (!$password_len_checks) {
                                        $error = "Password can't be longer than 72 characters";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

    }

    return $error;
}

# Check if data provided on login matches that in the database
# Returns bool
# True if user entered username and password correctly, false if not
function checkLogin()
{
    global $db;

    # User entered username and password on login page (index.php)
    $username = $_POST["text_username_login"];
    $password = $_POST["text_password_login"];
    $password_hashed = hash("sha256", $password);
    $username_hashed = hash("sha256", $username);

    # Get username and password from database for the provided username
    $query_username = "SELECT username FROM user_accounts WHERE username = '$username_hashed'";
    $query_password = "SELECT password FROM user_accounts WHERE username = '$username_hashed'";

    $username_query_result = $db->query($query_username)->fetchAll();
    $password_query_result = $db->query($query_password)->fetchAll();

    # Check if username and password fetched from database match those entered by user
    if ($password_hashed == $password_query_result[0]["password"] && $username_hashed == $username_query_result[0]["username"]) {
        return true;
    } else {
        return false;
    }
}

# GENERAL FUNCTIONS

# Returns HTML for file upload error
function printError($error)
{
    return '<div class="dialog-container">
				<img src="img/error_icon.svg" alt="ERROR ICON" style="height: 20px; width: 20px; top: 17px; margin-left: 5px; position: relative;">
				<label style="font-family: Arial; color: red; position: relative; top: 13px; margin-left: 5px;" class="fontsize">' . $error . '</label>
		  </div>';
}

# Returns HTML for MySQL connection error
function printMysqlError($error)
{
    return '<div style="display: block; position: absolute; left: 0; top: 0; width: 100%;">    
                <label style="font-family: Arial; color: white; left: 0; top: 0; width: 100%; font-weight: bold; font-size: 56px; background-color: red; height: 150px; display: flex; align-items: center; margin-bottom: 10px; padding-left: 10px;" class="fontsize">' . 'Fatal MySQL Error' . '</label>
                <label style="font-family: Arial; color: black; font-weight: bold; font-size: 20px; padding-left: 10px;">' . 'Error code: ' . '</label>
                <label style="font-family: Arial; color: black; font-size: 20px;">' . $error . '</label>
            </div>';
}

# FILE FUNCTIONS

# Download $file from server
# $file must be the absolute path of the file in storage
# Example $file = "user_folders/<username_hashed>/<file_name>"
function download($file)
{
    $fmime = mime_content_type($file);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $fmime);
    header('Content-Disposition: attachment; filename=' . basename($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    die();
}

# Print all filenames and sizes for the current user from database
function printAllFiles($username)
{

    global $db;

    # Get all file names and sizes from database
    $query_getfiles = "SELECT name FROM user_files WHERE owner = '$username'";
    $query_getsizes = "SELECT size FROM user_files WHERE owner = '$username'";
    # Default file icon, if file type isn't detected
    $file_icon = "img/file-earmark-fill.svg";
    $path = "user_folders/" . $username . "/";

    $files = $db->query($query_getfiles)->fetchAll();
    $fsizes = $db->query($query_getsizes)->fetchAll();

    for ($i = 0; $i < count($files); $i++) {

        $file = $files[$i]["name"];
        $fsize = $fsizes[$i]["size"];
        $str_file = strval($file);
        $str_fsize = strval($fsize);
        $mime_type = explode("/", mime_content_type($path . $str_file));

        # Detect file type and set appropriate icon
        switch ($mime_type[0]) {
            case "application":
                $file_icon = "img/file-earmark-binary-fill.svg";
                break;
            case "audio":
                $file_icon = "img/file-earmark-music-fill.svg";
                break;
            case "font":
                $file_icon = "img/file-earmark-font-fill.svg";
                break;
            case "image":
                $file_icon = "img/file-earmark-image-fill.svg";
                break;
            case "text":
                $file_icon = "img/file-earmark-text-fill.svg";
                break;
            case "video":
                $file_icon = "img/file-earmark-play-fill.svg";
                break;
        }

        echo '<a href="website.php?file=' . $str_file . '" class="file">
                    <img src="' . $file_icon . '" class="file-icon">                     
                        <label style="float: left; margin-left: 5px; cursor: pointer; font-weight: normal;">'
            . $str_file . '</label>                        
                    <label style="float: right; margin-right: 15px; margin-left: auto;">' . $str_fsize . '</label>
              </a>';
    }
}

# Delete file from server storage and database
# $fullpath -> full file path
# $fname -> only basename of file
function delete($fullpath, $fname, $username)
{
    global $db;

    $filesize = filesize($fullpath);

    # Delete file from server storage
    unlink($fullpath);

    # Delete file info from database
    $query_delete = "DELETE FROM user_files WHERE name = '$fname' and owner = '$username'";
    $db->query($query_delete);

    # Remove file size from storage space used by user
    $query_subtract_size = "UPDATE user_accounts SET spaceused_b = spaceused_b - '$filesize' WHERE username = '$username'";
    $db->query($query_subtract_size);
}

# Upload file to server storage and info about file to database
# Returns empty string if upload successful, or HTML error message
function upload($file_input_name = "upload")
{
    global $db;

    $username = $_SESSION["user"];
    $username_hashed = hash("sha256", $username);
    $file_count = count($_FILES[$file_input_name]["tmp_name"]);
    $upload = true;
    $return = "";
    $upload_size = 0;

    $query_spaceused = "SELECT spaceused_b FROM user_accounts WHERE username = '$username_hashed'";
    $spaceused = $db->query($query_spaceused)->fetchArray();

    # Check if all upload requirements are met
    # If $upload is false, files won't be uploaded
    if (count($_FILES[$file_input_name]["tmp_name"]) > MAX_UPLOAD_COUNT) {
        $upload = false;
        $return = printError("Total file upload count can't exceed 50 files");
    }

    for ($i = 0; $i < $file_count; $i++) {
        $upload_size += $_FILES[$file_input_name]["size"][$i];
    }

    if ($upload_size > MAX_UPLOAD_SIZE) {
        $upload = false;
        $return = printError("Total file upload size can't exceed 1 GB");
    } else {
        if ($upload_size + $spaceused["spaceused_b"] > MAX_STORAGE) {
            $upload = false;
            $return = printError("Upload can't exceed storage limit");
        }
    }

    if ($upload) {

        for ($i = 0; $i < $file_count; $i++) {

            if ($_FILES[$file_input_name]["tmp_name"][$i] != "") {

                $dir = USER_FOLDERS_PATH . $username_hashed . "/";
                $tmp_file = $_FILES[$file_input_name]["tmp_name"][$i];
                $filename_original = $_FILES[$file_input_name]["name"][$i];
                $filename = $filename_original;
                # Filesize in bytes
                $filesize_raw = filesize($tmp_file);
                $filesize_mb = $filesize_raw / 1000000;
                $filesize_kb = round($filesize_mb * 1000);
                # Filesize string with unit extension
                # Example: 100 MB
                $filesize = round($filesize_mb) . " MB";
                $num_same_files = 0;

                # If file size is too small to represent as an integer in current unit, unit will be decreased to KB and B
                if ($filesize_kb < 1) {
                    $filesize = $filesize_raw . " B";
                } else {
                    if ($filesize_mb < 1) {
                        $filesize = $filesize_kb . " KB";
                    }
                }

                # Rename file if a file with the same name already exists
                # File will be changed by adding an index to the end of the name
                # Example: file.txt, file (2).txt, file (2).txt...
                while (true) {

                    $query_check_file = "SELECT name FROM user_files WHERE owner = '$username_hashed' AND name = '$filename'";
                    $query_check_file_rows = $db->query($query_check_file)->numRows();

                    if ($query_check_file_rows > 0) {

                        $num_same_files++;
                        $fext = pathinfo($filename_original)["extension"];
                        $fbasename = basename($filename_original, "." . $fext);

                        $filename = "$fbasename ($num_same_files).$fext";
                    } else {
                        break;
                    }
                }

                $query_upload = "INSERT INTO user_files (owner, name, size) VALUES ('$username_hashed', '$filename', '$filesize')";
                $db->query($query_upload);

                $query_add_to_size = "UPDATE user_accounts SET spaceused_b = spaceused_b + $filesize_raw WHERE username = '$username_hashed'";
                $db->query($query_add_to_size);

                move_uploaded_file($tmp_file, $dir . $filename);
            }
        }
    }

    return $return;
}

?>