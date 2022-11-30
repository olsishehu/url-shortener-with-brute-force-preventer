<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>




<head>
   <title>URL Shortner</title>

     <link href="style.css" rel="stylesheet" type="text/css" /> 
       
 <style>
.tooltip {
  position: relative;
  display: inline-block;
}

.tooltip .tooltiptext {
  visibility: hidden;
  width: 140px;
  background-color: #555;
  color: #fff;
  text-align: center;
  border-radius: 6px;
  padding: 5px;
  position: absolute;
  z-index: 1;
  bottom: 150%;
  left: 50%;
  margin-left: -75px;
  opacity: 0;
  transition: opacity 0.3s;
}

.tooltip .tooltiptext::after {
  content: "";
  position: absolute;
  top: 100%;
  left: 50%;
  margin-left: -5px;
  border-width: 5px;
  border-style: solid;
  border-color: #555 transparent transparent transparent;
}

.tooltip:hover .tooltiptext {
  visibility: visible;
  opacity: 1;
}
</style>
     

</head>
<body>

  <script>
function myFunction() {
  var copyText = document.getElementById("myInput");
  copyText.select();
  document.execCommand("copy");
  
  var tooltip = document.getElementById("myTooltip");
  tooltip.innerHTML = "Copied: " + copyText.value;
}

function outFunc() {
  var tooltip = document.getElementById("myTooltip");
  tooltip.innerHTML = "Copy to clipboard";
}
</script>


<?php 

include("config.php");



$HITS_PER_TIME = 40;
$TIME = 1; #minutes
$BAN_TIME = 10; #minutes


$allowed = true;

function countHits(){
    global $conn, $allowed, $HITS_PER_TIME, $BAN_TIME;
    $client_ip = $_SERVER['REMOTE_ADDR'];
    $date = date("Y-m-d H:i:s");
    $dbDate = "";
    // $query = "SELECT * FROM ip_list WHERE ip = \"".$client_ip."\" AND added_date = \"".$date. "\"";
    $query = "SELECT * FROM `ip_list` WHERE ip = \"".$client_ip."\" AND DATE(added_date) = '" .  date("Y-m-d") . "'";
    $result = $conn->query($query);
    if ($result->num_rows > 0)
    {
        $query = "SELECT * FROM ip_list WHERE ip = \"".$client_ip."\"";
        $result = $conn->query($query);
        // exit(strval($result->num_rows));
        while($row = $result->fetch_assoc()) {
            $hits = $row["hits"];
            $dbDate = date ('Y-m-d H:i:s', strtotime($row["added_date"]));
        }
        $timeDiff = intval(strtotime(date("H:i:s", strtotime($date))) - strtotime(date("H:i:s", strtotime($dbDate))));
        if (date("Y-m-d", strtotime($dbDate)) == date("Y-m-d", strtotime($date)))
        {
             if ($timeDiff < $HITS_PER_TIME && $hits < $HITS_PER_TIME){
                $hits += 1;
                $query = "UPDATE ip_list SET hits=".$hits." WHERE ip='".$client_ip."' ";
                $result = $conn->query($query);
                return true;
             }else if ($timeDiff > $BAN_TIME * 60){
                 $query = "INSERT INTO ip_list (ip, hits, added_date) VALUES ('".$client_ip."', '"."1"."','".strval($date)."')";
                $result = $conn->query($query);
                return true;
             }else {
                 return false;
             }
            
            
        }else{
            return false;
            exit();
        }
    }
    else
    {
        $query = "INSERT INTO ip_list (ip, hits, added_date) VALUES ('".$client_ip."', '"."1"."','".strval($date)."')";
        $result = $conn->query($query);
        return true;
    }
    
    // if (($dbDate == $date && $hits <= 10) || $dbDate == ""){

    if (date("Y-m-d", strtotime($dbDate)) == date("Y-m-d", strtotime($date)) && $timeDiff < $HITS_PER_TIME) {
        return(true);
    }else{
        if ($timeDiff > $HITS_PER_TIME){
            return true;
        }else{
           return(false); 
        }
        
        
    }
}


