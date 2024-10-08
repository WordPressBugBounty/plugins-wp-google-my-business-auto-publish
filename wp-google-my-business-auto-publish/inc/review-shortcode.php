<?php
// shortcode
function wp_google_my_business_auto_publish_review_shortcode( $atts ) {


    //enqueue styles and scripts
    wp_enqueue_script(array('slick','custom-frontend-script','read-more-gmb'));   
    wp_enqueue_style(array('custom-frontend-style','font-awesome-icons','slick-style'));

    $a = shortcode_atts( array(
            'location' => '', 
            'type' => 'slider', //also accepts false 
            'minimum-stars' => 5,
            'sort-by' => 'date', //also accepts random and stars
            'sort-order' => 'desc', //also accepts asc
            'review-amount' => 200,
            'slides-page' => 1, 
            'slides-scroll' => 1, 
            'autoplay' => 'false', //also accepts true 
            'speed' => 5000,
            'transition' => 'slide', //also accepts fade
            'read-more' => 'true', //also accepts false 
            'show-stars' => 'true', //also accepts false 
            'show-date' => 'true', //also accepts false 
            'show-quotes' => 'true', //also accepts false 
    ), $atts );
    
    //lets validate the parameters
    $errors = false;

    if( strlen($a['location']) < 55 || strlen($a['location']) > 65 || strpos($a['location'], 'accounts') === false || strpos($a['location'], 'locations') === false ){
        $errors = true;
    } 

    $accepted_values = array('slider','grid');
    if( !in_array($a['type'], $accepted_values) ){
        $errors = true;
    }

    if( !is_numeric( $a['minimum-stars'] ) ){
        $errors = true;
    }

    $accepted_values = array('date','random','stars');
    if( !in_array($a['sort-by'], $accepted_values) ){
        $errors = true;
    }

    $accepted_values = array('asc','desc');
    if( !in_array($a['sort-order'], $accepted_values) ){
        $errors = true;
    }

    if( !is_numeric( $a['review-amount'] ) ){
        $errors = true;
    }

    if( !is_numeric( $a['slides-page'] ) ){
        $errors = true;
    }

    if( !is_numeric( $a['slides-scroll'] ) ){
        $errors = true;
    }

    $accepted_values = array('false','true');
    if( !in_array($a['autoplay'], $accepted_values) ){
        $errors = true;
    }

    if( !is_numeric( $a['speed'] ) ){
        $errors = true;
    }

    $accepted_values = array('slide','fade');
    if( !in_array($a['transition'], $accepted_values) ){
        $errors = true;
    }

    $accepted_values = array('false','true');
    if( !in_array($a['read-more'], $accepted_values) ){
        $errors = true;
    }

    $accepted_values = array('false','true');
    if( !in_array($a['show-stars'], $accepted_values) ){
        $errors = true;
    }

    $accepted_values = array('false','true');
    if( !in_array($a['show-date'], $accepted_values) ){
        $errors = true;
    }

    $accepted_values = array('false','true');
    if( !in_array($a['show-quotes'], $accepted_values) ){
        $errors = true;
    }

    if($errors){
        return __('There were errors in your shortcode, please use the shortcode builder on the plugin settings page.','wp-google-my-business-auto-publish');
    } else {
        return wp_google_my_business_auto_publish_review_shortcode_content($a['location'],$a['type'],intval($a['minimum-stars']),$a['sort-by'],$a['sort-order'],intval($a['review-amount']),intval($a['slides-page']),intval($a['slides-scroll']),$a['autoplay'],intval($a['speed']),$a['transition'],$a['read-more'],$a['show-stars'],$a['show-date'],$a['show-quotes']);
    }

}
add_shortcode('gmb-review', 'wp_google_my_business_auto_publish_review_shortcode');


