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
    use TMessage, TTranslation, TBase;

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
        if ( is_callable($this->callback) ) {
            add_action(
                'save_post'
                ,[ $this, 'callbackSave' ]
                ,10
                ,3
            );
        }
    }

    public function register( $plugin ) {

        if ( is_callable($this->html) ) {
            $html = [ $this, 'callbackDisplay' ];
        } else {
            $html = function() { echo "<p>Warning: the html() function has not been defined for this metabox.</p>"; };
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

    }

    /**
     * Adds a nonce field and calls the MetaBox.html($this, $post) function
     */
    public function callbackDisplay( $post ) {
        $nonce_name = $this->getNonceId( $post );
        wp_nonce_field( $nonce_name, $nonce_name );
        $this->loadFromDb( $post );
        if ( is_callable( $this->html ) ) {
            return call_user_func( $this->html, $this, $post );
        } else {
            sosidee_log("Metabox html() method not found.");
            return null;
        }
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
            // when the type is different from 'post' and 'page', the metabox is not displayed
            // and therefore the nonce is not included in the post -> error message on saving
            return $post_ID;
        }

        $nonce_name = $this->getNonceId($post_ID);
        if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST[$nonce_name])), $nonce_name ) ) {
            if ( $update ) {
                if ( self::plugin()::$internationalized !== false ) {
                    if ( !isset( $_POST[$nonce_name] ) ) {
                        $msg = self::t_(__METHOD__ . '__nonce-empty' );
                    } else {
                        $msg = self::t_( __METHOD__ . '__nonce-invalid' );
                    }
                } else {
                    if ( !isset( $_POST[$nonce_name] ) ) {
                        $msg = 'A security problem (empty nonce) occurred while checking a metabox data';
                    } else {
                        $msg = 'A security problem (invalid nonce) occurred while checking a metabox data';
                    }
                }
                sosidee_log( 'Metabox::callbackSave() - empty or invalid nonce' );
            }
        } else {
            $post_type = get_post_type_object( $post->post_type );
            if ( !current_user_can( $post_type->cap->edit_post, $post_ID ) ) {
                if ( self::plugin()::$internationalized !== false ) {
                    $msg = self::t_( __METHOD__ . '__user-unauthorized' );
                } else {
                    $msg = "You're not authorized to modify a metabox content";
                }
            }
        }

        if ( $msg === false ) {
            if ( is_callable($this->callback) ) {
                $this->loadFromDb($post);
                $this->loadFromRequest();
                return call_user_func( $this->callback, $this, $post, $update );
            } else {
                self::msgErr("Metabox callback method not found.");
            }
        } else {
            self::msgErr($msg);
        }
        return $post_ID;
    }

    public function addField( $key, $value = null, $is_checkbox = false ) {
        $id = $this->key . '-' . sosidee_check_id($key);
        $field = new Data\MbField( $key, $value, $id, $is_checkbox );
        $this->fields[] = $field;
        return $field;
    }

    public function getField( $key ) {
        $ret = false;
        foreach ( $this->fields as $field ) {
            if ( $field->key == $key ) {
                $ret = $field;
                break;
            }
        }
        return $ret;
    }
    
    public function getFromDb( $post ) {
        $ret = false;
        $results = get_post_meta( $post->ID, $this->key, true );
        if ( $results ) {
            $ret = is_serialized($results)
                ? unserialize(trim($results), ['allowed_classes' => false])
                : $results;
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
                foreach ( $this->fields as $field ) {
                    if ( $field->key == $key ) {
                        $field->value = $value;
                    }
                }
            }
        }
    }
    
    private function loadFromRequest() {
        foreach ( $this->fields as $field ) {
            if ( !$field->isCheckbox ) {
                if ( isset($_POST[$field->id]) ) {
                    $field->value = sosidee_sanitize_recursive( wp_unslash($_POST[$field->id]) );
                }
            } else {
                $field->value = isset( $_POST[$field->id] );
            }
        }
    }
    
    /**
     * Save data in the 'postmeta' table
     * 
     * @param \WP_Post $post : the post related to the metabox
     * @return mixed:
     *                  (bool) true: success
     *                  (bool) false: failure
     *                  (int)  0: no update (identical values)
     */
    public function save( $post ) {
        $ret = 0;
        $prev_values = $this->getFromDb( $post );
        $values = array();
        foreach ( $this->fields as $field ) {
            $key = $field->key;
            $values[$key] = $field->value;
            if ( !is_array($prev_values) || !array_key_exists($key, $prev_values) || $prev_values[$key] != $field->value ) {
                $ret = false;
            }
        }
        if ( $ret === false ) {
            $ret = update_post_meta( $post->ID, $this->key, $values ); //
        }
        return $ret;
    }

}