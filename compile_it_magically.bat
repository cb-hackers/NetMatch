@echo off
title Maaginen NetMatchin kasaaja

:: ----------------------------------------------------------------------------
:: T„m„ tiedosto tekee taikoja ja tuottaa sinulle valmiin, toimivan
:: NetMatch_TheEnd.exe tiedoston. N„in saat suoraan git-reposta revityn
:: koodin k„„nnetty„.
::
:: T„m„ toimii vaikkei sinulla olisi CoolBasicia asennettuna koneellasi.
::
:: Valinnaiset parametrit:
::   -o
::      >> Luotavan exen nimi, ilman .exe p„„tett„. Oletuksena NetMatch_TheEnd
::   -s
::      >> Jos t„m„ parametri on annettu, k„ynnistet„„n luotu exe k„„nt„misen
::         valmistuttua.
::   -? || -h || -help || /?
::      >> Tulostaa k„ytt”ohjeet
:: ----------------------------------------------------------------------------

setlocal enableDelayedExpansion

:: Komentoriviasetuksien parsinta muuttujiin.
:: Credits: http://stackoverflow.com/a/8162578/1152564

:: Define the option names along with default values, using a <space>
:: delimiter between options.
::
:: Each option has the format -name:[default]
::
:: The option names are NOT case sensitive.
::
:: Options that have a default value expect the subsequent command line
:: argument to contain the value. If the option is not provided then the
:: option is set to the default. If the default contains spaces, contains
:: special characters, or starts with a colon, then it should be enclosed
:: within double quotes. The default can be undefined by specifying the
:: default as empty quotes "".
:: NOTE - defaults cannot contain * or ? with this solution.
::
:: Options that are specified without any default value are simply flags
:: that are either defined or undefined. All flags start out undefined by
:: default and become defined if the option is supplied.
::
:: The order of the definitions is not important.
::
set "options=-o:NetMatch_TheEnd -s: -?: -h: -help: --help: /?:"

:: Set the default option values
for %%O in (%options%) do for /f "tokens=1,* delims=:" %%A in ("%%O") do set "%%A=%%~B"

:loop
:: Validate and store the options, one at a time, using a loop.
::
if not "%~1"=="" (
  set "test=!options:*%~1:=! "
  if "!test!"=="!options! " (
    rem No substitution was made so this is an invalid option.
    rem Error handling goes here.
    rem I will simply echo an error message.
    echo Error: Invalid option %~1
  ) else if "!test:~0,1!"==" " (
    rem Set the flag option using the option name.
    rem The value doesn't matter, it just needs to be defined.
    set "%~1=1"
  ) else (
    rem Set the option value using the option as the name.
    rem and the next arg as the value
    set "%~1=%~2"
    shift
  )
  shift
  goto :loop
)

:: Now all supplied options are stored in variables whose names are the
:: option names. Missing options have the default value, or are undefined if
:: there is no default.
::

:: ----------------------------------------------------------------------------
:: Exen kasaamiskoodi alkaa t„„lt„
:: ----------------------------------------------------------------------------
if defined -h goto OUTPUT_HELP
if defined -help goto OUTPUT_HELP
if defined --help goto OUTPUT_HELP
if defined /? goto OUTPUT_HELP

set exeName=%-o%

:: Tarkistetaan onko tmp-niminen kansio tahi tiedosto jo olemassa
IF EXIST "%~dp0tmp\" (
  echo tmp-kansio l”ydetty, NetMatchia ei voida k„„nt„„!
  echo Poista tmp-kansio ennen t„m„n skriptin uudelleen suorittamista.
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
:: Kopioidaan CBCompiler-modded.exe t„m„n skriptin kansiosta tmp-kansioon CBCompiler.exe nimelle
copy "%~dp0CBCompiler-modded.exe" "%~dp0tmp\CBCompiler.exe" > nul

:: Luodaan Compiler-tiedosto tmp-kansioon
echo type=2 > "%~dp0tmp\Compiler"
echo sourcedir=%~dp0cb_source\ >> "%~dp0tmp\Compiler"
echo buildto=%~dp0%exeName% >> "%~dp0tmp\Compiler"
echo force=0 >> "%~dp0tmp\Compiler"

:: Kopioidaan cb_source\NetMatch_TheEnd.cb tiedostoon tmp\Editor.out
copy "%~dp0cb_source\NetMatch_TheEnd.cb" "%~dp0tmp\Editor.out" > nul

:: Ajetaan k„„nt„j„ ja odotetaan sen p„„ttymist„
"%~dp0tmp\CBCompiler.exe"

:: Tarkistetaan onnistuiko k„„nt„minen
IF EXIST "%~dp0tmp\CompileLog.txt" (
  set /P result=<"%~dp0tmp\CompileLog.txt"
)


IF %result%==Succeeded! (
  REM Onnistui, poistetaan turhuudet ja ajetaan luotu tiedosto mik„li haluttiin
  rmdir /S /Q "%~dp0tmp"
  if defined -s start /D "%~dp0" %exeName%
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

:OUTPUT_HELP
echo K„ytt”:
echo   -o
echo      ^>^> Luotavan exen nimi, ilman .exe p„„tett„. Oletuksena NetMatch_TheEnd
echo   -s
echo      ^>^> Jos t„m„ parametri on annettu, k„ynnistet„„n luotu exe k„„nt„misen
echo         valmistuttua.
echo.  /?, -h, -help tai --help
echo      ^>^> Tulostaa k„ytt”ohjeet
echo.
goto THE_END

:THE_END
exit /B