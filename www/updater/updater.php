<?php
$html = true;
$listType = 0;
if( isset( $_GET['list'] ) )
{
    $html = false;
    $listType = (int) $_GET['list'];
}

$versionOnly = false;
if( isset( $_GET['version'] ) )
    $versionOnly = true;
if( !$html || $versionOnly ) header( "Content-type: text\plain;" );
define( "SCRIPT_DIR", "http://" . $_SERVER['SERVER_NAME'] . pathinfo( $_SERVER['REQUEST_URI'], PATHINFO_DIRNAME ) . "/" );
define( "CRC_DELIMITER", "|" );
define( "CRC_NEXTFILE", ";" );
define( "NM_VERSION", "2.5" );
define( "NM_REVISION", "" );
define( "NM_PATCH", 0 );

if( $versionOnly ) die( "NM:" . NM_VERSION . ":" . NM_REVISION . ":" . NM_PATCH );

chdir( __DIR__ );
$lastModified = gohash();

if( $html )
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
    <title>NetMatch updater files</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<h1>NetMatch updater files</h1>
<?php
echo '<h2>Current version: v' . NM_VERSION . NM_REVISION;
if( NM_PATCH > 0 )
    echo ( NM_PATCH <= 9 ? "_0" : "_" ) . NM_PATCH;
echo '</h2>' . "\n";
echo '<h3>Last modified: ' . strftime( '%a %d.%m. %Y - %H:%M', $lastModified ) . "</h3>\n";
echo "\n<ol>";
}

// Bufferoidaan output ettei CB jäädy.
if( !$html ) ob_start();
foreach( $crcArray as $dirArray )
{
    uksort($dirArray, "isort");
    foreach( $dirArray as $file => $fileArray )
    {
        if( !$html )
        {
            if( $listType == 2 )
                echo $file . CRC_DELIMITER . $fileArray['hash'] . CRC_DELIMITER . $fileArray['size'] . CRC_DELIMITER . $fileArray['link'] . CRC_NEXTFILE;
            else
                echo $file . CRC_DELIMITER . $fileArray['hash'] . CRC_DELIMITER . $fileArray['link'] . CRC_NEXTFILE;
        } else {
            echo "\n<li>$file<ul>";
            echo "\n\t<li>Hash: $fileArray[hash]</li>";
            echo "\n\t<li>Size: " . format_bytes( $fileArray['size'] ) . "</li>";
            echo "\n\t<li>Link: <a href=\"$fileArray[link]\">$fileArray[link]</a></li>";
            echo "\n</ul></li>";
        }
    }
}

// Tulostetaan kaikki nyt vasta.
if( !$html )
{
    ob_end_flush();
} else {
?>
</ol>

</body>
</html>
<?php
}


function gohash( $dir = ".", $lastmod = 0 )
{
    global $crcArray;

    $lastModified = $lastmod;

    $fileArray = scandir( $dir );
    foreach( $fileArray as $file )
    {
        if( $file != "." && $file != ".." && $file != basename( __FILE__ ) )
        {
            if( $dir != "." )
                $file = $dir . "/" . $file;
            if( is_dir( $file ) )
            {
                $lastModified = gohash( $file, $lastModified );
                continue;
            }
            $hash = strtoupper(hash_file('crc32b', $file));
            $link = SCRIPT_DIR . $file;
            $size = filesize($file);
            $crcArray[$dir][$file] = array( "hash" => $hash, "link" => $link, "size" => $size );
            $fileModified = filemtime( $file );
            if( $fileModified > $lastModified )
                $lastModified = $fileModified;
        }
    }
    return $lastModified;
}

function isort($a,$b) {
    return strtolower($a)>strtolower($b);
}

function format_bytes($size) {
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 2).$units[$i];
}
?>