<?php
// functions
/**
 *Gets the thrivehive form option
 *@return string text containing the form html
 **/
function th_display_form( $atts ){

    wp_enqueue_style('thrivehive-grid');

    $account_id = get_option('th_tracking_code');
    if(!isset($atts['id'])){
        if(!isset($atts['type'])){
            $contact_form_id = get_option('th_contactform_id');

            if($contact_form_id){
                return formGenerator($contact_form_id, $account_id);
            }
            else {
                return get_option('th_form_html');
            }
        }
        else{
            //write query to get database form with the type and return the html
            $form = get_default_form_by_type($atts['type']);
            if($form != null) {
                return $form['html'];
            }
            else{
                $contact_form_id = get_option('th_contactform_id');

                if( $contact_form_id ){
                    return formGenerator($contact_form_id, $account_id);
                }
            }
        }
    }

    $id = $atts['id'];
    $form = get_form_from_id($id);
    $html = $form['html'];
    return $html;
}

/**
 *Gets the phone number option for thrivehive
 *@return string contains the phone number option
 **/
function th_display_phone( $atts ){
    $num = get_option('th_phone_number');
    $num = preg_replace('/[^\d]/','', $num);
    $num = preg_replace( '/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $num );
    return $num;
}

/**
 *Displays a thrivehive button
 *@return string css for the specified thrivehive button
 *
 * todo: refactor this so the widget and shortcode output the same html
 **/
function th_display_button ($atts){
    $id = isset($atts['id']) ? $atts['id'] : 1;
    $buttonOptions = get_thrivehive_button( $id );
    $css =  stripslashes($buttonOptions['generated_css']);
    $text = stripslashes($buttonOptions['text']);
    $url = stripslashes($buttonOptions['url']);
    $target = stripslashes($buttonOptions['target']);

    return  "<a class='thrivehive-button' target='$target' style='$css display: inline-block;' href='$url'>$text</a>";
}

/**
 *Displays a wysiwyg button
 *@return string css for the specified thrivehive button
 *
 * todo: refactor this so the widget and shortcode output the same html
 **/
function th_display_wysiwyg_button ($atts){
    $id = isset($atts['id']) ? $atts['id'] : 1;
    $buttonOptions = get_wysiwyg_button( $id );
    $text = stripslashes($buttonOptions['text']);
    $url = stripslashes($buttonOptions['url']);
    $target = stripslashes($buttonOptions['target']);
    $classes =  stripslashes($buttonOptions['classes']);

    return  "<a class='$classes' target='$target' href='$url'>$text</a>";
}

function th_display_address($atts){
    return get_option('th_company_address');
}

function th_map(){
    $address = urlencode(str_replace("<br/>", " ", get_option('th_company_address')));
    return "<iframe frameborder='0' allowfullscreen width='375' height='205' src='//www.google.com/maps/embed/v1/place?&q=$address&key=AIzaSyACA63DJmSWnuOJ62QZYLF2bYShQeiu68Q'></iframe>";
}

function th_display_gallery($atts){
    $fake_shortcode = '';

    if (isset($atts['isslider']) && $atts['isslider'] === 'true') {
        $fake_shortcode = '[sugar_slider';
    } else {
        $fake_shortcode = '[gallery';
    }

    if(is_array($atts)){
        foreach($atts as $attName => $attValue){
            $fake_shortcode .= " $attName = \"$attValue\"";
        }
    }

    $fake_shortcode .= ']';

    // We must remove the srcset attribute, or some images will not show up in
    // the editor
    add_filter( 'wp_calculate_image_srcset', '__return_false' );

    return do_shortcode($fake_shortcode);
}

function th_display_pdf($atts){
    $fake_shortcode = '<div>[pdf-embedder';
    $file_found = false;
    $show_image = true;
    $download = false;
    $url = null;

    if(is_array($atts)){
        foreach ($atts as $attName => $attValue) {
            if (($attName == "url" || $attName == "file") && !empty($attValue)) {
                $file_found = true;
                $url = $attValue;
                $attName = "url";
            }
            if ($attName == "save" && $attValue == 1) {
                $download = true;
            }
            if ($attName == "width" && strpos($attValue, "%") !== false) {
                $attValue = "max";
            }
            if ($attName == "hide" && $attValue == 1) {
                $show_image = false;
            }
            $fake_shortcode .= " $attName = \"$attValue\"";
        }
    }

    if (!$file_found) {
        return;
    }
    if (!$show_image) {
        return do_shortcode('<div><a href="'.$url.'">Download PDF</a></div>');
    }
    if ($download) {
        $fake_shortcode .= ']<a href="'.$url.'">Download PDF</a></div>';
    } else {
        $fake_shortcode .= ']</div>';
    }

    return do_shortcode($fake_shortcode);
}

function th_display_snippet($atts){
    if (isset($atts['id'])) {
        $id = $atts['id'];
        $snippet = get_thrivehive_snippet($id);
        if($snippet){
            $snippet = stripslashes_deep($snippet);
            extract($snippet);  // get $css, $html, $javascript, $name, $id
            // Use $rendered_source if available.  Otherwise (legacy snippets), render it from the parts.
            $html = do_shortcode($html);
            if (empty($rendered_source)) {
                $html_from_parts = "<style>$css</style>$html<script>$javascript</script>";
                if (is_thrivehive_wysiwyg()) {
                    return $html_from_parts;
                }
                return "<div>$html_from_parts</div>";
            } else {
                return do_shortcode($rendered_source);
            }
        }
    }
}

function th_display_youtube($atts){
    if(isset($atts['id'])){
        $id = $atts['id'];
        $width = isset( $atts['width'] ) ? $atts['width'] : '100%';
        $height = isset( $atts['height'] ) ? $atts['height'] : '315';
        $allowfullscreen = isset( $atts['allowfullscreen'] ) && $atts['allowfullscreen'] == 'false' ? '' : 'allowfullscreen';
        $autoplay = isset( $atts['autoplay']) && $atts['autoplay'] == "true" ? 1 : null;
        $name = isset( $atts['name'] ) ? $atts['name'] : 'Video';

        $query = http_build_query(array(
            'enablejsapi' => 1, // allow javascript api (for tracking)
            'autoplay' => $autoplay, // user settable autoplay
            'rel' => 0, // hide related videos
            'modestbranding' => 1, // modest YouTube branding,
            'origin' => get_bloginfo('url')
        ));
        return( "<iframe id='thrivehive-$id' width='$width' height='$height' src='//www.youtube.com/embed/$id?$query' frameborder='0' $allowfullscreen></iframe>\n<script>cat.instrumentYouTubeIframe( document.getElementById('thrivehive-$id'), '$name' );</script>");
    }
}
?>
