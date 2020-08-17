@echo off
set name=%1
cd tests
php ../vendor/codeception/codeception/codecept generate:cest api %name%
cd ..