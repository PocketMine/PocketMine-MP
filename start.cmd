@echo off
TITLE PocketMine-MP Server - by @shoghicp
COLOR F0
mode con: cols=90

echo.
echo             -
echo           /   \
echo        /         \
echo     /   PocketMine  \
echo  /          MP         \
echo  ^|\     @shoghicp     /^|
echo  ^|.   \           /   .^|
echo  ^| ..     \   /     .. ^|
echo  ^|    ..    ^|    ..    ^|
echo  ^|       .. ^| ..       ^|
echo  \          ^|          /
echo     \       ^|       /
echo        \    ^|    /
echo           \ ^| /
echo.
echo.
cd /d %~dp0
FOR /F "tokens=*" %%i in ('php -r "echo 1;"') do SET PHPOUTPUT=%%i
if not "%PHPOUTPUT%"=="1" (
echo [ERROR] Couldn't find PHP binary in PATH.
ping 127.0.0.1 -n 3 -w 1000>nul
) else (
START /B /WAIT php -d enable_dl=On PocketMine-MP.php
)
pause
