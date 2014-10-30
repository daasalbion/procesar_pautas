<?php
    /**
     * Created by PhpStorm.
     * User: DAAS
     * Date: 22/09/14
     * Time: 12:43 PM
     * Version: 3.2
     */

    define('FFMPEG_LIBRARY', 'C:/ffmpeg/bin/ffmpeg.exe');
    define('BUSCARPAUTA_LIBRARY', 'C:/Users/USER/ENTERMOVIL/DAAS/PROYECTOS/PROCESAR_PAUTAS/buscarPautas/Debug/buscarPautas.exe');
    define('PROCESAR_PAUTAS', 'C:/Users/USER/ENTERMOVIL/DAAS/PROYECTOS/PROCESAR_PAUTAS/procesar_pautas.php');
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

        $writer = new Zend_Log_Writer_Stream('logs/logs_' . date('Y-m-d') .  '.log');
        $writer->setFormatter($formatter);

        $consola = new Zend_Log_Writer_Stream('php://output');
        $consola->setFormatter($formatter);

        $logger->addWriter($consola);
        $logger->addWriter($writer);

        //Base de Datos
        $config = new Zend_Config(array(
            'database' => array(
                'adapter' => 'Pdo_Pgsql',
                'params'  => array(
                    'host'     => '10.0.2.8',
                    'username' => 'konectagw',
                    'password' => 'konectagw2006',
                    'dbname'   => 'gw'
                )
            )
        ));
        $db = Zend_Db::factory($config->database);
        //$db->getConnection();


    } catch( Zend_Db_Exception $e ) {
        $logger->err($e);
    } catch( Zend_Log_Exception $e ) {
        echo $e->getMessage();
    } catch( Exception $e ) {
        echo $e->getMessage();
    }

    function obtenerDatosSpots( $fecha ){

        global $db;
        $datos = array();

        $sql = 'select * from pautas where fecha = ? order by 1';
        //$sql = "select * from pautas where alias = 'AFORTUNA1' AND fecha > '2014-09-14' and fecha < '2014-09-18'";

        $rs = $db->fetchAll( $sql, $fecha );

        $plantillasPautas = array();
        $nombre_dias = array('Sunday' => 'DOMINGO', 'Monday' => 'LUNES', 'Tuesday' => 'MARTES' ,'Wednesday'=>'MIERCOLES','Thursday'=>'JUEVES','Friday' => 'VIERNES', 'Saturday'=>'SABADO');
        $path_fijo = 'C:/Users/USER/ENTERMOVIL/DAAS/PROYECTOS/TVCHAT/PAUTAS/AFORTUNADOS1/TEMPLATES/';

        foreach ( $rs as $fila ){

            $fila = (array)$fila;
            $plantillasPautas[$fila['alias']]['PATHPLANTILLA'] = $path_fijo . $fila['alias'] .'/'.$fila['path_plantilla'];
            $plantillasPautas[$fila['alias']]['DURACION'] = $fila['duracion'];
            $plantillasPautas[$fila['alias']]['DELTAX'] = $fila['deltax'];
            $plantillasPautas[$fila['alias']]['DELTAY'] = $fila['deltay'];

            $nombre_dia = $nombre_dias[date ("l", strtotime($fila['fecha']))];

            $plantillasPautas[$fila['alias']]['FECHAS'][$fila['canal']][] = $fila['fecha'] . "_".$nombre_dia;

        }

        return $plantillasPautas;

    }

    function cargarBD( $reporte ){

        global $db;
        $nombre_dias = array('Sunday' => 'DOMINGO', 'Monday' => 'LUNES', 'Tuesday' => 'MARTES' ,'Wednesday'=>'MIERCOLES','Thursday'=>'JUEVES','Friday' => 'VIERNES', 'Saturday' => 'SABADO');

        foreach( $reporte as $alias ){
            /*ACTUALIZAR PAUTAS ENCONTRADAS*/
            $nombre_dia = $nombre_dias[date ("l", strtotime($alias['DIA']))];
            $data = array(

                'pautas_encontradas' => $alias[$alias['DIA'] . '_'.$nombre_dia]['PAUTAS_ENCONTRADAS_DIA'],

            );

            $where = array(
                'alias = ?' => $alias['NOMBRE_SERVICIO'],
                'canal = ?' => $alias['CANAL'],
                'fecha = ?' => $alias['DIA'],

            );
            $n = $db->update('pautas', $data, $where);

            /*GUARDAR EN TABLA REPORTEPAUTAS*/
            foreach( $alias[$alias['DIA'] . '_'.$nombre_dia] as $archivoavi=>$tiempos ){

                foreach( $tiempos as $indice =>$hora ){

                    $data = array(

                        'servicio' => $alias['NOMBRE_SERVICIO'],
                        'canal_pautas' => $alias['CANAL'],
                        'fecha_hora' => $alias['DIA']." ".$hora ,
                        'fecha_pautas' => $alias['DIA'],
                    );
                    echo $hora;
                    $n = $db->insert('reportepautas', $data);
                }
            }
        }
        printf("\nCARGUE CORRECTAMENTE LA BD\n");
        return;
    }

    function help() {
        printf("\n\tVERSION DAAS FINAL\n");
        printf("\nUso: procesar_pautas.php -p <arg0> -t <arg1>\n");
        printf("\t-p: Path de la Imagen Plantilla\n");
        printf("\t-t: Duracion de la Pauta en Segundos. La Pauta deber durar al menos 10 segundos.\n");
    }

    function write_ini_file( $assoc_arr, $path, $has_sections=FALSE ) {
        $content = "";
        if ($has_sections) {
            foreach ($assoc_arr as $key=>$elem) {
                $content .= "[".$key."]\n";
                foreach ($elem as $key2=>$elem2) {
                    if(is_array($elem2))
                    {
                        for($i=0;$i<count($elem2);$i++)
                        {
                            $content .= $key2."[] = \"".$elem2[$i]."\"\n";
                        }
                    } else if($elem2=="") {
                        $content .= $key2." = \n";
                    } else {
                        $content .= $key2." = \"".$elem2."\"\n";
                    }
                }
            }

        } else {
            foreach ($assoc_arr as $key=>$elem) {
                if(is_array($elem))
                {
                    for($i=0;$i<count($elem);$i++)
                    {
                        $content .= $key."[] = \"".$elem[$i]."\"\n";
                    }
                }
                else if($elem=="") {
                    $content .= $key." = \n";
                } else {
                    $content .= $key." = \"".$elem."\"\n";
                }
            }
        }

        if (!$handle = fopen($path, 'w')) {
            return false;
        }
        if (!fwrite($handle, $content)) {
            return false;
        }
        fclose($handle);
        return true;
    }

    function fechaAyer( $formato='ANHO_MES_DIA' ) {

        $hoy = array(
            'anho' => date('Y'),
            'mes' => date('m'),
            'dia' => date('d')
        );
        $time_ayer = mktime(0, 0, 0, (int)$hoy['mes'], ((int)$hoy['dia'] - 1), $hoy['anho']);

        $ayer = array(
            'anho' => date('Y', $time_ayer),
            'mes' => date('m', $time_ayer),
            'dia' => date('d', $time_ayer)
        );

        if($formato == 'ANHO_MES_DIA') {
            return $ayer['anho'] . '-' . $ayer['mes'] . '-' . $ayer['dia'];
        }

        return $ayer;
    }

    function segundos2hms( $segundos ) {

        $h = (int) ($segundos / 3600);
        ($h>= 24)? $h = 0: $h;
        $m = (int) (($segundos % 3600) / 60);
        $s = (int) (($segundos % 3600) % 60);

        return sprintf("%02d:%02d:%02d", $h, $m, $s);
    }

    function hms2segundos( $hms ) {

        $partes = explode(":", $hms);
        $h = (int)$partes[0];
        $m = (int)$partes[1];
        $s = (int)$partes[2];

        return (int)($h*3600 + $m*60 + $s);
    }

    function rename_win( $oldfile, $newfile ){
        if(!rename($oldfile,$newfile)){
            if(copy($oldfile,$newfile)){
                unlink($oldfile);
                return TRUE;
            }
            return FALSE;
        }
        return TRUE;
    }
    /*funcion que ejecuta las lineas de comando*/
    function ejecutar_comando( $comando ) {
        //se aguarda el comando en un buffer interno
        ob_start();
        //comando para llamar a una funcion externa, en este caso la linea de comandos
        passthru($comando);
        //se asigna a $resultado lo guardado en el buffer interno
        $resultado = ob_get_contents();
        //eliminar el buffer interno
        ob_end_clean();

        return $resultado;
    }

    function ejecutar_configuracion( $accion, $configuracion ){

        global $logger;

        if( $accion == 'CONTROL_GRABACION' ){

            $plantilla_comando = 'php -q {PROCESAR_PAUTAS} --config -grabacion -canal {CANAL} -hora_inicio {HORA_INICIO} -fecha {FECHA} -duracion {DURACION} -alias {ALIAS} -hora_fin {HORA_FIN} 2>> logs/ejecutar_comando.txt';

            $traduccion = array(
                '{PROCESAR_PAUTAS}' => PROCESAR_PAUTAS,
                '{CANAL}' => $configuracion['canal'],
                '{HORA_INICIO}' => $configuracion['hora_inicio'],
                '{FECHA}' => $configuracion['fecha'],
                '{DURACION}' => $configuracion['duracion'],
                '{ALIAS}' => $configuracion['alias'],
                '{HORA_FIN}' => $configuracion['hora_fin']
            );

        }else if( $accion == 'CONTROL_CONVERSION' ){

            $plantilla_comando = 'php -q {PROCESAR_PAUTAS} --config -conversorpauta -canal {CANAL} -fecha {FECHA} -alias {ALIAS} -duracion {DURACION} -hora_fin {HORA_FIN} 2>> logs/ejecutar_comando2.txt';

            $traduccion = array(

                '{PROCESAR_PAUTAS}' => PROCESAR_PAUTAS,
                '{CANAL}' => $configuracion['canal'],
                '{FECHA}' => $configuracion['fecha'],
                '{ALIAS}' => $configuracion['alias'],
                '{DURACION}' => $configuracion['duracion'],
                '{HORA_FIN}' => $configuracion['hora_fin'],
            );

        }

        $logger->info( "accion solicitada: $accion" );
        $comando_ejecutar = strtr( $plantilla_comando, $traduccion );
        $logger->info( "ejecutar_configuracion: $comando_ejecutar" );

        $resultado = exec_bg( $comando_ejecutar );

        return $resultado;

    }

    function ejecutar_configuracion_conversor( $configuracion ){

        /*$argv[1] == '--config' && $argv[2] == '-conversorpauta' && $argv[3]== '-canal' && $argv[5] == '-fecha' && $argv[7] == '-alias'*/
        global $logger;

        $plantilla_comando_buscar_pautas = 'php -q {PROCESAR_PAUTAS} --config -conversorpauta -canal {CANAL} -fecha {FECHA} -alias {ALIAS} -duracion {DURACION} 2>> logs/ejecutar_comando2.txt';

        $traduccion = array(

            '{PROCESAR_PAUTAS}' => PROCESAR_PAUTAS,
            '{CANAL}' => $configuracion['canal'],
            '{FECHA}' => $configuracion['fecha'],
            '{ALIAS}' => $configuracion['alias'],
            '{DURACION}' => $configuracion['duracion'],
        );

        $comando_buscar_pautas = strtr($plantilla_comando_buscar_pautas, $traduccion);
        $logger->info( 'ejecutar_conversorpauta: ' . $comando_buscar_pautas );
        $resultado = exec_bg($comando_buscar_pautas);

        return $resultado;

    }
    //para ejecutar un comando y correrlo en background
    function exec_bg($cmd) {

        global $logger;

        if (substr(php_uname(), 0, 7) == "Windows"){

            pclose(popen("start /B ". $cmd, "r"));
            $logger->info("start /B " . $cmd);
        }else {

            exec($cmd . " > /dev/null &");
        }
    }

    function filtrar_directorio($dir, $extension, $debug=false) {

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

    function filtrar_carpetas( $dir, $debug=false ) {

        $lista_archivos = array();
        if($debug) printf("directorio:[%s]\n", $dir);
        if (is_dir($dir)) {
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
        if($debug) printf("Archivos[%s]\n", print_r($lista_archivos, true));

        return $lista_archivos;
    }

    function duracion_video( $path_archivo, &$datos_resumen ) {

        $plantilla_comando_duracion = '{PATH_FFMPEG} -i {PATH_ARCHIVO} 2>&1';
        $traduccion = array(
            '{PATH_FFMPEG}' => FFMPEG_LIBRARY,
            '{PATH_ARCHIVO}' => $path_archivo
        );
        $comando_duracion = strtr($plantilla_comando_duracion, $traduccion);

        $resultado = ejecutar_comando($comando_duracion);

        $datos_duracion = array(
            'duracion_segundos' => 0,
            'duracion_hms' => 0
        );
        if(!empty($resultado)) {

            preg_match('/Duration: (.*?),/', $resultado, $matches);
            $duracion_hms = substr($matches[1], 0, 8);
            printf("archivo:[%s]\n", $path_archivo);
            printf("duracion_hms:[%s]\n", $duracion_hms);
            $duracion_segundos = hms2segundos($duracion_hms);
            printf("duracion_segundos:[%d]\n", $duracion_segundos);

            $datos_duracion['duracion_segundos'] = $duracion_segundos;
            $datos_duracion['duracion_hms'] = $duracion_hms;

            $datos_resumen[basename($path_archivo)]['duracion_segundos'] = $duracion_segundos;
            $datos_resumen[basename($path_archivo)]['duracion_hms'] = $duracion_hms;
        }

        return $datos_duracion;
    }
    /*modifique duracion video para mi caso particular solo requiero de la cantidad total de segundos del video*/
    function duracion_video_new( $path_archivo ) {

        $plantilla_comando_duracion = '{PATH_FFMPEG} -i {PATH_ARCHIVO} 2>&1';
        $traduccion = array(
            '{PATH_FFMPEG}' => FFMPEG_LIBRARY,
            '{PATH_ARCHIVO}' => $path_archivo
        );
        $comando_duracion = strtr($plantilla_comando_duracion, $traduccion);

        $resultado = ejecutar_comando($comando_duracion);

        $datos_duracion = array(
            'duracion_segundos' => 0,
            'duracion_hms' => 0
        );
        if(!empty($resultado)) {

            preg_match('/Duration: (.*?),/', $resultado, $matches);
            $duracion_hms = substr($matches[1], 0, 8);
            //printf("archivo:[%s]\n", $path_archivo);
            //printf("duracion_hms:[%s]\n", $duracion_hms);
            $duracion_segundos = hms2segundos($duracion_hms);
            printf("duracion_segundos:[%d]\n", $duracion_segundos);

            $datos_duracion['duracion_segundos'] = $duracion_segundos;
            $datos_duracion['duracion_hms'] = $duracion_hms;

            /*$datos_resumen[basename($path_archivo)]['duracion_segundos'] = $duracion_segundos;
            $datos_resumen[basename($path_archivo)]['duracion_hms'] = $duracion_hms;*/
        }

        return $duracion_segundos;
    }

    function convertir_a_AVI( $path_archivo, $path_carpeta_trabajo, &$datos_resumen ) {

        $plantilla_comando_convertir = '{PATH_FFMPEG} -i {PATH_ARCHIVO} -an -vcodec mpeg4 -vtag xvid -s 720x480 -b:v 400k {PATH_ARCHIVO_DESTINO} 2>>{PATH_LOG_CONVERSION}';//2>&1
        printf("Convirtiendo archivo:[%s]\n", basename($path_archivo));
        $path_archivo_destino = dirname($path_archivo) . '/' . basename($path_archivo) . '.avi';

        $traduccion = array(
            '{PATH_FFMPEG}' => FFMPEG_LIBRARY,
            '{PATH_ARCHIVO}' => $path_archivo,
            '{PATH_ARCHIVO_DESTINO}' => $path_archivo_destino,
            '{PATH_LOG_CONVERSION}' => basename($path_archivo) . '__conversion.log'
        );
        $comando_convertir = strtr($plantilla_comando_convertir, $traduccion);
        printf("comando_convertir:[%s]\n", $comando_convertir);

        $resultado_convertir = ejecutar_comando($comando_convertir);

        $datos_resumen[basename($path_archivo_destino)] = array(
            'tipo_archivo' => 'ORIGINAL_CONVERTIDO_A_AVI',
            'duracion_segundos' => 0,
            'duracion_hms' => '00:00:00',
            'HORA_MINUTO_REAL' => 'COMPLETAR AQUI HORA:MINUTO REAL'
        );

        //duracion_video($path_archivo_destino, $datos_resumen);

        return $path_archivo_destino;

    }
    /*funcion modificada para que lea un directorio especifico de archivos a convertir*/
    function convertir_a_AVI2( $path_archivo, $path_carpeta_trabajo, &$datos_resumen ) {

        $plantilla_comando_convertir = '{PATH_FFMPEG} -i {PATH_ARCHIVO} -an -vcodec mpeg4 -vtag xvid -s 720x480 -b:v 400k {PATH_ARCHIVO_DESTINO} 2>>{PATH_LOG_CONVERSION}';//2>&1
        printf("Convirtiendo archivo:[%s]\n", basename($path_archivo));
        $path_archivo_destino = $path_carpeta_trabajo . '/' . basename($path_archivo) . '.avi';

        $traduccion = array(
            '{PATH_FFMPEG}' => FFMPEG_LIBRARY,
            '{PATH_ARCHIVO}' => $path_archivo,
            '{PATH_ARCHIVO_DESTINO}' => $path_archivo_destino,
            '{PATH_LOG_CONVERSION}' => 'logs/' . basename($path_archivo) . '__conversion.log'
        );
        $comando_convertir = strtr($plantilla_comando_convertir, $traduccion);
        printf("comando_convertir:[%s]\n", $comando_convertir);

        $resultado_convertir = ejecutar_comando($comando_convertir);

        $datos_resumen[basename($path_archivo_destino)] = array(
            'tipo_archivo' => 'ORIGINAL_CONVERTIDO_A_AVI',
            'duracion_segundos' => 0,
            'duracion_hms' => '00:00:00',
            'HORA_MINUTO_REAL' => 'COMPLETAR AQUI HORA:MINUTO REAL'
        );

        //duracion_video($path_archivo_destino, $datos_resumen);

        return $path_archivo_destino;

    }
    /*funcion modificada para que pueda convertir archivos .mov y cree carpetas con sus respectivos nombres*/
    function convertir_de_MOV( $path_archivo, $path_carpeta_trabajo, &$datos_resumen ) {

        $plantilla_comando_convertir = '{PATH_FFMPEG} -i {PATH_ARCHIVO} -an -vcodec mpeg4 -vtag xvid -s 720x480 -b:v 400k {PATH_ARCHIVO_DESTINO} 2>>{PATH_LOG_CONVERSION}';//2>&1
        $carpeta_spot = $path_carpeta_trabajo . '/'. basename($path_archivo);
        mkdir($carpeta_spot);
        printf("Convirtiendo archivo:[%s]\n", basename($path_archivo));
        $path_archivo_destino = $carpeta_spot . '/' . basename($path_archivo) . '.avi';

        $traduccion = array(
            '{PATH_FFMPEG}' => FFMPEG_LIBRARY,
            '{PATH_ARCHIVO}' => $path_archivo,
            '{PATH_ARCHIVO_DESTINO}' => $path_archivo_destino,
            '{PATH_LOG_CONVERSION}' => basename($path_archivo) . '__conversion.log'
        );
        $comando_convertir = strtr($plantilla_comando_convertir, $traduccion);
        printf("comando_convertir:[%s]\n", $comando_convertir);

        $resultado_convertir = ejecutar_comando($comando_convertir);

        $datos_resumen[basename($path_archivo_destino)] = array(
            'tipo_archivo' => 'ORIGINAL_CONVERTIDO_A_AVI',
            'duracion_segundos' => 0,
            'duracion_hms' => '00:00:00',
            'HORA_MINUTO_REAL' => 'COMPLETAR AQUI HORA:MINUTO REAL'
        );

        //duracion_video($path_archivo_destino, $datos_resumen);

        return $path_archivo_destino;

    }
    /*funcion que crea una secuencia de imagenes pasando el directorio del archivo avi a convertir a imagenes*/
    function crear_imagenes_pautas( $path_archivo, $path_carpeta_trabajo, $duracion_pautas ) {

        for($i = 0.0; $i <= $duracion_pautas; $i = $i + 0.5){
            $plantilla_comando_convertir = '{PATH_FFMPEG} -itsoffset -'.$i.' -i {PATH_ARCHIVO} -vcodec mjpeg -vframes 1 -an -f rawvideo -s 720x480 {PATH_ARCHIVO_DESTINO} 2>>{PATH_LOG_CONVERSION}';//2>&1
            //ffmpeg -itsoffset -0.0 -i 6767_INGLES.mpg -vcodec mjpeg -vframes 1 -an -f rawvideo -s 720x480 salida_0-0.jpg

            printf("Convirtiendo archivo:[%s]\n", basename($path_archivo));
            $path_archivo_destino = $path_carpeta_trabajo . '/' . 'salida_' . $i . '.jpg';

            $traduccion = array(
                '{PATH_FFMPEG}' => FFMPEG_LIBRARY,
                '{PATH_ARCHIVO}' => $path_archivo,
                '{PATH_ARCHIVO_DESTINO}' => $path_archivo_destino,
                '{PATH_LOG_CONVERSION}' => basename($path_archivo) . '__conversion.log'
            );
            $comando_convertir = strtr($plantilla_comando_convertir, $traduccion);
            printf("comando_convertir:[%s]\n", $comando_convertir);
            $resultado_convertir = ejecutar_comando($comando_convertir);
        }

        //duracion_video($path_archivo_destino, $datos_resumen);
        return;

    }
    /*funcion especial para verificar si un video mpg ya tiene su correspondiente avi convertido en el directorio*/
    function filtrar_directorio_sin_avis( $dir, $extension, $debug=false ) {

        $lista_archivos = array();

        $lista_archivos_mpg = filtrar_directorio($dir,$extension,true);
        $lista_archivos_avi = filtrar_directorio($dir,'avi',true);

        foreach($lista_archivos_mpg as $archivompg){
            $hash = $archivompg . '.avi';
            if(!in_array($hash,$lista_archivos_avi)){
                $lista_archivos[] = $archivompg;
            }
            else{

                echo "\n" . "EL ARCHIVO '$archivompg' YA SE ENCUENTRA CONVERTIDO A AVI" . "\n";
            }
        }
        if($debug) printf("Archivos A CONVERTIR(%s)[%s]\n", $extension, print_r($lista_archivos, true));
        return $lista_archivos;
    }
    /*modificado por DAAS para que envie la duracion del video como parametro y el ROI*/
    function procesar_pautas( $path_template, $duracion_pauta, $path_carpeta_trabajo, $dx, $dy, $flagAnimacion ) {

        $lista_archivos_a_procesar = filtrar_directorio($path_carpeta_trabajo, 'avi');
        print_r($lista_archivos_a_procesar);

        foreach($lista_archivos_a_procesar as $path_archivo_avi) {

            printf("\nBuscando pautas en [%s]\n", $path_archivo_avi);
            $duracion_video_avi = duracion_video_new($path_archivo_avi);

            if( $duracion_video_avi > 0 ){

                $plantilla_comando_buscar_pautas = '{BUSCARPAUTA_LIBRARY} -s {DURACION_VIDEO} -t {DURACION_PAUTA} -p {PATH_IMAGEN_PLANTILLA} -D {DIRECTORIO_TRABAJO} -v {NOMBRE_ARCHIVO_VIDEO} -dx '. $dx .' -dy ' .$dy . ' -a '. $flagAnimacion .'>> logs/log_buscar_pautas.txt';

                $traduccion = array(
                    '{DURACION_VIDEO}' => $duracion_video_avi,
                    '{BUSCARPAUTA_LIBRARY}' => BUSCARPAUTA_LIBRARY,
                    '{DURACION_PAUTA}' => $duracion_pauta,
                    '{PATH_IMAGEN_PLANTILLA}' => $path_template,
                    '{DIRECTORIO_TRABAJO}' => dirname($path_archivo_avi) . "/",
                    '{NOMBRE_ARCHIVO_VIDEO}' => basename($path_archivo_avi)
                );

                $comando_buscar_pautas = strtr($plantilla_comando_buscar_pautas, $traduccion);
                printf("comando_buscar_pautas:[%s]\n", $comando_buscar_pautas);
                $resultado_buscar = ejecutar_comando($comando_buscar_pautas);
            }else{

                printf("\nDuracion igual a 0\n");
            }
        }
    }

    function eliminar_espacios( $path ){

        $canales = filtrar_carpetas( $path,true );
        foreach( $canales as $canal ){

            if( is_dir( $canal ) ){

                printf( "CANAL:[%s]\n", $canal );
                $archivos_videos_canal = filtrar_directorio($canal,'mpg',true);

                if( !empty( $archivos_videos_canal ) ){

                    foreach( $archivos_videos_canal as $video ){

                        printf( "ARCHIVO A RENOMBRAR:[%s]\n", $video );
                        $temporal = $video;
                        $quitar_espacio = str_replace( ' ', '_', $temporal );
                        rename( $video, $quitar_espacio );
                        printf( "NUEVO NOMBRE:[%s]\n", $quitar_espacio );

                    }
                }
            }
        }

    }

    function cargar_datos( $path_de_trabajo ){

        $reporte_mpg = array(

            //'NOMBRE_ARCHIVO_MPEG' =>'HORA_REAL_INICIO',
        );
        //EL MAS IMPORTANTE
        $reporte_avi = array(

            //'NOMBRE_ARCHIVO_AVI' => 'HORA_INTERNA',
            /*'PAUTAS_ENCONTRADAS' => array(
                    'HORA_INTERNA' => null,
            )*/
        );

        $archivos_mpegs = filtrar_directorio( $path_de_trabajo,'mpg',true );
        $archivos_avis_jpgs = filtrar_directorio( $path_de_trabajo,'jpg',true );

        foreach( $archivos_mpegs as $nombre_archivo_mpg )
        {
            //cargando en reporte mpg y tiniendo como clave al dia y valor al horario real de inicio del archivo
            $day = basename( $nombre_archivo_mpg );
            $reporte_mpg[ $day ] = date ( "H:i:s", filemtime( $nombre_archivo_mpg ) );

            //cargando en reporte avi
            $nombre_avi = basename( $nombre_archivo_mpg ) . '.avi';
            //$reporte_avi[ $nombre_avi ] = date ("H:i:s", filemtime($nombre_archivo_mpg));
            //CONVERTIR HORAS A SEGUNDOS
            $conversion_hora_segundos = hms2segundos(date ("H:i:s", filemtime($nombre_archivo_mpg)));

            foreach( $archivos_avis_jpgs as $jpgs ){

                if( strpos( $jpgs, basename($nombre_archivo_mpg) . '.avi' ) == true ){

                    $cadena_tiempo = null;
                    //obtengo la cantida total de segundos
                    $tiempo_interno = strpos( $jpgs,'seg' );
                    $cadena_tiempo = substr( $jpgs, $tiempo_interno + 4, 4 );
                    //CONVIERTO A HORAS MINUTOS Y SEGUNDOS
                    $reporte_avi[ $nombre_avi ] [] = segundos2hms((int)$cadena_tiempo + (int)$conversion_hora_segundos);

                }
                else{

                    echo  "\nNO SE ENCUENTRA EL ARCHIVO\n";
                }
            }

        }
        if( !empty( $reporte_mpg )&& !empty( $reporte_avi ) ){
            print_r( $reporte_mpg );
            print_r( $reporte_avi );
        }
        return $reporte_avi;
    }

    function analizar_parametros( $argv ) {

        global $logger;
        //sirve para obtener la duracion de un video -d acompañado de $argv[2] = path completo del archivo de video
        if($argv[1] == '-d'){
            $path_carpeta_trabajo = $argv[2];
            $duracion_video = duracion_video_new($path_carpeta_trabajo);
            echo "DURACION DE VIDEO - >" . $duracion_video . "\n";
            return;
        }
        //-p archivo_template -t duracion_pauta en segundos -dx roi en x -dy roi en y -a argumento opcional por si la pauta tiene animacion
        if($argv[1] == '-p' && $argv[3] == '-t' && $argv[5] == '-dx' && $argv[7] == '-dy' || $argv[9] == '-a') {

            $algo = 0;
            $path_template = $argv[2];
            $duracion_pauta = $argv[4];
            $dx  = $argv[6];
            $dy = $argv[8];
            $path_archivo_origen = $argv[10];
            $lista_carpetas_a_procesar = filtrar_carpetas($path_archivo_origen,true);

            foreach($lista_carpetas_a_procesar as $path_archivo_origen){

                procesar_pautas($path_template, $duracion_pauta, $path_archivo_origen,$dx,$dy,$algo);
            }

            return;
        }
        //MODIFICADO PARA QUE HAGA POR CARPETAS
        if($argv[1] == '-p' && $argv[3] == '-t' && $argv[5] == '-dx' && $argv[7] == '-dy' || $argv[9] == '-c') {

            $algo = 0;
            $path_template = $argv[2];
            $duracion_pauta = $argv[4];
            $dx  = $argv[6];
            $dy = $argv[8];
            $path_archivo_origen = $argv[10];

            procesar_pautas($path_template, $duracion_pauta, $path_archivo_origen,$dx,$dy,$algo);

            return;
        }
        //agregado mucho despues. Convierte los archivos de un directorio especifico que se le pasa con -p
        if($argv[1] == '--convertir' && $argv[2] == '-p') {

            $path_archivo_origen = $argv[3];
            $lista_archivos_a_procesar = filtrar_directorio($path_archivo_origen, 'mpg', true);

            //$fecha_ayer = fechaAyer('ANHO_MES_DIA');
            $path_carpeta_trabajo = $path_archivo_origen; //. '/' . $fecha_ayer;
            printf("PathCarpetaORIGEN:[%s]\n", $path_archivo_origen);
            printf("PathCarpetaTrabajo:[%s]\n", $path_carpeta_trabajo);

            $path_archivo_resumen = $path_carpeta_trabajo . '/resumen.ini';
            $datos_resumen = array();

            //============ CONVERTIR ARCHIVOS ===========================
            foreach($lista_archivos_a_procesar as $path_archivo_a_procesar) {

                printf("\n====================================================\n");
                printf("Procesando: [%s]\n", basename($path_archivo_a_procesar));
                printf("====================================================\n\n");
                $path_archivo_convertido = convertir_a_AVI2($path_archivo_a_procesar, $path_carpeta_trabajo, $datos_resumen);

            }

            print_r($datos_resumen);

            write_ini_file($datos_resumen, $path_archivo_resumen, true);

            return;
        }
        //para listar los archivos con la extension ingresada
        if($argv[1] == '-ls') {

            printf("Carpeta:[%s]\n", basename(getcwd()));
            $archivos_buscados = filtrar_directorio(getcwd(), $argv[2]);
            print_r($archivos_buscados);
            return;
        }
        //Ingresar el tiempo y lo convierte a hms
        if($argv[1] == '-hms') {
            printf("hms:[%s] -> segundos:[%d] -> hms:[%s]\n", $argv[2], hms2segundos($argv[2]), segundos2hms(hms2segundos($argv[2])));
            return;
        }
        //Ingresa un tiempo h:m:s y lo expresa en segundos
        if($argv[1] == '-seg') {
            printf("segundos:[%d] -> hms:[%s] -> segundos:[%d]\n", $argv[2], segundos2hms($argv[2]), hms2segundos(segundos2hms($argv[2])));
            return;
        }
        //convertir archivos avis a secuencia de imagenes con -p se pasa directorio del video y -t la duracion del video
        if($argv[1] == '-p' && $argv[3] == '-t') {
            //procesar_archivo($argv[2]);
            $path_archivo_origen = $argv[2];
            $duracion_pauta = $argv[4];
            $path_carpeta_trabajo = $path_archivo_origen;
            $path_archivo_a_procesar = filtrar_directorio($path_archivo_origen, 'avi',true);

            foreach($path_archivo_a_procesar as $path_archivo_origen){

                $path_archivo_convertido = crear_imagenes_pautas($path_archivo_origen, $path_carpeta_trabajo,$duracion_pauta);
            }
            return;
        }
        //convertirtodo recibe con -p donde estan los archivos almacenados en carpetas
        if($argv[1] == '--convertirtodo' && $argv[2] == '-p') {
            $path_archivo_origen = $argv[3];

            $lista_carpetas_a_procesar = filtrar_carpetas($path_archivo_origen,true);

            foreach($lista_carpetas_a_procesar as $path_archivo_origen){
                $lista_archivos_a_procesar = filtrar_directorio_sin_avis($path_archivo_origen, 'mpg', true);
                $path_carpeta_trabajo = $path_archivo_origen;
                printf("PathCarpetaORIGEN:[%s]\n", $path_archivo_origen);
                printf("PathCarpetaTrabajo:[%s]\n", $path_carpeta_trabajo);
                $path_archivo_resumen = $path_carpeta_trabajo . '/resumen.ini';
                $datos_resumen = array();

                //============ CONVERTIR ARCHIVOS ===========================
                foreach($lista_archivos_a_procesar as $path_archivo_a_procesar) {

                    printf("\n====================================================\n");
                    printf("Procesando: [%s]\n", basename($path_archivo_a_procesar));
                    printf("====================================================\n\n");
                    $path_archivo_convertido = convertir_a_AVI2($path_archivo_a_procesar, $path_carpeta_trabajo, $datos_resumen);
                    //dividir_archivo($path_archivo_convertido, $path_carpeta_trabajo, $datos_resumen);
                }
                print_r($datos_resumen);

                write_ini_file($datos_resumen, $path_archivo_resumen, true);
            }

            return;
        }
        //convertirtodo recibe con -p donde estan los archivos almacenados en carpetas
        if($argv[1] == '--convertirpath' && $argv[2] == '-p') {

            $path_archivo_origen = $argv[3];
            $lista_archivos_a_procesar = filtrar_directorio_sin_avis($path_archivo_origen, 'mpg', true);
            $path_carpeta_trabajo = $path_archivo_origen;
            printf("PathCarpetaORIGEN:[%s]\n", $path_archivo_origen);
            printf("PathCarpetaTrabajo:[%s]\n", $path_carpeta_trabajo);
            $path_archivo_resumen = $path_carpeta_trabajo . '/resumen.ini';
            $datos_resumen = array();

            //============ CONVERTIR ARCHIVOS ===========================
            foreach($lista_archivos_a_procesar as $path_archivo_a_procesar) {

                printf("\n====================================================\n");
                printf("Procesando: [%s]\n", basename($path_archivo_a_procesar));
                printf("====================================================\n\n");
                $path_archivo_convertido = convertir_a_AVI2($path_archivo_a_procesar, $path_carpeta_trabajo, $datos_resumen);
                //dividir_archivo($path_archivo_convertido, $path_carpeta_trabajo, $datos_resumen);
            }
            print_r($datos_resumen);

            write_ini_file($datos_resumen, $path_archivo_resumen, true);

            return;
        }
        //convertir los spots publicitarios a archivos de video .avi
        if($argv[1] == '--convertirspots' && $argv[2] == '-p') {
            $path_archivo_origen = $argv[3];
            $path_carpeta_trabajo = $argv[3];

            $lista_archivos_a_procesar = filtrar_directorio($path_archivo_origen,'mov',true);

            printf("PathCarpetaORIGEN:[%s]\n", $path_archivo_origen);
            printf("PathCarpetaDESTINO:[%s]\n", $path_carpeta_trabajo);
            $path_archivo_resumen = $path_carpeta_trabajo . '/resumen.ini';
            $datos_resumen = array();

            //============ CONVERTIR ARCHIVOS ===========================
            foreach($lista_archivos_a_procesar as $path_archivo_a_procesar) {

                printf("\n====================================================\n");
                printf("Procesando: [%s]\n", basename($path_archivo_a_procesar));
                printf("====================================================\n\n");
                $path_archivo_convertido = convertir_de_MOV($path_archivo_a_procesar, $path_carpeta_trabajo, $datos_resumen);
                //dividir_archivo($path_archivo_convertido, $path_carpeta_trabajo, $datos_resumen);
            }
            print_r($datos_resumen);

            write_ini_file($datos_resumen, $path_archivo_resumen, true);

            return;
        }
        //LO NUEVO
        if( $argv[1] == '--reporte'){

            $logger->info( "argumentos -> " . print_r( $argv, true ) );
            $path_carpeta_trabajo = 'C:\Users\USER\ENTERMOVIL\DAAS\PROYECTOS\TVCHAT\PAUTAS\AFORTUNADOS1\PAUTAS';//FIJO
            $fechaEnviar = isset( $argv[2] )? $argv[3]: date('Y-m-d', strtotime('-1 day'));
            $logger->info( "fecha a procesar: $fechaEnviar" );
            $plantillasPautas = obtenerDatosSpots( $fechaEnviar );
            $logger->info( "plantilla pautas: " . print_r( $plantillasPautas, true ) );
            //los archivos que vamos a procesar
            $carpetasCanal = array();
            $reporte = array();
            //ACA EMPIEZA EL ALGORITMO
            //tengo que buscar la pauta solo en los dias en el que aparece esa pauta
            foreach( $plantillasPautas as $nombrePauta=> $pauta ){

                if( !empty( $pauta ) ){

                    $reporte[$nombrePauta]['NOMBRE_SERVICIO'] = $nombrePauta;//MODIFICADO
                    //asumiendo que en el directorio se encuentran las carpetas
                    $carpetasCanal = filtrar_carpetas($path_carpeta_trabajo,true);

                    foreach( $pauta['FECHAS'] as $canal=> $dias ){
                        //$canal posee telefuturo dias posee los dias
                        $reporte[$nombrePauta]['CANAL'] = $canal;//MODIFICADO

                        if( !empty( $dias ) ){

                            foreach( $carpetasCanal as $canaldeCarpeta ){
                                //VERIFICO QUE SE BUSQUE EN EL CANAL CORRECTO
                                if( strpos( $canaldeCarpeta, $canal ) == true ){

                                    echo "\nLA CARPETA ES:". basename($canaldeCarpeta) . "\n";
                                    foreach( $dias as $days ){
                                        //PROCESO LAS PAUTAS CON LOS DIAS REQUERIDOS
                                        if( is_dir( $canaldeCarpeta .'/'.$days ) ){

                                            procesar_pautas($pauta['PATHPLANTILLA'], $pauta['DURACION'], $canaldeCarpeta .'/'. $days, $pauta['DELTAX'], $pauta['DELTAY'], 2);

                                            $reporte_del_dia = cargar_datos( $canaldeCarpeta .'/'.$days );
                                            $reporte[$nombrePauta][ 'DIA' ] = substr($days, 0, 10); //MODIFICADO
                                            $reporte[$nombrePauta][ $days ] = $reporte_del_dia;//MODIFICADO
                                            $reporte[$nombrePauta][ $days ]['PAUTAS_ENCONTRADAS_DIA'] = 0;//MODIFICADO

                                        }
                                        else{
                                            echo "\nLA CARPETA: ". basename($canaldeCarpeta .'/'.$days) . " NO EXISTE O NO CORRESPONDE\n";
                                        }
                                    }
                                }
                                else{

                                    echo "\nNO SE ENCUENTRA EL ELEMENTO " . $canal . " EN ESTA CARPETA\n";
                                    echo "\nLA CARPETA ES:". basename($canaldeCarpeta) . "\n";
                                }
                            }
                        }
                        else{

                            echo "\nNO SE ENCUENTRA NINGUN ELEMENTO\n";
                        }

                    }

                }
                else{
                    echo "\nLa plantilla no tiene ningun dato\n";
                }

            }
            $nombre_dias = array('Sunday' => 'DOMINGO', 'Monday' => 'LUNES', 'Tuesday' => 'MARTES' ,'Wednesday'=>'MIERCOLES','Thursday'=>'JUEVES','Friday' => 'VIERNES', 'Saturday' => 'SABADO');
            //CONTAR EL NUMERO TOTAL DE PAUTAS Y POR DIA
            foreach( $reporte as $alias ){ //MODIFICADO

                $nombre_dia = $nombre_dias[date ("l", strtotime($alias['DIA']))];

                foreach( $alias[$alias['DIA'] . '_'.$nombre_dia] as $archivo_avi => $pautas ){

                    if( !empty( $pautas ) ){

                        //echo "MIRAR\n";
                        //print_r( $pautas );
                        //echo "MIRAR CARAJO TIENE QUE SUMAR".$reporte[$alias][ $alias['DIA'] . '_'.$nombre_dia ]['PAUTAS_ENCONTRADAS_DIA']."\n";
                        //echo "Elementos = ". count($pautas) . "\n";
                        //echo "En alias =". $alias['NOMBRE_SERVICIO']. " dia = ".$alias['DIA'] . '_'.$nombre_dia ." valor al inicio = ". $alias[ $alias['DIA'] . '_'.$nombre_dia ]['PAUTAS_ENCONTRADAS_DIA'] . "\n";
                        $reporte[$alias['NOMBRE_SERVICIO']][ $alias['DIA'] . '_'.$nombre_dia ]['PAUTAS_ENCONTRADAS_DIA'] += count($pautas);
                        //echo "cuenta = ". $reporte[$alias['NOMBRE_SERVICIO']][ $alias['DIA'] . '_'.$nombre_dia ]['PAUTAS_ENCONTRADAS_DIA'] . "\n";

                    }

                }
            }
            print_r($reporte);
            printf("La fecha es -->(%s)\n",$fechaEnviar);
            cargarBD( $reporte );
            echo "\nSe termino Correctamente\n";
            return;

        }
        //genera la secuencia de procesamiento de acuerdo al dia actual o al dia pasado como parametro
        if($argv[1] == '--config' && $argv[2] == '-dia' ){
            //configuracion de log personalizado
            $logger_dia = new Zend_Log();
            $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
            $formatter = new Zend_Log_Formatter_Simple($format);
            $writer = new Zend_Log_Writer_Stream('logs/configuracion_dia_' . date('Y-m-d') .  '.log');
            $writer->setFormatter($formatter);
            $consola = new Zend_Log_Writer_Stream('php://output');
            $consola->setFormatter($formatter);
            $logger_dia->addWriter($consola);
            $logger_dia->addWriter($writer);

            $logger_dia->info( "[--config][-dia]" );
            //si no se pasa el dia como parametro entonces se obtiene el dia actual
            $fechaActual = isset( $argv[3] )? $argv[3]: date("Y-m-d");
            $logger_dia->info( "dia: $fechaActual" );
            $generar_config_pautas_procesamiento = consulta( 'CONFIGURACIONES_ACTIVAS', array( 'fecha' => $fechaActual ) );
            $lista_archivos_a_procesar = array();

            if( !empty( $generar_config_pautas_procesamiento ) ){

                foreach( $generar_config_pautas_procesamiento as $indice => $datos ){

                    $intervalo = $datos['hora_inicio'];
                    $hora_fin = segundos2hms( hms2segundos($datos['hora_fin']) + hms2segundos( $datos['duracion'] ) );
                    $formato = formatear_archivo_video( $datos['formato'], $intervalo );

                    $logger_dia->info( "hora inicio: $intervalo" );
                    $logger_dia->info( "hora fin: $hora_fin" );
                    $logger_dia->info( "formato: $formato" );

                    //se realiza de esta forma para porque es ciclico y si o si se tiene que poder entrar una vez para poder iterar
                    do{

                        $lista_archivos_a_procesar[$intervalo]['fecha'] = $fechaActual;
                        $lista_archivos_a_procesar[$intervalo]['grabacion'] = 0;
                        $lista_archivos_a_procesar[$intervalo]['conversion'] = 0;
                        $lista_archivos_a_procesar[$intervalo]['deteccion'] = 0;
                        $lista_archivos_a_procesar[$intervalo]['archivo'] = $formato;
                        $lista_archivos_a_procesar[$intervalo]['canal'] = $datos['canal'];
                        $lista_archivos_a_procesar[$intervalo]['alias'] = $datos['alias'];

                        $intervalo = segundos2hms( hms2segundos($intervalo) + hms2segundos( $datos['duracion'] ) );
                        $formato = formatear_archivo_video( $datos['formato'], $intervalo);
                        $logger_dia->info("siguiente hora: $intervalo" );

                    }while( $intervalo != $hora_fin );

                    $logger_dia->info("lista de archivos para configuracion: " . print_r($lista_archivos_a_procesar, true) );

                    try {

                        $status = consulta( 'INSERTAR_PAUTAS_PROCESAMIENTO', $lista_archivos_a_procesar );
                        $logger_dia->info("configuracion del dia cargada correctamente!!! con status[$status]" );

                    } catch (Exception $e) {

                        $logger_dia->err("excepcion capturada: " . $e->getMessage() );
                        $logger_dia->err("no se pudo cargar la configuracion" );
                    }
                }//endforeach
            }else{

                $logger_dia->info("lista de archivos para configuracion vacia " );
            }

            return;

        }
        if($argv[1] == '--config' && $argv[2] == '-activas' ){

            //configuracion de log personalizado
            $logger_activas = new Zend_Log();
            $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
            $formatter = new Zend_Log_Formatter_Simple($format);
            $writer = new Zend_Log_Writer_Stream('logs/configuraciones_activas_' . date('Y-m-d') .  '.log');
            $writer->setFormatter($formatter);
            $consola = new Zend_Log_Writer_Stream('php://output');
            $consola->setFormatter($formatter);
            $logger_activas->addWriter($consola);
            $logger_activas->addWriter($writer);

            $logger_activas->info( "[--config][-activas]" );
            $fecha_actual = isset( $argv[3] )? $argv[3]: date("Y-m-d");
            $logger_activas->info( "dia: " . $fecha_actual );
            $configuraciones_controlar = consulta( 'CONFIGURACIONES_ACTIVAS', array( 'fecha' => $fecha_actual  ) );

            if( !empty( $configuraciones_controlar ) ){

                foreach( $configuraciones_controlar as $indice => $configuracion ){

                    $configuracion['fecha'] = $fecha_actual;
                    $logger_activas->info( "configuracion controlar grabaciones: " . print_r( $configuracion, true ) );
                    $ejecutar_configuracion = ejecutar_configuracion( 'CONTROL_GRABACION', $configuracion );
                    $logger_activas->info( "configuracion controlar conversiones: " . print_r( $configuracion, true ) );
                    $ejecutar_configuracion_conversor = ejecutar_configuracion( 'CONTROL_CONVERSION', $configuracion );
                }

            }else{

                $logger_activas->info("lista de archivos para configuracion vacia " );
            }

            return;

        }
        if( $argv[1] == '--config' && $argv[2] == '-grabacion' && $argv[3]== '-canal' && $argv[5] == '-hora_inicio' &&
            $argv[7] == '-fecha' && $argv[9] == '-duracion' && $argv[11] == '-alias' && $argv[13] == '-hora_fin' ){

            //configuracion de log personalizado
            $logger_grabacion = new Zend_Log();
            $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
            $formatter = new Zend_Log_Formatter_Simple($format);
            $writer = new Zend_Log_Writer_Stream('logs/configuracion_grabacion_' . date('Y-m-d') .  '.log');
            $writer->setFormatter($formatter);
            $consola = new Zend_Log_Writer_Stream('php://output');
            $consola->setFormatter($formatter);
            $logger_grabacion->addWriter($consola);
            $logger_grabacion->addWriter($writer);
            
            $logger_grabacion->info( "[--config][-grabacion]" );

            $canal = $argv[4];
            $logger_grabacion->info("canal : $canal");
            $hora_inicio = $argv[6];
            $logger_grabacion->info("hora_inicio : $hora_inicio");
            $fecha = $argv[8];
            $logger_grabacion->info("fecha : $fecha");
            $duracion = $argv[10];
            $logger_grabacion->info("duracion : $duracion");
            $alias = $argv[12];
            $logger_grabacion->info("alias : $alias");
            $hora_fin = $argv[14];

            //convertimos adecuadamente la hora fin y le agregamos 10 minutos como reserva
            $hora_fin = segundos2hms( hms2segundos( $hora_fin ) + hms2segundos( $duracion ) + 10*60 );
            $logger_grabacion->info( "hora fin: $hora_fin" );

            //convertimos adecuadamente la hora a evaluar y le agregamos 2 minutos
            $hora_evaluar = segundos2hms( hms2segundos($hora_inicio) + hms2segundos( $duracion ) + 2*60 );
            $logger_grabacion->info( "hora a evaluar: $hora_evaluar" );

            //$hora_inicio > $hora_fin
            if( strcmp( $hora_inicio, $hora_fin ) > 0 ){
                //entonces hay cambio de dia
                $manhana = date("Y-m-d", strtotime("+1day"));
                $fecha_hora_fin = "$manhana $hora_fin";
                $logger_grabacion->info( "fechar hora fin (proximo dia): $fecha_hora_fin" );
            }else{

                $fecha_hora_fin = "$fecha $hora_fin";
                $logger_grabacion->info( "fechar hora fin (en el dia): $fecha_hora_fin" );
            }

            $indice = 0;
            $path_fijo = 'Y:\\';
            $path_archivo_a_procesar = $path_fijo . $canal;

            $archivos_a_procesar = consulta( 'GET_ARCHIVOS_PROCESAR', array( 'fecha' => $fecha, 'canal' => $canal, 'alias' => $alias ) );

            if( !empty( $archivos_a_procesar ) ){

                $logger_grabacion->info( "archivos a procesar : " . print_r( $archivos_a_procesar, true ) );
                $duracion_esperada = 0;//tienen que tener mayor duracion a 0

                while( true ){

                    $fecha_hora_actual = date( "Y-m-d H:i:s");
                    $hora_actual = date("H:i:s");
                    $logger_grabacion->info( "fecha hora actual: $fecha_hora_actual" );
                    $logger_grabacion->info( "fecha hora fin: $fecha_hora_fin" );
                    $logger_grabacion->info( "hora actual: $hora_actual" );
                    $logger_grabacion->info( "hora a evaluar: $hora_evaluar" );

                    //$fecha_hora_actual < $fecha_hora_fin
                    if( strcmp( $fecha_hora_actual, $fecha_hora_fin ) < 0 ){
                        //hora_actual < hora_evaluar
                        if( strcmp($hora_actual, $hora_evaluar) < 0 ){

                            $diferencia_tiempo =  hms2segundos( $hora_evaluar ) - hms2segundos( $hora_actual );
                            $logger_grabacion->info( "dormir: " . segundos2hms( $diferencia_tiempo ) );
                            sleep( $diferencia_tiempo );

                        }else{

                            $archivo = $path_archivo_a_procesar . '/' . $archivos_a_procesar[$indice]['archivo'] . '.mpg';
                            $logger_grabacion->info( "archivo a procesar: $archivo" );

                            /*
                             * en el caso de que el nombre del archivo sea diferente del que se espera ejemplo, con el enutv -> (20141014-143219.mpg)
                             * se espera (20141014-140000.mpg)
                            */

                            if( !is_file( $archivo ) ){

                                $archivos = filtrar_directorio( $path_archivo_a_procesar, 'mpg', true );

                                foreach( $archivos as $path ){

                                    $cadena = substr( basename( $path ), 0, -8 );
                                    $logger_grabacion->info( "cadena: $cadena" );
                                    $archivo_verificar = substr( $archivos_a_procesar[$indice]['archivo'], 0, -4 );
                                    $logger_grabacion->info( "archivo_verificar: $archivo_verificar" );

                                    if( strcmp ($archivo_verificar , $cadena ) == 0 ){

                                        $archivo = $path;
                                        break;
                                    }
                                }
                            }

                            $duracion_video = duracion_video_new($archivo);
                            $logger_grabacion->info( "duracion: $duracion_video" );

                            if( $duracion_video > $duracion_esperada ){

                                $datos_actualizar = array( 'fecha' => $fecha, 'canal' => $canal, 'alias' => $alias, 'archivo' => $archivos_a_procesar[$indice]['archivo'], 'estado' => 1 );
                                $logger_grabacion->info( "datos a actualizar [grabacion]: " . print_r( $datos_actualizar, true) );
                                $status = consulta( 'ACTUALIZAR_ESTADO_ARCHIVO', $datos_actualizar );
                            }else{

                                $datos_actualizar = array( 'fecha' => $fecha, 'canal' => $canal, 'alias' => $alias, 'archivo' => $archivos_a_procesar[$indice]['archivo'], 'estado' => 2 );
                                $logger_grabacion->info( "datos a actualizar [grabacion]: " . print_r( $datos_actualizar, true) );
                                $status = consulta( 'ACTUALIZAR_ESTADO_ARCHIVO', $datos_actualizar );
                            }

                            $hora_evaluar = segundos2hms( hms2segundos( $hora_evaluar ) + hms2segundos( $duracion ) );
                            $logger_grabacion->info( "nueva hora a evaluar: $hora_evaluar" );
                            $indice++;
                        }
                    }else{

                        return;
                    }
                }
            }else{

                $logger_grabacion->info("No existen archivos a procesar" );
            }

            return;

        }
        if( $argv[1] == '--config' && $argv[2] == '-conversorpauta' && $argv[3]== '-canal' && $argv[5] == '-fecha' &&
            $argv[7] == '-alias' && $argv[9] == '-duracion' && $argv[11] == '-hora_fin' ){

            //configuracion de log personalizado
            $logger_conversor = new Zend_Log();
            $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
            $formatter = new Zend_Log_Formatter_Simple($format);
            $writer = new Zend_Log_Writer_Stream('logs/configuracion_conversion_' . date('Y-m-d') .  '.log');
            $writer->setFormatter($formatter);
            $consola = new Zend_Log_Writer_Stream('php://output');
            $consola->setFormatter($formatter);
            $logger_conversor->addWriter($consola);
            $logger_conversor->addWriter($writer);
            
            $logger_conversor->info( "[--config][-conversorpauta]" );

            $canal = $argv[4];
            $logger_conversor->info( "canal: " . $canal);
            $fecha = $argv[6];
            $logger_conversor->info( "fecha: " . $fecha);
            $alias = $argv[8];
            $logger_conversor->info( "alias: " . $alias);
            $duracion = $argv[10];
            $logger_conversor->info( "duracion: " . $duracion);
            $hora_fin = $argv[12];
            $logger_conversor->info( "hora fin: " . $hora_fin);

            $hora_fin_correcta = segundos2hms( hms2segundos( $hora_fin ) + hms2segundos( $duracion ) + 20*60 );
            $logger_conversor->info( "hora fin correcta: " . $hora_fin_correcta);

            $path_fijo_pautas = 'Y:\\';
            $path_fijo_pautas_convertidas = 'C:/Users/USER/ENTERMOVIL/DAAS/PROYECTOS/TVCHAT/PAUTAS/AFORTUNADOS1/PAUTAS/SNT';
            $nombre_dias = array('Sunday' => 'DOMINGO', 'Monday' => 'LUNES', 'Tuesday' => 'MARTES' ,'Wednesday'=>'MIERCOLES','Thursday'=>'JUEVES','Friday' => 'VIERNES', 'Saturday'=>'SABADO');
            $path_archivo_a_procesar = $path_fijo_pautas .  $canal;
            $nombre_dia = $nombre_dias[date ("l", strtotime($fecha))];
            $path_destino_archivo_convertido = $path_fijo_pautas_convertidas . '/' . $fecha . '_' . $nombre_dia;
            $tiempo_termino_grabacion = 0;

            if( !is_dir( $path_destino_archivo_convertido ) ){

                if( mkdir( $path_destino_archivo_convertido ) ){

                    $logger_conversor->info( "carpeta creada correctamente ->(" . basename($path_destino_archivo_convertido) . ")"  );

                }else{

                    $logger_conversor->info( "no se pudo crear la carpeta" );
                }
            }else{

                $logger_conversor->info( "ya existe la carpeta" );
            }

            $fecha_hora_actual = date("Y-m-d H:i:s");
            $logger_conversor->info( "fecha hora actual: $fecha_hora_actual" );
            $hora_inicio = date("H:i:s");
            $logger_conversor->info( "hora inicio: $hora_inicio" );

            //preparar condicion de termino
            //$hora_inicio > $hora_fin
            if( strcmp( $hora_inicio, $hora_fin_correcta ) > 0 ){
                //entonces hay cambio de dia
                $manhana = date("Y-m-d", strtotime("+1day"));
                $fecha_hora_fin = "$manhana $hora_fin_correcta";
                $logger_conversor->info( "fecha hora fin (proximo dia): $fecha_hora_fin" );
            }else{

                $fecha_hora_fin = "$fecha $hora_fin_correcta";
                $logger_conversor->info( "fechar hora fin (en el dia): $fecha_hora_fin" );
            }

            //$fecha_hora_actual < $fecha_hora_fin
            while( strcmp( $fecha_hora_actual, $fecha_hora_fin ) < 0 ){

                $archivos_a_procesar = consulta( 'GET_ARCHIVO_CONVERTIR', array( 'fecha' => $fecha, 'canal' => $canal ) );
                $logger_conversor->info( "archivo a convertir: " . print_r( $archivos_a_procesar, true ) );

                $fecha_hora_actual = date("Y-m-d H:i:s");
                $logger_conversor->info( "fecha hora actual while: $fecha_hora_actual" );

                if( !is_null( $archivos_a_procesar ) ){

                    $archivo = $path_archivo_a_procesar .'/'.$archivos_a_procesar['archivo'] . '.mpg';
                    $logger_conversor->info( "archivo: $archivo" );
                    $hora_archivo_grabacion = substr( $archivos_a_procesar['archivo'], 9, 2 ) . ":" . substr( $archivos_a_procesar['archivo'], 11, 2 ). ":" . substr( $archivos_a_procesar['archivo'], 13, 2 );
                    //calculamos la siguiente hora posible de procesamiento de conversion de archivo
                    $tiempo_termino_grabacion = hms2segundos( $hora_archivo_grabacion ) + 2*( hms2segundos( $duracion ) ) + 4*60;
                    $logger_conversor->info( "tiempo terminar grabacion en segundos: $tiempo_termino_grabacion" );
                    $logger_conversor->info( "tiempo terminar grabacion en h:m:s: " . segundos2hms( $tiempo_termino_grabacion ) );

                    /*
                     * en el caso de que el nombre del archivo sea diferente del que se espera ejemplo, con el enutv -> (20141014-143219.mpg)
                     * se espera (20141014-140000.mpg)
                    */

                    if( !is_file( $archivo ) ){

                        $logger_conversor->info( "no existe el archivo en el path ($archivo)" );
                        $archivos = filtrar_directorio( $path_archivo_a_procesar, 'mpg', true );

                        foreach( $archivos as $path ){

                            $cadena = substr( basename( $path ), 0, -8 );
                            $logger_conversor->info( "cadena ( $cadena )" );
                            $archivo_verificar = substr( $archivos_a_procesar['archivo'], 0, -4 );
                            $logger_conversor->info( "archivo_verificar ( $archivo_verificar )" );

                            if( strcmp ($archivo_verificar , $cadena ) == 0 ){

                                $archivo = $path;
                                break;
                            }
                        }
                    }

                    if( is_file( $archivo ) ){

                        $datos_resumen = array();
                        $conversion = convertir_a_AVI2( $archivo, $path_destino_archivo_convertido, $datos_resumen );

                        if( isset( $conversion ) ){

                            $datos_actualizar = array( 'fecha' => $fecha, 'canal' => $canal, 'alias' => $alias, 'archivo' => $archivos_a_procesar['archivo'], 'estado' => 1 );
                            $logger_conversor->info( "datos a actualizar [conversion]: " . print_r( $datos_actualizar, true) );
                            $status = consulta( 'ACTUALIZAR_ESTADO_ARCHIVO_CONVERSION', $datos_actualizar );
                        }else{

                            $datos_actualizar = array( 'fecha' => $fecha, 'canal' => $canal, 'alias' => $alias, 'archivo' => $archivos_a_procesar['archivo'], 'estado' => 2 );
                            $logger_conversor->info( "datos a actualizar [conversion]: " . print_r( $datos_actualizar, true) );
                            $status = consulta( 'ACTUALIZAR_ESTADO_ARCHIVO_CONVERSION', $datos_actualizar );
                        }
                    }else{

                        $datos_actualizar = array( 'fecha' => $fecha, 'canal' => $canal, 'alias' => $alias, 'archivo' => $archivos_a_procesar['archivo'], 'estado' => 2 );
                        $logger_conversor->info( "datos a actualizar [conversion]: " . print_r( $datos_actualizar, true) );
                        $status = consulta( 'ACTUALIZAR_ESTADO_ARCHIVO_CONVERSION', $datos_actualizar );
                    }

                }else{

                    $logger_conversor->info( "no existen archivos a convertir..." );

                    $hora_actual = hms2segundos( date("H:i:s") );

                    if( $tiempo_termino_grabacion > $hora_actual ){

                        $tiempo_dormir = $tiempo_termino_grabacion - $hora_actual;
                        $logger_conversor->info( "tiempo a dormir ($tiempo_dormir)" );
                        sleep($tiempo_dormir);
                    }else{

                        $tiempo_dormir = 5*60;
                        $logger_conversor->info( "tiempo a dormir ($tiempo_dormir)" );
                        sleep($tiempo_dormir);
                    }
                }
            }

            return;
        }
        if($argv[1] == '--reportepautas'){

            /***************************************** PRIMER PASO - MOVERARCHIVOSPAUTAS ****************************************************/

            //DIRECTORIO DE GRABACIONES TELEFUTURO - SNT
            $directorio_grabaciones = 'C:\Users\User\Videos\GRABACIONES';//CAMBIAR AL CAMBIAR DE PC
            //ELIMINAR ESPACIOS
            eliminar_espacios( $directorio_grabaciones );
            //DIRECTORIO DE ARCHIVO PAUTAS TELEFUTURO - SNT
            $directorio_archivo_pautas = 'C:\Users\User\Desktop\ArchivoPautas';//CAMBIAR AL CAMBIAR DE PC

            $directorio_nombres = array();

            $fecha_hoy = date('Y-m-d');
            printf("FechaHoy:[%s]\n", $fecha_hoy);

            $canales = filtrar_carpetas( $directorio_grabaciones ,true);

            foreach( $canales as $canal ){

                if( is_dir( $canal ) ){
                    $listado_archivos = filtrar_directorio( $canal, 'mpg', true );
                    $fechas_archivos = array();
                    $nombre_dias = array('Sunday' => 'DOMINGO', 'Monday' => 'LUNES', 'Tuesday' => 'MARTES' ,'Wednesday'=>'MIERCOLES','Thursday'=>'JUEVES','Friday' => 'VIERNES', 'Saturday'=>'SABADO');
                    $mapeo = array();

                    foreach($listado_archivos as $path_archivo) {

                        printf("FechaCreacion:[%s]\n", date ("Y-m-d H:i:s", filemtime($path_archivo)));

                        $fecha = date ("Y-m-d", filemtime($path_archivo));
                        $nombre_dia = $nombre_dias[date ("l", filemtime($path_archivo))];
                        $nombre_carpeta = $fecha . '_' . $nombre_dia;

                        $mapeo[$nombre_carpeta][] = $path_archivo;

                    }

                    foreach($mapeo as $nombre_carpeta => $path_archivos) {

                        printf("Procesando archivos de Carpeta:[%s]\n", $nombre_carpeta);
                        if( is_dir(  $directorio_archivo_pautas . '/' . basename($canal) ) ){

                            $carpeta_destino = $directorio_archivo_pautas . '/' . basename($canal) . '/' . $nombre_carpeta;
                            printf("Carpeta destino:[%s]\n", $carpeta_destino);
                            mkdir($carpeta_destino);
                            foreach($path_archivos as $path_archivo) {
                                printf("Archivo:[%s]\n", $path_archivo);
                                $oldfile = $path_archivo;
                                $newfile = $carpeta_destino . "/" . basename($oldfile);
                                printf("directorio destino:[%s]\n", $newfile);
                                rename_win($oldfile,$newfile);

                            }
                        }
                        else{
                            printf("Crear Carpeta Canal:[%s]\n",  $directorio_archivo_pautas . '/' . basename($canal));
                            mkdir( $directorio_archivo_pautas . '/' . basename($canal) );
                            $carpeta_destino = $directorio_archivo_pautas . '/' . basename($canal) . '/' . $nombre_carpeta;
                            printf("Carpeta destino:[%s]\n", $carpeta_destino);
                            mkdir($carpeta_destino);
                            foreach($path_archivos as $path_archivo) {
                                printf("Archivo:[%s]\n", $path_archivo);
                                $oldfile = $path_archivo;
                                $newfile = $carpeta_destino . "/" . basename($oldfile);
                                rename_win($oldfile,$newfile);

                            }

                        }
                    }
                }
            }

            /***************************************** SEGUNDO PASO - CONVERTIR A AVI ****************************************************/

            //$directorio_archivo_pautas ES EL PATH DONDE SE ENCUENTRAN LAS CARPETAS - SNT - TELEFUTURO - ETC

            //$path_archivo_origen = $directorio_archivo_pautas;
            $lista_carpetas_canal = filtrar_carpetas( $directorio_archivo_pautas ); //SNT, TELEFUTURO ETC

            foreach( $lista_carpetas_canal as  $path_archivo_origen_canal ){

                $lista_carpetas_a_procesar = filtrar_carpetas($path_archivo_origen_canal,true);

                foreach($lista_carpetas_a_procesar as $path_archivo_origen){

                    $lista_archivos_a_procesar = filtrar_directorio_sin_avis($path_archivo_origen, 'mpg', true);
                    $path_carpeta_trabajo = $path_archivo_origen;
                    printf("PathCarpetaORIGEN:[%s]\n", $path_archivo_origen);
                    printf("PathCarpetaTrabajo:[%s]\n", $path_carpeta_trabajo);
                    $path_archivo_resumen = $path_carpeta_trabajo . '/resumen.ini';
                    $datos_resumen = array();

                    //============ CONVERTIR ARCHIVOS ===========================
                    foreach($lista_archivos_a_procesar as $path_archivo_a_procesar) {

                        printf("\n====================================================\n");
                        printf("Procesando: [%s]\n", basename($path_archivo_a_procesar));
                        printf("====================================================\n\n");
                        convertir_a_AVI2($path_archivo_a_procesar, $path_carpeta_trabajo, $datos_resumen);
                    }

                    print_r($datos_resumen);

                    write_ini_file($datos_resumen, $path_archivo_resumen, true);
                }
            }


            /************************************ ULTIMO PASO - OBTENER DATOS DEL A BASE DE DATOS Y PROCESAR - IMAGENES ***************************/

            //DATOS QUE VAMOS A OBTENER DE LA BASE DE DATOS
            $path_carpeta_trabajo = 'C:\Users\User\Desktop\ArchivoPautas';//FIJO
            //$fechaEnviar = $fecha_hoy;
            $fechaEnviar = '2013-04-29'; //MODIFICADO
            echo "VAMOS A VER QUE FECHA LE ENVIAMOS ".$fechaEnviar ."\n";
            printf("Vamos a ver que fecha tiene Fecha_hoy -->(%s)",$fecha_hoy);
            $plantillasPautas = obtenerDatosSpots( $fechaEnviar );
            print_r( $plantillasPautas );

            //los archivos que vamos a procesar
            $carpetasCanal = array();
            $reporte = array(

            );
            //CONTINUO CON EL PROCESAMIENTO DE IMAGENES
            //ACA EMPIEZA EL ALGORITMO
            //tengo que buscar la pauta solo en los dias en el que aparece esa pauta
            foreach( $plantillasPautas as $nombrePauta=> $pauta ){

                if( !empty( $pauta ) ){

                    $reporte[$nombrePauta]['NOMBRE_SERVICIO'] = $nombrePauta;//MODIFICADO
                    //asumiendo que en el directorio se encuentran las carpetas
                    $carpetasCanal = filtrar_carpetas($path_carpeta_trabajo,true);

                    foreach( $pauta['FECHAS'] as $canal=> $dias ){
                        //$canal posee telefuturo dias posee los dias
                        $reporte[$nombrePauta]['CANAL'] = $canal;//MODIFICADO

                        if( !empty( $dias ) ){

                            foreach( $carpetasCanal as $canaldeCarpeta ){
                                //VERIFICO QUE SE BUSQUE EN EL CANAL CORRECTO
                                if( strpos( $canaldeCarpeta, $canal ) == true ){

                                    echo "\nLA CARPETA ES:". basename($canaldeCarpeta) . "\n";
                                    foreach( $dias as $days ){
                                        //PROCESO LAS PAUTAS CON LOS DIAS REQUERIDOS
                                        if( is_dir( $canaldeCarpeta .'/'.$days ) ){

                                            procesar_pautas($pauta['PATHPLANTILLA'], $pauta['DURACION'], $canaldeCarpeta .'/'.$days,$pauta['DELTAX'],$pauta['DELTAY'],2);

                                            $reporte_del_dia = cargar_datos( $canaldeCarpeta .'/'.$days );
                                            $reporte[$nombrePauta][ 'DIA' ] = substr($days, 0, 10); //MODIFICADO
                                            $reporte[$nombrePauta][ $days ] = $reporte_del_dia;//MODIFICADO
                                            $reporte[$nombrePauta][ $days ]['PAUTAS_ENCONTRADAS_DIA'] = 0;//MODIFICADO

                                        }
                                        else{
                                            echo "\nLA CARPETA: ". basename($canaldeCarpeta .'/'.$days) . " NO EXISTE O NO CORRESPONDE\n";
                                        }
                                    }
                                }
                                else{

                                    echo "\nNO SE ENCUENTRA EL ELEMENTO " . $canal . " EN ESTA CARPETA\n";
                                    echo "\nLA CARPETA ES:". basename($canaldeCarpeta) . "\n";
                                }
                            }
                        }
                        else{

                            echo "\nNO SE ENCUENTRA NINGUN ELEMENTO\n";
                        }

                    }

                }
                else{
                    echo "\nLa plantilla no tiene ningun dato\n";
                }

            }
            $nombre_dias = array('Sunday' => 'DOMINGO', 'Monday' => 'LUNES', 'Tuesday' => 'MARTES' ,'Wednesday'=>'MIERCOLES','Thursday'=>'JUEVES','Friday' => 'VIERNES', 'Saturday' => 'SABADO');
            //CONTAR EL NUMERO TOTAL DE PAUTAS Y POR DIA
            foreach( $reporte as $alias ){ //MODIFICADO

                $nombre_dia = $nombre_dias[date ("l", strtotime($alias['DIA']))];

                foreach( $alias[$alias['DIA'] . '_'.$nombre_dia] as $archivo_avi => $pautas ){

                    if( !empty( $pautas ) ){

                        //echo "MIRAR\n";
                        //print_r( $pautas );
                        //echo "MIRAR CARAJO TIENE QUE SUMAR".$reporte[$alias][ $alias['DIA'] . '_'.$nombre_dia ]['PAUTAS_ENCONTRADAS_DIA']."\n";
                        //echo "Elementos = ". count($pautas) . "\n";
                        //echo "En alias =". $alias['NOMBRE_SERVICIO']. " dia = ".$alias['DIA'] . '_'.$nombre_dia ." valor al inicio = ". $alias[ $alias['DIA'] . '_'.$nombre_dia ]['PAUTAS_ENCONTRADAS_DIA'] . "\n";
                        $reporte[$alias['NOMBRE_SERVICIO']][ $alias['DIA'] . '_'.$nombre_dia ]['PAUTAS_ENCONTRADAS_DIA'] += count($pautas);
                        //echo "cuenta = ". $reporte[$alias['NOMBRE_SERVICIO']][ $alias['DIA'] . '_'.$nombre_dia ]['PAUTAS_ENCONTRADAS_DIA'] . "\n";

                    }
                }
            }
            print_r($reporte);
            echo "voy a entrar ak";
            cargarBD( $reporte );
            echo "\nSE termino Correctamente\n";
            return;
        }
        if($argv[1] == '--prueba'){

            $text = hms2segundos("00:1:00") + hms2segundos("00:59:00");
            echo $text."\n";
            $text_nuevo = segundos2hms($text);
            echo 'mirar el texto: ' . $text_nuevo;
            return;
        }

        help();
        printf("\n");
    }

    analizar_parametros( $argv );

    function consulta( $accion, $datos = null ){

        global $db;
        global $logger;

        $resultado = null;

        if( $accion == 'CONFIGURACIONES_ACTIVAS' ){

            $sql = "select *
                    from configuracion_pautas
                    where dia_fin >= ? and dia_inicio <= ? and activo = 'true'";

            $rs = $db->fetchAll( $sql, array( $datos['fecha'], $datos['fecha'] ) );

            //si se retorna filas entonces se debe crear nuevas entradas en la tabla configuracion_pautas_procesamiento
            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }

                $logger->info("resultado: " . print_r( $resultado, true ));
            }else{

                $logger->info("resultset vacio ");
            }

        }
        elseif( $accion == 'INSERTAR_PAUTAS_PROCESAMIENTO' ){

            foreach( $datos as $indice => $dato  ){

                try{

                    $resultado = $db->insert( 'configuracion_pautas_procesamiento', $dato );

                }catch( Exception $e ) {

                    echo 'No se pudo insertar a la base de datos: ',  $e->getMessage(), "\n";
                }
            }
        }
        elseif( $accion == 'GET_ARCHIVOS_PROCESAR' ){

            $sql = "select CPP.archivo
                    from configuracion_pautas_procesamiento CPP
                    where CPP.fecha = ? and CPP.canal = ? and CPP.alias = ?
                    order by 1";

            $rs = $db->fetchAll( $sql, array( $datos['fecha'], $datos['canal'], $datos['alias'] ) );

            //si se retorna filas entonces se debe crear nuevas entradas en la tabla configuracion_pautas_procesamiento
            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado[] = (array)$fila;
                }
            }
        }
        elseif( $accion == 'ACTUALIZAR_ESTADO_ARCHIVO' ){

            $data = array(

                'grabacion' => $datos['estado'],

            );

            $where = array(

                'alias = ?' => $datos['alias'],
                'canal = ?' => $datos['canal'],
                'fecha = ?' => $datos['fecha'],
                'archivo = ?' => $datos['archivo']

            );

            $resultado = $db->update( 'configuracion_pautas_procesamiento', $data, $where );

            print_r($datos);

        }
        elseif( $accion == 'ACTUALIZAR_ESTADO_ARCHIVO_CONVERSION' ){

            $data = array(

                'conversion' => $datos['estado'],

            );

            $where = array(

                'alias = ?' => $datos['alias'],
                'canal = ?' => $datos['canal'],
                'fecha = ?' => $datos['fecha'],
                'archivo = ?' => $datos['archivo']

            );

            $resultado = $db->update( 'configuracion_pautas_procesamiento', $data, $where );

            print_r($datos);

        }
        elseif( $accion == 'GET_ARCHIVO_CONVERTIR' ){

            $sql = "select cpp.*
            from configuracion_pautas_procesamiento cpp
            where cpp.fecha = ? and cpp.grabacion = 1 and cpp.conversion = 0 and cpp.canal = ?
            order by 1,2,6
            limit 1";

            $rs = $db->fetchAll( $sql, array( $datos['fecha'], $datos['canal'] ) );

            if( !empty( $rs ) ){

                foreach( $rs as $fila ){

                    $resultado = (array)$fila;
                }
            }

        }

        return $resultado;
    }

    function formatear_archivo_video( $formato, $dato ){

        $dato_formateado = null;

        if( $formato == "aaaammdd-hhmmss" ){

            $partes = explode( ":", $dato );
            $dato = $partes[0].$partes[1].$partes[2];
            $dato_formateado = date("Ymd") . '-' . $dato;
        }

        return $dato_formateado;
    }