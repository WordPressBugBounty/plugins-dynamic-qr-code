<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP\BE;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

/**
 * Class for the admin console nav-tab control
 */
class NavTab
{
    protected $pages;

    public function __construct() {
        $this->pages = array();
    }

    public function add($page) {
        $this->pages[] = $page;
        return $this;
    }
    
    public function html() {
        if (count($this->pages) > 0) {
            if (count($this->pages) > 1) {
                echo '<h2 class="nav-tab-wrapper current">';
                $current = '';
                for ($n=0; $n<count($this->pages); $n++) {
                    $page = $this->pages[$n];
                    echo '<a href="' . esc_url( menu_page_url($page->key, false) ) . '" class="nav-tab';
                    if ( $page->isCurrent() ) {
                        echo ' nav-tab-active';
                    }
                    echo '">' . sosidee_kses( $page->name ) . '</a>';
                }
                echo '</h2>';
            } else {
                echo '<h1>' . sosidee_kses( $this->pages[0]->name ) . '</h1>';
            }
        }
    }

}