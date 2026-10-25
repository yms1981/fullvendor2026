<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class CompresionImage extends CI_Model {
    function __construct() 
    {

    }

    public function comprimir($url_redirect){        
        set_time_limit(2400); // Aumenta el límite de tiempo a 300 segundos (5 minutos)

        if (!file_exists('./uploads/company_comprimido/categories')){
			mkdir('uploads/company_comprimido/categories');			
		}

		$directorio = 'uploads/categories';

		// Verifica si el directorio existe
		if (is_dir($directorio)) {
			// Escanea el directorio y obtén la lista de elementos
			$elementos = scandir($directorio);

			// Filtra solo los directorios (excluye . y ..)
			$carpetas = array_filter($elementos, function ($elemento) use ($directorio) {
				return is_dir($directorio . '/' . $elemento) && $elemento != '.' && $elemento != '..';
				//return is_dir($directorio . '/' . $elemento) && !in_array($elemento, ['.', '..']);
			});

			// Cuenta la cantidad de carpetas
			$cantidad_carpetas = count($carpetas);

			// Imprime la cantidad de carpetas
			echo "Número de carpetas en '$directorio': $cantidad_carpetas <br>";
			
			foreach ($carpetas as $key => $id_comp) {
								
				$rutaDirectorioComprimido = 'uploads/company_comprimido/categories/'.$id_comp;
				$rutaDirectorio = 'uploads/categories/'. $id_comp;
				
				if (!file_exists('./'. $rutaDirectorioComprimido)){					
					mkdir($rutaDirectorioComprimido);
				}
				
				// Obtener la lista de archivos en el directorio
				$archivos = scandir($rutaDirectorio);

				// Filtrar archivos para excluir los directorios . y ..
				$archivos = array_diff($archivos, array('..', '.'));
				$nuevaCalidad = 75;
				// Mostrar los nombres de las imágenes
				echo "Nombres de las imágenes en el directorio:<br>";
			
				foreach ($archivos as $imagen) {

					$extension = pathinfo($imagen, PATHINFO_EXTENSION);
					// Verificar si la extensión indica que es una imagen
					//if (in_array(strtolower($extension), array('jpeg'))) {
					if (in_array(strtolower($extension), array('jpg', 'jpeg', 'png'))) {
						
						// Ruta de la imagen original
						$rutaImagenOriginal = $rutaDirectorio . '/' . $imagen;

						// Cargar la imagen original
						$imagenOriginal = imagecreatefromstring(file_get_contents($rutaImagenOriginal));
				
						// Crear una nueva imagen comprimida
						$imagenComprimida = imagecreatetruecolor(imagesx($imagenOriginal), imagesy($imagenOriginal));
				
						// Copiar la imagen original en la nueva imagen con compresión
						imagecopyresampled($imagenComprimida, $imagenOriginal, 0, 0, 0, 0, imagesx($imagenOriginal), imagesy($imagenOriginal), imagesx($imagenOriginal), imagesy($imagenOriginal));
				
						// Ruta de la imagen comprimida
						$rutaImagenComprimida = $rutaDirectorioComprimido . '/' . $imagen;
                        
						// Guardar la imagen comprimida (ajusta según sea necesario)
						imagejpeg($imagenComprimida, $rutaImagenComprimida, 40); // El último parámetro (80) es la calidad del JPEG (ajusta según sea necesario)
						
						// Liberar memoria
						imagedestroy($imagenOriginal);
						imagedestroy($imagenComprimida);
				
						echo $imagen . " comprimida y guardada exitosamente.<br>";

					}
				}

			}

		} else {
			echo "El directorio '$directorio' no existe.";
		}
    }

    public function comprimir_directory($dir, $pro_id){
                
        $images_pro = $this->ProductModel->get_imagesNotComprimed($pro_id);
        
        
        if (count($images_pro) > 0) {
            // echo json_encode($images_pro);
            
            $image_column = array_column($images_pro , "images") ;
            // Directorio donde se encuentran las imágenes
            // $rutaDirectorio = 'uploads/company';
    
            $rutaDirectorio = $dir;
    
            //$rutaDirectorioComprimido = 'uploads/company_comprimido';
            $rutaDirectorioComprimido = $dir;
    
            // Obtener la lista de archivos en el directorio
            $archivos = scandir($rutaDirectorio);
    
            // Filtrar archivos para excluir los directorios . y ..
            $archivos = array_diff($archivos, array('..', '.'));
            $nuevaCalidad = 75;
            // Mostrar los nombres de las imágenes
            // echo "Nombres de las imágenes en el directorio:<br>";
            
            foreach ($archivos as $imagen) {
                // validar si ya fue comprimida que no se vuelva a comprimir            
                if (in_array($imagen, $image_column)) {                
                    $extension = pathinfo($imagen, PATHINFO_EXTENSION);
                    // Verificar si la extensión indica que es una imagen
                    //if (in_array(strtolower($extension), array('jpeg'))) {
                    if (in_array(strtolower($extension), array('jpg', 'jpeg', 'png'))) {
                        
                        // Ruta de la imagen original
                        $rutaImagenOriginal = $rutaDirectorio . '/' . $imagen;
        
                        // Cargar la imagen original
                        $imagenOriginal = imagecreatefromstring(file_get_contents($rutaImagenOriginal));
                
                        // Crear una nueva imagen comprimida
                        $imagenComprimida = imagecreatetruecolor(imagesx($imagenOriginal), imagesy($imagenOriginal));
                
                        // Copiar la imagen original en la nueva imagen con compresión
                        imagecopyresampled($imagenComprimida, $imagenOriginal, 0, 0, 0, 0, imagesx($imagenOriginal), imagesy($imagenOriginal), imagesx($imagenOriginal), imagesy($imagenOriginal));
                
                        // Ruta de la imagen comprimida
                        $rutaImagenComprimida = $rutaDirectorioComprimido . '/' . $imagen;
                
                        // Guardar la imagen comprimida (ajusta según sea necesario)
                        imagejpeg($imagenComprimida, $rutaImagenComprimida, 40); // El último parámetro (80) es la calidad del JPEG (ajusta según sea necesario)
                        
                        // Liberar memoria
                        imagedestroy($imagenOriginal);
                        imagedestroy($imagenComprimida);
                
                        // echo $imagen . " comprimida y guardada exitosamente.<br>";
        
                    }
                }        
            }

            foreach ($images_pro as $key => $image) {
                
                $this->mymodel->update(array(
                    'comprimed' => 1),
                    'product_images',
                    array(
                    'img_id'=>$image->img_id));
            }


        }

    }

}
	

