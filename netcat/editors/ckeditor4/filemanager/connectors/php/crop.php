<?
/**
 * Crop plugin for filemanager of CKEditor
 */
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{

    $config = json_decode(file_get_contents("../../scripts/filemanager.config.js"), true);

    if ($config['options']['fileRoot'] !== false ) {
        if($config['options']['serverRoot'] === true) {
            $doc_root = $_SERVER['DOCUMENT_ROOT'];
            $separator = basename($config['options']['fileRoot']);
        } else {
            $doc_root = $config['options']['fileRoot'];
            $separator = basename($config['options']['fileRoot']);
        }
    } else {
        $doc_root = $_SERVER['DOCUMENT_ROOT'];
    }


    include ($doc_root.'/vars.inc.php');

    $type = str_replace('.','',substr($_REQUEST['img'], -4));

    $info = parse_url($_REQUEST['img']);

    parse_str($info['query']);

    $img_src = str_replace('//','/',$doc_root.$HTTP_FILES_PATH."userfiles".$path);

    //$img_src = $doc_root.

    // start netcat & fm
    require_once('./inc/filemanager.inc.php');
    #NETCAT START
    require_once('filemanager.config.php');
    #NETCAT END
    require_once('filemanager.class.php');
    $fm = new Filemanager();
    if(!auth()) {
        $fm->error($fm->lang('AUTHORIZATION_REQUIRED'));
        return false;
    }


    $x = $_REQUEST['x'];
    $y = $_REQUEST['y'];
    $w = $_REQUEST['w'];
    $h = $_REQUEST['h'];


    $cropped_img = img_helper::crop($img_src,$x,$y,$w,$h);

    if (img_helper::save($cropped_img,$img_src,$type) !== false){

        // make new thumbnail

        $fm->setFileRoot($SUB_FOLDER . $config['rel_path']);

        $fm->get_thumbnail($img_src,true);

        return true;
    }
}





class img_helper {


    /**
     * Check if GD extension is loaded
     * @access public
     * @return bool
     */
    static public function isgdloaded()
    {
        if (!extension_loaded('gd') && !extension_loaded('gd2')) {
            trigger_error("GD is not loaded", E_USER_WARNING);
            return false;
        }
        else {
            return true;
        }
    }


    /**
     * Check if cURL extension is loaded
     * @access public
     * @return bool
     */
    static public function iscurlloaded()
    {
        if (!extension_loaded('curl')) {
            trigger_error("cURL is not loaded", E_USER_WARNING);
            return false;
        }
        else {
            return true;
        }
    }


    /**
     *
     * Validate gd resource
     * @access public
     * @param  mixed $image_source  - gd image resource
     * @return bool
     */
    public static function isgdresource($image_source)
    {
        $gd_resource = false;
        if(gettype($image_source) == 'resource') {
            if(get_resource_type($image_source) == 'gd') {
                $gd_resource = true;
            }
        }
        return $gd_resource;
    }


    /**
     * Get basic information from Image source
     * @access public
     * @param  mixed  $image_source - path to image source or image object
     * @param  string $info_type    - type of the returned information
     * @return mixed  source image information (int, string or array)
     */
    public static function info($image_source,$info_type=false)
    {
        # read information from image file
        if(@is_string($image_source) && @file_exists($image_source)) {
            $image_info   = getimagesize($image_source);
        }
        elseif(self::isgdresource($image_source)) {
            $image_info    = array();
            $image_info[0] = imagesx($image_source);
            $image_info[1] = imagesy($image_source);
            if (imagetypes() & IMG_PNG) {
                $image_info[2] = 3;
                $image_info['mime'] = 'image/png';
            }
            elseif (imagetypes() & IMG_GIF) {
                $image_info[2] = 1;
                $image_info['mime'] = 'image/gif';
            }
            else {
                $image_info[2] = 2;
                $image_info['mime'] = 'image/jpeg';
            }
        }
        else { $image_info = array(0=>0,1=>0,2=>false,'mime'=>false); }

        if($image_info) {
            if($info_type) {
                switch($info_type) {
                    case 'width':
                        return $image_info[0];
                        break;
                    case 'height':
                        return $image_info[1];
                        break;
                    case 'type':
                        return $image_info[2];
                        break;
                    case 'mime':
                        return $image_info['mime'];
                        break;
                    default;
                        return $image_info;
                }
            } else { return $image_info; }
        } else { return false; }
    }

