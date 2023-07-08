<?php
    error_reporting(0);

    if($_POST){
        if(isset($_POST['dir'])){
            contenido($_POST['dir']);
        }else if(isset($_POST['inputpelicula'])){
            moviedescripcion($_POST['inputpelicula'],$_POST['rutapelicula']);
        }
        if($_POST['log']){
            RegistroLog('Play '.$_POST['log']);
        }
    }elseif(!$_GET){
        ProgressBar();
        RegistroLog('');
    }elseif(isset($_GET['ok'])){
        contenido('./peliculas');
    }


    function listar_directorios($ruta='./'){
        
        $menu = '<br>
        <div  class="superponer">
            <form action="#" id="enrutador" class="navbar" method="post">
                <input id="ruta" name="dir" type="hidden" value="">
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
        
        $menu .= '
                    <input class="rounded btn-menu single-line" type="text" id="search-text" style="width:300px; color:#20ffc4b6;" placeholder="Buscar..." autocomplete="off"/> &nbsp;&nbsp;
                    <span class="rounded" style="color:#c0c0c0; float:right;">
                        '.UsuariosOnline().' 
                        <span class="material-symbols-rounded">emoji_people</span> &nbsp;&nbsp;
                    </span>
                </form>
            </div>';
        echo $menu;
    }


    function contenido($dir='./peliculas'){
        date_default_timezone_set('America/Mexico_City');
        ini_set('max_execution_time', '300');
        listar_directorios();
                
        if($dir=="." or $dir=="./"){
            echo '
            <div class="superponer" style="color:#20ffc4be;">
                <center>
                <h2><b>Bienvenido a Movies Direct</b></h2>
                <br>
                <h3>
                    Aquí podrás administrar y ver tus películas almacenadas localmente. <br>
                    Además, de transmitir en la red local y ver información detallada de tus películas favoritas.<br>
                    ¡Disfruta de la experiencia cinematográfica en casa con Movies Direct!<br><br>
                    Utiliza la siguiente dirección para acceder desde otro equipo <b>http://'.ObtieneIP().'/md<b>
                </h3>
                <h4>
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
            return;
        }
        
        // Escanea el directorio y obtiene los nombres de los archivos
        $files = scandir($dir);
        // Crea un array asociativo con los nombres de los archivos y sus fechas de modificación
        $files_with_dates = array();
        foreach ($files as $file) {
            // Ignora los archivos ocultos y los directorios
            if ($file[0] != "." /*&& !is_dir($dir . "/" . $file)*/) {
            // Obtiene la fecha de modificación del archivo en formato Unix
            $date = filemtime($dir . "/" . $file);
            // Añade el nombre del archivo y la fecha al array asociativo
            $files_with_dates[$file] = $date;
            }
        }
        
        // Ordena el array asociativo por los valores de las fechas, de mayor a menor
        arsort($files_with_dates);
                
        if(isset($files_with_dates) and $files_with_dates!=''){
        
            $contenido ='
                <form id="formovie" action="#" method="post">
                <input type="hidden" name="inputpelicula" id="inputpelicula" value="" />
                <input type="hidden" name="rutapelicula" id="rutapelicula" value="" />
                </form>
            <br><br>
            <main class="cards" id="peliculas">
        ';
            foreach ($files_with_dates as $file => $date)
            {
                $ruta="http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
                $url = dirname($ruta);

                if(is_dir($dir.'/'.$file)){
                    $nombre = $file;
                    $extension = 'folder';
                }else{
                    $nombre = substr($file,0,-4);
                    $extension = substr(strtolower($file), -3);
                }
        
                if (($extension=="mp4")or($extension=="mpg")or($extension=="avi")or($extension=="mov")or($extension=="mkv")){
                    $tipo='video';
                }else{
                    $tipo='video';
                }
        
                if ($tipo<>''){
                    if($tipo=='video'){
                        if (file_exists('files/md/json/'.$nombre.'.json')){
                            $result_url = file_get_contents( 'files/md/json/'.$nombre.'.json' );
                            $arr = json_decode($result_url);
                        }else{
                            //cuando es la primera carga de los archivos descargara todo en local
                            $arr = RequestTMDB($dir,$nombre);
                        }
                    }
                }
        
                if(isset($arr->original_title) or isset($arr->original_name)){
                    
                    if($extension=='folder'){
                        $contenido .='
                        <a href="#" class="superponer" onclick="
                            document.getElementById(\'ruta\').value=\''.$dir.'/'.$nombre.'\'; 
                            document.getElementById(\'enrutador\').submit();
                        ">
                            <article class="card">
                                <img class="poster rounded" src="files/md/poster'.$arr->poster_path.'" alt="'.$arr->title.'">
                                <p class="text single-line">
                                    '.$arr->title.$arr->original_name.'
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
                                <img class="poster rounded" src="files/md/poster'.$arr->poster_path.'" alt="'.$arr->title.'">
                                <p class="text single-line">
                                    '.$arr->title.$arr->original_name.'
                                </p>
                            </article>
                        </a>
                        ';
                    }
                    
                    
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
                <br><br><br><br>
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
                        
        }else{
            echo '<br><br><center><h2 style="color:#20ffc4be;">No hay contenido</h2><center>';
        }
        RegistroLog('Accedio a '.$dir);
    }
    
    function moviedescripcion($pelicula=null, $ruta=null){
                
        if($pelicula==null){
            contenido('./peliculas');
        }
        
        RegistroLog('Accedio a '.$ruta);
        
        listar_directorios();        
        if (file_exists('files/md/json/'.$pelicula.'.json')){
            $result_url = file_get_contents( 'files/md/json/'.$pelicula.'.json' );
            $arr = json_decode($result_url);
        }
        
        if(isset($arr->original_title) or isset($arr->original_name)){
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
                                    <a href="javascript:history.back()" class="btn-menu single-line rounded" target="_self">
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
                                    Titulo original: '.$arr->original_title.$arr->original_name.' <br>
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
                            
                            function logplay(ruta){
                                $.ajax({
                                    type: "POST",
                                    url: "files/cont/content.php",
                                    data: { log: "Reproduciendo "+ruta }
                                });
                            }
                        </script>
            ';
        }else{
            $contenido ='
                    <div class="cardinfo superponer">
                        <div class="player">
                            <p class="titulo">
                                <b>'.$pelicula.'</b>
                            </p>
                            <video width="100%" height="auto" id="VideoPlayer" poster="files/img/backdrop.jpg" controls preload="none">
                                <source src="'.$ruta.'">
                                Tu navegador no soporta video HTML5 .
                            </video>
                            <center>
                                <br>
                                <a href="javascript:history.back()" class="btn-menu single-line rounded" target="_self">
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
                            background-image: url("files/img/backdrop.jpg");
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
        
        if(!is_dir('files/logs')){
            mkdir("files/logs", 0777);
        }
        
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
    
    function RequestTMDB($dir,$nombre){
        //valida la estructura de carpetas
        if(!is_dir('files/md')){
            mkdir("files/md", 0777);
            mkdir("files/md/json", 0777);
            mkdir("files/md/poster", 0777);
            mkdir("files/md/backdrop", 0777);
        }
        
        if($dir=='peliculas' or $dir=='./peliculas'){
            $tipo='movie';
        }else{
            $tipo='tv';
        }
        $result_url = file_get_contents('https://api.themoviedb.org/3/search/'.$tipo.'?language=es-MX&api_key=cb05d4f190724a88b8fd401d539912cb&query='.str_replace(' ', '+', $nombre));
        $arr = json_decode($result_url);
        
        if(isset($arr->results[0]->poster_path)){
            $jsonfile = json_encode($arr->results[0]);
            
            //guarda archivo JSON con la informacion
            $nombreArchivo = 'files/md/json/'.$nombre.'.json';
            $archivo = fopen($nombreArchivo, "w");
            fwrite($archivo, $jsonfile);
            fclose($archivo);
            
            //guarda Poster de la pelicula
            $poster = file_get_contents( 'https://image.tmdb.org/t/p/w300'.$arr->results[0]->poster_path );
            $nombreArchivo = 'files/md/poster/'.$arr->results[0]->poster_path;
            $archivo = fopen($nombreArchivo, "w");
            fwrite($archivo, $poster);
            fclose($archivo);
            
            //guarda Backdrop de la pelicula
            $backdrop = file_get_contents( 'https://image.tmdb.org/t/p/original'.$arr->results[0]->backdrop_path );
            $nombreArchivo = 'files/md/backdrop/'.$arr->results[0]->backdrop_path;
            $archivo = fopen($nombreArchivo, "w");
            fwrite($archivo, $backdrop);
            fclose($archivo);
        }
        
        if (file_exists('files/md/json/'.$nombre.'.json')) {
            $result_url = file_get_contents( 'files/md/json/'.$nombre.'.json' );
            $arr = json_decode($result_url);
            return $arr;
        }
    }
    
    Function ProgressBar($folder='./peliculas'){
        $IP = ObtieneIP();
        
        $peliculas = count(scandir($folder));
        if(file_exists('./files/md/json')){
            $archivosjson = count(scandir('./files/md/json'));
        }else{
            $archivosjson=0;
        }
        $restantes=$peliculas-$archivosjson;
        
        if($restantes==0){
            echo '
            <script>
                window.location.href = "http://'.$IP.'/md/?ok";
            </script>
            }';
        }else{            
            
            $total=$restantes*6;
                //muestra una barra de progreso mientras indexa los archivos
                echo '
                <style>
                    #myProgress {
                    width: 100%;
                    height: 31px;
                    background-color: #202023;
                    border: 1px solid #04AA6D;
                    border-radius:5px;
                    }

                    #myBar {
                    width: 1%;
                    height: 30px;
                    border-radius:5px;
                    background-color: #04AA6D;
                    float:left;
                    }
                </style>

                <div class="superponer" style="color:#20ffc4be;">
                    <center>
                    <h2><b>Bienvenido a Movies Direct</b></h2>
                    <br>
                    <h3>
                        Aquí podrás administrar y ver tus películas almacenadas localmente. <br>
                        Además, de transmitir en la red local y ver información detallada de tus películas favoritas.<br>
                        ¡Disfruta de la experiencia cinematográfica en casa con Movies Direct!<br><br>
                        Utiliza la siguiente dirección para acceder desde otro equipo <b>http://'.$IP.'/md<b>
                    </h3>
                    <h4>
                        Si deseas agregar categorias o series puedes crear carpetas en raiz, estas apareceran en el menu superior,
                        <br>
                        dentro de cada carpeta puedes poner tus archivos de video para que sean reconocidos.
                        <br>
                        Formatos reconocidos *.mp4, *.mpg, *.avi, *.mov, *.mkv
                    </h4>
                    <h6>
                        Este es un sistema de entretenimiento creado sin fines de lucro por www.creamoscodigo.com
                    </h6>
                    
                    <br><br>
                    <div style="width:500px;">
                        indexando '.$restantes.' archivos <br>
                        <div id="myProgress">
                            <div id="myBar"></div>
                        </div>
                    </div>
                    
                    </center>
                </div>
                
                <style>
                    .fondobg {
                        background-image: url("files/img/backdrop.jpg");
                    }
                </style>
                <script>
                    var i = 0;
                    function move() {
                    if (i == 0) {
                        i = 1;
                        var elem = document.getElementById("myBar");
                        var width = 1;
                        var id = setInterval(frame, '.$total.');
                        function frame() {
                            if (width >= 100) {
                                clearInterval(id);
                                i = 0;
                            } else {
                                width++;
                                elem.style.width = width + "%";
                            }
                        }
                    }
                    }
                    move();
                    window.location.href = "http://'.$IP.'/md/?ok";
                </script>
            ';
        }
    }
    
    function ObtieneIP(){
        if(!is_dir('files/logs/')){
            mkdir("files/logs/", 0777);
        }
        
        if (file_exists('files/logs/server.txt')){
            $IP = file_get_contents( 'files/logs/server.txt' );
        }else{
            //guarda la ip del servidor
            $IP = file_get_contents( 'https://api.ipify.org/' );
            $nombreArchivo = 'files/logs/server.txt';
            $archivo = fopen($nombreArchivo, "w");
            fwrite($archivo, $IP);
            fclose($archivo);
        }
        return $IP;
    }
    
    function UsuariosOnline(){
        date_default_timezone_set('America/Mexico_City');
        RegistroLog('');
        /*  OBTENEMOS FECHA Y HORA ACTUAL Y LE RESTAMOS 10 MINUTOS PARA HACER LA COMPARACION **PENDIENTE
        $mifecha = new DateTime(); 
        $fechaactual = $mifecha->format('d-m-Y H:i');
        $mifecha->modify('-10 minute'); 
        $fechacompara = $mifecha->format('d-m-Y H:i');
        */
        
        $dir = 'files/logs/';
        $files = scandir($dir);
        $usuarios = 0;
        foreach ($files as $file) {
            if ($file[0] != "." && !is_dir($dir . "/" . $file)) {
                if(date ("Y-m-d H",time()) == date ("Y-m-d H", filemtime($dir . "/" . $file))){
                    $usuarios ++;
                }
            }
        }
        return $usuarios;
    }
;?>