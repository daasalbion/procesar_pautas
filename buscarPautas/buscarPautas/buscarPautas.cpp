// buscarPautas.cpp: define el punto de entrada de la aplicación de consola.
//VERSION DAAS

#include "stdafx.h"

#include "opencv2/highgui/highgui.hpp"
#include "opencv2/imgproc/imgproc.hpp"
#include "opencv2/video/video.hpp"
#include <iostream>	
#include <stdio.h>

using namespace std;
using namespace cv;

#define CANTIDAD_MINIMA_PARAMETROS 5

int duracionPauta = 0; //segundos
int duracionVideo = 0; //duracion del archivo de video procesado, usado como limite
char pathImagenPlantilla[200];
char pathDirectorioVideos[200];
char nombreArchivoVideo[200];
int deltaY = 0;
int deltaX = 0;
int animacion = 0; //por defecto pauta a buscar sin animacion
int error_pixeles_arriba_x = 50;
int error_pixeles_arriba_y = 50;
int error_pixeles_abajo_x = 50;
int error_pixeles_abajo_y = 50;

Point margenSeguridadArriba;
Point margenSeguridadAbajo;

bool flagDuracionPauta = false;
bool flagImagenPlantilla = false;
bool flagDeltaX = false;
bool flagDeltaY = false;
bool flagDirectorioVideos = false;
bool flagNombreArchivoVideo = false;
bool flagDuracionVideo = false;
bool flagAnimacion = false;

/// Global Variables
Mat imagenReferencia;//matriz donde se guardan lass imagenes de los frames
Mat imagenPlantilla; //matriz donde se guarda la imagen buscada
Mat result;
//agregado
const char* image_window = "Source Image";
const char* result_window = "Result window";
//creo una imagen captura
IplImage* imgReferencia;
//agregado
IplImage* imgPrueba;

int match_method;
int max_Trackbar = 5;

Point listaPuntos[2];
double listaValores[2];

Scalar listaColores[2] = {
    Scalar(255, 0, 0), 
	Scalar(165, 42, 42)
};

/// Function Headers
void MatchingMethod(int, void*);
bool puntosIguales();
bool checkROI();
int help();
int analizarParametros(int, char**);
void partesPath();
bool insideROI();
int verificarPosicion();

/* display usage */
int help() {

   printf("\n\tVERSION 10.2 date:2014-09-26\n");
   printf("Uso: buscarpauta -s <arg0> -t <arg1> -p <arg2> -D <arg3> -v <arg4> [ -dx <arg5> ] [ -dy <arg6> ]\n");
   printf("\t-s: Duracion del archivo de video en segundos\n");
   printf("\t-t: Duracion de la Pauta en Segundos. La Pauta deber durar al menos 10 segundos.\n");
   printf("\t-p: Path de la Imagen Plantilla\n");
   printf("\t-D: Path de Directorio de Trabajo.\n");
   printf("\t-v: Nombre Archivo de Video. El formato del video debe ser AVI\n");
   printf("\t-dx: DeltaX para Sub-Region de Busqueda. (Opcional)\n");
   printf("\t-dy: DeltaY para Sub-Region de Busqueda. (Opcional)\n");
   printf("\t-a: Flag que sirve para busquedas intensivas de pautas con animacion. (Opcional)\n");
   system("pause");
   return 1;
}