    /**
     * Get image width
     * @access public
     * @param  mixed  $image_source - path to image source or gd resource
     * @return int    source image width in px
     */
    public static function width($image_source)
    {
        return self::info($image_source,'width');
    }

    /**
     * Get image height
     * @access public
     * @param  mixed  $image_source - path to image source or gd resource
     * @return int    source image width in px
     */
    public static function height($image_source)
    {
        return self::info($image_source,'height');
    }

    /**
     * Get image mime/type
     * @access public
     * @param  mixed  $image_source - path to image source or gd resource
     * @return string source image mime type
     */
    public static function mime($image_source)
    {
        return self::info($image_source,'mime');
    }



    /**
     *
     * Create image from source
     * @access public
     * @param  string $image_source - path to image source
     * @return mixed  gd resource or false
     */
    public static function create($image_source)
    {
        $image = false;

        if(@file_exists($image_source)) {
            $image_type = self::info($image_source,'type');

            if($image_type) {
                switch ($image_type)
                {
                    case 1:
                    case IMAGETYPE_GIF:
                        $image = imagecreatefromgif($image_source);
                        break;
                    case 2:
                    case IMAGETYPE_JPEG:
                        $image = imagecreatefromjpeg($image_source);
                        break;
                    case 3:
                    case IMAGETYPE_PNG:
                        $image = imagecreatefrompng($image_source);
                        break;
                    case 15:
                    case IMAGETYPE_WBMP:
                        $image = imagecreatefromwbmp($image_source);
                        break;
                    case 16:
                    case IMAGETYPE_XBM:
                        $image = imagecreatefromxbm($image_source);
                        break;
                }
            }
        }

        return $image;
    }





    /**
     * Crop image
     * @access public
     * @param  mixed    $image_source - path to image source or gd image resource
     * @param  int      $src_x        - x-coordinate of source point.
     * @param  int      $src_y        - y-coordinate of source point.
     * @param  int      $crp_width    - destination image width
     * @param  int      $crp_height   - destination image height
     * @return resource gd resource
     */
    public static function crop($image_source, $src_x, $src_y, $crp_width, $crp_height)
    {
        if(self::isgdloaded()) {
            $width_src        = self::width($image_source);
            $height_src       = self::height($image_source);
            $image_source     = self::create($image_source);
            $crp_width        = ($crp_width >= $width_src || $crp_width <= 0)?$width_src:$crp_width;
            $crp_height       = ($crp_height >= $height_src || $crp_height <= 0)?$height_src:$crp_height;
            $destination = imagecreatetruecolor($crp_width, $crp_height);
            imagecopy($destination, $image_source, 0, 0, $src_x, $src_y, $crp_width, $crp_height);
            imagedestroy($image_source);
            return $destination;
        }
        else {
            return false;
        }
    }




    /**
     * Save image in to file
     * @access public
     * @param  resource $image       - gd image resource
     * @param  string   $destination - output destination path and filename
     * @param  string   $prefix      - prefix to the file name
     * @param  string   $type        - new image format (default = png)
     * @param  int      $quality     - resulted image quality (default = 100)
     * @return string path to destination
     */
    public static function save($image,$destination,$prefix='',$type='png',$quality=100)
    {
        $type = strtolower($type);

        # build new image name
        //if($prefix || $type) {
            //if($type != '' || $prefix != '') {
            //    $destination = self::buildimagename($destination,$prefix,$type);
            //}
        //}
        if(self::isgdresource($image)) {
            switch ($type)
            {
                case 'gif':
                    imagegif($image,$destination);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($image, $destination,$quality);
                    break;
                case 'png':
                    imagepng($image,$destination);
                    break;
                case 'wbmp':
                    imagewbmp($image,$destination);
                    break;
                case 'xbm':
                    imagexbm($image,$destination);
                    break;
                default:
                    echo 'Unsupported image file format.';
            }
        }
        else {
            $destination = false;
        }
        return $destination;
    }


}




/*
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $targ_w = $targ_h = 150;
    $jpeg_quality = 90;

    $src = 'demo_files/pool.jpg';
    $img_r = imagecreatefromjpeg($src);
    $dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

    imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],
        $targ_w,$targ_h,$_POST['w'],$_POST['h']);

    header('Content-type: image/jpeg');
    imagejpeg($dst_r,null,$jpeg_quality);

    exit;
}

*/
?>