function wp_google_my_business_auto_publish_review_shortcode_content($location,$type,$minimumStars,$sortBy,$sortOrder,$reviewAmount,$slidesPage,$slidesScroll,$autoplay,$speed,$transition,$readMore,$showStars,$showDate,$showQuotes) {
    
    
    $html = '';

    //get reviews
    $reviews = wp_google_my_business_auto_publish_get_reviews($location);

    if($reviews !== 'ERROR'){

        //used for ordering data
        $reviewsParsed = array();

        //used to get data later on
        $reviewsData = array();

        //used to translate reviews
        $ratingTranslated = array(
            'FIVE' => 5,
            'FOUR' => 4,
            'THREE' => 3,
            'TWO' => 2,
            'ONE' => 1);

        //excluded reviews
        //getthe exclude setting and turn it into array
        $options = get_option('wp_google_my_business_auto_publish_settings');
        $existingSetting = $options['wp_google_my_business_auto_publish_hide_reviews'];

        if(isset($existingSetting)){
            $settingToArray = explode(",",$existingSetting);     
        } else {
            $settingToArray = array();    
        }

        foreach($reviews as $review){
            //only add items to array if they meet the minimum star rating
            if($ratingTranslated[$review['starRating']] >= $minimumStars && !in_array($review['name'],$settingToArray)){
                //work out the array key for sorting
                if($sortBy == 'date' || $sortBy == 'random'){
                    $reviewsParsed[$review['name']] = $review['createTime'];
                } else {
                    //we are going to sort by stars
                    $reviewsParsed[$review['name']] = $ratingTranslated[$review['starRating']];    
                }

                //the review may not have a comment so let's resolve this
                if(array_key_exists('comment',$review)){
                    $review_comment = $review['comment'];
                } else {
                    $review_comment = '';    
                }

                //lets store the data of the review for later retrieval
                $reviewsData[$review['name']] = array(
                    'reviewer' => $review['reviewer']['displayName'],
                    'date' => $review['createTime'],
                    'rating' => $ratingTranslated[$review['starRating']],
                    'comment' => $review_comment
                );
            }
        }

        

        //lets sort our sorting array appropriately
        if($sortOrder == 'desc'){
            arsort($reviewsParsed);
        } else {
            asort($reviewsParsed);
        }

        //if the order is random lets shuffle things around
        if($sortBy == 'random'){
            $shuffleKeys = array_keys($reviewsParsed);
            shuffle($shuffleKeys);
            $sortedReviews = array();
            foreach($shuffleKeys as $key) {
                $sortedReviews[$key] = $reviewsParsed[$key];
            } 
            $reviewsParsed = $sortedReviews;
        }

        //if fade is necessary
        if($transition == 'fade'){
            $transition = '"fade": true,"cssEase": "linear",';
        } else {
            $transition = '"fade": false,';    
        }

        //output custom css for readmore version
        if($readMore == 'true'){
            $html .= '<style>.gmb-reviews .slick-list {height: auto !important;}</style>';
        }

        if($type == "slider"){
            $html .= '<div class="gmb-reviews make-me-slick" data-slick=\'{'.esc_attr($transition).' "adaptiveHeight": true,"arrows": false,"dots": true,"infinite": true,"autoplaySpeed":'.$speed.',"slidesToShow":'.esc_attr($slidesPage).', "slidesToScroll":'.esc_attr($slidesScroll).', "autoplay":'.esc_attr($autoplay).'    }\'>';
        } else {
            $html .= '<div class="gmb-reviews">';    
        }

            $count = 1;

            //now lets actually output the reviews
            foreach($reviewsParsed as $key=>$value){

                if($count++ > $reviewAmount) break;

                $html .= '<div class="gmb-review" data="'.esc_attr($key).'"><div class="gmb-review-inner">';


                    //actual comment
                    //only show comment if comment has a length
                    if(strlen($reviewsData[$key]['comment'])>0){

                        if($showQuotes == 'true'){
                            $quoteLeft = '<i class="review-quotes fa fa-quote-left" aria-hidden="true"></i>';
                            $quoteRight = '<i class="review-quotes fa fa-quote-right" aria-hidden="true"></i>';
                        } else {
                            $quoteLeft = '';
                            $quoteRight = '';    
                        }

                        //do readmore
                        if($readMore == 'true'){
                            $reviewCommentClass = 'review-comment-readmore';
                        } else {
                            $reviewCommentClass = 'review-comment';    
                        }

                        $allowed_html = array(
                            'img' => array(
                                'draggable'  => array(),
                                'role'    => array(),
                                'class'  => array(),
                                'alt' => array(),
                                'src' => array(),
                             ),
                        );

                        $html .= '<span class="'.esc_attr($reviewCommentClass).'">'.$quoteLeft.wp_kses($reviewsData[$key]['comment'], $allowed_html).$quoteRight.'</span>';
                    }

                    //display stars only if we have to
                    if($showStars == "true"){

                        $stars = '';
                        for ($i = 0 ; $i < $reviewsData[$key]['rating']; $i++){ 
                            $stars .= '<i class="fa fa-star" aria-hidden="true"></i>'; 
                        }

                        $html .= '<span class="review-rating">'.$stars.'</span>'; //does not need to be escaped as static html
                    }


                    $html .= '<span class="review-reviewer">'.esc_html($reviewsData[$key]['reviewer']).'</span>';

                    //display date only if we have to
                    if($showDate == "true"){

                        $niceDate = strtotime($reviewsData[$key]['date']);
                        $niceDate = date(get_option('date_format'),$niceDate);

                        $html .= '<span class="review-date">'.esc_html($niceDate).'</span>';
                    }    


                $html .= '</div></div>';

            }

        $html .= '</div>';
    }
    
    return $html;

}    


?>