int analizarParametros( int argc, char** argv ) {
    
    if( argc < ( ( CANTIDAD_MINIMA_PARAMETROS*2 ) + 1 ) ) {
        
		printf("\nCantidad de Parametros Insuficientes\n\n");
        return help();
    }
    
    for ( int i = 1; i < ( argc - 1 ); i++ ) {
      
        if ( strcmp( "-s", argv[i] ) == 0 ) {//Duracion del archivo donde buscar la pauta
            
			duracionVideo = atoi(argv[++i]);
            if( duracionVideo <= 0 ) {
                
				printf("\nduracionVideo:[%d] - Duracion Muy Corta\n");
                return help();
            }

            printf("duracionVideo:[%d]\n", duracionVideo);
            flagDuracionVideo = true;
            continue;
        }
		
		if (strcmp( "-t", argv[i]) == 0) {//Duracion de la pauta
            
			duracionPauta = atoi(argv[++i]);
            if(duracionPauta <= 0) {
                
				printf("duracionPauta:[%d] - Duracion Muy Corta");
                return help();
            }

            printf("duracionPauta:[%d]\n", duracionPauta);
            flagDuracionPauta = true;
            continue;
        }
        
        if (strcmp("-p", argv[i]) == 0) {//Path de la Imagen Plantilla
            
			sprintf(pathImagenPlantilla, "%s", argv[++i]);
            printf("pathImagenPlantilla:[%s] Longitud:[%d]\n", pathImagenPlantilla, strlen(pathImagenPlantilla));
            flagImagenPlantilla = true;
			continue;
        }
        
        if (strcmp("-D", argv[i]) == 0) {//Path Directorio de Trabajo
            
			sprintf(pathDirectorioVideos, "%s", argv[++i]);
            printf("pathDirectorioVideos:[%s] Longitud:[%d]\n", pathDirectorioVideos, strlen(pathDirectorioVideos));
            flagDirectorioVideos = true;
            continue;
        }
        
        if (strcmp("-v", argv[i]) == 0) {//Path de Video
            
			sprintf(nombreArchivoVideo, "%s", argv[++i]);
            printf("pathArchivoVideo:[%s] Longitud:[%d]\n", nombreArchivoVideo, strlen(nombreArchivoVideo));
            flagNombreArchivoVideo = true;
            continue;
        }
        
        if (strcmp("-dx", argv[i]) == 0) {//DeltaX
            
			deltaX = atoi(argv[++i]);
            printf("deltaX:[%d]\n", deltaX);
            flagDeltaX = true;
            continue;
        }
        
        if (strcmp("-dy", argv[i]) == 0) {//DeltaY
            
			deltaY = atoi(argv[++i]);
            printf("deltaY:[%d]\n", deltaY);
            flagDeltaY = true;
            continue;
        }
        if (strcmp("-a", argv[i]) == 0) {
            
			animacion = atoi(argv[++i]);
            printf("animacion:[%d]\n", animacion);
			if( animacion == 0 ){
				
				animacion = 0;
			}
			else{
				
				animacion = 1;
			}
            
			flagAnimacion = true;
            continue;
        }

        printf("\nParametro Incorrecto! Switch:[%s] No Soportado!\n\n", argv[i]);
        return help();

    }
    
    //Verificar si los parametros obligatorios estan presentes
    if( !flagDuracionVideo || !flagDuracionPauta || !flagImagenPlantilla || !flagDirectorioVideos || !flagNombreArchivoVideo ) {
        
		printf("\nParametros Incorrectos! Faltan Parametros Obligatorios\n\n");
        return help();
    }
    
    //Verificar los Paths recibidos.
    //Path Archivo de Video debe terminar en .avi
    if(flagNombreArchivoVideo) {
        
		if( nombreArchivoVideo[strlen(nombreArchivoVideo)-1] != 'i' || 
            nombreArchivoVideo[strlen(nombreArchivoVideo)-2] != 'v' ||
            nombreArchivoVideo[strlen(nombreArchivoVideo)-3] != 'a' ||
            nombreArchivoVideo[strlen(nombreArchivoVideo)-4] != '.') {
            
            printf("\nParametro Incorrecto! El formato del Video debe ser AVI\n\n");
            return help();
        }
    }
    
    //Path Directorio Videos debe terminar en /
    if(flagDirectorioVideos) {
        //int largo = strlen(pathDirectorioVideos);
        printf("UltimaLetra:[%c]\n", pathDirectorioVideos[strlen(pathDirectorioVideos)-1]);
        if(pathDirectorioVideos[strlen(pathDirectorioVideos)-1] == '\\' || pathDirectorioVideos[strlen(pathDirectorioVideos)-1] == '/') {
            
			printf("Ya tiene Barra\n");
        } else {
            
			printf("No tiene Barra\n");
            sprintf(pathDirectorioVideos, "%s/", pathDirectorioVideos);
            printf("pathDirectorioVideos:[%s] Longitud:[%d]\n", pathDirectorioVideos, strlen(pathDirectorioVideos));
	
			return help();
        }
    }
    
    return 0;
}


