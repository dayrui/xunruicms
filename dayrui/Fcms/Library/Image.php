<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


/**
 * 图片处理类
 */
class Image {

    /**
     * Can be: imagemagick, netpbm, gd, gd2
     */
    public $image_library = 'gd2';

    /**
     * Path to the graphic library (if applicable)
     */
    public $library_path = '';

    /**
     * Whether to send to browser or write to disk
     */
    public $dynamic_output = FALSE;

    /**
     * Path to original image
     */
    public $source_image = '';

    /**
     * Path to the modified image
     */
    public $new_image = '';

    /**
     * Image width
     */
    public $width = '';

    /**
     * Image height
     */
    public $height = '';

    /**
     * Quality percentage of new image
     */
    public $quality = 100;

    /**
     * Whether to create a thumbnail
     */
    public $create_thumb = FALSE;

    /**
     * String to add to thumbnail version of image
     */

    public $thumb_marker = '_thumb';
    /**
     * Whether to maintain aspect ratio when resizing or use hard values
     *
     * @var bool
     */
    public $maintain_ratio = TRUE;

    /**
     * auto, height, or width.  Determines what to use as the master dimension
     */
    public $master_dim = 'auto';

    /**
     * Angle at to rotate image
     */
    public $rotation_angle = '';

    /**
     * X Coordinate for manipulation of the current image
     */
    public $x_axis = '';

    /**
     * Y Coordinate for manipulation of the current image
     */
    public $y_axis = '';

    // --------------------------------------------------------------------------
    // Watermark Vars
    // --------------------------------------------------------------------------
    /**
     * Watermark text if graphic is not used
     *
     * @var string
     */
    public $wm_text	= '';
    /**
     * Type of watermarking.  Options:  text/overlay
     *
     * @var string
     */
    public $wm_type	= 'text';
    /**
     * Default transparency for watermark
     *
     * @var int
     */
    public $wm_x_transp	= 4;
    /**
     * Default transparency for watermark
     *
     * @var int
     */
    public $wm_y_transp	= 4;
    /**
     * Watermark image path
     *
     * @var string
     */
    public $wm_overlay_path	= '';
    /**
     * TT font
     *
     * @var string
     */
    public $wm_font_path = '';
    /**
     * Font size (different versions of GD will either use points or pixels)
     *
     * @var int
     */
    public $wm_font_size = 17;
    /**
     * Vertical alignment:   T M B
     *
     * @var string
     */
    public $wm_vrt_alignment = 'B';
    /**
     * Horizontal alignment: L R C
     *
     * @var string
     */
    public $wm_hor_alignment = 'C';
    /**
     * Padding around text
     *
     * @var int
     */
    public $wm_padding = 0;
    /**
     * Lets you push text to the right
     *
     * @var int
     */
    public $wm_hor_offset = 0;
    /**
     * Lets you push text down
     *
     * @var int
     */
    public $wm_vrt_offset = 0;
    /**
     * Text color
     *
     * @var string
     */
    protected $wm_font_color = '#ffffff';
    /**
     * Dropshadow color
     *
     * @var string
     */
    protected $wm_shadow_color = '';
    /**
     * Dropshadow distance
     *
     * @var int
     */
    public $wm_shadow_distance = 2;
    /**
     * Image opacity: 1 - 100  Only works with image
     *
     * @var int
     */
    public $wm_opacity = 100;
    // --------------------------------------------------------------------------
    // Private Vars
    // --------------------------------------------------------------------------
    /**
     * Source image folder
     *
     * @var string
     */
    public $source_folder = '';

    /**
     * Destination image folder
     */
    public $dest_folder	= '';

    /**
     * Image mime-type
     */
    public $mime_type = '';

    /**
     * Original image width
     */
    public $orig_width = '';

    /**
     * Original image height
     */
    public $orig_height	= '';

    /**
     * Image format
     */
    public $image_type = '';

    /**
     * Size of current image
     */
    public $size_str = '';

    /**
     * Full path to source image
     */
    public $full_src_path = '';

    /**
     * Full path to destination image
     */
    public $full_dst_path = '';

    /**
     * File permissions
     */
    public $file_permissions = 0644;

    /**
     * Name of function to create image
     */
    public $create_fnc = 'imagecreatetruecolor';

    /**
     * Name of function to copy image
     */
    public $copy_fnc = 'imagecopyresampled';

    /**
     * Error messages
     */
    public $error_msg = [];

    /**
     * Whether to have a drop shadow on watermark
     */
    protected $wm_use_drop_shadow = FALSE;

    /**
     * Whether to use truetype fonts
     */
    public $wm_use_truetype	= FALSE;


    // 缓存大小
    protected $cache_size = 0;


    protected $image_info;
    protected $dest_image;


