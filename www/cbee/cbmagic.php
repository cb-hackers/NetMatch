<?php
// T‰m‰ tiedosto on tarkoitettu vain NetMatch_v2.1b versiolle!

/*
// Salli vain CoolBasicin p‰‰st‰ suorittamaan t‰t‰ skripti‰
if( $_SERVER['HTTP_USER_AGENT'] !== "CoolBasic" ) 
{
    header("Location: ./");
    die("ok"); // Huijataan niit‰ luulemaan, ett‰ onnistuivat >:D
}
*/

// Sis‰lt‰‰ connectToDatabase-funktion
require("../../connect.php");

define( "NM_VER", "v2.1b" );
define( "NM_PROFILE", "NetMatch" );

header("Content-type: text/plain;");


CreateLog();

if( isset( $_GET['mode'] ) )
{
    switch( $_GET['mode'] )
    {
        case "list":    die( ListServers() );
        case "reg":     die( RegisterServer() );
        case "unreg":   die( UnRegisterServer() );
        case "update":  die( UpdateServer() );
    }
}

die("unknown_mode");

// #####################################
// SERVUJEN LISTAAMINEN
// ----
// Palautettava merkkijono sis‰lt‰‰ ensin merkkijonon GSS:
// ja sitten n‰m‰ merkkijonot | merkill‰ eroteltuna:
//   name=<palvelimen_nimi>
//   addr=<ip-osoite>
//   port=<palvelimen_portti>
//   info=<pelaajia>,<botteja>,<kartta>,<max_pelaajia>
//   ver=<versio>
// T‰m‰n j‰lkeen tulee rivinvaihto, ja seuraavan palvelimen tiedot.
// ----
// T‰t‰ funktiota kutsuttaessa tarkistetaan myˆs, onko tietokannassa palvelimia,
// joita on p‰ivitetty viimeksi 90sek sitten. Jos on, poistetaan ne.
// ----
// Jos listaamisessa tapahtuu virhe, palautettava merkkijono on joku seuraavista:
//    listing_failed    = URL:n tiedoista oli jotain v‰‰rin
//    system_error      = MySQL feilasi
// #####################################
function ListServers()
{
    // mode -> list | profile -> NetMatch | ver -> v2.1b 

    // Tarkistetaan, ett‰ tarvittavat tiedot on annettu
    if( !isset( $_GET['profile'], $_GET['ver'] ) ) return "listing_failed";
    if( $_GET['profile'] !== NM_PROFILE ) return "listing_failed";
    
    // Yhdistet‰‰n MySQL-tietokantaan. 1 tarkoittaa, ett‰ k‰ytet‰‰n Object Oriented-tyyli‰, eik‰ proseduraalista tyyli‰.
    $mysqli = connectToDatabase(1);
    
    if( $mysqli->connect_error )
    {
        // Tietokantaan yhdist‰minen ei onnistunut. Kaadetaan homma t‰h‰n.
        return "system_error";
    }
    
    // Tehd‰‰n MySQL:iin haku.
    $result = $mysqli->query("SELECT * FROM `netmatch`");
    if( !$result )
    {
        // query ei onnistunut. Kaadetaan homma t‰h‰n.
        $mysqli->close();
        return "system_error";
    }
    
    
    
    // T‰h‰n muuttujaan tallennetaan palautettava merkkijono
    $liststring = "GSS:";
    
    while( $row = $result->fetch_assoc() )
    {
        $id = $row['ID'];
        // Tarkistetaan, onko palvelin aktiivinen
        if( $row['active'] )
        {
            // Tarkistetaan, onko palvelinta p‰ivitetty 90sek sis‰‰n.
            if( strtotime( $row['updated'] ) < ( time() - 90 ) )
            {
                // Palvelin on viimeksi p‰ivitetty yli 90sek sitten. Asetetaan se ei-aktiiviseksi.
                $ret = $mysqli->query("UPDATE `netmatch` SET `active` = '0' WHERE `ID` = '$id'");
                if( !$ret )
                {
                    // query ei onnistunut. Kaadetaan homma t‰h‰n.
                    $result->close();
                    $mysqli->close();
                    return "system_error";
                }
                
                // Haetaan seuraava rivi.
                continue;
            }
            // Lis‰t‰‰n palvelimen tiedot merkkijonoon
            $liststring .= "name=" . StringConvert( $row['desc'] ) . "|";
            $liststring .= "addr=" . $row['ip'] . "|";
            $liststring .= "port=" . $row['port'] . "|";
            $liststring .= "info=" . StringConvert( $row['info'] ) . "|";
            $liststring .= "ver=" . $row['version'];
            $liststring .= "\n";
        }
        else
        {
            // Tarkistetaan, onko palvelinta p‰ivitetty 10min sis‰‰n
            if( strtotime( $row['updated'] ) < ( time() - 600 ) )
            {
                // Ei ole p‰ivitetty. Poistetaan se listalta.
                $ret = $mysqli->query("DELETE FROM `netmatch` WHERE ID = '$id'");
                if( !$ret )
                {
                    // query ei onnistunut. Kaadetaan homma t‰h‰n.
                    $result->close();
                    $mysqli->close();
                    return "system_error";
                }
            }
        }
    }
    
    // Vapautetaan muistia
    $result->close();
    
    // Suljetaan tietokantayhteys
    $mysqli->close();
    
    return $liststring;
}

