@ECHO off
ECHO Hold on... Starting it up for you! :)
PING 1.1.1.1 -n 1 -w 1000 >NUL
DEL NetMatch_TheEnd.exe
RENAME NetMatch_TheEnd.exe.updated NetMatch_TheEnd.exe
START NetMatch_TheEnd.exe