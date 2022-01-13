<?php

require_once "mysql_connect.php";

# LOGIN FUNCTIONS

# SPECIAL SYMBOL EXCLUSION REGEX
# Regex characters, such as / must be ESCAPED with \\
const USERNAME_ALLOWED_SYMBOLS = "/[^0-9a-zA-Z_]/";
# 1 GB
const MAX_UPLOAD_SIZE = 1000000000;
const MAX_UPLOAD_COUNT = 50;
# 10 GB
const MAX_STORAGE = 10000000000;

# LOGIN FUNCTIONS

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
        
        $query_username_exists = "SELECT username FROM user_accounts WHERE username = '$username'";
        $query_result = $db->query($query_username_exists)->numRows();
        
        if ($query_result != 0) {
            $username_exists = true;
        }
        
        if ($empty_username) {
            $error = "Username is empty";
        } else if ($username_contains_symbols) {
            $error = "Username can't contain symbols";
        } else if ($username_exists) {
            $error = "Username is already in use";
        } else if ($empty_password) {
            $error = "Password is empty";
        } else if ($empty_confirm_password) {
            $error = "Password wasn't confirmed";
        } else if (!$password_confirmed) {
            $error = "Confirm password doesn't match";
        } else if (!$username_len_checks) {
            $error = "Username can't be longer than 16 characters";
        } else if (!$password_len_checks) {
            $error = "Password can't be longer than 72 characters";
        }
        
    }
    
    return $error;
}

function checkLogin()
{
    global $db;
    
    $username = $_POST["text_username_login"];
    $password = $_POST["text_password_login"];
    $password_hashed = hash("sha256", $password);
    
    $query_username = "SELECT username FROM user_accounts WHERE username = '$username'";
    $query_password = "SELECT password FROM user_accounts WHERE username = '$username'";
    
    $username_query_result = $db->query($query_username)->fetchAll();
    $password_query_result = $db->query($query_password)->fetchAll();
    
    if ($password_hashed == $password_query_result[0]["password"] && $username == $username_query_result[0]["username"]) {
        return true;
    } else {
        return false;
    }
}

# GENERAL FUNCTIONS

function printError($error)
{
    return '<div class="dialog_container">
				<img src="img/error_icon.svg" alt="ERROR ICON" style="height: 20px; width: 20px; top: 17px; margin-left: 5px; position: relative;">
				<label style="font-family: Arial; color: red; position: relative; top: 13px; margin-left: 5px;" class="fontsize">'.$error.'</label>
		  </div>';
}

# FILE FUNCTIONS

function download($file)
{
    $fmime = mime_content_type($file);
    
    header('Content-Description: File Transfer');
    header('Content-Type: '.$fmime);
    header('Content-Disposition: attachment; filename='.basename($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: '.filesize($file));
    readfile($file);
    die();
}

function printAllFiles($username)
{
    
    global $db;
    
    $query_getfiles = "SELECT name FROM user_files WHERE owner = '$username'";
    $query_getsizes = "SELECT size FROM user_files WHERE owner = '$username'";
    
    $files = $db->query($query_getfiles)->fetchAll();
    $fsizes = $db->query($query_getsizes)->fetchAll();
    
    for ($i = 0; $i < count($files); $i++) {
        
        $file = $files[$i]["name"];
        $fsize = $fsizes[$i]["size"];
        $str_file = strval($file);
        $str_fsize = strval($fsize);
        
        echo '<div class="file">
                    <a href="website.php?file='.$str_file.'">
                        <label style="float: left; margin-left: 3px; cursor: pointer">'.$str_file.'</label>
                        <label style="float: right; margin-right: 3px;">'.$str_fsize.'</label>
                    </a>
              </div>';
    }
}

function delete($fullpath, $fname, $username)
{
    global $db;
    
    $filesize = filesize($fullpath);
    
    unlink($fullpath);
    
    $query_delete = "DELETE FROM user_files WHERE name = '$fname' AND owner = '$username'";
    $db->query($query_delete);
    
    $query_subtract_size = "UPDATE user_accounts SET spaceused_b = spaceused_b - '$filesize' WHERE username = '$username'";
    $db->query($query_subtract_size);
}

function upload($file_input_name = "upload")
{
    
    global $db;
    
    $username = $_SESSION["user"];
    $file_count = count($_FILES[$file_input_name]["tmp_name"]);
    $upload = true;
    $return = "";
    $upload_size = 0;
    
    $query_spaceused = "SELECT spaceused_b FROM user_accounts WHERE username = '$username'";
    $spaceused = $db->query($query_spaceused)->fetchArray();
    
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
    } else if ($upload_size + $spaceused["spaceused_b"] > MAX_STORAGE) {
        $upload = false;
        $return = printError("Upload can't exceed storage limit");
    }
    
    if ($upload) {
        
        for ($i = 0; $i < $file_count; $i++) {
            
            if ($_FILES[$file_input_name]["tmp_name"][$i] != "") {
                
                $dir = "user_folders/".$username."/";
                $tmp_file = $_FILES[$file_input_name]["tmp_name"][$i];
                $filename_original = $_FILES[$file_input_name]["name"][$i];
                $filename = $filename_original;
                $filesize_raw = filesize($tmp_file);
                $filesize_mb = $filesize_raw / 1000000;
                $filesize = round($filesize_mb)." MB";
                $num_same_files = 0;
                
                if ($filesize_mb < 1) {
                    $filesize = round($filesize_mb * 1000)." KB";
                }
                
                while (true) {
                    
                    $query_check_file = "SELECT name FROM user_files WHERE owner = '$username' AND name = '$filename'";
                    $query_check_file_rows = $db->query($query_check_file)->numRows();
                    
                    if ($query_check_file_rows > 0) {
                        
                        $num_same_files++;
                        $fext = pathinfo($filename_original)["extension"];
                        $fbasename = basename($filename_original, ".".$fext);
                        
                        $filename = "$fbasename ($num_same_files).$fext";
                    } else {
                        break;
                    }
                }
                
                $query_upload = "INSERT INTO user_files (owner, name, size) VALUES ('$username', '$filename', '$filesize')";
                $db->query($query_upload);
                
                $query_add_to_size = "UPDATE user_accounts SET spaceused_b = spaceused_b + $filesize_raw WHERE username = '$username'";
                $db->query($query_add_to_size);
                
                move_uploaded_file($tmp_file, $dir.$filename);
            }
        }
    }
    
    return $return;
}

?>