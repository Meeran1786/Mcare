@echo off

cd /d C:\Apache24\htdocs\mcare

:loop

C:\php\php.exe artisan schedule:run

timeout /t 60 >nul

goto loop