// #####################################
// SERVUN REKISTER÷INTI
// ----
// Palautettava merkkijono on jokin seuraavista:
//   registering_failed     = URL:n tiedoista oli jotain v‰‰rin
//   system_error           = MySQL tai socket failasi
//   server_exists          = samalla IP:ll‰ ja portilla on jo palvelin
//   ok                     = palvelin rekisterˆitiin onnistuneesti
// #####################################
function RegisterServer()
{
    // profile -> NetMatch | ver -> v2.1b | mode -> reg | desc -> (palvelimen_nimi) | port -> (palvelimen_portti)

    // Rekisterˆintiin vaadittavien tietojen tarkistus
    if( !isset( $_GET['profile'], $_GET['ver'], $_GET['desc'], $_GET['port'] ) ) return "registering_failed";
    if( $_GET['profile'] !== NM_PROFILE || $_GET['ver'] !== NM_VER || empty($_GET['desc']) || $_GET['port'] < 1 || $_GET['port'] > 65535 ) return "registering_failed";
    
    // Yhdistet‰‰n MySQL-tietokantaan. 1 tarkoittaa, ett‰ k‰ytet‰‰n Object Oriented-tyyli‰, eik‰ proseduraalista tyyli‰.
    $mysqli = connectToDatabase(1);
    
    if( $mysqli->connect_error )
    {
        // Tietokantaan yhdist‰minen ei onnistunut. Kaadetaan homma t‰h‰n.
        return "system_error";
    }
    
    // Tehd‰‰n kaikista merkkijonoista MySQL-turvallisia
    $profile = $mysqli->real_escape_string( $_GET['profile'] );
    $version = $mysqli->real_escape_string( $_GET['ver'] );
    $desc = $mysqli->real_escape_string( $_GET['desc'] );
    $port = (int) $_GET['port'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Tarkistetaan, onko listalla olemassa jo palvelin samalla IP:ll‰ ja portilla.
    $result = $mysqli->query("SELECT * FROM `netmatch` WHERE `ip` = '$ip' AND `port` = '$port'");
    if( !$result )
    {
        // query ei onnistunut. Kaadetaan homma t‰h‰n.
        $mysqli->close();
        return "system_error";
    }
    
    if( $result->num_rows != 0 ) 
    {
        while( $row = $result->fetch_assoc() )
        {
            if( strtotime( $row['updated'] ) < ( time() - 90 ) || !$row['active'] )
            {
                // Palvelin on viimeksi p‰ivitetty yli 90sek sitten. Poistetaan se listalta.
                $ret = $mysqli->query("DELETE FROM `netmatch` WHERE ID = '{$row['ID']}'");
                if( !$ret )
                {
                    // query ei onnistunut. Kaadetaan homma t‰h‰n.
                    $result->close();
                    $mysqli->close();
                    return "system_error";
                }
            }
            else
            {
                // Palvelin on viel‰ "pystyss‰", joten pys‰ytet‰‰n rekisterˆinti t‰h‰n.
                $result->close();
                $mysqli->close();
                return "server_exists";
            }
        }
    }
    // Tarkistettiin ja vapautetaan muistia.
    $result->close();
    
    // Lis‰t‰‰n palvelin
    // profile -> NetMatch | ver -> v2.1b | mode -> reg | desc -> VesQ-server | port -> 10999 
    $ret = $mysqli->query( "INSERT INTO `netmatch` " .
                          "( `ip`, `port`, `version`, `desc`) VALUES" .
                          "( '$ip', '$port', '$version', '$desc' )"
                        );
    if( !$ret )
    {
        // query ei onnistunut. Kaadetaan homma t‰h‰n.
        $mysqli->close();
        return "system_error";
    }
    
    // Suljetaan tietokantayhteys
    $mysqli->close();
    
    // Servun rekisterˆinti on onnistunut, l‰hetet‰‰n UDP-paketti CB:lle
    $socket = @socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );

    if( !$socket ){
        return "system_error";
    }

    
    // 67 73 73 20 <-- ID (544437095)
    // 47 53 53 2B <-- GSS+
    $msg = pack( "C*",
                0x67, 0x73, 0x73, 0x20,
                0x47, 0x53, 0x53, 0x2B );
    
    //$msg = pack( "C*", 544437095, "GSS+" );
    $len = 8;

    @socket_sendto( $socket, $msg, $len, MSG_EOR, $ip, $port );

    @socket_close( $socket );

    // Tarkistetaan viel‰ aivan lopuksi, onko tullut virheit‰.
    if( socket_last_error() ) return "system_error";
    
    return "ok";
}

