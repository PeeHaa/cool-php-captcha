<?php
/**
 * Script para la generaci�n de CAPTCHAS
 *
 * @author Jos� Rodr�guez <jose.rodriguez@exec.cl>
 * http://joserodriguez.cl
 * http://www.exec.cl
 *
 * En caso que hagas uso de este c�digo o una variaci�n,
 * te pido que me lo hagas saber.
 *
 */



// Alto y ancho de la imagen
$width  = 200;
$height = 66;

// Nombre de la variable de sesion
$session_var = "captcha";

// Colores
$colors = array(
   array(27,78,181), // azul
   array(22,163,35), // verde
   array(214,36,7),  // rojo
);

/**
* Configuraci�n de tipograf�as
* - font: archivo TTF
* - condensation: cantidad de pixeles que se juntar� cada caracter
* - minSize: tama�o minimo del texto
* - maxSize: tama�o m�ximo del texto
*/
$fonts = array(
    array('font' => 'TimesNewRomanBold.ttf','condensation' => 2,   'minSize' => 28, 'maxSize' => 40),


   /*
   array('font' => 'Danoisemedium.ttf','condensation' => 2,   'minSize' => 28, 'maxSize' => 40),
   array('font' => 'HEINEKEN.TTF',     'condensation' => 2.5, 'minSize' => 24, 'maxSize' => 40),
   array('font' => 'VeraSeBd.ttf',     'condensation' => 3.5, 'minSize' => 20, 'maxSize' => 33),
   array('font' => 'VeraSe.ttf',       'condensation' => 4,   'minSize' => 26, 'maxSize' => 40),
   array('font' => 'CrazyHarold.ttf',  'condensation' => 2,   'minSize' => 20, 'maxSize' => 28),
   array('font' => 'Duality.ttf',      'condensation' => 2,   'minSize' => 28, 'maxSize' => 48),
   // Otras tipograf�as
   array('font' => 'BeyondWonderland.ttf', 'condensation' => 3, 'minSize' => 28, 'maxSize' => 39),
   array('font' => 'BennyBlanco.ttf',      'condensation' => 1, 'minSize' => 24, 'maxSize' => 30),
   array('font' => 'freak.ttf',            'condensation' => 2, 'minSize' => 32, 'maxSize' => 54),
   */
);

// Configuraci�n de ondulacion del texto
// Periodo y amplitud en ejes X e Y
$periodoY  = 15;
$amplitudY = 16;
$periodoX  = 12;
$amplitudX = 4;

/**
* Factor de resoluci�n con que se trabajar� internamente
* Se prefiere manipular la imagen al doble de su tama�o
* para evitar p�rdida de calidad al aplicar filtro wave.
* Valores posibles: 1, 2 o 3.
*/
$scale = 2;

// Utilizar palabras inexistentes?
$extended = false;


// Permite habilitar depurado
$debug = false;










$ini = microtime(true);












session_start();



// Creo la imagen
$im       = imagecreatetruecolor($width*$scale, $height*$scale);
$bg_color = imagecolorallocate($im, 255, 255, 255);
$color    = $colors[mt_rand(0, sizeof($colors)-1)];
$fg_color = imagecolorallocate($im, $color[0], $color[1], $color[2]);
imagefilledrectangle($im, 0, 0, $width*$scale, $height*$scale, $bg_color);




// Genero el texto, caracter por caracter
$text    = getCaptchaText();
$fontcfg = $fonts[mt_rand(0, sizeof($fonts)-1)];
$x       = 20*$scale;
for ($i=0; $i<=6; $i++) {
    $grade    = rand(12, 12);
    $fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize'])*$scale;
    $coords   = imagettftext($im, $fontsize, $grade, $x, 47*$scale,
        $fg_color, 'fonts/'.$fontcfg['font'], substr($text, $i, 1));
    $x       += ($coords[2]-$x)-$fontcfg['condensation']*$scale;
}




// Genero ondas verticales (eje X)
$period    = $scale*$periodoX;
$amplitude = $scale*$amplitudX;
$k         = rand(0, 100);
for ($i = 0;$i < ($width*$scale);$i++) {
    imagecopy($im, $im,
             $i-1, sin($k+$i/$period) * $amplitude,
             $i, 0,
             1, $height*$scale);
}

// Genero ondas horizontales (eje Y)
$period    = $scale*$periodoY;
$amplitude = $scale*$amplitudY;
$k         = rand(0, 100);
for ($i = 0;$i < ($height*$scale);$i++) {
    imagecopy($im, $im,
             sin($k+$i/$period) * $amplitude, $i-1,
             0, $i,
             $width*$scale, 1);
}




// Reduzco el tama�o de la imagen
$imResampled = imagecreatetruecolor($width, $height);
imagecopyresampled($imResampled, $im, 0, 0, 0, 0, $width, $height, $width*$scale, $height*$scale);
imagedestroy($im);


// Guardo el texto en sesi�n
$_SESSION[$session_var] = $text;


if ($debug) {
    imagestring($imResampled, 1, 1, $height-8,
        "$text ".$fontcfg['font'].' '.round((microtime(true)-$ini)*1000),
        $fg_color);
}


header("Content-type: image/jpeg");
imagejpeg($imResampled, null, 80);

// Limpieza
imagedestroy($imResampled);





/**
 * Retorna un texto de diccionario aleatorio
 *
 * @param boolean $extended Permite generaci�n de palabras adicionales
 * @return string Texto
 */
function getDictionaryCaptchaText($extended = false) {
    $fp    = fopen("words-es.txt", "r");
    $linea = rand(0, (filesize("words-es.txt")/8)-1);
    fseek($fp, 8*$linea);
    $text = trim(fgets($fp));
    fclose($fp);


    // Cambio vocales al azar
    if ($extended) {
        $text   = str_split($text, 1);
        $vocals = array('a', 'e', 'i', 'o', 'u');
        foreach ($text as $i => $char) {
            if (mt_rand(0, 1) && in_array($char, $vocals)) {
                $text[$i] = $vocals[mt_rand(0, 4)];
            }
        }
        $text = implode('', $text);
    }

    return $text;
}







/**
 * Retorna un texto aleatorio
 *
 * @return string Texto
 */
function getCaptchaText() {
    $length     = rand(5, 7);
    $consonants = "abcdefghijlmnopqrstvwyz";
    $vocals     = "aeiou";

    $text  = "";
    $vocal = rand(0, 1);
    for ($i=0; $i<$length; $i++) {
        if ($vocal) {
            $text .= substr($vocals, mt_rand(0, 4), 1);
        } else {
            $text .= substr($consonants, mt_rand(0, 22), 1);
        }
        $vocal = !$vocal;
    }
    return $text;
}



?>
