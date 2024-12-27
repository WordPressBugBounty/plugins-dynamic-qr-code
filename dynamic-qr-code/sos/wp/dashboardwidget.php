<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class DashboardWidget
{
    use TBase;

    public $id;
    public $title;
    public $priority; // high | core | default | low
    public $callback;

    public function __construct( $id, $title, $priority = 'core' ) {
        $this->id = $id;
        $this->title = $title;
        $this->priority = $priority;
        $this->callback = null;
        $me = $this;
        add_action('wp_dashboard_setup', function () use($me) {
            wp_add_dashboard_widget($me->id, $me->title, [$this, '_html'], null, null, 'normal', $this->priority);
        });
    }

    /***
     * Usage (example for the plugin class):
     * $widget = new DashboardWidget('custom_dashboard_widget', 'My dashboard custom widget');
     * $widget->callback = [$this, 'foobar']
     * public function foobar() {
     *      echo "bla bla...";
     * }
     */
    public function _html() {
        if ( !is_null($this->callback) ) {
            $ret = call_user_func( $this->callback, $this );
            if ( !is_null( $ret ) ) {
                echo $ret;
            }
        } else {
            echo '<div>Warning: <em>callback function is not defined.</em></div>';
            echo '<div style="text-align: right;">Plugin: ' . self::plugin()->name . '</div>';
        }
    }

}