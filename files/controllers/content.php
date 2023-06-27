<?php
    error_reporting(0);
    
    if($_POST){
        if(isset($_POST['dir'])){
            contenido($_POST['dir']);
        }else if(isset($_POST['inputpelicula'])){
            moviedescripcion($_POST['inputpelicula'],$_POST['rutapelicula']);
        }
    }else{
        RegistroLog('');
        contenido('./peliculas');
    }


    function listar_directorios($ruta='./'){
        
        $menu = '<br>
        <div  class="navbar superponer">
            <form action="#" method="post">
                <button class="rounded btn-menu single-line" name="dir" value="./" onclick="this.submit()" style="width:auto;"><span class="material-symbols-rounded">home</span> Inicio</button>
        ';
        
        if (is_dir($ruta)) {
            if ($dh = opendir($ruta)) {
                    while (($file = readdir($dh)) !== false) {
                        if (is_dir($ruta . $file) && $file!="." && $file!=".." && $file!=".htdocs" && $file!="vendor" && $file!="files"){
                            $menu .= '<button class="rounded btn-menu single-line" name="dir" value="'.$file.'" style="width:auto;"><span class="material-symbols-rounded">movie</span> '.ucwords($file).'</button>'."\n";
                        }
                    }
            closedir($dh);
            }
        }
        date_default_timezone_set('America/Mexico_City');
        $menu .= '
                <input class="rounded btn-menu single-line" type="text" id="search-text" style="width:300px; color:#20ffc4b6;" placeholder="Buscar..."/><br>
                </form>
            </div>';
        echo $menu;
    }


    function contenido($dir='./peliculas'){
        date_default_timezone_set('America/Mexico_City');
        listar_directorios();
        $directorio = opendir($dir);
        $i = 1;
        while ($archivo = readdir($directorio)) {

            if ($archivo=="." || $archivo==".." || $archivo=="index.php" || $archivo==".htdocs" || $archivo=="vendor" || $archivo=="files" || $archivo=="peliculas") { 
                echo " "; 
            } else {
                $archivos[$archivo] = $archivo;
                
                //Obtenemos la ultima fecha de adicion de peliculas
                $file_last_modified = filemtime($dir.'/'.$archivo);
                $last_modified = date('m/d/Y H:i:s', $file_last_modified);
                $ultimas[$i] = array($last_modified,$dir,$archivo);
                
                $i++;
            }
        }
        
        if($dir=="." or $dir=="./"){
            echo '
            <div class="content superponer" style="color:#20ffc4be;">
                <center>
                <h2><b>Bienvenido a Movies Direct</b></h2>
                <br>
                <h3>
                    Aquí podrás administrar y ver tus películas almacenadas localmente. <br>
                    Además, de transmitir en la red local y ver información detallada de tus películas favoritas.<br>
                    ¡Disfruta de la experiencia cinematográfica en casa con Movies Direct!
                </h3>
                <h4>
                    Para acceder desde otro equipo en la misma red ingresa desde esta ruta http://'.$_SERVER['SERVER_ADDR'].'/md
                    <br>
                    Si deseas agregar categorias o series puedes crear carpetas en raiz, estas apareceran en el menu superior,
                    <br>
                    dentro de cada carpeta puedes poner tus archivos de video para que sean reconocidos.
                    <br>
                    Formatos reconocidos *.mp4, *.mpg, *.avi, *.mov, *.mkv
                </h4>
                <h6>
                    Este es un sistema de entretenimiento creado sin fines de lucro por www.creamoscodigo.com
                </h6>
                </center>
            </div>
            
            <style>
                .fondobg {
                    background-image: url("files/img/backdrop.jpg");
                }
            </style>
            <script>
              document.getElementById("search-text").style.display = "none";
            </script>
            ';
            $archivos='inicio';
            RegistroLog('Accedio a inicio');
        }

        if(isset($archivos) and $archivos!=''){

            $contenido ='
                <form id="formovie" action="#" method="post">
                <input type="hidden" name="inputpelicula" id="inputpelicula" value="" />
                <input type="hidden" name="rutapelicula" id="rutapelicula" value="" />
                </form>
            <br><br>
            <main class="cards" id="peliculas">
        ';
            
            ksort ($archivos);
            
            foreach ($archivos as $archivo)
            {
    
                $ruta="http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
                $url = dirname($ruta);
        
                $nombre = substr($archivo,0,-4);
                $extension = substr(strtolower($archivo), -3);
    
                if (($extension=="mp4")or($extension=="mpg")or($extension=="avi")or($extension=="mov")or($extension=="mkv")){
                    $tipo='video';
                }else{
                    $tipo='';
                }
    
                if ($tipo<>''){
                    if($tipo=='video'){
                        if (file_exists('files/md/json/'.$nombre.'.json')){
                            $result_url = file_get_contents( 'files/md/json/'.$nombre.'.json' );
                            $arr = json_decode($result_url);
                        }else{
                            //cuando es la primera carga de los archivos descargara todo en local
                            $arr = RequestTMDB($nombre);
                        }
                    }
                }
                    

                if(isset($arr->original_title)){
                    $contenido .='
                    <a href="#" class="superponer" onclick="
                        document.getElementById(\'inputpelicula\').value=\''.$nombre.'\'; 
                        document.getElementById(\'rutapelicula\').value=\''.$dir.'/'.$nombre.'.'.$extension.'\'; 
                        document.getElementById(\'formovie\').submit();
                    ">
                        <article class="card">
                            <img class="poster rounded" src="files/md/poster'.$arr->poster_path.'" alt="'.$arr->title.'">
                            <p class="text single-line">
                                '.$arr->title.'
                            </p>
                        </article>
                    </a>
                    ';
                    
                }else{
                    $contenido .='
                        <a href="#" class="superponer" onclick="
                            document.getElementById(\'inputpelicula\').value=\''.$nombre.'\'; 
                            document.getElementById(\'rutapelicula\').value=\''.$dir.'/'.$nombre.'.'.$extension.'\'; 
                            document.getElementById(\'formovie\').submit();
                        ">
                            <article class="card">
                                <img class="poster rounded" src="files/img/poster.png" alt="poster">
                                <p class="text single-line">
                                    '.$nombre.'
                                </p>
                            </article>
                        </a>
                    ';
                }    
            }
            
            $contenido.='</main>
                <style>
                    .fondobg {
                        background-image: url("files/img/backdrop.jpg");
                    }
                </style>
                <script>
                    function filterDivs(searchText) {
                      var peliculas = document.getElementById("peliculas");
                      // Get all the a elements on the page
                      var divs = peliculas.querySelectorAll("article");

                      // Loop through each div element
                      for (var i = 0; i < divs.length; i++) {
                          // Get the text content of the div element
                          var text = divs[i].textContent;

                          // Check if the text content contains the search text
                          if (text.toUpperCase().indexOf(searchText) !== -1) {
                          // If it does, then show the div element
                          divs[i].style.display = "block";
                          } else {
                          // Otherwise, hide the div element
                          divs[i].style.display = "none";
                          }
                      }
                    }

                    // Create an input text element
                    var inputText = document.getElementById("search-text");

                    // Add an event listener to the input text element
                    inputText.addEventListener("input", function() {
                    // Get the text content of the input text element
                    var searchText = this.value.toUpperCase();

                    // Filter the div elements based on the search text
                    filterDivs(searchText);
                    });
                </script>
            ';
            
            echo $contenido;
            
            RegistroLog('Accedio al catalogo de peliculas');
                        
        }else{
            echo '<br><br><center><h2 style="color:#20ffc4be;">No hay contenido</h2><center>';
        }
    }
    
    function moviedescripcion($pelicula=null, $ruta=null){
        if($pelicula==null){
            contenido('./peliculas');
        }
        
        RegistroLog('Accedio a la pelicula '.$pelicula);
        
        listar_directorios();        
        if (file_exists('files/md/json/'.$pelicula.'.json')){
            $result_url = file_get_contents( 'files/md/json/'.$pelicula.'.json' );
            $arr = json_decode($result_url);
        }
        
        if(isset($arr->original_title)){
            $contenido ='
                        <div class="cardinfo superponer">
                            <div class="player">
                                <p class="titulo">
                                    <b>'.$arr->title.'</b>
                                </p>
                                <video width="100%" height="auto" id="VideoPlayer" poster="files/md/backdrop'.$arr->backdrop_path.'" controls preload="none">
                                    <source src="'.$ruta.'">
                                    Tu navegador no soporta video HTML5 .
                                </video>
                                <center>
                                    <br>
                                    <a href="'.$dir.'" class="btn-menu single-line rounded" target="_self">
                                    <span class="material-symbols-rounded">arrow_back_ios</span>Regresar
                                    </a>
                                    &nbsp;&nbsp;&nbsp;
                                    <a href="'.$ruta.'" class="btn-menu single-line rounded" target="_blank" Download>
                                        <span class="material-symbols-rounded">download</span>Descargar
                                    </a>
                                </center>
                            </div>

                            <div class="info">
                                <p class="datos">
                                    Titulo original: '.$arr->original_title.' <br>
                                    Lenguaje original: '.$arr->original_language.' <br>
                                    Año de publicación: '.$arr->release_date.'<br>
                                </p>
                                
                                <p class="descripcion">
                                    '.$arr->overview.'
                                </p>
                            </div>
                        </div>
                        
                        <style>
                            .fondobg {
                                background-image: url("files/md/backdrop'.$arr->backdrop_path.'");
                            }
                        </style>
                        <script>
                        document.getElementById("search-text").style.display = "none";
                        </script>
            ';
        }else{
            $contenido ='
                    <div class="cardinfo superponer">
                        <div class="player">
                            <p class="titulo">
                                <b>'.$pelicula.'</b>
                            </p>
                            <video width="100%" height="auto" id="VideoPlayer" poster="files/img/poster.png" controls preload="none">
                                <source src="'.$ruta.'">
                                Tu navegador no soporta video HTML5 .
                            </video>
                            <center>
                                <br>
                                <a href="'.$dir.'" class="btn-menu single-line rounded" target="_self">
                                <span class="material-symbols-rounded">arrow_back_ios</span>Regresar
                                </a>
                                &nbsp;&nbsp;&nbsp;
                                <a href="'.$ruta.'" class="btn-menu single-line rounded" target="_blank" Download>
                                    <span class="material-symbols-rounded">download</span>Descargar
                                </a>
                            </center>
                        </div>
                    </div>
                    
                    <style>
                        .fondobg {
                            background-image: url("files/md/backdrop'.$arr->backdrop_path.'");
                        }
                    </style>
                    <script>
                    document.getElementById("search-text").style.display = "none";
                    </script>
            ';
        }
        
            echo $contenido;
    }
    
    function RegistroLog($accion=''){
        date_default_timezone_set('America/Mexico_City');
        
        $nombreArchivo = 'files/logs/'.$_SERVER["REMOTE_ADDR"].'.txt';
        
        if (!file_exists($nombreArchivo)){
            //info del equipo que se conecta
            $info = 'IP: '.$_SERVER["REMOTE_ADDR"]."\n".
                    'OS: '.$_SERVER['HTTP_USER_AGENT']."\r\n".
                    'Primer ingreso: '.date("Y-m-d H:i:s")."\n\n";
            
            $archivo = fopen($nombreArchivo, "w");
            fwrite($archivo, $info);
            fclose($archivo);
        }elseif($accion!=''){
            //Agrega info al archivo
            $info = date("Y-m-d H:i:s").' '.$accion."\n";
            file_put_contents($nombreArchivo, $info,  FILE_APPEND | LOCK_EX);
        }
        
    }
    
    function RequestTMDB($nombre){
        $result_url = file_get_contents('https://api.themoviedb.org/3/search/movie?language=es-MX&api_key=cb05d4f190724a88b8fd401d539912cb&query='.str_replace(' ', '+', $nombre));
        $arr = json_decode($result_url);
        $jsonfile = json_encode($arr->results[0]);
        
        if(isset($arr->results[0])){
            //guarda archivo JSON con la informacion
            $nombreArchivo = 'files/md/json/'.$nombre.'.json';
            $archivo = fopen($nombreArchivo, "w");
            fwrite($archivo, $jsonfile);
            fclose($archivo);
            
            //guarda Poster de la pelicula
            $poster = file_get_contents( 'https://image.tmdb.org/t/p/w300/'.$arr->results[0]->poster_path );
            $nombreArchivo = 'files/md/poster/'.$arr->results[0]->poster_path;
            $archivo = fopen($nombreArchivo, "w");
            fwrite($archivo, $poster);
            fclose($archivo);
            
            //guarda Backdrop de la pelicula
            $poster = file_get_contents( 'https://image.tmdb.org/t/p/original/'.$arr->results[0]->backdrop_path );
            $nombreArchivo = 'files/md/backdrop/'.$arr->results[0]->backdrop_path;
            $archivo = fopen($nombreArchivo, "w");
            fwrite($archivo, $poster);
            fclose($archivo);
        }
        
        $result_url = file_get_contents( 'files/md/json/'.$nombre.'.json' );
        $arr = json_decode($result_url);
        return $arr;
    }
;?>