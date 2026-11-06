<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SRC;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

use SOSIDEE_DYNAMIC_QRCODE\SRC\FORM\Base;
use SOSIDEE_DYNAMIC_QRCODE\SOS\WP\Mobile;

class Metabox
{
    use \SOSIDEE_DYNAMIC_QRCODE\SOS\WP\TBase;

    const FLD_QC = 'qid';
    const FLD_FORM = 'form';

    private $ctrl;
    private $config;

    public function __construct( $ctrl ) {
        $ctrl->addField( self::FLD_QC, 0 );
        $ctrl->addField( self::FLD_FORM, false, true );
        $ctrl->html = [ $this, 'html' ];
        $ctrl->callback = [ $this, 'save' ];

        $this->ctrl = $ctrl;
        $this->config = self::plugin()->config;
    }

    public function html( $metabox, $post ) {

        echo '<p>Prevent users to view the content if not accessed by scanning the image of this QR-Code: ';
        echo wp_kses( self::plugin()->help('hide-2', 'float: right;'), sosidee_allowed_html() );
        echo '</p>';
        //$options = self::plugin()->loadQrCodeList('- select -');
        $options = QrCode::loadQrCodeList('- select -');
        $qid = $metabox->getField(self::FLD_QC);
        echo '<p>';
        echo wp_kses( $qid->getSelect( [ 'options' => $options ] ), sosidee_allowed_html() );
        echo '</p>';
        $form = $metabox->getField(self::FLD_FORM);
        echo '<p>';
        echo wp_kses( $form->getCheckbox( [ 'label' => 'allow reloading this post/page by submitting the form(s) contained in it' ] ), sosidee_allowed_html() );
        echo '</p>';

    }

    public function save( $metabox, $post, $update ) {
        $res = $metabox->save( $post );
        if ( $res === false ) {
            $metabox->err( self::plugin()->name . ": cannot save the data.");
        }
    }

    private function isPagePasswordCorrect() {
        global $post;
        $cookie = $_COOKIE['wp-postpass_' . COOKIEHASH] ?? '';
        return ( hash_equals($post->post_password, stripslashes($cookie)) );
    }

    public function checkPost() {
        if ( !is_singular() ) {
            return; // if it's not a post or a single page
        }
        $this->ctrl->load();
        $qid = $this->ctrl->getField(self::FLD_QC);

        if ( $qid->value > 0 ) {
            $show = false;
            $id = $qid->value;
            $form = $this->ctrl->getField(self::FLD_FORM);
            $hasForm = boolval( $form->value );
            $delete_cookie = true;

            $isMobileBrowser = Mobile::is() && Mobile::browser();
            $this->config->load(); // load current configuration
            $isDeviceEnabled = $isMobileBrowser || $this->config->anyDeviceEnabled->value; // it's a mobile browser OR any device

            if ( $isDeviceEnabled ) {
                $continue = true;
                if ( post_password_required() && !$this->isPagePasswordCorrect() ) {
                    $delete_cookie = false;
                    $show = true;
                    $continue = false;
                }
                if ( $continue ) {
                    // controlla il valore del cookie, e poi ...
                    $key = OTKey::getCookie();
                    if ( $key != '' ) {
                        /*
                        $used = self::database()->otkeys->consumeOnce( $key, $id );
                        if ( $used ) {
                            if ( $hasForm ) {
                                $delete_cookie = false;
                            }
                            $show = true;
                        } else {
                            if ( $hasForm && Base::checkPosted( $id ) ) {
                                $show = true;
                                $delete_cookie = false;
                            } else {
                                sosidee_log("Hiding content: cookie already used or not found.");
                            }
                        }
                        */
                        $otkey = self::database()->otkeys->load( $key, $id );
                        if ( $otkey !== false && $otkey->otk_id > 0 ) {
                            $tally = intval( $otkey->tally );
                            $tally++;
                            if ( self::database()->otkeys->save( $otkey->otk_id, $tally ) == false ) {
                                sosidee_log("database.updateOTKey({$tally}) failed for key.id={$otkey->otk_id}");
                            }
                            if ( $tally == 1 ) {
                                if ( $hasForm ) {
                                    $delete_cookie = false;
                                }
                                $show = true;
                            } else {
                                if ( $hasForm && Base::checkPosted( $id ) ) {
                                    $show = true;
                                    $delete_cookie = false;
                                } else {
                                    sosidee_log("Hiding content: cookie already used.");
                                }
                            }
                        } else {
                            if ( $otkey !== false ) {
                                sosidee_log("Hiding content: cookie key not found in the database.");
                            } else {
                                sosidee_log("Hiding content: a problem occurred while reading the cookie key in the database.");
                            }
                        }
                    } else {
                        sosidee_log("Hiding content: cookie not found.");
                    }
                }
            } else {
                sosidee_log("Hiding content: device not enabled.");
            }

            if ( $delete_cookie ) {
                //OTKey::setJsCookieEraser();
                OTKey::deleteCookie();
            }

            if ( $show ) {
                return;
            } else {
                $qrcode = self::database()->qrcodes->load( $id );
                if ( $qrcode !== false ) {
                    $url = $qrcode->url_cypher;
                } else {
                    $url = '';
                    sosidee_log("database.loadQrCode({$qid->value}) returned false.");
                }
                if ( $url == '') {
                    $url = $this->config->urlError->value;
                }

                if ( $url != '') {
                    $url = self::plugin()->getRedirectUrl($url);
                    wp_redirect($url);
                    exit;
                }
            }

        } else {
            //
        }
    }
}