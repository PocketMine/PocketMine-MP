@echo off
TITLE Steadfast - PocketMine-MP for Production Servers!
cd /d %~dp0
if exist bin\php\php.exe (
        if exist bin\mintty.exe (
                start "" bin\mintty.exe -o Columns=88 -o Rows=32 -o AllowBlinking=0 -o FontQuality=3 -o Font="DejaVu Sans Mono" -o FontHeight=10 -o CursorType=0 -o Cu$
        ) else (
                bin\php\php.exe -d enable_dl=On src/PocketMine-MP.php %*
        )
) else (
        if exist bin\mintty.exe (
                start "" bin\mintty.exe -o Columns=88 -o Rows=32 -o AllowBlinking=0 -o FontQuality=3 -o Font="DejaVu Sans Mono" -o FontHeight=10 -o CursorType=0 -o Cu$
        ) else (
                php -d enable_dl=On src/PocketMine-MP.php %*
        )
)
