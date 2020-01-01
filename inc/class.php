<?php
/*
* wp_super_sticky_notes Class 
*/

if (!class_exists('wp_super_sticky_notesClass')) {
    class wp_super_sticky_notesClass{
        public $plugin_url;
        public $plugin_dir;
        public $wpdb;
        public $option_tbl; 
        
        /**Plugin init action**/ 
        public function __construct() {
            global $wpdb;
            $this->plugin_url 				= wp_super_sticky_notesURL;
            $this->plugin_dir 				= wp_super_sticky_notesDIR;
            $this->wpdb 					= $wpdb;	
            $this->option_tbl               = $this->wpdb->prefix . 'options';
         
            $this->init();
        }

        private function init(){

            //Backend Script
            add_action( 'admin_enqueue_scripts', array($this, 'larasoftNote_backend_script') );
            //Frontend Script
            add_action( 'wp_enqueue_scripts', array($this, 'larasoftbd_Note_frontend_script') );

            //Add Menu Options
            add_action('admin_menu', array($this, 'sticky_notes_admin_menu_function'));

            // Add Theme Options to Admin Bar
            add_action('admin_bar_menu', array($this, 'sticky_notes_admin_bar_menu_function'), 55);

            /* Send item field */ 
            add_action('wp_ajax_nopriv_sendtonotesajax', array($this, 'sendtonotesajax'));
            add_action( 'wp_ajax_sendtonotesajax', array($this, 'sendtonotesajax') );

            /* Send item to allcomment */ 
            add_action('wp_ajax_nopriv_allcommentajax', array($this, 'allcommentajax'));
            add_action( 'wp_ajax_allcommentajax', array($this, 'allcommentajax') );

            /* Send item to deletecomment */ 
            add_action('wp_ajax_nopriv_deletecommentajax', array($this, 'deletecommentajax'));
            add_action( 'wp_ajax_deletecommentajax', array($this, 'deletecommentajax') );

            //note save 
            add_action('admin_init', array($this, 'notes_save_create_db'));

            //Store logininid to cookies
            add_action('init', array($this, 'storeloginidtocookies'));

            //add filter
            add_filter( 'the_content', array($this, 'filter_the_content_in_the_main_loop') );

            // Shortcode for frontend use
            add_shortcode( 'all-sticky-comments', array($this, 'larasoftbd_question_lists_shortcode') );
            
        }

        /*
        * Store Login-id to cookies
        */
        public function storeloginidtocookies(){
            if(is_user_logged_in()){
                setcookie("sticky_id", get_current_user_id() ,time()+31556926 ,'/');// where 31556926 is total seconds for a year.
            }
        }

        /*
        * Admin Menu
        */
        function sticky_notes_admin_menu_function(){
            add_menu_page( 'All Sticky Notes', 'All Sticky Notes', 'manage_options', 'sticky-notes-menu', array($this, 'submenufunction'), 'dashicons-list-view', 50 );
        }

        // Add Theme Options to Admin Bar Menu
        function sticky_notes_admin_bar_menu_function() {
            global $wp_admin_bar;
            global $post;
            // echo 'pageid : ' . $post->ID;
            $oldCommentUrl = get_the_permalink( get_option( 'allcommentpage', 1 ) );
            $wp_admin_bar->add_menu( array(
                'id'        => 'admin_bar_custom_menu',
                'title'     => '<span class="ab-icon"></span>'.__( 'Sticky Notes', 'some-textdomain' ),
                'href'      => '#'
            ) );
            $wp_admin_bar->add_menu( array(
                'parent'    => 'admin_bar_custom_menu',
                'id'        => 'note_new_comment',
                'title'     => __( 'New Comment', 'some-textdomain' ),
                'href'      => get_the_permalink( $post->ID ) . '?note=1',
            ) );
            $wp_admin_bar->add_menu( array(
                'parent'    => 'admin_bar_custom_menu',
                'id'        => 'note_old_comments',
                'title'     => __( 'Old Comments', 'some-textdomain' ),
                'href'      => $oldCommentUrl
            ) );

            $wp_admin_bar->add_menu( array(
                'id'        => 'admin_bar_custom_menu2',
                'title'     => '<span class="ab-icon"></span>'.__( 'All Sticky Notes', 'some-textdomain' ),
                'href'      => 'admin.php?page=sticky-notes-menu'
            ) );

        }

        /*
        * Appointment backend Script
        */
        function larasoftNote_backend_script(){
            
            wp_enqueue_style( 'larasoftNoteCSS', $this->plugin_url . 'asset/css/note_backend.css', array(), true, 'all' );
            wp_enqueue_script( 'larasoftNote', $this->plugin_url . 'asset/js/ls_note_backend.js', array(), true );
            //ajax
            wp_localize_script( 'larasoftNote', 'notesAjax', admin_url( 'admin-ajax.php' ));

        }

        /*
        * Appointment frontend Script
        */
        function larasoftbd_Note_frontend_script(){
            global $post;
            global $wpdb;

            $noteoptions = array();
            if(isset($_COOKIE['noteoptions'])):
            $noteoptions = json_decode(stripcslashes($_COOKIE['noteoptions']));
            endif;

            $current_user_id = (isset($_COOKIE['sticky_id'])) ? $_COOKIE['sticky_id'] : get_current_user_id();
            $current_page_id = $post->ID;
            $current_page_url = get_permalink( $current_page_id );

            $status = (isset($_REQUEST['note']) && $_REQUEST['note'] == 1) ? 'active' : '';

            $table_name = $wpdb->prefix . 'super_sticky_notes';
            $note_values = $wpdb->get_results("SELECT `id`, `note_values` FROM $table_name WHERE `user_id` = $current_user_id AND `page_id` = $current_page_id ", OBJECT);  

            $next_conv_allowed = $wpdb->get_results("SELECT `id`, `next_conv_allowed` FROM $table_name WHERE `user_id` = $current_user_id AND `page_id` = $current_page_id ", OBJECT);

            $note_valuess = array();
            foreach($note_values as $single) $note_valuess[$single->id] = $single->note_values;

            $next_conv_alloweds = array();
            foreach($next_conv_allowed as $next_conv) $next_conv_alloweds[$next_conv->id] = $next_conv->next_conv_allowed;

            wp_enqueue_style( 'larasoftbd_NotetCSS', $this->plugin_url . 'asset/css/note_frontend.css', array(), true, 'all' );
            
            // Add the styles first, in the <head> (last parameter false, true = bottom of page!)
            wp_enqueue_style('qtip', $this->plugin_url . 'asset/qtip_asset/jquery.qtip.min.css', null, false, false);

            // Not using imagesLoaded? :( Okay... then this.
            
            
            wp_enqueue_script('qtipjs', $this->plugin_url . 'asset/qtip_asset/jquery.qtip.min.js', array(), time(), true);
            wp_enqueue_script('larasoftbd_NoteJS', $this->plugin_url . 'asset/js/ls_note_frontend.js', array('jquery'), time(), true);
            //ajax
            wp_localize_script( 'larasoftbd_NoteJS', 'notesAjax', 
                array(
                    'ajax' => admin_url( 'admin-ajax.php' ),
                    'current_page_id' => $post->ID,
                    'user_id' => $current_user_id,
                    'title' => get_the_title(),
                    'login_status' => (is_user_logged_in()) ? 'login':'logout',
                    'nottopcolor' => (isset($noteoptions->topoption)) ? $noteoptions->topoption : '',
                    'notetextbg' => (isset($noteoptions->texteditorbg)) ? $noteoptions->texteditorbg : '',
                    'textval' => $note_valuess,
                    'submitorreply' => $next_conv_alloweds,
                    'status' => $status,
                    'current_page_url' => $current_page_url
                )
            );
            
        }


        // sql data save queries 
        function notes_save_create_db() {

            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();

            $note_table = $wpdb->prefix . 'super_sticky_notes';
            $sql = "CREATE TABLE $note_table ( 
                id INT(20) NOT NULL AUTO_INCREMENT,
                user_id INT(20) NOT NULL,
                page_id VARCHAR(200) NOT NULL,
                parent_class VARCHAR(50) NOT NULL,
                current_Class VARCHAR(50) NOT NULL,
                note_position INT(20) NOT NULL,
                note_values TEXT NOT NULL,
                insert_time TIMESTAMP,
                title VARCHAR(100) NOT NULL, 
                note_status VARCHAR(20) NOT NULL,
                note_reply TEXT NOT NULL,
                note_repliedOn VARCHAR(20) NOT NULL,
                next_conv_allowed INT(5) NOT NULL,
                parent_id INT(20) NOT NULL,
                desable INT(5) NOT NULL DEFAULT 0,
                UNIQUE KEY id (id)
                ) $charset_collate;";
        
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            // DROP TABLE `gym_super_sticky_notes`
        }

        function allcommentajax(){
            $page_id = get_option( 'allcommentpage', 1 );
            $page_url = get_permalink( $page_id );
            echo json_encode(
                array(
                    'message' => 'success',
                    'page_url' => $page_url
                )
            );
            die();           
        }

        function deletecommentajax(){
            global $wpdb;

           $position = $_POST['position'];
           $table_name = $wpdb->prefix . 'super_sticky_notes';
           $wpdb->delete( $table_name, [ 'note_position' => $position] );

            echo json_encode(
                array(
                    'message' => 'success',
                    'position' => $position
                )
            );
            die();           
        }

        /*
        * Send Code as Sold
        * This action work when hover on a item from New Code
        * All this sold store in a option as json
        */
        function sendtonotesajax(){
            //insert data start
            global $wpdb;

            //all values
            $position = $_POST['position'];
            $current_page_id = $_POST['current_page_id'];
            $parentClass = $_POST['parentClass'];
            $current_Class = $_POST['currentClass'];
            $user_id = $_POST['user_id'];
            $text_content = $_POST['text_content'];
            $title = $_POST['title'];
            $next_conv_allowed = 0;
            $data_id = $_POST['data_id'];

            $table_name = $wpdb->prefix . 'super_sticky_notes';

            $j = '';
            if ($data_id != '' ) {
                $next_conv_allowedss = $wpdb->get_row("SELECT `next_conv_allowed` FROM ".$table_name." WHERE `id`=".$data_id."", OBJECT);
                $next_conv_allowedsss = $next_conv_allowedss->next_conv_allowed;

                if ($next_conv_allowedsss == 1 ){
                    $reply_id = $wpdb->get_row("SELECT `id` FROM ".$table_name." WHERE `parent_id`=".$data_id."", OBJECT);
                    $reply_ids = $reply_id->id;
                    if ($reply_ids != '' ) {

                        $wpdb->update( $table_name,
                        array(
                             'note_values' => $text_content
                            ),
                        array(
                            'id' => $reply_ids
                            ),
                        array('%s'),
                        array('%d')
                        );
                        $j = 'yes';

                    }else{
                        $wpdb->insert( 
                            $table_name, 
                            array(
                                'user_id' => $user_id,
                                'page_id' => $current_page_id,
                                'current_Class' => $current_Class,
                                'note_position' => $position,
                                'note_values' => $text_content,
                                'title' => $title,
                                'next_conv_allowed' => $next_conv_allowed,
                                'parent_id' => $data_id
                            ),
                            array('%d', '%d', '%s', '%d', '%s', '%s', '%d', '%d')
                        );
                        //insert data end
                        $j = 'no';
                    }

                }else{

                    $wpdb->update( $table_name,
                    array(
                         'note_values' => $text_content
                        ),
                    array(
                        'id' => $data_id
                        ),
                    array('%s'),
                    array('%d')
                    );
                    $j = 'yes';
                }
                
            }
            else{

                $wpdb->insert( 
                    $table_name, 
                    array(
                        'user_id' => $user_id,
                        'page_id' => $current_page_id,
                        'parent_class' => $parentClass,
                        'current_Class' => $current_Class,
                        'note_position' => $position,
                        'note_values' => $text_content,
                        'title' => $title,
                        'next_conv_allowed' => $next_conv_allowed
                    ),
                    array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%d')
                );
                //insert data end
                $j = 'no';
            }

            echo json_encode(
                array(
                    'message' => $j
                ));
            die();

        }
        
        
        function filter_the_content_in_the_main_loop( $content ) {
            if(get_option( 'visitor_allowed', 1 ) == 0){
                return $content;
            }
            $content = preg_replace("/<br\W*?\/>/", "<div class='br-replace'></div><p>", $content);
            // Check if we're inside the main loop in a single post page.
            //if ( is_single() && in_the_loop() && is_main_query() ) { c
                global $wpdb;
                $current_page_id = get_the_ID();
                $user_id = (isset($_COOKIE['sticky_id'])) ? $_COOKIE['sticky_id'] :  get_current_user_id();
                $table_name = $wpdb->prefix . 'super_sticky_notes';
                $all_page_id = $wpdb->get_results("SELECT `page_id` FROM $table_name WHERE `user_id` = $user_id", OBJECT);
                $all_page_ids = array();
                foreach ($all_page_id as $value)
                { 
                    $all_page_ids[] = $value->page_id;
                }
                $show_values = array();
                if(!isset($_REQUEST['note']) && in_array($current_page_id, $all_page_ids))
                {
                    // // echo 'yes';
                    $all_current_Class = $wpdb->get_results("SELECT `current_Class` FROM $table_name WHERE `user_id` = $user_id AND `page_id` = $current_page_id GROUP BY `current_Class`", OBJECT);
                    $all_current_Classs = array();
                    foreach ($all_current_Class as $value)
                    { 
                        $all_current_Classs[] = $value->current_Class;
                    }

                    $content = '<div class="supper_sticky_note">' . $content . '</div>';

                    $DOM = new DOMDocument();
                    $DOM->loadHTML($content);
                    $list = $DOM->getElementsByTagName('p');
                    $i = 0;



                    foreach($list as $p){
                        $p->setAttribute('class', 'p-class'.$i++);
                    }
                    $DOM=$DOM->saveHTML();
                    $content = $DOM;

                    // Add new data
                    $doc = new DOMDocument();
                    $doc->loadHTML($content);
                    $xpath = new DOMXPath($doc);
                   
                    for ($x = 0; $x < count($all_current_Classs); $x++) {

                     
                        
                        $classname= $all_current_Classs[$x];
                        
                        $elements = $xpath->query("//p[@class='$classname']");
                       
                        
                        $real_values= $elements->item(0)->nodeValue;

                        $all_note_position = $wpdb->get_results("SELECT `note_position` FROM $table_name WHERE `current_Class` = '".$classname."' AND `user_id` = $user_id AND `page_id` = $current_page_id ORDER BY `note_position`", OBJECT);
                        $all_note_positions = array();
                        foreach ($all_note_position as $note_position)
                        { 
                            $all_note_positions[] = $note_position->note_position;
                        }
                        // echo '<pre>';
                        // print_r($all_note_positions);
                        // echo '</pre>';
                        
                        $real_values_in_array = array();
                        $real_values_in_array = str_split($real_values, 1);

                        $my_html = 0;
                        foreach ($all_note_positions as $single_positions) {
                            $data_id = $wpdb->get_row("SELECT `id`, `next_conv_allowed` FROM $table_name WHERE `current_Class` = '".$classname."' AND `user_id` = $user_id AND `page_id` = $current_page_id AND `note_position` = $single_positions ", OBJECT);
                            $data_ids = json_decode(json_encode($data_id), true);
                            // echo 'Ids<br/><pre>';
                            // print_r($data_ids);
                            // echo '</pre>';

                            $data_idd = $data_ids['id'];
                            $dataActive = ($data_ids['next_conv_allowed'] == 1) ? 'allowed' : '';

                            $single_position = $single_positions + $my_html;

                            $actual_value_text = array_slice($real_values_in_array, 0, $single_position, true) +
                            array("my_html'.$my_html.'" => "<sub data-current='$classname' data-id='$data_idd' data-position='$single_positions' class='note-question ".$dataActive." '><span class='note-question-icon-button old'></span></sub>") +
                            array_slice($real_values_in_array, $single_position, count($real_values_in_array) - 1, true) ;


                            $real_values_in_array = $actual_value_text;
                            $my_html++;
                        }
                        $actual_value_texts = implode( $actual_value_text );

                        // $show_values[$classname] = $actual_value_texts;
                        $elements->item(0)->nodeValue = '';
                        $f = $doc->createDocumentFragment();
                        $f->appendXML($actual_value_texts);
                        $elements->item(0)->appendChild($f);
                    }

                    $DOM=$doc->saveHTML();
                    $content = $DOM;

                    
                }
                else{
                    $content = '<div class="supper_sticky_note">' . $content . '</div>';
                    $DOM = new DOMDocument();
                    $DOM->loadHTML($content);
                    $list = $DOM->getElementsByTagName('p');
                    $i = 0;
                    foreach($list as $p){
                        $p->setAttribute('class', 'p-class'.$i++);
                    }
                    $DOM=$DOM->saveHTML();
                    $content = $DOM;
                    
                }
            //}
        
            return $content;
        }

        // update Settings
        public function updateSettings($data){
            update_option( 'allcommentpage', $data );
        }


        function submenufunction(){

            global $wpdb;
            if (isset($_POST['status_message']))
            {
                $status = $_POST['status'];
                $status_id = $_POST['status_message'];

                $table_name = $wpdb->prefix . 'super_sticky_notes';
                $wpdb->update( $table_name,
                array(
                        'note_status' => $status
                    ),
                array(
                    'id'=> $status_id
                ),
                array('%s'),
                array('%d')
                );
            }
            if (isset($_POST['note_reply_ids']))
            {
                $status_ids = $_POST['note_reply_ids'];
                $note_reply = $_POST['note_reply_text'];
                $next_conv_allowed = $_POST['next_conv_allowed'];
                $note_repliedOn_date = date("Y-m-d");
                

                $table_name = $wpdb->prefix . 'super_sticky_notes';
                $wpdb->update( $table_name,
                array(
                        'note_reply' => $note_reply,
                        'next_conv_allowed' => $next_conv_allowed,
                        'note_repliedOn' => $note_repliedOn_date
                    ),
                array(
                    'id'=> $status_ids
                ),
                array('%s', '%d', '%s'),
                array('%d')
                );
            }
            if (isset($_POST['visitor_allowed']))
            {
                $visitor_allowed = $_POST['visitor_allowed'];

                    $option_name = 'visitor_allowed' ;
                    $new_value = 'red';
                    if ( get_option( $option_name ) !== false ) {
                        update_option( $option_name, $visitor_allowed );
                    } else {
                        $deprecated = null;
                        $autoload = 'no';
                        add_option( $option_name, $visitor_allowed, $deprecated, $autoload );
                    }
            }

            if(isset($_POST['allcommentpage'])) $this->updateSettings($_POST['allcommentpage']);

            ?>
            <div class="super-sticky-notes">
                <div class="sticky-setting-title"><div class=setting-icon><h1><?php _e('Sticky Question Settings', 'wp_super_sticky_notes'); ?></h1></div></div>
                <div class="sticky-top-bar">
                    <div class="tab">
                        <button class="tablinks active" onclick="openTab(event, 'all')"><?php _e('All', 'wp_super_sticky_notes'); ?></button><div class="tab-icons"></div>
                        <button class="tablinks" onclick="openTab(event, 'approved')"><?php _e('Approved', 'wp_super_sticky_notes'); ?></button><div class="tab-icons"></div>
                        <button class="tablinks" onclick="openTab(event, 'disapproved')"><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></button><div class="tab-icons"></div>
                        <button class="tablinks" onclick="openTab(event, 'settings')"><?php _e('Settings', 'wp_super_sticky_notes'); ?></button>
                    </div>
                    <div class="tab-search">
                        <form method="POST">
                            <input type="text" name="search_value" placeholder="Search here..." required>
                            <button class="tab-search-button" type="submit">Search</button>
                        </form>
                    </div>
                </div>

                <div id="all" class="tabcontent" style="display:block;" >

                    <table class="sticky-notes-data-table">
                        <tr class="note-heading-wrapper">
                            <th><?php _e('User', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Asked Question', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Page/Post', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('AskedOn', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Status', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Action', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Reply', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('RepliedOn', 'wp_super_sticky_notes'); ?></th>
                        </tr>
                        <?php
                            global $wpdb;

                            if (isset($_POST['search_value']))
                            {   
                                $search_value = $_POST['search_value'];
                                $table_name = $wpdb->prefix . 'super_sticky_notes';
                                $all_valus_notes = $wpdb->get_results("SELECT * FROM $table_name 
                                WHERE `note_values` LIKE '%".$search_value."%' 
                                OR `insert_time` LIKE '%".$search_value."%'
                                OR `note_status` LIKE '%".$search_value."%'
                                OR `note_reply` LIKE '%".$search_value."%' 
                                ", OBJECT);
                                $all_valus_notes = json_decode(json_encode($all_valus_notes), true);
                            }else{

                            $table_name = $wpdb->prefix . 'super_sticky_notes';
                            $all_valus_notes = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);                   
                            $all_valus_notes = json_decode(json_encode($all_valus_notes), true);
                            }
                            foreach ($all_valus_notes as $note_values){
                        ?>
                        <tr>
                            <td><?php $author_obj = get_user_by('id', $note_values['user_id']); echo $author_obj->data->user_nicename; ?></td>
                            <td><?php echo $note_values['note_values']; ?></td>
                            <td class="note-title"><a href="<?php echo get_permalink($note_values['page_id']); ?>" target="_blank"><?php echo $note_values['title']; ?></a></td>
                            <td><?php echo $note_values['insert_time']; ?></td>
                            <td class="note-status-view">
                                <?php if($note_values['note_status'] == ''){?>
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="approved" value="Approved" name="status"><?php _e('Approved', 'wp_super_sticky_notes'); ?></button>
                                        <button class="disapproved" value="Disapproved" name="status"><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }elseif($note_values['note_status'] == 'Approved'){ ?>
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="disapprovedd" value="Disapproved" name="status"><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }elseif($note_values['note_status'] == 'Disapproved'){ ?> 
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="approvedd" value="Approved" name="status"><?php _e('Approved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }?> 
                            </td>
                            <td>
                                <?php if($note_values['note_status'] == 'Disapproved'){?>
                                    <div class="disapproved-reply"><?php _e('REPLY', 'wp_super_sticky_notes'); ?></div>
                                <?php }else{
                                        $current_id = $note_values['id'];
                                    ?>
                                    <button class="reply"><?php _e('REPLY', 'wp_super_sticky_notes'); ?></button>
                                        <div class="modal-overlay">
                                        <div class="modal">
                                            <a class="close-modal">
                                            <svg viewBox="0 0 20 20">
                                                <path fill="#000000" d="M15.898,4.045c-0.271-0.272-0.713-0.272-0.986,0l-4.71,4.711L5.493,4.045c-0.272-0.272-0.714-0.272-0.986,0s-0.272,0.714,0,0.986l4.709,4.711l-4.71,4.711c-0.272,0.271-0.272,0.713,0,0.986c0.136,0.136,0.314,0.203,0.492,0.203c0.179,0,0.357-0.067,0.493-0.203l4.711-4.711l4.71,4.711c0.137,0.136,0.314,0.203,0.494,0.203c0.178,0,0.355-0.067,0.492-0.203c0.273-0.273,0.273-0.715,0-0.986l-4.711-4.711l4.711-4.711C16.172,4.759,16.172,4.317,15.898,4.045z"></path>
                                            </svg>
                                            </a>
                                            <div class="modal-content">

                                                <h3><?php _e('Write your reply', 'wp_super_sticky_notes'); ?></h3>
                                                <div class="note-reply-page">
                                                    <form method="POST">
                                                        <input type="hidden" name="note_reply_ids" value="<?php echo $current_id; ?>" />
                                                        <!-- <input type="text" name="note_reply_title" placeholder="Title"> -->
                                                        <textarea name="note_reply_text" placeholder="Write your reply.." style="height:200px"><?php echo $note_values['note_reply']; ?></textarea>
                                                        <div class="next-conversation">
                                                            <p><?php _e('Next Conversation Allowed', 'wp_super_sticky_notes'); ?></p>
                                                            <label class="switch">
                                                                <?php $checked = ($note_values['next_conv_allowed'] == 1) ? 'checked' : ''; ?>
                                                                <input type="hidden" name="next_conv_allowed" value="0" />
                                                                <input type="checkbox" name="next_conv_allowed" value="1" <?php echo $checked; ?>/>
                                                                <span class="slider round"></span>
                                                            </label>
                                                            <!-- <p class="checked-message"></p> -->
                                                        </div>
                                                        <input type="submit" class="note-reply" value="Reply">
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                        </div>
                                <?php }?>  
                            </td>
                            <td class="note-class-view"><?php if($note_values['note_status'] == 'Disapproved'){ ?> <div class="note-disapproved"><p><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></p></div> <?php }else{ echo $note_values['note_reply']; } ?></td>
                            <td><?php if($note_values['note_status'] == 'Disapproved'){ ?> <div class="note-disapproved"><p><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></p></div> <?php }else{ echo $note_values['note_repliedOn']; } ?></td>
                        </tr>
                        <?php
                            }
                        ?>
                    </table>
                </div>
                
                <div id="approved" class="tabcontent">

                    <table class="sticky-notes-data-table">
                        <tr class="note-heading-wrapper">
                            <th><?php _e('User', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Asked Question', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Page/Post', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('AskedOn', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Status', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Action', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Reply', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('RepliedOn', 'wp_super_sticky_notes'); ?></th>
                        </tr>
                        <?php
                            global $wpdb;

                            if (isset($_POST['search_value']))
                            {   
                                $search_value = $_POST['search_value'];
                                $table_name = $wpdb->prefix . 'super_sticky_notes';
                                $all_valus_notes = $wpdb->get_results("SELECT * FROM $table_name 
                                WHERE `note_status` = 'Approved' AND
                                `note_values` LIKE '%".$search_value."%' 
                                OR `insert_time` LIKE '%".$search_value."%'
                                OR `note_reply` LIKE '%".$search_value."%' 
                                ", OBJECT);
                                $all_valus_notes = json_decode(json_encode($all_valus_notes), true);
                            }else{

                            $table_name = $wpdb->prefix . 'super_sticky_notes';
                            $all_valus_notes = $wpdb->get_results("SELECT * FROM $table_name WHERE `note_status` = 'Approved' ", OBJECT);                   
                            $all_valus_notes = json_decode(json_encode($all_valus_notes), true);
                            }
                            foreach ($all_valus_notes as $note_values){
                        ?>
                        <tr>
                            <td><?php $author_obj = get_user_by('id', $note_values['user_id']); echo $author_obj->data->user_nicename; ?></td>
                            <td><?php echo $note_values['note_values']; ?></td>
                            <td class="note-title"><a href="<?php echo get_permalink($note_values['page_id']); ?>" target="_blank"><?php echo $note_values['title']; ?></a></td>
                            <td><?php echo $note_values['insert_time']; ?></td>
                            <td class="note-status-view">
                                <?php if($note_values['note_status'] == ''){?>
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="approved" value="Approved" name="status"><?php _e('Approved', 'wp_super_sticky_notes'); ?></button>
                                        <button class="disapproved" value="Disapproved" name="status"><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }elseif($note_values['note_status'] == 'Approved'){ ?>
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="disapprovedd" value="Disapproved" name="status"><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }elseif($note_values['note_status'] == 'Disapproved'){ ?> 
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="approvedd" value="Approved" name="status"><?php _e('Approved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }?> 
                            </td>
                            <td>
                                <?php if($note_values['note_status'] == 'Disapproved'){?>
                                    <div class="disapproved-reply"><?php _e('REPLY', 'wp_super_sticky_notes'); ?></div>
                                <?php }else{
                                    $current_id = $note_values['id'];
                                    ?>
                                    <button class="reply"><?php _e('REPLY', 'wp_super_sticky_notes'); ?></button>
                                        <div class="modal-overlay">
                                        <div class="modal">
                                            <a class="close-modal">
                                            <svg viewBox="0 0 20 20">
                                                <path fill="#000000" d="M15.898,4.045c-0.271-0.272-0.713-0.272-0.986,0l-4.71,4.711L5.493,4.045c-0.272-0.272-0.714-0.272-0.986,0s-0.272,0.714,0,0.986l4.709,4.711l-4.71,4.711c-0.272,0.271-0.272,0.713,0,0.986c0.136,0.136,0.314,0.203,0.492,0.203c0.179,0,0.357-0.067,0.493-0.203l4.711-4.711l4.71,4.711c0.137,0.136,0.314,0.203,0.494,0.203c0.178,0,0.355-0.067,0.492-0.203c0.273-0.273,0.273-0.715,0-0.986l-4.711-4.711l4.711-4.711C16.172,4.759,16.172,4.317,15.898,4.045z"></path>
                                            </svg>
                                            </a>
                                            <div class="modal-content">

                                                <h3><?php _e('Write your reply', 'wp_super_sticky_notes'); ?></h3>
                                                <div class="note-reply-page">
                                                    <form method="POST">
                                                        <input type="hidden" name="note_reply_ids" value="<?php echo $current_id; ?>" />
                                                        <!-- <input type="text" name="note_reply_title" placeholder="Title"> -->
                                                        <textarea name="note_reply_text" placeholder="Write your reply.." style="height:200px"><?php echo $note_values['note_reply']; ?></textarea>
                                                        <div class="next-conversation">
                                                            <p><?php _e('Next Conversation Allowed', 'wp_super_sticky_notes'); ?></p>
                                                            <label class="switch">
                                                                <?php $checked = ($note_values['next_conv_allowed'] == 1) ? 'checked' : ''; ?>
                                                                <input type="hidden" name="next_conv_allowed" value="0" />
                                                                <input type="checkbox" name="next_conv_allowed" value="1" <?php echo $checked; ?>/>
                                                                <span class="slider round"></span>
                                                            </label>
                                                        </div>
                                                        <input type="submit" class="note-reply" value="Reply">
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                        </div>
                                <?php }?>  
                            </td>
                            <td class="note-class-view"><?php if($note_values['note_status'] == 'Disapproved'){ ?> <div class="note-disapproved"><p><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></p></div> <?php }else{ echo $note_values['note_reply']; } ?></td>
                            <td><?php if($note_values['note_status'] == 'Disapproved'){ ?> <div class="note-disapproved"><p><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></p></div> <?php }else{ echo $note_values['note_repliedOn']; } ?></td>
                        </tr>
                        <?php
                            }
                        ?>
                    </table>

                </div>
                <div id="disapproved" class="tabcontent">

                    <table class="sticky-notes-data-table">
                        <tr class="note-heading-wrapper">
                            <th><?php _e('User', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Asked Question', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Page/Post', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('AskedOn', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Status', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Action', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('Reply', 'wp_super_sticky_notes'); ?></th>
                            <th><?php _e('RepliedOn', 'wp_super_sticky_notes'); ?></th>
                        </tr>
                        <?php
                            global $wpdb;

                            if (isset($_POST['search_value']))
                            {   
                                $search_value = $_POST['search_value'];
                                $table_name = $wpdb->prefix . 'super_sticky_notes';
                                $all_valus_notes = $wpdb->get_results("SELECT * FROM $table_name 
                                WHERE `note_status` = 'Disapproved' AND
                                `note_values` LIKE '%".$search_value."%' 
                                OR `insert_time` LIKE '%".$search_value."%'
                                OR `note_reply` LIKE '%".$search_value."%' 
                                ", OBJECT);
                                $all_valus_notes = json_decode(json_encode($all_valus_notes), true);
                            }else{

                            $table_name = $wpdb->prefix . 'super_sticky_notes';
                            $all_valus_notes = $wpdb->get_results("SELECT * FROM $table_name WHERE `note_status` = 'Disapproved' ", OBJECT);                   
                            $all_valus_notes = json_decode(json_encode($all_valus_notes), true);
                            }
                            foreach ($all_valus_notes as $note_values){
                        ?>
                        <tr>
                            <td><?php $author_obj = get_user_by('id', $note_values['user_id']); echo $author_obj->data->user_nicename; ?></td>
                            <td><?php echo $note_values['note_values']; ?></td>
                            <td class="note-title"><a href="<?php echo get_permalink($note_values['page_id']); ?>" target="_blank"><?php echo $note_values['title']; ?></a></td>
                            <td><?php echo $note_values['insert_time']; ?></td>
                            <td class="note-status-view">
                                <?php if($note_values['note_status'] == ''){?>
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="approved" value="Approved" name="status"><?php _e('Approved', 'wp_super_sticky_notes'); ?></button>
                                        <button class="disapproved" value="Disapproved" name="status"><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }elseif($note_values['note_status'] == 'Approved'){ ?>
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="disapprovedd" value="Disapproved" name="status"><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }elseif($note_values['note_status'] == 'Disapproved'){ ?> 
                                    <form method="POST">
                                        <input type="hidden" name="status_message" value="<?php echo $note_values['id']; ?>" />
                                        <button class="approvedd" value="Approved" name="status"><?php _e('Approved', 'wp_super_sticky_notes'); ?></button>
                                    </form>
                                <?php }?> 
                            </td>
                            <td>
                                <?php if($note_values['note_status'] == 'Disapproved'){?>
                                    <div class="disapproved-reply"><?php _e('REPLY', 'wp_super_sticky_notes'); ?></div>
                                <?php }else{
                                        $current_id = $note_values['id'];
                                    ?>
                                    <button class="reply"><?php _e('REPLY', 'wp_super_sticky_notes'); ?></button>
                                        <div class="modal-overlay">
                                        <div class="modal">
                                            <a class="close-modal">
                                            <svg viewBox="0 0 20 20">
                                                <path fill="#000000" d="M15.898,4.045c-0.271-0.272-0.713-0.272-0.986,0l-4.71,4.711L5.493,4.045c-0.272-0.272-0.714-0.272-0.986,0s-0.272,0.714,0,0.986l4.709,4.711l-4.71,4.711c-0.272,0.271-0.272,0.713,0,0.986c0.136,0.136,0.314,0.203,0.492,0.203c0.179,0,0.357-0.067,0.493-0.203l4.711-4.711l4.71,4.711c0.137,0.136,0.314,0.203,0.494,0.203c0.178,0,0.355-0.067,0.492-0.203c0.273-0.273,0.273-0.715,0-0.986l-4.711-4.711l4.711-4.711C16.172,4.759,16.172,4.317,15.898,4.045z"></path>
                                            </svg>
                                            </a>
                                            <div class="modal-content">

                                                <h3><?php _e('Write your reply', 'wp_super_sticky_notes'); ?></h3>
                                                <div class="note-reply-page">
                                                    <form method="POST">
                                                        <input type="hidden" name="note_reply_ids" value="<?php echo $current_id; ?>" />
                                                        <!-- <input type="text" name="note_reply_title" placeholder="Title"> -->
                                                        <textarea name="note_reply_text" placeholder="Write your reply.." style="height:200px"><?php echo $note_values['note_reply']; ?></textarea>
                                                        <div class="next-conversation">
                                                            <p><?php _e('Next Conversation Allowed', 'wp_super_sticky_notes'); ?></p>
                                                            <label class="switch">
                                                                <?php $checked = ($note_values['next_conv_allowed'] == 1) ? 'checked' : ''; ?>
                                                                <input type="hidden" name="next_conv_allowed" value="0" />
                                                                <input type="checkbox" name="next_conv_allowed" value="1" <?php echo $checked; ?>/>
                                                                <span class="slider round"></span>
                                                            </label>
                                                        </div>
                                                        <input type="submit" class="note-reply" value="Reply">
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                        </div>
                                <?php }?>  
                            </td>
                            <td class="note-class-view"><?php if($note_values['note_status'] == 'Disapproved'){ ?> <div class="note-disapproved"><p><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></p></div> <?php }else{ echo $note_values['note_reply']; } ?></td>
                            <td><?php if($note_values['note_status'] == 'Disapproved'){ ?> <div class="note-disapproved"><p><?php _e('Disapproved', 'wp_super_sticky_notes'); ?></p></div> <?php }else{ echo $note_values['note_repliedOn']; } ?></td>
                        </tr>
                        <?php
                            }
                        ?>
                    </table>

                </div>


                <!-- Settings -->
                <div id="settings" class="tabcontent">
                    <div class="settingsInner">
                        <form id="settingsForm" method="post" action="">
                            <table class="sticky-notes-data-table">
                                <tbody>
                                    <tr>
                                        <th class="text-left"><?php _e('All Comment\'s page', 'wp_super_sticky_notes' ); ?></th>
                                        <td class="text-left">
                                            <?php $allpages = get_all_page_ids(); ?>
                                            <select name="allcommentpage" class="form-control" id="allcommentpage">
                                                <?php   foreach( $allpages as $sp):
                                                    $selected = (get_option( 'allcommentpage') == $sp ) ? 'selected' : '';
                                                    ?>
                                                    <option <?php echo $selected; ?> value="<?php echo $sp; ?>"><?php echo get_the_title($sp); ?></option>
                                                <?php endforeach; ?>

                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                            <th class="text-left"><?php _e('All Comment\'s Shortcode', 'wp_super_sticky_notes'); ?></th>
                                            <td class="text-left"><?php echo '[all-sticky-comments]'; ?></td>

                                    </tr>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>


                <div class="visitors-conversation">
                    <form method="POST">
                        <div class="visitor-conversation">
                            <p><?php _e('Visitor can see the conversation ?', 'wp_super_sticky_notes'); ?></p>
                            <label class="switch">
                                <?php $checked = ( get_option( 'visitor_allowed' ) == 1) ? 'checked' : ''; ?>
                                <input type="hidden" name="visitor_allowed" value="0" />
                                <input type="checkbox" class="checbox-visitor" onChange="submit();" name="visitor_allowed" value="1" <?php echo $checked; ?>/>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </form>
                </div>


            </div>
            <?php
        } // Admin page

        public function larasoftbd_question_lists_shortcode(){
            ob_start();
            require_once($this->plugin_dir . 'template/user_question_lists.php');
            $output = ob_get_clean();
            return $output;
            wp_reset_query();
        }
  
                
                


    } // End Class
} // End Class check if exist / not

?>

