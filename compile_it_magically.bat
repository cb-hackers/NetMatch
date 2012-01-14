@echo OFF

:: -----------
:: T„m„ tiedosto tekee taikoja ja tuottaa sinulle valmiin, toimivan
:: NetMatch_TheEnd.exe tiedoston ja ajaa sen automaattisesti.
:: N„in saat suoraan git-reposta revityn koodin k„„nnetty„.
::
:: T„m„ toimii vaikkei sinulla olisi CoolBasicia asennettuna koneellasi.
::
:: Voit antaa tiedostolle komentoriviparametrina haluamasi .exe-tiedoston nimen
:: (ilman .exe-p„„tett„) ja skripti luo sen oletuksen NetMatch_TheEnd sijaan.
::------------

set exeName=%1
IF "%exeName%"=="" set exeName=NetMatch_TheEnd

title Maaginen NetMatchin kasaaja

:: Tarkistetaan onko tmp-niminen kansio tahi tiedosto jo olemassa
IF EXIST "%~dp0tmp\" (
  echo tmp-kansio l”ydetty, NetMatch_TheEnd.exe-tiedostoa ei voida luoda!
  echo Poista tmp-kansio ennen t„m„n skriptin uudelleen suorittamista.
  echo.
  pause
  echo.
  exit /B 1
)
IF EXIST "%~dp0tmp" (
  echo tmp-niminen tiedosto l”ydetty! Poista se ja aja t„m„ skripti uudelleen.
  echo NetMatch_TheEnd.exe-tiedostoa ei voida luoda!
  echo.
  pause
  echo.
  exit /B 1
)

:: Tarkistetaan, l”ytyyk” NetMatch.dat tiedostoa ja luodaan se tarvittaessa
IF NOT EXIST "%~dp0NetMatch.dat" (
  echo Mediatiedostoa NetMatch.dat ei l”ytynyt.
  echo Pakataanpa siis mokoma tiedosto nyt!
  "%~dp0media\CBRC.exe" "%~dp0media\template.cbrs"
)


echo Luodaan %exeName%.exe tiedosto...

:: Luodaan tmp-kansio
mkdir tmp
:: Kopioidaan CBCompiler_modded.exe t„m„n skriptin kansiosta tmp-kansioon CBCompiler.exe nimelle
copy "%~dp0CBCompiler-modded.exe" "%~dp0tmp\CBCompiler.exe"

:: Luodaan Compiler-tiedosto tmp-kansioon
echo type=2 > "%~dp0tmp\Compiler"
echo sourcedir=%~dp0cb_source\ >> "%~dp0tmp\Compiler"
echo buildto=%~dp0%exeName% >> "%~dp0tmp\Compiler"
echo force=0 >> "%~dp0tmp\Compiler"

:: Kopioidaan cb_source\NetMatch_TheEnd.cb tiedostoon tmp\Editor.out
copy "%~dp0cb_source\NetMatch_TheEnd.cb" "%~dp0tmp\Editor.out"

:: Ajetaan k„„nt„j„ ja odotetaan sen p„„ttymist„
"%~dp0tmp\CBCompiler.exe"

:: Tarkistetaan onnistuiko k„„nt„minen
IF EXIST "%~dp0tmp\CompileLog.txt" (
  set /P result=<"%~dp0tmp\CompileLog.txt"
)


IF %result%==Succeeded! (
  REM Onnistui, poistetaan turhuudet ja ajetaan luotu tiedosto
  rmdir /S /Q "%~dp0tmp"
  start /D "%~dp0" %exeName%
  goto THE_END
)

:: Jos l”yd„mme itsemme t„„lt„, on jotain mennyt pieleen.
:: Virheviesti„ kehiin ja pausella komentorivi-ikkuna pys„hdyksiin.
echo Jotain meni pieleen :(
echo Koita ajaa skripti uudelleen, poista kuitenkin tmp-kansio sit„ aikaisemmin.
echo Jos ongelma ei korjaudu, ota yhteytt„ projektin kehitt„jiin.
echo.
pause
echo.
exit /B 1


:THE_END
echo Skripti suoritettu onnistuneesti!
exit /B