// #####################################
// SERVUN POISTAMINEN LISTALTA
// ----
// Palautettava merkkijono on jokin seuraavista:
//   unregistering_failed   = URL:n tiedoista oli jotain v‰‰rin
//   system_error           = MySQL feilasi
//   ok                     = palvelin poistettiin listalta onnistuneesti
// #####################################
function UnRegisterServer()
{
    // profile -> NetMatch | port -> 10999 | mode -> unreg
    
    // Tarkistetaan, ett‰ tarvittavat tiedot on annettu ja oikein
    if( !isset( $_GET['profile'], $_GET['port'] ) ) return "unregistering_failed";
    if( $_GET['profile'] !== NM_PROFILE || $_GET['port'] < 1 || $_GET['port'] > 65535 ) return "unregistering_failed";

    // Yhdistet‰‰n MySQL-tietokantaan. 1 tarkoittaa, ett‰ k‰ytet‰‰n Object Oriented-tyyli‰, eik‰ proseduraalista tyyli‰.
    $mysqli = connectToDatabase(1);
    
    if( $mysqli->connect_error )
    {
        // Tietokantaan yhdist‰minen ei onnistunut. Kaadetaan homma t‰h‰n.
        return "system_error";
    }
    
    // V‰h‰n helpompi k‰sitell‰ n‰it‰kin. Tehd‰‰n portistakin turvallinen.
    $ip = $_SERVER['REMOTE_ADDR'];
    $port = (int) $_GET['port'];
    
    // Poistetaan palvelin listalta, JOS se on siell‰.
    $result = $mysqli->query("SELECT `ID` FROM `netmatch` WHERE `ip` = '$ip' AND `port` = '$port'");
    if( $result->num_rows > 0 )
    {
        $row = $result->fetch_assoc();
        $ret = $mysqli->query("DELETE FROM `netmatch` WHERE ID = '{$row['ID']}'");
        if( !$ret )
        {
            // query ei onnistunut. Kaadetaan homma t‰h‰n.
            $mysqli->close();
            return "system_error";
        }
    }
    
    // Vapautetaan muistia
    $result->close();
    
    // Suljetaan tietokantayhteys
    $mysqli->close();
    
    return "ok";
}

