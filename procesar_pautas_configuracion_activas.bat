@ECHO OFF
REM Script de Conversion de Grabaciones y de Busqueda de Pautas
ECHO.
ECHO Aplicacion: %0
ECHO.
REM start "%%F" /D.
php -q procesar_pautas.php --config -activas
ECHO Listo!!!
ECHO ON