/**
 * @function main
 * @argc numero de argumentos
 * @argv string
 */
int main(int argc, char** argv) {


    //Analizar parametros recibidos...
    int continuar = analizarParametros(argc, argv);
    if(continuar > 0) {
        
		return continuar; 
    }

    //return 0;

//    printf("Finalizamos...\n");
//    return 0;
    
    //Archivo de Video a Analizar...
    char pathArchivoVideo[200];
    sprintf(pathArchivoVideo, "%s%s", pathDirectorioVideos, nombreArchivoVideo);
    printf("PathArchivoVideo: %s\n", pathArchivoVideo);
    
    //Creamos objeto de captura
    CvCapture* capture = cvCaptureFromAVI(pathArchivoVideo);

    //Imagen plantilla..
    printf("PathImagenPlantilla: %s\n", pathImagenPlantilla);
	//se convierte en una matriz para comparar luego
    imagenPlantilla = imread(pathImagenPlantilla, CV_LOAD_IMAGE_UNCHANGED);

    //Duracion de la pauta
    printf("DuracionPauta: %d\n", duracionPauta);

    //Desplazamiento en Segundos
    //A la duracion de la pauta, restamos 5 segundos
    int deltaFijoFramesEnSegundos = (int) (duracionPauta* 0.7) +  1;

	//Verificamos q tenga animacion para calcularle el paso a saltar dentro del video avi
	if(animacion > 0){
		
		deltaFijoFramesEnSegundos = (int) ( duracionPauta* 0.3 );	
		printf("\nLa pauta posee animacion por eso realizamos una busqueda intensiva cada -> (%d)\n", deltaFijoFramesEnSegundos);
	}

    //Cuando encontramos un match, bajamos el delta a 1 segundo por 3 veces, para asegurarnos que
    //encontramos una pauta y luego incrementamos de vuelta a 5
    int deltaVariableFramesEnSegundos = deltaFijoFramesEnSegundos;

    int posicionFrameEnSegundosAnterior = 0;
    //Ubicamos el puntero
    int posicionFrameEnSegundos = deltaVariableFramesEnSegundos;

    //Ubicamos el puntero para capturar el primer frame en el Segundo = 1
    cvSetCaptureProperty(capture, CV_CAP_PROP_POS_MSEC, (double) (1000 * 1));

    //Capturamos el frame y lo cargamos como imagen de referencia
    imgReferencia = cvQueryFrame(capture);

    int frameH = (int) cvGetCaptureProperty(capture, CV_CAP_PROP_FRAME_HEIGHT);
    int frameW = (int) cvGetCaptureProperty(capture, CV_CAP_PROP_FRAME_WIDTH);
    int fps = (int) cvGetCaptureProperty(capture, CV_CAP_PROP_FPS);
    int numFrames = (int) cvGetCaptureProperty(capture, CV_CAP_PROP_FRAME_COUNT);

    printf("frameW: %d\n", frameW);
    printf("frameH: %d\n", frameH);
    printf("fps: %d\n", fps);
    printf("numFrames: %d\n", numFrames);
    printf("\n");

    float posMsec = cvGetCaptureProperty(capture, CV_CAP_PROP_POS_MSEC);
    int posFrames = (int) cvGetCaptureProperty(capture, CV_CAP_PROP_POS_FRAMES);
    float posRatio = cvGetCaptureProperty(capture, CV_CAP_PROP_POS_AVI_RATIO);
    printf("posMsec: %d\n", posMsec);
    printf("posFrames: %d\n", posFrames);
    printf("posRatio: %d\n", posRatio);


    printf("\n");
    printf("imgFrame->width: %d\n", imagenReferencia.cols);
    printf("imgFrame->height: %d\n", imagenReferencia.rows);

    //Coordenadas de la sub-area en la imagen de referencia. Sirve para agilizar las busquedas
    printf("ROI -> x0,y0 = (%d, %d)\n", deltaX, deltaY);

    int numPruebas = 0;
	int segundosEntrePautas = 0;

    int contadorPautasEncontradas = 0;
    int contadorInternoPosiblePauta = 0;

	margenSeguridadArriba.x = deltaX - error_pixeles_arriba_x;
	margenSeguridadArriba.y = deltaY - error_pixeles_arriba_y;

	margenSeguridadAbajo.x = deltaX + imagenPlantilla.cols + error_pixeles_abajo_x;
	margenSeguridadAbajo.y = deltaY + imagenPlantilla.rows + error_pixeles_abajo_y;
	
    while (numFrames > posFrames) {// && numPruebas < 3

        if (imgReferencia->width > 0 && imgReferencia->height > 0) {
			
            numPruebas++;
            
            //Convertimos a otra estructura de datos... un objeto Matriz
            imagenReferencia = cv::cvarrToMat(imgReferencia);

            if (imagenReferencia.cols > 0 && imagenReferencia.rows > 0) {
                
				printf("imagenReferencia:(%d, %d)\n", imagenReferencia.cols, imagenReferencia.rows);

            } else {
                
				printf("imagenReferencia SIN DIMENSION!\n");
                printf("Finalizamos...\n\n");
                return 1;

            }
            
            if (checkROI()) {

                if (puntosIguales()) {

                    printf("==========================================\n");
                    printf("La imagen contiene la plantilla buscada!!!\n");
                    printf("==========================================\n\n");

                    contadorInternoPosiblePauta++; //incrementamos contador de posible pauta
					printf("POSIBLE PAUTA ENCONTRADA -> (%d)\n", contadorInternoPosiblePauta);
                    deltaVariableFramesEnSegundos = 1; //verificamos cada 1 segundo hasta contabilizar 2 match
					
                    if ( contadorInternoPosiblePauta >= 2 ) {

                        contadorInternoPosiblePauta = 0;
                        deltaVariableFramesEnSegundos = deltaFijoFramesEnSegundos;

						if( posicionFrameEnSegundos < duracionPauta ){
							//hacemos lo mas negativo posible por ser un caso especial
							posicionFrameEnSegundosAnterior = -100;

						}
                        //Verificamos que no se haya detectado la misma pauta.
                        //Puede ocurrir si el frame detectado cae justo en el primer frame de la pauta

                        segundosEntrePautas = posicionFrameEnSegundos - posicionFrameEnSegundosAnterior;
						printf( "diferencia ->(%d)\n", segundosEntrePautas );

                        if ( segundosEntrePautas > duracionPauta ) {

                            printf("Deteccion Valida. Hay suficiente Segundos entre Pautas\n");

                            contadorPautasEncontradas++;
                            printf("\n\n**** PAUTA ENCONTRADA!! **** Total:[%d]\n\n", contadorPautasEncontradas);

                            for(int i = 0; i < 2; i++ ) {
                                
								printf("::::::||||:::::: Method:[%d](%d , %d) = %f", i, listaPuntos[i].x, listaPuntos[i].y, listaValores[i]);
                            }
                            
                            deltaVariableFramesEnSegundos = duracionPauta;
							posicionFrameEnSegundosAnterior = posicionFrameEnSegundos;

                            Mat img_display;
                            imagenReferencia.copyTo(img_display);

                            for (int i = 0; i < 2; i++) {

                                rectangle(img_display, listaPuntos[i], Point(listaPuntos[i].x + imagenPlantilla.cols, listaPuntos[i].y + imagenPlantilla.rows), listaColores[i], 2, 8, 0);
                                rectangle(result, listaPuntos[i], Point(listaPuntos[i].x + imagenPlantilla.cols, listaPuntos[i].y + imagenPlantilla.rows), listaColores[i], 2, 8, 0);
                            }

                            char archivo[200];

                            int h = (int) posicionFrameEnSegundos / 3600;
                            int m = (int) (posicionFrameEnSegundos % 3600) / 60;
                            int s = (int) (posicionFrameEnSegundos % 3600) % 60;

                            char hora_minuto_segundo[10];
                            sprintf(hora_minuto_segundo, "%.2d-%.2d-%.2d", h, m, s);

                            char texto_prueba[50];
                            sprintf(texto_prueba, "Ubicacion Interna = %.2d:%.2d:%.2d", h, m, s);

                            int baseline = 0;
                            int fontFace = FONT_HERSHEY_PLAIN;
                            double fontScale = 1;
                            int thickness = 2;
                            Size sizeTexto = getTextSize(texto_prueba, fontFace, fontScale, thickness, &baseline);
                            baseline += thickness;

                            Point origenTexto((img_display.cols - sizeTexto.width) / 2, (img_display.rows - sizeTexto.height) / 4);
                            rectangle(img_display, origenTexto, origenTexto + Point(sizeTexto.width, -(sizeTexto.height + 10)), Scalar(0, 0, 255), -1);
                            putText(img_display, texto_prueba, origenTexto, fontFace, fontScale, Scalar::all(255), thickness, 8);

                            sprintf(archivo, "%s%s__pauta_%.2d_T_%s_seg_%d.jpg", pathDirectorioVideos, nombreArchivoVideo, contadorPautasEncontradas, hora_minuto_segundo, posicionFrameEnSegundos);

                            printf("H:M:S -> %s\n", hora_minuto_segundo);
                            printf("ArchivoPauta: %s\n", archivo);

                            imwrite(archivo, img_display);

                        } else {
                            
							printf("\nDeteccion de la misma pauta. No se computa\n");

                        }

                    }

                } else {


                    printf("=============================================\n");
                    printf("La imagen no contiene a la plantilla buscada.\n");
                    printf("=============================================\n\n");

                    deltaVariableFramesEnSegundos = deltaFijoFramesEnSegundos;
                    contadorInternoPosiblePauta = 0;
                }

            } else {

                printf("No se cumple insideROI. Continuamos...\n\n", numFrames);
                deltaVariableFramesEnSegundos = deltaFijoFramesEnSegundos;
                contadorInternoPosiblePauta = 0;
            }

        } else {

            printf("@@@ IMAGEN SIN DIMENSION @@@\n\n\n");
            printf("Finalizamos...\n");
            return 1;

        }

        posicionFrameEnSegundos += deltaVariableFramesEnSegundos;
        printf("Aumentamos posicion en %d segundos. posicionFrameEnSegundos: %d\n\n", deltaVariableFramesEnSegundos, posicionFrameEnSegundos);

        cvSetCaptureProperty(capture, CV_CAP_PROP_POS_MSEC, (double) (1000 * posicionFrameEnSegundos));

        int posFrames = (int) cvGetCaptureProperty(capture, CV_CAP_PROP_POS_FRAMES);
        printf("posFrames: %d\n", posFrames);
        printf("numFrames: %d\n", numFrames);
		//modificamos
        if (posFrames >= numFrames || posicionFrameEnSegundos >= duracionVideo) {

            printf("Sobrepasamos cantidad de Frames del Video. (%d > %d)\n", posFrames, numFrames);
            printf("Finalizamos...\n");
			//system("pause");
            break;

        }

        printf("Antes de cvQueryFrame...\n");
        imgReferencia = cvQueryFrame(capture);

		/*imgPrueba = imgReferencia;
		agregado para probar si obtiene realmente el capture y vemos que si lo hace pero luego ya no quiere entrar	
			cvNamedWindow("imagen", CV_WINDOW_AUTOSIZE); 	// Crea la ventana donde mostrar la imagen
			cvShowImage("imagen", imgPrueba); 			// Muestra la imagen en la ventana.
			cvWaitKey(0); 					// Espera que se pulse una tecla. 
			cvReleaseImage( &imgPrueba ); 			// Retira la imagen de la ventana.
			cvDestroyWindow("imagen");
			system("pause");
		finagregado*/
        printf("imgReferencia:(%d, %d)\n", imgReferencia->width, imgReferencia->height);
        printf("Despues de cvQueryFrame...\n");

    }

    cvReleaseCapture(&capture);

    printf("\n\nPAUTAS_ENCONTRADAS: %d\n\n", contadorPautasEncontradas);
	//system ("pause");
    return 0;
}

