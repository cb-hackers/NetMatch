<?
header( "Content-type: text\plain;" );
define( "SCRIPT_DIR", "http://netmatch.vesq.org/updater/" );

gohash();

function gohash( $dir = "." )
{

    $fileArray = scandir( $dir );
    foreach( $fileArray as $file )
    {
        if( $file != "." && $file != ".." )
        {
            if( $dir != "." )
                $file = $dir . "/" . $file;
            if( is_dir( $file ) )
            {
                gohash( $file );
                continue;
            }
            echo $file . "|";
            echo strtoupper(hash_file('crc32b', $file)) . "|";
            echo SCRIPT_DIR;
            echo "$file" . ';';
        }
    }
}
?>