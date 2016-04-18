<?php

class Site_Status_Sites {
    var $post_type = 'site';

    function __construct() {
        add_action( 'init' , array( $this , 'init' ) );
        add_action( 'manage_' . $this->post_type . '_posts_custom_column' , array( $this , 'manage_custom_columns' ) , 10 , 2 );
        add_action( 'save_post' , array( $this , 'metabox_save' ) );
        add_filter( 'manage_edit-' . $this->post_type . '_columns' , array( $this , 'edit_columns' ) );
    }

    function init() {
        register_post_type( $this->post_type ,
            array(
                'capability_type' => 'post',
                'exclude_from_search' => true,
                'hierarchical' => false,
                'labels' => array(
                    'name' => __( 'Sites' ),
                    'singular_name' => __( 'Site' ),
                    'add_new' => __( 'Add New' ),
                    'add_new_item' => __( 'Add New Site' ),
                    'edit' => __( 'Edit' ),
                    'edit_item' => __( 'Edit Site' ),
                    'new_item' => __( 'New Site' ),
                    'view' => __( 'View' ),
                    'view_item' => __( 'View Site' ),
                    'search_items' => __( 'Search Sites' ),
                    'not_found' => __( 'No sites found' ),
                    'not_found_in_trash' => __( 'No sites found in Trash' ),
                ),
                'public' => true,
                'publicly_queryable' => true,
                'query_var' => true,
                'register_meta_box_cb' => array( $this , 'metabox_register' ),
                'rewrite' => array( 'slug' => 'site' , 'with_front' => false ),
                'show_ui' => true,
                'supports' => array( 'title' ),
            )
        );
    }

    function manage_custom_columns( $column , $post_id ) {
        $site_custom = get_post_custom($post_id);

        $status = isset( $site_custom[ 'last_status' ] ) ? $site_custom[ 'last_status' ][0] : "unknown";
        $last_updated = isset( $site_custom[ 'last_updated' ] ) ? $site_custom[ 'last_updated' ][0] : __( 'Never' );

        switch( $column ) {
            case 'status':
                echo '<img src="'. SITE_STATUS_DIRECTORY_PLUGIN_URL . '/images/status_' . $status .'.png" />';
                break;

            case 'last_updated':
                if($last_updated != __( 'Never') ) {
                    echo $this->human_timing($last_updated);
                } else {
                    echo $last_updated;
                }
                break;
        }
    }

    /**
     * Found this method here: http://stackoverflow.com/questions/2915864/php-how-to-find-the-time-elapsed-since-a-date-time
     */
    function human_timing ($time)
    {

        $time = time() - $time; // to get the time since that moment
        $time = ($time<1)? 1 : $time;
        $tokens = array (
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
        }

    }

    function edit_columns($columns) {
        $columns[ 'status' ] = __( 'Status' );
        $columns[ 'last_updated' ] = __( 'Last Updated' );
        unset( $columns[ 'date' ] );
        return $columns;
    }

    function metabox_register() {
        add_meta_box( $this->post_type , __( 'Site Information' ), array( $this , 'metabox_info' ) , $this->post_type , 'normal' , 'high' );
    }

    function metabox_info() {
        global $post;
        $custom = get_post_custom($post->ID);
        ?>
        <div>
            <table width="100%">
                <tr>
                    <td style="width: 150px;" valign="top"><label for="url"><strong><?php _e('URL'); ?></strong></label></td>
                    <td>
                        <input type="url" name="url" id="url" class="large-text" value="<?php echo ( isset( $custom[ 'url' ] ) ? $custom[ 'url' ][0] : '' ); ?>" />
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    function metabox_save() {
        global $post;
        if( $post->post_type == $this->post_type ) {
            if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
                return;
            }

            update_post_meta( $post->ID , 'url' , $_POST['url'] );
        }
    }
}
?>