if (isset($_GET['url']) && $_GET['url']!="")
{ 
    $url=urldecode($_GET['url']);
    if (filter_var($url, FILTER_VALIDATE_URL)) 
    {
        
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 
        $allowed = countHits();
        if ($allowed){
            $slug=GetShortUrl($url);
            if (!file_exists($slug)) {
                mkdir($slug, 0755, true);
                createIndex($slug, $url);
            }
        }
        
        #crete table if doesn't exist
        $query = "CREATE TABLE IF NOT EXISTS `url_shorten` ( `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `url` tinytext NOT NULL, `short_code` varchar(50) NOT NULL, `hits` int(11) NOT NULL, `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
        $conn->query($query);
        
        $query = "CREATE TABLE IF NOT EXISTS `ip_list` ( `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `ip` varchar(15) NOT NULL, `hits` int(11) NOT NULL, `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
        $conn->query($query);
  
        $conn->close();
        

        //echo $base_url.$slug;

?>
<center>
<h1>Paste Your Url Here</h1>
<form>
<p><input style="width: 500px; height: 22px;" type="url" name="url" required /></p>
<p><input class="button" type="submit" /></p>
</form><br/>
<?php
    
    if ($allowed){
        echo 'Here is the short <a href="'; echo $base_url; echo"/"; echo $slug;
        echo '" target="_blank">'; echo 'link</a>: ';
        echo '<input type="text" value="' . $base_url. "/" . $slug .'" id="myInput">';
        echo '<div class="tooltip"><button onclick="myFunction()" onmouseout="outFunc()"><span class="tooltiptext" id="myTooltip" >Copy to clipboard</span>Copy URL</button></div></center>';
    }else{
        echo "Limit Exceeded";
       
    }
        
  
?>
  

<?php

        } 
        else 
        {
            die("$url is not a valid URL");
        }

    }
else
{
?>
<center>
<h1>Paste Your Url Here</h1>
<form>
<p><input style="width: 500px; height: 22px;" type="url" name="url" required /></p>
<p><input class="button" type="submit" /></p>
</form>
</center>
<?php
}


function GetShortUrl($url){
    global $conn;
    $query = "SELECT * FROM url_shorten WHERE url = '".$url."' "; 
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['short_code'];
    } 
    else 
    {
        $short_code = generateUniqueID();
        $sql = "INSERT INTO url_shorten (url, short_code, hits) VALUES ('".$url."', '".$short_code."', '0')";
        if ($conn->query($sql) === TRUE) {
            return $short_code;
            
        }
        else
        { 
            die("Unknown Error Occured");
        }
    }
}

function createIndex($directory, $url){

    $myfile = fopen("./" . $directory . "/index.php", "w") or die("Unable proceed!");
    $txt = "<?php\nheader(\"Location: ". $url . "\");\nexit();\n?>";
    fwrite($myfile, $txt);
    fclose($myfile);

}




function generateUniqueID(){
 global $conn; 
 $token = substr(md5(uniqid(rand(), true)),0,4); // creates a 3 digit unique short id. You can maximize it but remember to change .htacess value as well
 $query = "SELECT * FROM url_shorten WHERE short_code = '".$token."' ";
 $result = $conn->query($query); 
 if ($result->num_rows > 0) {
 generateUniqueID();
 } else {
 return $token;
 }
}


if(isset($_GET['redirect']) && $_GET['redirect']!="")
{ 
$slug=urldecode($_GET['redirect']);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
die("Connection failed: " . $conn->connect_error);
}
$url= GetRedirectUrl($slug);
$conn->close();
header("location:".$url);
exit;
}



function GetRedirectUrl($slug){
 global $conn;
 $query = "SELECT * FROM url_shorten WHERE short_code = '".addslashes($slug)."' "; 
 $result = $conn->query($query);
 if ($result->num_rows > 0) {
$row = $result->fetch_assoc();
// increase the hit
$hits=$row['hits']+1;
$sql = "update url_shorten set hits='".$hits."' where id='".$row['id']."' ";
$conn->query($sql);
return $row['url'];
}
else 
 { 
die("Invalid Link!");
}
}

?>
  </body>
  