bool checkROI() {

    bool flaginsideROI;
    printf("DeltaXY -> (%d, %d)\n", deltaX, deltaY);

    /*
     * 0  SQDIFF
     * 1  SQDIFF NORMED
     * 2  CROSS CORRELATION
     * 3  CROSS CORRELATION NORMED
     * 4  COEFFICIENT
     * 5  COEFFICIENT NORMED
     */
	match_method = CV_TM_SQDIFF; //metodo 0
	MatchingMethod(0, 0);
	flaginsideROI = insideROI();

	if(flaginsideROI){ 

		printf("\nEl metodo 0 cumple la condicion\n");
		printf("\nProbamos el metodo 1\n");
		match_method = 1;
		MatchingMethod(0, 0); 
		flaginsideROI = insideROI();

		if(flaginsideROI){
			
			printf("\nEl metodo ->(1) cumple la condicion ... seguimos\n");
		}
		else{
			
			printf("\nEl metodo ->(1) NO cumple la condicion ... seguimos\n");
		}
	}
	else{
		
		printf("\nNO SE CUMPLE EL ROI\n");
	}

    return flaginsideROI;
}


/**
 * @function MatchingMethod
 * @brief Trackbar callback
 */
void MatchingMethod(int, void*) {

	/// Source image to display
    Mat img_display;
	imagenReferencia.copyTo( img_display );
	Mat templ = imagenPlantilla;
	//
    printf("imagenReferencia:(cols,rows)=(%d,%d)\n", imagenReferencia.cols, imagenReferencia.rows);
    printf("imagenPlantilla:(cols,rows)=(%d,%d)\n", imagenPlantilla.cols, imagenPlantilla.rows);
	// Create the result matrix
    printf("=== result.create ===\n\n");
    int result_cols = imagenReferencia.cols - imagenPlantilla.cols + 1;
    int result_rows = imagenReferencia.rows - imagenPlantilla.rows + 1;
    
    printf("result_cols: %d\n",result_rows );
    printf("result_rows: %d\n", result_cols);
    printf("Antes de result.create()...\n\n\n");
    
    result.create(result_cols, result_rows, CV_32FC1);
    
    printf("Despues de result.create()...\n\n");
    printf("=== result.create ===\n\n");

    /// Do the Matching and Normalize
    matchTemplate(imagenReferencia, imagenPlantilla, result, match_method);
	normalize(result, result, 0, 1, NORM_MINMAX, -1, Mat());

    /// Localizing the best match with minMaxLoc
    double minVal;
    double maxVal;
    Point minLoc;
    Point maxLoc;
    Point matchLoc;

    minMaxLoc(result, &minVal, &maxVal, &minLoc, &maxLoc, Mat());

	/// For SQDIFF and SQDIFF_NORMED, the best matches are lower values. For all the other methods, the higher the better
    if (match_method == CV_TM_SQDIFF || match_method == CV_TM_SQDIFF_NORMED) {
        //simpre elige este metodo por eso lleva los valores mas pequeños
		matchLoc = minLoc;
    } else {
        
		matchLoc = maxLoc;
    }

    printf("method:(%d), matchLoc:(%d,%d)\n", match_method, matchLoc.x, matchLoc.y);
	
    listaPuntos[match_method] = matchLoc;
    //aca falla el programa, averiguar porque las dimensiones son demasiado pequeñas en el primer caso de 10 y 34. tienenq q ser mayor a las delta x,y
    //listaValores[match_method] = result.at<double>((int)matchLoc.x, (int)matchLoc.y);
	printf("inicio:(%d)(%d), fin:(%d,%d)\n", margenSeguridadArriba.x, margenSeguridadArriba.y, margenSeguridadAbajo.x, margenSeguridadAbajo.y);
	rectangle( img_display, margenSeguridadArriba, margenSeguridadAbajo, Scalar(0,255,255), 2, 10, 0 );

	rectangle( img_display, matchLoc, Point( matchLoc.x + templ.cols , matchLoc.y + templ.rows ), Scalar::all(0), 2, 8, 0 );
    rectangle( result, matchLoc, Point( matchLoc.x + templ.cols , matchLoc.y + templ.rows ), Scalar::all(0), 2, 8, 0 );

	//comentar
    /*imshow( image_window, img_display );
    imshow( result_window, result );
	waitKey(1);
	system("pause");*/

    return;
}