// #####################################
// SERVUN PƒIVITTƒMINEN
// ----
// Palautettava merkkijono on jokin seuraavista:
//   update_failed      = URL:n tiedoista oli jotain v‰‰rin
//   system_error       = MySQL feilasi
//   ok                 = palvelin p‰ivitettiin onnistuneesti
// #####################################
function UpdateServer()
{
    // profile -> NetMatch | port -> 10999 | mode -> update | data -> 6,6,Luna,10,Bot_1|Bot_2|Bot_3|Bot_4|Bot_5|Bot_6 

    // Tarkistetaan, ett‰ on annettu oikeat tiedot
    if( !isset( $_GET['profile'], $_GET['port'], $_GET['data'] ) ) return "update_failed";
    if( $_GET['profile'] !== NM_PROFILE || empty($_GET['data']) || $_GET['port'] < 1 || $_GET['port'] > 65535 ) return "update_failed";
    
    // Yhdistet‰‰n MySQL-tietokantaan. 1 tarkoittaa, ett‰ k‰ytet‰‰n Object Oriented-tyyli‰, eik‰ proseduraalista tyyli‰.
    $mysqli = connectToDatabase(1);
    
    if( $mysqli->connect_error )
    {
        // Tietokantaan yhdist‰minen ei onnistunut. Kaadetaan homma t‰h‰n.
        return "system_error";
    }
    
    // Otetaan IP ja portti talteen
    $ip = $_SERVER['REMOTE_ADDR'];
    $port = (int) $_GET['port'];
    
    // Parsitaan data MySQL-turvallisiin muuttujiin
    $players = (int)( strtok( $_GET['data'], "," ) ); 
    $bots = (int)( strtok(",") ); 
    $map = $mysqli->real_escape_string( str_replace( " ", "_", strtok(",") ) );
    $maxplayers = (int)( strtok(",") );
    $names = $mysqli->real_escape_string( strtok(",") );
    
    // T‰m‰ tallennetaan tietokantaan "info"-soluun
    $info = "$players,$bots,$map,$maxplayers";
    
    // Tarkistetaan, onko listalla olemassa jo palvelin samalla IP:ll‰ ja portilla.
    $result = $mysqli->query("SELECT * FROM `netmatch` WHERE `ip` = '$ip' AND `port` = '$port'");
    if( $result->num_rows > 0 )
    {
        // Otetaan ID talteen muuttujaan $id
        $row = $result->fetch_assoc();
        $id = $row['ID'];
        
        // P‰ivitet‰‰n se servu listaan nyt.
        $ret = $mysqli->query("UPDATE `netmatch` SET `active` = '1', `info` = '$info', `players` = '$names', `updated` = NOW() WHERE `ID` = '$id'");
        if( !$ret )
        {
            // query ei onnistunut. Kaadetaan homma t‰h‰n.
            $result->close();
            $mysqli->close();
            return "system_error";
        }
    }
    
    // Suljetaan tietokantayhteys
    $mysqli->close();
    
    return "ok";
}


// Sis‰iseen k‰yttˆˆn
function StringConvert( $str, $decode=0 )
{
    $arr = array( '"' => '&#34;',
                  '$' => '&#36;',
                  '<' => '&#60;',
                  '=' => '&#61',
                  '>' => '&#62;',
                  "\\" => '&#92;',
                  '|' => '&#124;' );
    
    if( $decode ) array_flip( $arr );
    
    return strtr( $str, $arr );
}

// Loggaaminen
function CreateLog( $logfile = "info.txt" )
{
    if( !empty( $_GET ) )
    {
        $str = date( "d-m-y, G:i:s" ) . " - " . $_SERVER['REMOTE_ADDR'] . " |";

        foreach( $_GET as $key=>$val )
        {
            $str .= "| $key -> $val ";
        }
        $str .= "\n";

        $filehandle = fopen("info.txt", "a");
        fwrite( $filehandle, $str );
        fclose( $filehandle );
    }
}
?>