    /**
     * Initialize image properties
     *
     * Resets values in case this class is used in a loop
     *
     * @return	void
     */
    public function clear()
    {
        $props = array('thumb_marker', 'library_path', 'source_image', 'new_image', 'width', 'height', 'rotation_angle', 'x_axis', 'y_axis', 'wm_text', 'wm_overlay_path', 'wm_font_path', 'wm_shadow_color', 'source_folder', 'dest_folder', 'mime_type', 'orig_width', 'orig_height', 'image_type', 'size_str', 'full_src_path', 'full_dst_path');
        foreach ($props as $val)
        {
            $this->$val = '';
        }
        $this->image_library 		= 'gd2';
        $this->dynamic_output 		= FALSE;
        $this->quality 				= 100;
        $this->create_thumb 		= FALSE;
        $this->thumb_marker 		= '_thumb';
        $this->maintain_ratio 		= TRUE;
        $this->master_dim 			= 'auto';
        $this->wm_type 				= 'text';
        $this->wm_x_transp 			= 4;
        $this->wm_y_transp 			= 4;
        $this->wm_font_size 		= 17;
        $this->wm_vrt_alignment 	= 'B';
        $this->wm_hor_alignment 	= 'C';
        $this->wm_padding 			= 0;
        $this->wm_hor_offset 		= 0;
        $this->wm_vrt_offset 		= 0;
        $this->wm_font_color		= '#ffffff';
        $this->wm_shadow_distance 	= 2;
        $this->wm_opacity 			= 100;
        $this->create_fnc 			= 'imagecreatetruecolor';
        $this->copy_fnc 			= 'imagecopyresampled';
        $this->error_msg 			= array();
        $this->wm_use_drop_shadow 	= FALSE;
        $this->wm_use_truetype 		= FALSE;
    }
    // --------------------------------------------------------------------
    /**
     * initialize image preferences
     *
     * @param	array
     * @return	bool
     */
    public function initialize($props = array())
    {
        // Convert array elements into class variables
        if (dr_count($props) > 0)
        {
            foreach ($props as $key => $val)
            {
                if (property_exists($this, $key))
                {
                    if (in_array($key, array('wm_font_color', 'wm_shadow_color'), TRUE))
                    {
                        if (preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $val, $matches))
                        {
                            /* $matches[1] contains our hex color value, but it might be
                             * both in the full 6-length format or the shortened 3-length
                             * value.
                             * We'll later need the full version, so we keep it if it's
                             * already there and if not - we'll convert to it. We can
                             * access string characters by their index as in an array,
                             * so we'll do that and use concatenation to form the final
                             * value:
                             */
                            $val = (strlen($matches[1]) === 6)
                                ? '#'.$matches[1]
                                : '#'.$matches[1][0].$matches[1][0].$matches[1][1].$matches[1][1].$matches[1][2].$matches[1][2];
                        }
                        else
                        {
                            continue;
                        }
                    }
                    elseif (in_array($key, array('width', 'height'), TRUE) && ! ctype_digit((string) $val))
                    {
                        continue;
                    }
                    $this->$key = $val;
                }
            }
        }
        // Is there a source image? If not, there's no reason to continue
        if ($this->source_image === '')
        {
            $this->set_error('源图片参数不能为空');
            return FALSE;
        }
        /* Is getimagesize() available?
         *
         * We use it to determine the image properties (width/height).
         * Note: We need to figure out how to determine image
         * properties using ImageMagick and NetPBM
         */
        if ( ! function_exists('getimagesize'))
        {
            $this->set_error('getimagesize函数不可用');
            return FALSE;
        }
        $this->image_library = strtolower($this->image_library);
        /* Set the full server path
         *
         * The source image may or may not contain a path.
         * Either way, we'll try use realpath to generate the
         * full server path in order to more reliably read it.
         */
        if (($full_source_path = realpath($this->source_image)) !== FALSE)
        {
            $full_source_path = str_replace('\\', '/', $full_source_path);
        }
        else
        {
            $full_source_path = $this->source_image;
        }
        $x = explode('/', $full_source_path);
        $this->source_image = end($x);
        $this->source_folder = str_replace($this->source_image, '', $full_source_path);
        // Set the Image Properties
        if ( ! $this->get_image_properties($this->source_folder.$this->source_image))
        {
            return FALSE;
        }
        /*
         * Assign the "new" image name/path
         *
         * If the user has set a "new_image" name it means
         * we are making a copy of the source image. If not
         * it means we are altering the original. We'll
         * set the destination filename and path accordingly.
         */
        if ($this->new_image === '')
        {
            $this->dest_image = $this->source_image;
            $this->dest_folder = $this->source_folder;
        }
        elseif (strpos($this->new_image, '/') === FALSE)
        {
            $this->dest_folder = $this->source_folder;
            $this->dest_image = $this->new_image;
        }
        else
        {
            if (strpos($this->new_image, '/') === FALSE && strpos($this->new_image, '\\') === FALSE)
            {
                $full_dest_path = str_replace('\\', '/', realpath($this->new_image));
            }
            else
            {
                $full_dest_path = $this->new_image;
            }
            // Is there a file name?
            if ( ! preg_match('#\.(jpg|jpeg|gif|png|webp)$#i', $full_dest_path))
            {
                $this->dest_folder = $full_dest_path.'/';
                $this->dest_image = $this->source_image;
            }
            else
            {
                $x = explode('/', $full_dest_path);
                $this->dest_image = end($x);
                $this->dest_folder = str_replace($this->dest_image, '', $full_dest_path);
            }
        }
        /* Compile the finalized filenames/paths
         *
         * We'll create two master strings containing the
         * full server path to the source image and the
         * full server path to the destination image.
         * We'll also split the destination image name
         * so we can insert the thumbnail marker if needed.
         */
        if ($this->create_thumb === FALSE OR $this->thumb_marker === '')
        {
            $this->thumb_marker = '';
        }
        $xp = $this->explode_name($this->dest_image);
        $filename = $xp['name'];
        $file_ext = $xp['ext'];
        $this->full_src_path = $this->source_folder.$this->source_image;
        $this->full_dst_path = $this->dest_folder.$filename.$this->thumb_marker.$file_ext;
        /* Should we maintain image proportions?
         *
         * When creating thumbs or copies, the target width/height
         * might not be in correct proportion with the source
         * image's width/height. We'll recalculate it here.
         */
        if ($this->maintain_ratio === TRUE && ($this->width !== 0 OR $this->height !== 0))
        {
            $this->image_reproportion();
        }
        /* Was a width and height specified?
         *
         * If the destination width/height was not submitted we
         * will use the values from the actual file
         */
        if (!$this->width)
        {
            $this->width = $this->orig_width;
        }
        if (!$this->height)
        {
            $this->height = $this->orig_height;
        }
        // Set the quality
        $this->quality = trim(str_replace('%', '', $this->quality));
        if ($this->quality === '' OR $this->quality === 0 OR ! ctype_digit($this->quality))
        {
            $this->quality = 100;
        }
        // Set the x/y coordinates
        is_numeric($this->x_axis) OR $this->x_axis = 0;
        is_numeric($this->y_axis) OR $this->y_axis = 0;
        // Watermark-related Stuff...
        if ($this->wm_overlay_path !== '')
        {
            $this->wm_overlay_path = str_replace('\\', '/', realpath($this->wm_overlay_path));
        }
        if ($this->wm_shadow_color !== '')
        {
            $this->wm_use_drop_shadow = TRUE;
        }
        elseif ($this->wm_use_drop_shadow === TRUE && $this->wm_shadow_color === '')
        {
            $this->wm_use_drop_shadow = FALSE;
        }
        if ($this->wm_font_path !== '')
        {
            $this->wm_use_truetype = TRUE;
        }
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Image Resize
     *
     * This is a wrapper function that chooses the proper
     * resize function based on the protocol specified
     *
     * @return	bool
     */
    public function resize()
    {
        $protocol = ($this->image_library === 'gd2') ? 'image_process_gd' : 'image_process_'.$this->image_library;
        return $this->$protocol('resize');
    }
    // --------------------------------------------------------------------
    /**
     * Image Crop
     *
     * This is a wrapper function that chooses the proper
     * cropping function based on the protocol specified
     *
     * @return	bool
     */
    public function crop()
    {
        $protocol = ($this->image_library === 'gd2') ? 'image_process_gd' : 'image_process_'.$this->image_library;
        return $this->$protocol('crop');
    }
    // --------------------------------------------------------------------
    /**
     * Image Rotate
     *
     * This is a wrapper function that chooses the proper
     * rotation function based on the protocol specified
     *
     * @return	bool
     */
    public function rotate()
    {
        // Allowed rotation values
        $degs = array(90, 180, 270, 'vrt', 'hor');
        if ($this->rotation_angle === '' OR ! in_array($this->rotation_angle, $degs))
        {
            $this->set_error('旋转角度参数有误');
            return FALSE;
        }
        // Reassign the width and height
        if ($this->rotation_angle === 90 OR $this->rotation_angle === 270)
        {
            $this->width	= $this->orig_height;
            $this->height	= $this->orig_width;
        }
        else
        {
            $this->width	= $this->orig_width;
            $this->height	= $this->orig_height;
        }
        // Choose resizing function
        if ($this->image_library === 'imagemagick' OR $this->image_library === 'netpbm')
        {
            $protocol = 'image_process_'.$this->image_library;
            return $this->$protocol('rotate');
        }
        return ($this->rotation_angle === 'hor' OR $this->rotation_angle === 'vrt')
            ? $this->image_mirror_gd()
            : $this->image_rotate_gd();
    }
    // --------------------------------------------------------------------
    /**
     * Image Process Using GD/GD2
     *
     * This function will resize or crop
     *
     * @param	string
     * @return	bool
     */
    public function image_process_gd($action = 'resize')
    {
        $v2_override = FALSE;
        // If the target width/height match the source, AND if the new file name is not equal to the old file name
        // we'll simply make a copy of the original with the new name... assuming dynamic rendering is off.
        if ($this->dynamic_output === FALSE && $this->orig_width === $this->width && $this->orig_height === $this->height)
        {
            if ($this->source_image !== $this->new_image && @copy($this->full_src_path, $this->full_dst_path))
            {
                //chmod($this->full_dst_path, $this->file_permissions);
            }
            return TRUE;
        }
        // Let's set up our values based on the action
        if ($action === 'crop')
        {
            // Reassign the source width/height if cropping
            $this->orig_width  = $this->width;
            $this->orig_height = $this->height;
            // GD 2.0 has a cropping bug so we'll test for it
            if ($this->gd_version() !== FALSE)
            {
                $gd_version = str_replace('0', '', $this->gd_version());
                $v2_override = ($gd_version == 2);
            }
        }
        else
        {
            // If resizing the x/y axis must be zero
            $this->x_axis = 0;
            $this->y_axis = 0;
        }
        // Create the image handle
        if ( ! ($src_img = $this->image_create_gd()))
        {
            return FALSE;
        }

        list($this->orig_width, $this->orig_height) = $this->_fix_orientation($this->full_src_path, $this->orig_width, $this->orig_height);

        /* Create the image
         *
         * Old conditional which users report cause problems with shared GD libs who report themselves as "2.0 or greater"
         * it appears that this is no longer the issue that it was in 2004, so we've removed it, retaining it in the comment
         * below should that ever prove inaccurate.
         *
         * if ($this->image_library === 'gd2' && function_exists('imagecreatetruecolor') && $v2_override === FALSE)
         */
        if ($this->image_library === 'gd2' && function_exists('imagecreatetruecolor'))
        {
            $create	= 'imagecreatetruecolor';
            $copy	= 'imagecopyresampled';
        }
        else
        {
            $create	= 'imagecreate';
            $copy	= 'imagecopyresized';
        }
        $dst_img = $create($this->width, $this->height);
        if ($this->image_type === 3) // png we can actually preserve transparency
        {
            imagealphablending($dst_img, FALSE);
            imagesavealpha($dst_img, TRUE);
        }
        $copy($dst_img, $src_img, 0, 0, $this->x_axis, $this->y_axis, $this->width, $this->height, $this->orig_width, $this->orig_height);
        // Show the image
        if ($this->dynamic_output === TRUE)
        {
            $this->image_display_gd($dst_img);
        }
        elseif ( ! $this->image_save_gd($dst_img)) // Or save it
        {
            return FALSE;
        }
        // Kill the file handles
        imagedestroy($dst_img);
        imagedestroy($src_img);
        //chmod($this->full_dst_path, $this->file_permissions);
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Image Process Using ImageMagick
     *
     * This function will resize, crop or rotate
     *
     * @param	string
     * @return	bool
     */
    public function image_process_imagemagick($action = 'resize')
    {
        // Do we have a vaild library path?
        if ($this->library_path === '')
        {
            $this->set_error('图片路径不能为空');
            return FALSE;
        }
        if ( ! preg_match('/convert$/i', $this->library_path))
        {
            $this->library_path = rtrim($this->library_path, '/').'/convert';
        }
        // Execute the command
        $cmd = $this->library_path.' -quality '.$this->quality;
        if ($action === 'crop')
        {
            $cmd .= ' -crop '.$this->width.'x'.$this->height.'+'.$this->x_axis.'+'.$this->y_axis;
        }
        elseif ($action === 'rotate')
        {
            $cmd .= ($this->rotation_angle === 'hor' OR $this->rotation_angle === 'vrt')
                ? ' -flop'
                : ' -rotate '.$this->rotation_angle;
        }
        else // Resize
        {
            if($this->maintain_ratio === TRUE)
            {
                $cmd .= ' -resize '.$this->width.'x'.$this->height;
            }
            else
            {
                $cmd .= ' -resize '.$this->width.'x'.$this->height.'\!';
            }
        }
        $cmd .= escapeshellarg($this->full_src_path).' '.escapeshellarg($this->full_dst_path).' 2>&1';
        $retval = 1;
        // exec() might be disabled
        if (function_usable('exec'))
        {
            @exec($cmd, $output, $retval);
        }
        // Did it work?
        if ($retval > 0)
        {
            $this->set_error('imglib_image进程失败');
            return FALSE;
        }
        //chmod($this->full_dst_path, $this->file_permissions);
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Image Process Using NetPBM
     *
     * This function will resize, crop or rotate
     *
     * @param	string
     * @return	bool
     */
    public function image_process_netpbm($action = 'resize')
    {
        if ($this->library_path === '')
        {
            $this->set_error('源图片路径不能为空');
            return FALSE;
        }
        // Build the resizing command
        switch ($this->image_type)
        {
            case 1 :
                $cmd_in		= 'giftopnm';
                $cmd_out	= 'ppmtogif';
                break;
            case 2 :
                $cmd_in		= 'jpegtopnm';
                $cmd_out	= 'ppmtojpeg';
                break;
            case 3 :
                $cmd_in		= 'pngtopnm';
                $cmd_out	= 'ppmtopng';
                break;
        }
        if ($action === 'crop')
        {
            $cmd_inner = 'pnmcut -left '.$this->x_axis.' -top '.$this->y_axis.' -width '.$this->width.' -height '.$this->height;
        }
        elseif ($action === 'rotate')
        {
            switch ($this->rotation_angle)
            {
                case 90:	$angle = 'r270';
                    break;
                case 180:	$angle = 'r180';
                    break;
                case 270:	$angle = 'r90';
                    break;
                case 'vrt':	$angle = 'tb';
                    break;
                case 'hor':	$angle = 'lr';
                    break;
            }
            $cmd_inner = 'pnmflip -'.$angle.' ';
        }
        else // Resize
        {
            $cmd_inner = 'pnmscale -xysize '.$this->width.' '.$this->height;
        }
        $cmd = $this->library_path.$cmd_in.' '.$this->full_src_path.' | '.$cmd_inner.' | '.$cmd_out.' > '.$this->dest_folder.'netpbm.tmp';
        $retval = 1;
        // exec() might be disabled
        if (function_usable('exec'))
        {
            @exec($cmd, $output, $retval);
        }
        // Did it work?
        if ($retval > 0)
        {
            $this->set_error('imglib_image进程失败');
            return FALSE;
        }
        // With NetPBM we have to create a temporary image.
        // If you try manipulating the original it fails so
        // we have to rename the temp file.
        copy($this->dest_folder.'netpbm.tmp', $this->full_dst_path);
        unlink($this->dest_folder.'netpbm.tmp');
        //chmod($this->full_dst_path, $this->file_permissions);
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Image Rotate Using GD
     *
     * @return	bool
     */
    public function image_rotate_gd()
    {
        // Create the image handle
        if ( ! ($src_img = $this->image_create_gd()))
        {
            return FALSE;
        }
        // Set the background color
        // This won't work with transparent PNG files so we are
        // going to have to figure out how to determine the color
        // of the alpha channel in a future release.
        $white = imagecolorallocate($src_img, 255, 255, 255);
        // Rotate it!
        $dst_img = imagerotate($src_img, $this->rotation_angle, $white);
        // Show the image
        if ($this->dynamic_output === TRUE)
        {
            $this->image_display_gd($dst_img);
        }
        elseif ( ! $this->image_save_gd($dst_img)) // ... or save it
        {
            return FALSE;
        }
        // Kill the file handles
        imagedestroy($dst_img);
        imagedestroy($src_img);
        //chmod($this->full_dst_path, $this->file_permissions);
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Create Mirror Image using GD
     *
     * This function will flip horizontal or vertical
     *
     * @return	bool
     */
    public function image_mirror_gd()
    {
        if ( ! $src_img = $this->image_create_gd())
        {
            return FALSE;
        }
        $width  = $this->orig_width;
        $height = $this->orig_height;
        if ($this->rotation_angle === 'hor')
        {
            for ($i = 0; $i < $height; $i++)
            {
                $left = 0;
                $right = $width - 1;
                while ($left < $right)
                {
                    $cl = imagecolorat($src_img, $left, $i);
                    $cr = imagecolorat($src_img, $right, $i);
                    imagesetpixel($src_img, $left, $i, $cr);
                    imagesetpixel($src_img, $right, $i, $cl);
                    $left++;
                    $right--;
                }
            }
        }
        else
        {
            for ($i = 0; $i < $width; $i++)
            {
                $top = 0;
                $bottom = $height - 1;
                while ($top < $bottom)
                {
                    $ct = imagecolorat($src_img, $i, $top);
                    $cb = imagecolorat($src_img, $i, $bottom);
                    imagesetpixel($src_img, $i, $top, $cb);
                    imagesetpixel($src_img, $i, $bottom, $ct);
                    $top++;
                    $bottom--;
                }
            }
        }
        // Show the image
        if ($this->dynamic_output === TRUE)
        {
            $this->image_display_gd($src_img);
        }
        elseif ( ! $this->image_save_gd($src_img)) // ... or save it
        {
            return FALSE;
        }
        // Kill the file handles
        imagedestroy($src_img);
        //chmod($this->full_dst_path, $this->file_permissions);
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Image Watermark
     *
     * This is a wrapper function that chooses the type
     * of watermarking based on the specified preference.
     *
     * @return	bool
     */
    public function watermark($data = [], $is_test = 0)
    {

        if (!in_array(str_replace('.', '', trim(strtolower(strrchr($data['source_image'], '.')), '.')), [
            'jpg', 'jpeg', 'png', 'webp'
        ])) {
            return false;
        }

        $config = [];
        $config['source_image'] = $data['source_image'];
        $config['dynamic_output'] = $data['dynamic_output'];
        if ($data['type']) {
            // 文字水印
            $config['wm_text'] = $data['wm_text'] ? $data['wm_text'] : 'xunruicms';
            $config['wm_type'] = 'text';
            $config['wm_font_path'] = WRITEPATH.'watermark/'.dr_safe_filename($data['wm_font_path']);
            if (!is_file($config['wm_font_path'])) {
                log_message('error', '文字水印字体文件不存在：'.$data['wm_font_path']);
                return '';
            }
            $config['wm_font_size'] = $data['wm_font_size'];
            $config['wm_font_color'] = $data['wm_font_color'];
        } else {
            // 图片水印
            $config['wm_type'] = 'overlay';
            $config['wm_overlay_path'] = WRITEPATH.'watermark/'.dr_safe_filename($data['wm_overlay_path']);
            if (!is_file($config['wm_overlay_path'])) {
                log_message('error', '图片水印图片文件不存在：'.$data['wm_overlay_path']);
                return '';
            }
            $config['wm_opacity'] = isset($data['wm_opacity']) && $data['wm_opacity'] ? min(100, max($data['wm_opacity'], 1)) : 100;
        }

        list($config['wm_hor_alignment'], $config['wm_vrt_alignment']) = explode('-', (string)$data['locate']);

        $config['wm_padding'] = (int)$data['wm_padding'];
        $config['wm_hor_offset'] = (int)$data['wm_hor_offset'];
        $config['wm_vrt_offset'] = (int)$data['wm_vrt_offset'];
        $this->initialize($config);

        if (!$this->full_dst_path) {
            $this->full_dst_path = $config['source_image'];
        }

        $this->source_image = $config['source_image'];

        // 判断水印尺寸
        if (!$is_test) {
            list($nw, $nh) = $this->image_info ? $this->image_info : getimagesize($this->source_image);
            if ($data['width'] && $data['width'] > $nw) {
                CI_DEBUG && log_message('debug', '系统要求宽度>'.$data['width'].'px才进行水印，当前图片宽度='.$nw.'，不满足水印条件：'.$data['source_image']);
                return '';
            } elseif ($data['height'] && $data['height'] > $nh) {
                CI_DEBUG && log_message('debug', '系统要求高度>'.$data['width'].'px才进行水印，当前图片高度='.$nh.'，不满足水印条件：'.$data['source_image']);
                return '';
            }
        }

        return ($this->wm_type === 'overlay') ? $this->overlay_watermark() : $this->text_watermark();
    }
    // --------------------------------------------------------------------
    /**
     * Watermark - Graphic Version
     *
     * @return	bool
     */
    public function overlay_watermark()
    {
        if ( ! function_exists('imagecolortransparent'))
        {
            $this->set_error('imagecolortransparent函数不支持');
            return FALSE;
        }
        // Fetch source image properties
        $this->get_image_properties();
        // Fetch watermark image properties
        $props		= $this->get_image_properties($this->wm_overlay_path, TRUE);
        $wm_img_type	= $props['image_type'];
        $wm_width	= $props['width'];
        $wm_height	= $props['height'];
        // Create two image resources
        $wm_img  = $this->image_create_gd($this->wm_overlay_path, $wm_img_type);
        $src_img = $this->image_create_gd($this->full_src_path);
        if (!$src_img) {
            return FALSE;
        }
        // Reverse the offset if necessary
        // When the image is positioned at the bottom
        // we don't want the vertical offset to push it
        // further down. We want the reverse, so we'll
        // invert the offset. Same with the horizontal
        // offset when the image is at the right
        $this->wm_vrt_alignment = strtoupper((string)$this->wm_vrt_alignment[0]);
        $this->wm_hor_alignment = strtoupper((string)$this->wm_hor_alignment[0]);
        if ($this->wm_vrt_alignment === 'B')
            $this->wm_vrt_offset = $this->wm_vrt_offset * -1;
        if ($this->wm_hor_alignment === 'R')
            $this->wm_hor_offset = $this->wm_hor_offset * -1;
        // Set the base x and y axis values
        $x_axis = $this->wm_hor_offset + $this->wm_padding;
        $y_axis = $this->wm_vrt_offset + $this->wm_padding;
        // Set the vertical position
        if ($this->wm_vrt_alignment === 'M')
        {
            $y_axis += ($this->orig_height / 2) - ($wm_height / 2);
        }
        elseif ($this->wm_vrt_alignment === 'B')
        {
            $y_axis += $this->orig_height - $wm_height;
        }
        // Set the horizontal position
        if ($this->wm_hor_alignment === 'C')
        {
            $x_axis += ($this->orig_width / 2) - ($wm_width / 2);
        }
        elseif ($this->wm_hor_alignment === 'R')
        {
            $x_axis += $this->orig_width - $wm_width;
        }
        // Build the finalized image
        if ($wm_img_type === 3 && function_exists('imagealphablending'))
        {
            @imagealphablending($src_img, TRUE);
        }
        // Set RGB values for text and shadow
        $rgba = imagecolorat($wm_img, $this->wm_x_transp, $this->wm_y_transp);
        $alpha = ($rgba & 0x7F000000) >> 24;
        $x_axis = intval($x_axis);
        $y_axis = intval($y_axis);
        // make a best guess as to whether we're dealing with an image with alpha transparency or no/binary transparency
        if ($alpha > 0 || $wm_img_type !== 3 ) {
            // copy the image directly, the image's alpha transparency being the sole determinant of blending
            imagecopy($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height);
        } else {
            // set our RGB value from above to be transparent and merge the images with the specified opacity
            imagecolortransparent($wm_img, imagecolorat($wm_img, $this->wm_x_transp, $this->wm_y_transp));
            imagecopymerge($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height, $this->wm_opacity);
        }

        // We can preserve transparency for PNG images
        if ($this->image_type === 3)
        {
            imagealphablending($src_img, FALSE);
            imagesavealpha($src_img, TRUE);
        }
        // Output the image
        if ($this->dynamic_output === TRUE)
        {
            $this->image_display_gd($src_img);
        }
        elseif ( ! $this->image_save_gd($src_img)) // ... or save it
        {
            return FALSE;
        }
        imagedestroy($src_img);
        imagedestroy($wm_img);
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Watermark - Text Version
     *
     * @return	bool
     */
    public function text_watermark()
    {
        if ( ! ($src_img = $this->image_create_gd()))
        {
            return FALSE;
        }
        if ($this->wm_use_truetype === TRUE && ! file_exists($this->wm_font_path))
        {
            $this->set_error(IS_DEV ? '字体文件（'.$this->wm_font_path.'）不存在' : '字体文件不存在');
            return FALSE;
        }
        // Fetch source image properties
        $this->get_image_properties();
        // Reverse the vertical offset
        // When the image is positioned at the bottom
        // we don't want the vertical offset to push it
        // further down. We want the reverse, so we'll
        // invert the offset. Note: The horizontal
        // offset flips itself automatically
        if ($this->wm_vrt_alignment === 'B')
        {
            $this->wm_vrt_offset = $this->wm_vrt_offset * -1;
        }
        if ($this->wm_hor_alignment === 'R')
        {
            $this->wm_hor_offset = $this->wm_hor_offset * -1;
        }
        // Set font width and height
        // These are calculated differently depending on
        // whether we are using the true type font or not
        if ($this->wm_use_truetype === TRUE)
        {
            if (empty($this->wm_font_size))
            {
                $this->wm_font_size = 17;
            }
            if (function_exists('imagettfbbox'))
            {
                $temp = imagettfbbox($this->wm_font_size, 0, $this->wm_font_path, $this->wm_text);
                $temp = $temp[2] - $temp[0];
                $fontwidth = $temp / strlen($this->wm_text);
            }
            else
            {
                $fontwidth = $this->wm_font_size - ($this->wm_font_size / 4);
            }
            $fontheight = $this->wm_font_size;
            $this->wm_vrt_offset += $this->wm_font_size;
        }
        else
        {
            $fontwidth  = imagefontwidth($this->wm_font_size);
            $fontheight = imagefontheight($this->wm_font_size);
        }
        // Set base X and Y axis values
        $x_axis = $this->wm_hor_offset + $this->wm_padding;
        $y_axis = $this->wm_vrt_offset + $this->wm_padding;
        if ($this->wm_use_drop_shadow === FALSE)
        {
            $this->wm_shadow_distance = 0;
        }
        $this->wm_vrt_alignment = strtoupper((string)$this->wm_vrt_alignment[0]);
        $this->wm_hor_alignment = strtoupper((string)$this->wm_hor_alignment[0]);
        // Set vertical alignment
        if ($this->wm_vrt_alignment === 'M')
        {
            $y_axis += ($this->orig_height / 2) + ($fontheight / 2);
        }
        elseif ($this->wm_vrt_alignment === 'B')
        {
            $y_axis += $this->orig_height - $fontheight - $this->wm_shadow_distance - ($fontheight / 2);
        }
        // Set horizontal alignment
        if ($this->wm_hor_alignment === 'R')
        {
            $x_axis += $this->orig_width - ($fontwidth * strlen($this->wm_text)) - $this->wm_shadow_distance;
        }
        elseif ($this->wm_hor_alignment === 'C')
        {
            $x_axis += floor(($this->orig_width - ($fontwidth * strlen($this->wm_text))) / 2);
        }
        if ($this->wm_use_drop_shadow)
        {
            // Offset from text
            $x_shad = $x_axis + $this->wm_shadow_distance;
            $y_shad = $y_axis + $this->wm_shadow_distance;
            /* Set RGB values for shadow
             *
             * First character is #, so we don't really need it.
             * Get the rest of the string and split it into 2-length
             * hex values:
             */
            $drp_color = str_split(substr($this->wm_shadow_color, 1, 6), 2);
            $drp_color = imagecolorclosest($src_img, hexdec($drp_color[0]), hexdec($drp_color[1]), hexdec($drp_color[2]));
            // Add the shadow to the source image
            if ($this->wm_use_truetype)
            {
                imagettftext($src_img, $this->wm_font_size, 0, $x_shad, $y_shad, $drp_color, $this->wm_font_path, $this->wm_text);
            }
            else
            {
                imagestring($src_img, $this->wm_font_size, $x_shad, $y_shad, $this->wm_text, $drp_color);
            }
        }
        /* Set RGB values for text
         *
         * First character is #, so we don't really need it.
         * Get the rest of the string and split it into 2-length
         * hex values:
         */
        $txt_color = str_split(substr($this->wm_font_color, 1, 6), 2);
        $txt_color = imagecolorclosest($src_img, hexdec($txt_color[0]), hexdec($txt_color[1]), hexdec($txt_color[2]));
        // Add the text to the source image
        if ($this->wm_use_truetype)
        {
            imagettftext($src_img, $this->wm_font_size, 0, $x_axis, $y_axis, $txt_color, $this->wm_font_path, $this->wm_text);
        }
        else
        {
            imagestring($src_img, $this->wm_font_size, $x_axis, $y_axis, $this->wm_text, $txt_color);
        }
        // We can preserve transparency for PNG images
        if ($this->image_type === 3)
        {
            imagealphablending($src_img, FALSE);
            imagesavealpha($src_img, TRUE);
        }
        // Output the final image
        if ($this->dynamic_output === TRUE)
        {
            $this->image_display_gd($src_img);
        }
        else
        {
            $this->image_save_gd($src_img);
        }
        imagedestroy($src_img);
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Create Image - GD
     *
     * This simply creates an image resource handle
     * based on the type of image being processed
     *
     * @param	string
     * @param	string
     * @return	resource
     */
    public function image_create_gd($path = '', $image_type = '')
    {
        if ($path === '')
        {
            $path = $this->full_src_path;
        }
        if ($image_type === '')
        {
            $image_type = $this->image_type;
        }
        switch ($image_type)
        {
            case 1:
                if ( ! function_exists('imagecreatefromgif'))
                {
                    $this->set_error('imagecreatefromgif函数不存在');
                    return FALSE;
                }
                return imagecreatefromgif($path);
            case 2:
                if ( ! function_exists('imagecreatefromjpeg'))
                {
                    $this->set_error('imagecreatefromjpeg函数不存在');
                    return FALSE;
                }
                return imagecreatefromjpeg($path);
            case 18:
                if ( ! function_exists('imagecreatefromwebp'))
                {
                    $this->set_error('imagecreatefromwebp函数不存在');
                    return FALSE;
                }
                return imagecreatefromwebp($path);
            case 3:
                if ( ! function_exists('imagecreatefrompng'))
                {
                    $this->set_error('imagecreatefrompng函数不存在');
                    return FALSE;
                }
                return imagecreatefrompng($path);
            default:
                $this->set_error('不支持的图片类型');
                return FALSE;
        }
    }
    // --------------------------------------------------------------------
    /**
     * Write image file to disk - GD
     *
     * Takes an image resource as input and writes the file
     * to the specified destination
     *
     * @param	resource
     * @return	bool
     */
    public function image_save_gd($resource)
    {
        if (!$this->full_dst_path) {
            if ($this->source_image) {
                $this->full_dst_path = $this->source_image;
            } else {
                $this->error_msg = dr_lang('full_dst_path值为空，程序逻辑错误');
                return;
            }
        }
        if ($this->image_type != 18 && strpos($this->full_dst_path, '.webp')) {
            $this->image_type = 18;
        }
        switch ($this->image_type)
        {
            case 1:
                if ( ! function_exists('imagegif'))
                {
                    $this->set_error('imagegif函数不存在');
                    return FALSE;
                }
                if ( ! @imagegif($resource, $this->full_dst_path))
                {
                    $this->set_error('图片储存失败');
                    return FALSE;
                }
                break;
            case 2:
                if ( ! function_exists('imagejpeg'))
                {
                    $this->set_error('imagejpeg函数不存在');
                    return FALSE;
                }
                if ( ! imagejpeg($resource, $this->full_dst_path, $this->quality))
                {
                    $this->set_error('图片储存失败');
                    return FALSE;
                }
                break;
            case 18:
                if ( ! function_exists('imagewebp'))
                {
                    $this->set_error('imagewebp函数不存在');
                    return FALSE;
                }
                if ( ! imagewebp($resource, $this->full_dst_path, $this->quality))
                {
                    $this->set_error('图片储存失败');
                    return FALSE;
                }
                break;
            case 3:
                if ( ! function_exists('imagepng'))
                {
                    $this->set_error('imagepng函数不存在');
                    return FALSE;
                }
                if ( ! @imagepng($resource, $this->full_dst_path))
                {
                    $this->set_error('图片储存失败');
                    return FALSE;
                }
                break;
            default:
                $this->set_error('不支持的图片类型');
                return FALSE;
                break;
        }
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Dynamically outputs an image
     *
     * @param	resource
     * @return	void
     */
    public function image_display_gd($resource)
    {
        header('Content-Disposition: filename='.$this->source_image.';');
        header('Content-Type: '.$this->mime_type);
        header('Content-Transfer-Encoding: binary');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
        switch ($this->image_type)
        {
            case 1	:	imagegif($resource);
                break;
            case 2	:	imagejpeg($resource, NULL, $this->quality);
                break;
            case 3	:	imagepng($resource);
                break;
            case 18	:	imagewebp($resource, NULL, $this->quality);
                break;
            default:	echo 'Unable to display the image';
                break;
        }
    }
    // --------------------------------------------------------------------
    /**
     * Re-proportion Image Width/Height
     *
     * When creating thumbs, the desired width/height
     * can end up warping the image due to an incorrect
     * ratio between the full-sized image and the thumb.
     *
     * This function lets us re-proportion the width/height
     * if users choose to maintain the aspect ratio when resizing.
     *
     * @return	void
     */
    public function image_reproportion()
    {
        if (($this->width === 0 && $this->height === 0) OR $this->orig_width === 0 OR $this->orig_height === 0
            OR ( ! ctype_digit((string) $this->width) && ! ctype_digit((string) $this->height))
            OR ! ctype_digit((string) $this->orig_width) OR ! ctype_digit((string) $this->orig_height))
        {
            return;
        }
        // Sanitize
        $this->width = (int) $this->width;
        $this->height = (int) $this->height;
        if ($this->master_dim !== 'width' && $this->master_dim !== 'height')
        {
            if ($this->width > 0 && $this->height > 0)
            {
                $this->master_dim = ((($this->orig_height/$this->orig_width) - ($this->height/$this->width)) < 0)
                    ? 'width' : 'height';
            }
            else
            {
                $this->master_dim = ($this->height === 0) ? 'width' : 'height';
            }
        }
        elseif (($this->master_dim === 'width' && $this->width === 0)
            OR ($this->master_dim === 'height' && $this->height === 0))
        {
            return;
        }
        if ($this->master_dim === 'width')
        {
            $this->height = (int) ceil($this->width*$this->orig_height/$this->orig_width);
        }
        else
        {
            $this->width = (int) ceil($this->orig_width*$this->height/$this->orig_height);
        }
    }
    // --------------------------------------------------------------------
    /**
     * Get image properties
     *
     * A helper function that gets info about the file
     *
     * @param	string
     * @param	bool
     * @return	mixed
     */
    public function get_image_properties($path = '', $return = FALSE)
    {
        // For now we require GD but we should
        // find a way to determine this using IM or NetPBM
        if ($path === '')
        {
            $path = $this->full_src_path;
        }
        if ( ! file_exists($path))
        {
            $this->set_error(IS_DEV ? '文件（'.$path.'）不存在' : '图片路径不存在');
            return FALSE;
        }
        $vals = getimagesize($path);
        $types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
        $mime = (isset($types[$vals[2]])) ? 'image/'.$types[$vals[2]] : 'image/jpg';
        if ($return === TRUE)
        {
            return array(
                'width' =>	$vals[0],
                'height' =>	$vals[1],
                'image_type' =>	$vals[2],
                'size_str' =>	$vals[3],
                'mime_type' =>	$mime
            );
        }
        $this->orig_width	= $vals[0];
        $this->orig_height	= $vals[1];
        $this->image_type	= $vals[2];
        $this->size_str		= $vals[3];
        $this->mime_type	= $mime;
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Size calculator
     *
     * This function takes a known width x height and
     * recalculates it to a new size. Only one
     * new variable needs to be known
     *
     *	$props = array(
     *			'width'		=> $width,
     *			'height'	=> $height,
     *			'new_width'	=> 40,
     *			'new_height'	=> ''
     *		);
     *
     * @param	array
     * @return	array
     */
    public function size_calculator($vals)
    {
        if ( ! is_array($vals))
        {
            return;
        }
        $allowed = array('new_width', 'new_height', 'width', 'height');
        foreach ($allowed as $item)
        {
            if (empty($vals[$item]))
            {
                $vals[$item] = 0;
            }
        }
        if ($vals['width'] === 0 OR $vals['height'] === 0)
        {
            return $vals;
        }
        if ($vals['new_width'] === 0)
        {
            $vals['new_width'] = ceil($vals['width']*$vals['new_height']/$vals['height']);
        }
        elseif ($vals['new_height'] === 0)
        {
            $vals['new_height'] = ceil($vals['new_width']*$vals['height']/$vals['width']);
        }
        return $vals;
    }
    // --------------------------------------------------------------------
    /**
     * Explode source_image
     *
     * This is a helper function that extracts the extension
     * from the source_image.  This function lets us deal with
     * source_images with multiple periods, like: my.cool.jpg
     * It returns an associative array with two elements:
     * $array['ext']  = '.jpg';
     * $array['name'] = 'my.cool';
     *
     * @param	array
     * @return	array
     */
    public function explode_name($source_image)
    {
        $ext = strrchr($source_image, '.');
        $name = ($ext === FALSE) ? $source_image : substr($source_image, 0, -strlen($ext));
        return array('ext' => $ext, 'name' => $name);
    }
    // --------------------------------------------------------------------
    /**
     * Is GD Installed?
     *
     * @return	bool
     */
    public function gd_loaded()
    {
        if ( ! extension_loaded('gd'))
        {
            /* As it is stated in the PHP manual, dl() is not always available
             * and even if so - it could generate an E_WARNING message on failure
             */
            return (function_exists('dl') && @dl('gd.so'));
        }
        return TRUE;
    }
    // --------------------------------------------------------------------
    /**
     * Get GD version
     *
     * @return	mixed
     */
    public function gd_version()
    {
        if (function_exists('gd_info'))
        {
            $gd_version = @gd_info();
            return preg_replace('/\D/', '', $gd_version['GD Version']);
        }
        return FALSE;
    }
    // --------------------------------------------------------------------
    /**
     * Set error message
     *
     * @param	string
     * @return	void
     */
    public function set_error($msg)
    {
        $this->error_msg = is_array($msg) ? implode(' - ', dr_lang($msg)) : dr_lang($msg);
    }
    // --------------------------------------------------------------------
    /**
     * Show error messages
     *
     * @param	string
     * @param	string
     * @return	string
     */
    public function display_errors($open = '<p>', $close = '</p>')
    {
        return $this->error_msg;
    }

    public function get_file($id) {
        return dr_get_file($id);
    }

    /**
     * 图片缩略图显示
     *
     * @param	string	$img	图片id或者路径
     * @param	intval	$width	输出宽度
     * @param	intval	$height	输出高度
     * @param	intval	$water	是否水印
     * @param	intval	$model	图片模式
     * @param	intval	$webimg	剪切网络图片
     * @return  url
     */
    public function thumb($img, $width = 0, $height = 0, $water = 0, $mode = 'auto', $webimg = 0) {

        list($cache_path, $cache_url, $ext, $path) = dr_thumb_path($img);

        // 图片缩略图文件
        $cache_file = $path.'/'.$width.'x'.$height.($water ? '_water' : '').'_'.$mode.'.'.($ext ? 'webp' : 'jpg');
        if (!IS_DEV && is_file($cache_path.$cache_file)) {
            return $cache_url.$cache_file;
        }

        if (is_numeric($img)) {
            $attach = \Phpcmf\Service::C()->get_attachment($img);
            if (!$attach) {
                CI_DEBUG && log_message('debug', '图片[id#'.$img.']不存在，dr_thumb函数无法调用');
                return ROOT_THEME_PATH.'assets/images/nopic.gif'.(CI_DEBUG ? '#图片[id#'.$img.']不存在，dr_thumb函数无法调用' : '');
            } elseif (!in_array($attach['fileext'], ['png', 'jpeg', 'jpg', 'webp'])) {
                CI_DEBUG && log_message('debug', '图片[id#'.$img.']扩展名不符合条件，dr_thumb函数无法调用');
                return ROOT_THEME_PATH.'assets/images/nopic.gif'.(CI_DEBUG ? '#图片[id#'.$img.']扩展名不符合条件，dr_thumb函数无法调用，dr_thumb函数无法调用' : '');
            }
        } else {
            $attach = [
                'id' => md5($img),
                'url' => dr_get_file($img),
                'file' => '',
                'remote' => 'webimg',
                'fileext' => trim(strtolower(strrchr($img, '.')), '.'),
            ];
            $attach['attachment'] = date('Ym').'/'.$attach['id'].'.'.$attach['fileext'];
        }

        // 本地存储的原始图片
        $file = $attach['file'];
        if ($attach['remote'] && $attach['url']) {
            if ($webimg) {
                // 远程图片下载到本地进行缩略图处理
                $data = dr_catcher_data($attach['url'], 10);
                if (!$data) {
                    CI_DEBUG && log_message('debug', '图片[id#'.$attach['id'].']的URL['.$attach['url'].']无法获取远程附件数据，dr_thumb函数无法调用');
                    return $attach['url'].(CI_DEBUG ? '#用' : '');
                }
                $file = WRITEPATH.'attach/'.$attach['id'].'.'.$attach['fileext'];
                if (!file_put_contents($file, $data)) {
                    CI_DEBUG && log_message('debug', '图片[id#'.$attach['id'].']的URL['.$attach['url'].']无法写入附件缓存目录，dr_thumb函数无法调用');
                    return $attach['url'].(CI_DEBUG ? '#URL['.$attach['url'].']无法写入附件缓存目录，dr_thumb函数无法调用' : '');
                }
            } else {
                // 远程图片进行带规则的缩略图处理
                $remote = \Phpcmf\Service::C()->get_cache('attachment', $attach['remote']);
                if ($remote) {
                    if (is_dir(SYS_UPLOAD_PATH.$remote['value']['path'])) {
                        // 相对路径
                        $file = SYS_UPLOAD_PATH.$remote['value']['path'].$attach['attachment'];
                    } else {
                        $file = $remote['value']['path'].$attach['attachment'];
                    }
                    if (!is_file($file)) {
                        // 文件不存在表示网络地址
                        if (($width > 0 || $height > 0) && $remote['value']['wh_prefix_image']) {
                            // 输出带尺寸的后缀图
                            return $attach['url'].str_replace(['{width}', '{height}'], [$width, $height], $remote['value']['wh_prefix_image']);
                        } elseif ($remote['value']['image']) {
                            // 输出带后缀的图片
                            return $attach['url'].$remote['value']['image'];
                        }
                        //输出直接地址
                        return $attach['url'].(CI_DEBUG ? '#自定义策略地址，将原样输出' : '');
                    }
                } else {
                    //输出直接地址
                    return $attach['url'].(CI_DEBUG ? '#自定义策略失效，将原样输出' : '');
                }
            }
        } elseif (!is_file($file)) {
            // 本地图片不存在
            CI_DEBUG && log_message('debug', '图片[id#'.$attach['id'].']的文件['.$attach['file'].']无法写入附件缓存目录，dr_thumb函数无法调用');
            return ROOT_THEME_PATH.'assets/images/nopic.gif'.(CI_DEBUG ? '#文件['.$attach['file'].']无法写入附件缓存目录，dr_thumb函数无法调用' : '');
        }

        if ($width == 0 && $height == 0 && $water == 0) {
            return $attach['url'].(CI_DEBUG ? '#参数为空，将原样输出' : ''); // 原样输出
        }

        $this->image_info = getimagesize($file);
        if ($this->memory_limit($this->image_info)) {
            CI_DEBUG && log_message('debug', '图片[id#'.$attach['id'].']的URL['.$attach['url'].']分辨率太大导致服务器内存溢出，无法进行缩略图处理，已按原图显示');
            return $attach['url'].(CI_DEBUG ? '#分辨率太大导致服务器内存溢出，无法进行缩略图处理，已按原图显示' : ''); // 原样输出
        }

        // 创建缓存目录
        dr_mkdirs($cache_path.dirname($cache_file));

        // 开始处理图片
        if ($width > 0 || $height > 0) {
            if ($mode == 'crop') {
                $this->imageCropper($file, $cache_path . $cache_file, $width, $height);
                $this->clear();
            } else {
                $config['source_image'] = $file;
                $config['new_image'] = $cache_path . $cache_file;
                $config['width'] = $width;
                $config['height'] = $height;
                $config['dynamic_output'] = FALSE;
                $config['create_thumb'] = true;
                $config['maintain_ratio'] = $mode == 'auto' ? false : true;
                $config['thumb_marker'] = '';
                $config['master_dim'] = $mode;
                $this->initialize($config);
                $this->resize();
            }
        } else {
            copy($file, $cache_path.$cache_file);
        }

        if (!is_file($cache_path.$cache_file)) {
            CI_DEBUG && log_message('debug', '图片[id#'.$attach['id'].']的URL['.$attach['url'].']生成失败['.$cache_file.']原样输出');
            return $attach['url'].(CI_DEBUG ? '#生成失败['.$cache_file.']原样输出' : ''); // 原样输出
        }

        // 水印处理
        if ($water) {
            $data = \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'watermark');
            if ($data) {
                $data['source_image'] = $cache_path.$cache_file;
                $data['dynamic_output'] = false;
                $this->watermark($data);
            } else {
                CI_DEBUG && log_message('debug', '网站没有设置水印数据，dr_thumb函数中的水印参数将无效');
            }
        }

        // 钩子处理
        $rs = \Phpcmf\Hooks::trigger_callback('thumb_save', $cache_path, $cache_file);
        if ($rs && isset($rs['code']) && $rs['code'] && $rs['msg']) {
            return $rs['msg'];
        }

        return $cache_url.$cache_file;
    }

    // 处理图片大小是否溢出内存（图片分辨率，图片对象的width和height ）X（图片的通道数，一般是3）X 1.7
    public function memory_limit($img) {
        $max = ($img[0] * $img[1] * 3 * 1.7)/1024/1024;
        $limit = intval(ini_get("memory_limit")) / 1.7; // 多预留一些内存
        return $limit && $limit - $max < 0;
    }

    public function base64($file) {
        $base64_file = '';
        if (file_exists($file)) {
            $mime_type= mime_content_type($file);
            $base64_data = base64_encode(file_get_contents($file));
            $base64_file = 'data:'.$mime_type.';base64,'.$base64_data;
        }
        return $base64_file;
    }

    protected function imageCropper_size($target_width, $target_height) {

        $source_width = $this->image_info[0];
        $source_height = $this->image_info[1];
        $target_width = (int)$target_width;
        $target_height = (int)$target_height;
        $source_ratio = $source_height / $source_width;
        $target_ratio = $target_height / $target_width;
        if ($source_ratio > $target_ratio) {
            // image-to-height
            $cropped_width = $source_width;
            $cropped_height = $source_width * $target_ratio;
        } elseif ($source_ratio < $target_ratio) {
            //image-to-widht
            $cropped_width = $source_height / $target_ratio;
            $cropped_height = $source_height;
        } else {
            //image-size-ok
            $cropped_width = $source_width;
            $cropped_height = $source_height;
        }

        return [floor($cropped_width), floor($cropped_height)];
    }

    // 图片剪切函数可继承
    protected function imageCropper($source_path, $new_path, $target_width, $target_height){

        $source_mime  = $this->image_info['mime'];
        switch ($source_mime){
            case 'image/gif':
                $source_image = imagecreatefromgif($source_path);
                break;
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($source_path);
                break;
            case 'image/webp':
                if (!function_exists('imagecreatefromwebp')) {
                    $source_image = imagecreatefromjpeg($source_path);
                } else {
                    $source_image = imagecreatefromwebp($source_path);
                }
                break;
            default:
                return ;
                break;
        }

        $source_width = max(1, $this->image_info[0]);
        $source_height = $this->image_info[1];
        list($source_width, $source_height) = $this->_fix_orientation($source_path, $source_width, $source_height);

        $target_width = max((int)$target_width, 1);
        $target_height = max((int)$target_height, 1);
        $source_ratio = ($source_height / $source_width);
        $target_ratio = ($target_height / $target_width);
        if ($source_ratio > $target_ratio) {
            // image-to-height
            $cropped_width = $source_width;
            $cropped_height = floor($source_width * $target_ratio);
            $source_x = 0;
            $source_y = floor(($source_height - $cropped_height) / 2);
        } elseif ($source_ratio < $target_ratio){
            //image-to-widht
            $cropped_width = floor($source_height / $target_ratio);
            $cropped_height = $source_height;
            $source_x = floor(($source_width - $cropped_width) / 2);
            $source_y = 0;
        } else {
            //image-size-ok
            $cropped_width = $source_width;
            $cropped_height = $source_height;
            $source_x = 0;
            $source_y = 0;
        }

        $target_image = imagecreatetruecolor($target_width, $target_height);
        $cropped_image = imagecreatetruecolor($cropped_width, $cropped_height);

        $color = imagecolorallocate($target_image, 255, 255, 255); //2.上色
        imagecolortransparent($target_image, $color); //3.设置透明色
        imagefill($target_image, 0, 0, $color); //4.填充透明色

        $color = imagecolorallocate($cropped_image, 255, 255, 255); //2.上色
        imagecolortransparent($cropped_image, $color); //3.设置透明色
        imagefill($cropped_image, 0, 0, $color); //4.填充透明色

        // copy
        imagecopy($cropped_image, $source_image, 0, 0, $source_x, $source_y, $cropped_width, $cropped_height);
        // zoom
        imagecopyresampled($target_image, $cropped_image, 0, 0, 0, 0, $target_width, $target_height, $cropped_width, $cropped_height);

        imagejpeg($target_image, $new_path, 100);
        imagedestroy($source_image);
        imagedestroy($target_image);
        imagedestroy($cropped_image);
    }

    //..////..//////.....//////////.......///////////

    // 图片方向修复
    protected function _fix_orientation($imgsrc, $width, $height) {

        if (function_exists('exif_read_data')) {
            $exif = exif_read_data($imgsrc);
            if(!empty($exif['Orientation'])) {
                switch($exif['Orientation']) {
                    case 8:
                        return [$height, $width];
                        break;
                    case 6:
                        return [$height, $width];
                        break;
                }
            }
        }

        return [$width, $height];
    }

    // 图片压缩处理
    public function reduce($imgsrc, $cw) {

        list($width, $height, $type) = getimagesize($imgsrc);
        list($width, $height) = $this->_fix_orientation($imgsrc, $width, $height);
        if ($type != 18 && strpos($imgsrc, '.webp')) {
            $type = 18;
        }

        if ($width > $cw) {
            $per = $cw / $width;//计算比例
            $new_width = floor($width * $per); //压缩后的图片宽
            $new_height = floor($height * $per); //压缩后的图片高
            switch ($type) {
                case 1:
                    // gif
                    break;
                case 2:
                    //header('Content-Type:image/jpeg');
                    $image_wp = imagecreatetruecolor($new_width, $new_height);
                    $image = imagecreatefromjpeg($imgsrc);
                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    //90代表的是质量、压缩图片容量大小
                    imagejpeg($image_wp, $imgsrc, 100);
                    imagedestroy($image_wp);
                    imagedestroy($image);
                    break;
                case 3:
                    header('Content-Type:image/png');
                    $image_wp = imagecreatetruecolor($new_width, $new_height);
                    $image = imagecreatefrompng($imgsrc);

                    //2.上色
                    $color=imagecolorallocate($image_wp,255,255,255);
                    //3.设置透明
                    imagecolortransparent($image_wp,$color);
                    imagefill($image_wp,0,0,$color);

                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    //90代表的是质量、压缩图片容量大小
                    imagejpeg($image_wp, $imgsrc, 100);
                    imagedestroy($image_wp);
                    imagedestroy($image);
                    break;
                case 18:
                    header('Content-Type:image/webp');
                    $image_wp = imagecreatetruecolor($new_width, $new_height);
                    if (!function_exists('imagecreatefromwebp')) {
                        $image = imagecreatefromjpeg($imgsrc);
                    } else {
                        $image = imagecreatefromwebp($imgsrc);
                    }
                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    //90代表的是质量、压缩图片容量大小
                    imagewebp($image_wp, $imgsrc, 100);
                    imagedestroy($image_wp);
                    imagedestroy($image);
                    break;
            }
        } else {
            CI_DEBUG && log_message('debug', '系统要求宽度>'.$cw.'px才进行压缩处理，当前图片宽度='.$width.'，不满足压缩条件：'.$imgsrc);
        }

        return;
    }

}
