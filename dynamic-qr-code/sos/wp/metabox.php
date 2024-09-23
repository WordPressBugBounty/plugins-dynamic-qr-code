<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Metabox class
 * Useful for posts and pages
 *
 * @property $key
 * @property $title
 * @property $screen : link, comment, screen_ID, custom post type
 * @property $context : normal|side|advanced|after_title
 * @property $html : callback to display the metabox
 * @property $callback : main callback (check and save data)
 * @property $priority : high|core|default|low
 * @property $compatible : with block editor
 */
class MetaBox
{
    use Property, Message, Translation;

    public $fields;

    public $key;
    public $title;
    public $screen;
    public $context;
    public $html;
    public $callback;
    public $priority;
    public $compatible;

    public function __construct( $key, $title, $screen, $context, $priority, $compatible ) {
        $this->key = $key;
        $this->title = $title;
        $this->context = $context;
        $this->html = null;
        $this->callback = null;
        $this->priority = $priority;
        $this->compatible = $compatible;

        $this->fields = array();

        if ( !is_array($screen) ) {
            $screen = array( $screen );
        }
        $this->screen = $screen;

    }
    
    private function getNonceId( $post ) {
        if ( !is_numeric($post) ) {
            return "$this->key-$post->ID";
        } else {
            return "$this->key-$post";
        }
        
    }

    public function setContext( $value ) {
        $this->context = $value;
        return $this;
    }

    public function registerCallback() {
        if ( !is_null($this->callback) ) {
            add_action(
                'save_post'
                ,[ $this, 'callbackSave' ]
                ,10
                ,3
            );

            global $pagenow;
            if ( $pagenow == 'post.php' ) {
                add_action( 'admin_notices', [ $this, 'handleAdminNotices' ] );
            }
        }
    }

    public function register( $plugin ) {

        if ( !is_null($this->html) ) {
            $html = [ $this, 'callbackDisplay' ];
        } else {
            $html = function() { echo "<p>Warning: the function html() of this metabox has not been defined.</p>"; };
        }

        $context = $this->context;
        if ( $context == 'after_title' && $plugin->gutenbergEnabled ) {
            $context = 'advanced';
        }

        add_meta_box(
             $this->key
            ,$this->title
            ,$html
            ,$this->screen
            ,$context
            ,$this->priority
            ,[ '__block_editor_compatible_meta_box' => $this->compatible ]
        );

        /*
        if ( !is_null($this->callback) ) {
            add_action(
                 'save_post'
                ,[ $this, 'callbackSave' ]
                ,10
                ,3
            );
            
            global $pagenow;
            if ( $pagenow == 'post.php' ) {
                add_action( 'admin_notices', [ $this, 'handleAdminNotices' ] );
            }
        }
        */
        
    }

    /**
     * Adds a nonce field and calls the MetaBox.html($this, $post) function
     */
    public function callbackDisplay( $post ) {
        $nonce_name = $this->getNonceId( $post );
        wp_nonce_field( $nonce_name, $nonce_name );
        $this->loadFromDb( $post );
        return call_user_func( $this->html, $this, $post );
    }