void PrintMat(CvMat *A) {
    int i, j;
    for (i = 0; i < A->rows; i++) {
        printf("\n");
        switch (CV_MAT_DEPTH(A->type)) {
            case CV_32F:
            case CV_64F:
                for (j = 0; j < A->cols; j++)
                    printf("%8.3f ", (float) cvGetReal2D(A, i, j));
                break;
            case CV_8U:
            case CV_16U:
                for (j = 0; j < A->cols; j++)
                    printf("%6d", (int) cvGetReal2D(A, i, j));
                break;
            default:
                break;
        }
    }
    printf("\n");
}

bool puntosIguales() {

	int contador = 0;
    Point puntoInicial = listaPuntos[0];
    printf("PuntoInicial:(%d,%d)\n", puntoInicial.x, puntoInicial.y);
    
	for (int i = 1; i < 2; i++) {
        
		printf("Punto:(%d,%d)\n", listaPuntos[i].x, listaPuntos[i].y);
		
        if(listaPuntos[i] == puntoInicial) {
            
			printf("Puntos Iguales...\n\n\n");
			contador++;
        } else {

			break;
		}
    }

	if(contador == 1)
		return true;
	else		
		return false;
}

bool insideROI(){
	
	int error = verificarPosicion();
	bool inside = false;

	printf("mirar ->(%d)", error);

	switch( error ){
		
		case 1:
			margenSeguridadArriba.x = 0;
			margenSeguridadArriba.y = 0;
			insideROI();
			break;
		case 2:
			margenSeguridadAbajo.x = (int)(margenSeguridadAbajo.x*0.99);
			margenSeguridadAbajo.y = (int)(margenSeguridadAbajo.y*0.96);
			insideROI();
			break;
		case 3:
			break;
		case 0:
			inside = true;
			break;
	}

	return inside;
}

int verificarPosicion(){

	//control de margen para arriba que se encuentre en el cuadro de imagen del video error 1
	if( ( ( margenSeguridadArriba.x ) >= 0 ) && ( ( margenSeguridadArriba.y ) >= 0 ) ){
		//control de margen para abajo que se encuentre en el cuadro de imagen del video
		if( ( margenSeguridadAbajo.x <= imagenReferencia.cols ) && ( margenSeguridadAbajo.y <= imagenReferencia.rows ) ){
			//control de margen para arriba que se encuentre en el cuadrado virtual de seguridad
			if( ( margenSeguridadArriba.x <= listaPuntos[match_method].x ) && ( margenSeguridadArriba.y <= listaPuntos[ match_method ].y ) ){
				//control de margen para abajo que se encuentre en el cuadrado virtual de seguridad
				if( margenSeguridadAbajo.x >= ( listaPuntos[match_method].x + imagenPlantilla.cols ) 
					&& margenSeguridadAbajo.y >= ( listaPuntos[match_method].y + imagenPlantilla.rows ) )
					return 0;
				else
					return 3;
			}else{
				return 3;
			}	
		}else{
			return 2;
		}
	}else{
		return 1;
	}
}