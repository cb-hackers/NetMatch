<?php require("../connect.php"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>NetMatch</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>
    <h1>NetMatch servers</h1>
    <?php echo utf8_encode( ListServersHTML() ); ?>

</body>

</html>


<?php
function ListServersHTML()
{
    // Yhdistetään MySQL-tietokantaan. 1 tarkoittaa, että käytetään Object Oriented-tyyliä, eikä proseduraalista tyyliä.
    $mysqli = connectToDatabase(1);
    
    if( $mysqli->connect_error )
    {
        // Tietokantaan yhdistäminen ei onnistunut. Kaadetaan homma tähän.
        return "Unable to connect to MySQL server!";
    }
    
    // Tehdään MySQL:iin haku.
    $result = $mysqli->query("SELECT * FROM `netmatch`");
    if( !$result )
    {
        // query ei onnistunut. Kaadetaan homma tähän.
        $mysqli->close();
        return "MySQL query failed!";
    }
    
    
    
    // Tähän muuttujaan tallennetaan palautettava merkkijono
    $liststring = '<table><tr>'.
                  '<th>Server name</th>'.
                  '<th>IP-address</th>'.
                  '<th>Port</th>'.
                  '<th>Players</th>'.
                  '<th>Map</th>'.
                  '<th>Player names</th>'.
                  '<th>Server version</th>'.
                  '</tr>';
    
    while( $row = $result->fetch_assoc() )
    {
        $id = $row['ID'];
        // Tarkistetaan, onko palvelin aktiivinen
        if( $row['active'] )
        {
            // Tarkistetaan, onko palvelinta päivitetty 90sek sisään.
            if( strtotime( $row['updated'] ) < ( time() - 90 ) )
            {
                // Palvelin on viimeksi päivitetty yli 90sek sitten. Asetetaan se ei-aktiiviseksi.
                $ret = $mysqli->query("UPDATE `netmatch` SET `active` = '0' WHERE `ID` = '$id'");
                if( !$ret )
                {
                    // query ei onnistunut. Kaadetaan homma tähän.
                    $result->close();
                    $mysqli->close();
                    return "MySQL query failed!";
                }
                
                // Haetaan seuraava rivi.
                continue;
            }
            
            // Parsitaan tiedot info-sarakkeesta
            $tmparr = explode(",", $row['info'] );
            $players = $tmparr[0];
            $bots = $tmparr[1];
            $map = $tmparr[2];
            $maxplayers = $tmparr[3];
            $humanplayers = $players - $bots;
            
            // Rullataan jokainen pelaaja merkkijonoon
            $playernames = strtr( $row['players'], "|", "\n" );
            $playernames = nl2br( $playernames );
            
            if( $row['devbuild'] == 0 )
            {
                // Lisätään palvelimen tiedot taulukkoon
                $liststring .= "\n<tr>".
                               "<td>{$row['desc']}</td>".
                               "<td>{$row['ip']}</td>".
                               "<td>{$row['port']}</td>".
                               "<td>$players ($humanplayers) / $maxplayers</td>".
                               "<td>$map</td>".
                               "<td>$playernames</td>".
                               "<td>{$row['version']}</td>".
                               "</tr>";
            } else {
                // Listataan myös devbuildin servut listaan, mutta laitetaan niille
                // mahdollista tyylitystä varten eri class-tag. Lisätään myös
                // version perään "-dev".
                $liststring .= "\n<tr class=\"devbuild\">".
                               "<td>{$row['desc']}</td>".
                               "<td>{$row['ip']}</td>".
                               "<td>{$row['port']}</td>".
                               "<td>$players ($humanplayers) / $maxplayers</td>".
                               "<td>$map</td>".
                               "<td>$playernames</td>".
                               "<td>{$row['version']}-dev</td>".
                               "</tr>";
            }
        }
        else
        {
            // Tarkistetaan, onko palvelinta päivitetty 10min sisään
            if( strtotime( $row['updated'] ) < ( time() - 600 ) )
            {
                // Ei ole päivitetty. Poistetaan se listalta.
                $ret = $mysqli->query("DELETE FROM `netmatch` WHERE ID = '$id'");
                if( !$ret )
                {
                    // query ei onnistunut. Kaadetaan homma tähän.
                    $result->close();
                    $mysqli->close();
                    return "MySQL query failed!";
                }
            }
        }
    }
    
    // Viimeistellään taulukko
    $liststring .= '</table>';
    
    // Vapautetaan muistia
    $result->close();
    
    // Suljetaan tietokantayhteys
    $mysqli->close();
    
    return $liststring;
}
?>