    /**
     * Performs the routine check procedures 
     * and calls the function MetaBox.check($this, $post, $update)
     *
     * @param bool $update : whether $post is being updated
     */
    public function callbackSave( $post_ID, $post, $update ) {
        $msg = false;

        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_ID; //If in autosave the form has not been submitted
        }
        /*
        if ( wp_is_post_autosave( $post_ID ) ) {
            return; //alternativa trovata in https://developer.wordpress.org/reference/functions/add_meta_box/
                    // e https://developer.wordpress.org/reference/functions/wp_is_post_autosave/
        }
        */
        if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return $post_ID;
        }
        if ( is_multisite() && ms_is_switched() ) {
            return $post_ID;
        }
        
        if ( isset( $_REQUEST['bulk_edit'] ) ) {
            return $post_ID;
        }

        if ( wp_is_post_revision( $post_ID ) ) {
            return $post_ID;
        }

        if ( !in_array( $post->post_type, $this->screen) ) {
            // when the post type is different from 'post' and 'page' the metabox is not displayed
            // and therefore the nonce is not included in the post -> error message on post saving
            return $post_ID;
        }

        $nonce_name = $this->getNonceId($post_ID);
        if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], $nonce_name ) ) {
            if ( $update ) {
                $plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
                if ( $plugin::$internationalized !== false ) {
                    if ( !isset( $_POST[$nonce_name] ) ) {
                        $msg = self::t_( __METHOD__ . '::nonce-empty' );
                    } else {
                        $msg = self::t_( __METHOD__ . '::nonce-invalid' );
                    }
                } else {
                    if ( !isset( $_POST[$nonce_name] ) ) {
                        $msg = 'A security problem (empty nonce) occurred while checking a metabox data';
                    } else {
                        $msg = 'A security problem (invalid nonce) occurred while checking a metabox data';
                    }
                }
                sosidee_log( 'Metabox.callbackSave(): empty or invalid nonce (' . esc_attr($_POST[$nonce_name]) . ')' );
            }
        } else {
            $post_type = get_post_type_object( $post->post_type );
            if ( !current_user_can( $post_type->cap->edit_post, $post_ID ) ) {
                $plugin = \SOSIDEE_DYNAMIC_QRCODE\SosPlugin::instance();
                if ( $plugin::$internationalized !== false) {
                    $msg = self::t_( __METHOD__ . '::user-unauthorized' );
                } else {
                    $msg = "You're not authorized to modify metabox content";
                }
            }
        }

        if ( $msg === false ) {
            $this->loadFromDb($post);
            $this->loadFromRequest();
            return call_user_func( $this->callback, $this, $post, $update );
        } else {
            $this->err($msg);
        }
    }

    public function addField( $key, $value = null, $is_checkbox = false ) {
        $id = "$this->key-" . self::checkId($key);
        $field = new Data\MbField( $key, $value, $id, $is_checkbox );
        $this->fields[] = $field;
        return $field;
    }

    public function getField( $key ) {
        $ret = false;
        for ( $n=0; $n<count($this->fields); $n++ ) {
            if ( $this->fields[$n]->key == $key ) {
                $ret = $this->fields[$n];
                break;
            }
        }
        return $ret;
    }
    
    public function getFromDb( $post ) {
        $ret = false;
        $results = get_post_meta( $post->ID, $this->key, true );
        if ( $results ) {
            $ret = maybe_unserialize( $results );
        }
        return $ret;
    }

    public function load() {
        $this->loadFromDb( get_post() );
    }

    private function loadFromDb( $post ) {
        $results = $this->getFromDb( $post );
        if ( is_array($results) ) {
            foreach ( $results as $key => $value ) {
                for ( $n=0; $n<count($this->fields); $n++ ) {
                    $field = $this->fields[$n];
                    if ( $field->key == $key ) {
                        $field->value = $value;
                    }
                }
            }
        }
    }
    
    private function loadFromRequest() {
        for ( $n=0; $n<count($this->fields); $n++ ) {
            $field = $this->fields[$n];
            if ( !$field->isCheckbox ) {
                if ( isset($_POST[$field->id]) ) {
                    $field->value = sanitize_text_field( $_POST[$field->id] );
                }
            } else {
                $field->value = isset( $_POST[$field->id] );
            }
        }
    }
    
    /**
     * Save data in the 'postmeta' table
     * 
     * @param WP_Post $post : the post related to the metabox
     * @return mixed:
     *                  (bool) true: success
     *                  (bool) false: failure
     *                  (int)  0: no update (identical values)
     */
    public function save( $post ) {
        $ret = 0;
        $prev_values = $this->getFromDb( $post );
        $values = array();
        for ( $n=0; $n<count($this->fields); $n++ ) {
            $field = $this->fields[$n];
            $key = $field->key;
            $values[$key] = $field->value;
            if ( !is_array($prev_values) || $prev_values[$key] != $field->value ) {
                $ret = false;
            }
        }
        if ( $ret === false ) {
            $ret = update_post_meta( $post->ID, $this->key, $values ); //
        }
        return $ret;
    }

    
    /**
     * Display the admin console messages simulating the standard wp layout
     */
    public function handleAdminNotices() {
        if ( !( $messages = get_transient( 'settings_errors' ) ) ) {
            return;
        }

        foreach ( $messages as $msg ) {
            $line = '<div id="setting-error-' . $msg['code'] . '" class="notice notice-' . $msg['type'] . ' settings-error is-dismissible">';
            $line .= ($msg['type'] == 'error' || $msg['type'] == 'warning') ? "<p><strong>{$msg['message']}</strong></p>" : "<p>{$msg['message']}</p>";
            $line .= '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
            $line .= '</div>';
            echo sosidee_kses( $line );
        }

        // these should avoid duplicating messages...
        delete_transient( 'settings_errors' );
        remove_action( 'admin_notices', array($this, 'handleAdminNotices') );
    }


    /**
     * Adds an admin console message
     * Use this in the metabox data checking function of the plugin
     * in place of self::msgXXX('message'), otherwise the message
     * will not be displayed
     *
     * @param $message
     */
    public function info( $message ) {
        self::msgInfo( $message );
        $this->setTransient();
    }
    public function err( $message ) {
        self::msgErr( $message );
        $this->setTransient();
    }
    public function ok( $message ) {
        self::msgOk( $message );
        $this->setTransient();
    }
    public function warn( $message ) {
        self::msgWarn( $message );
        $this->setTransient();
    }

    private function setTransient() {
        set_transient('settings_errors', get_settings_errors(), 30);
    }


    /**
     * Template for the displaying function
     * 
     * @param Metabox $metabox : a metabox
     * @param WP_Post $post : the post
     * 
    public function html( $metabox, $post ) {
        //get the field by key
        $field = $metabox->getField('field_key');

        //display the control
        echo '<input type="text" id="' . esc_attr($field->id) . '" name="' . esc_attr($field->id) . '" value="' . esc_attr($field->value) . '">';

    }
     */

    /**
     * Template for the data checking and saving function
     * 
    public function callback( $metabox, $post, $update ) {
        //get the field to check its value
        $field = $metabox->getField('field_key');
        if ( $field->value == 'foo' ) {
            $metabox->err('doh!');
            //etc.
        }

        //save the data
        $res = $metabox->save( $post );

        //to add a message to the admin console
        $metabox->ok('message'); // etc.

    }
     */

}