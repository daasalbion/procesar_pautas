<?php
    /**
     * Created by PhpStorm.
     * User: DAAS
     * Date: 22/09/14
     * Time: 10:18 AM
     */

    define('FFMPEG_LIBRARY', 'C:/ffmpeg/bin/ffmpeg.exe');
    define('BUSCARPAUTA_LIBRARY', 'C:/pautas2/buscarPautas/Debug/buscarPautas.exe');
    set_include_path('C:/Program Files (x86)/Zend/ZendServer/share/ZendFramework/library');
    ini_set('display_errors', true);
    error_reporting(E_STRICT);
    set_time_limit(0);

    date_default_timezone_set("America/Asuncion");

    try {

        require_once "Zend/Loader/Autoloader.php";
        $loader = Zend_Loader_Autoloader::getInstance();

        //Logger
        $logger = new Zend_Log();

        $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);

        $writer = new Zend_Log_Writer_Stream('eliminar_archivos_log_' . date('Y-m-d') .  '.log');
        $writer->setFormatter($formatter);

        $consola = new Zend_Log_Writer_Stream('php://output');
        $consola->setFormatter($formatter);

        $logger->addWriter($consola);
        $logger->addWriter($writer);

    } catch(Zend_Log_Exception $e) {
        echo $e->getMessage();
    } catch(Exception $e) {
        echo $e->getMessage();
    }

    function filtrar_carpetas( $dir, $debug=false ) {

        $lista_archivos = array();
        if( $debug ) printf( "directorio:[%s]\n", $dir );
        if( is_dir( $dir ) ) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if($file != "." && $file != "..") {
                        $info_archivo = pathinfo($dir . '/' . $file);
                        //guardo las carpetas en este array para poder recorrerlo despues
                        $lista_archivos[] = $dir . '/' . $file;

                    }
                }
                closedir($dh);
            }
        }
        if( $debug ) printf( "Archivos[%s]\n", print_r( $lista_archivos, true ) );

        return $lista_archivos;
    }

    function eliminar_archivo( $archivo ){

        global $logger;

        if( unlink( $archivo ) ){

            $logger->info( 'El archivo: ' . basename( $archivo ) . ' fue elimnado' );
        }else{

            $logger->info( 'El archivo: ' . $archivo . 'no pudo ser eliminado' );
        }

        return;
    }

    function help() {

        printf("\n\tEliminar Archivos\n");
        printf("\n\tUso: eliminar_archivos.php -p <arg2>\n");
        printf("\t-p: Path del directorio padre de los archivos a eliminar\n");
    }

    function filtrar_directorio( $dir, $extension, $debug=false ) {

        $lista_archivos = array();
        if($debug) printf("directorio:[%s] extension:[%s]\n", $dir, $extension);
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if($file != "." && $file != "..") {
                        $info_archivo = pathinfo($dir . '/' . $file);
                        //printf("info:[%s]\n", print_r($info_archivo, true));
                        if($info_archivo['extension'] == $extension) {
                            $lista_archivos[] = $dir . '/' . $file;
                        }
                    }
                }
                closedir($dh);
            }
        }
        if($debug) printf("Archivos(%s)[%s]\n", $extension, print_r($lista_archivos, true));

        return $lista_archivos;
    }

    function analizar_parametros( $argv ) {

        global $logger;

        if( $argv[1] == '-p' ) {

            $default_path = '//PAUTAS/Users/User/Desktop/desordenado/ArchivoPautas';
            $path_directorio_trabajo = isset( $argv[2] )? $argv[2]: $default_path;
            $lista_carpetas_eliminar_archivos = filtrar_carpetas( $path_directorio_trabajo );
            $logger->info( 'lista_carpetas_eliminar_archivos: ' . print_r( $lista_carpetas_eliminar_archivos, true ) );

            foreach( $lista_carpetas_eliminar_archivos as $carpeta ){

                $lista_carpetas_eliminar_archivos2 = filtrar_carpetas( $carpeta );
                $logger->info( 'lista_carpetas_eliminar_archivos2: ' . print_r( $lista_carpetas_eliminar_archivos2, true ) );

                foreach( $lista_carpetas_eliminar_archivos2 as $carpeta1 ){

                    $lista_archivos_eliminar = filtrar_directorio( $carpeta1, 'mpg', false );

                    foreach( $lista_archivos_eliminar as $archivo_eliminar ){

                        eliminar_archivo($archivo_eliminar);
                    }
                }
            }

            return;
        }

        help();
        printf("\n");
    }

    analizar_parametros( $argv );