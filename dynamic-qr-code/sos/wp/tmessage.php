<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );


trait TMessage
{
    use TBase;

    protected static $setting = '';

    private static function getSetting() {
        if ( empty( self::$setting ) ) {
            self::$setting = self::plugin()->key;
        }
        return self::$setting;
    }

    private static function translate($message) {
        if ( !self::plugin()::$internationalized ) {
            return $message;
        } else {
            return self::plugin()::t_($message);
        }
    }

    private static function _prepareMessage($message, $backend = false) {
        if ( !is_array($message) ) {
            $translated = self::translate($message);
            return $backend ? $translated : esc_html($translated);
        }

        $processed_lines = [];
        foreach ( $message as $line ) {
            $translated_line = self::translate($line);
            $processed_lines[] = $backend ? $translated_line : esc_html($translated_line);
        }

        $separator = $backend ? '<br>' : PHP_EOL;
        return implode($separator, $processed_lines);
    }

    public static function _add( $message, $type ) {
        $message = self::_prepareMessage($message, is_admin());
        if ( ! is_admin() ) {
            sosidee_log("message({$type}): {$message}");
            return;
        }

        $key = Transient::getUserKey(Configuration::MESSAGE_CODE);
        if ( empty( $key ) ) {
            return;
        }

        $messages = Transient::get($key);
        if (!is_array($messages)) {
            $messages = [];
        }

        $new_message = [
                'message' => $message,
                'type'    => $type,
            ];

        $is_duplicate = false;
        foreach ( $messages as $existing_message ) {
            if ( $existing_message['message'] === $new_message['message'] &&
                $existing_message['type'] === $new_message['type']) {
                $is_duplicate = true;
                break; // Trovato un duplicato, inutile continuare
            }
        }

        if ( !$is_duplicate ) {
            $messages[] = $new_message;
            Transient::set($key, $messages, MINUTE_IN_SECONDS * 5);
        }


    }

    // TODO: $handled va eliminato
    public static function msgInfo($message, $handled = false) {
        if ( sosidee_is_dev() ) {
            sosidee_log("TMessage::msgInfo() - handled is deprecated: remember to fix it.");
        }
        self::_add($message, MessageType::INFO);
    }
    public static function msgErr($message, $handled = false) {
        self::_add($message, MessageType::ERROR);
    }
    public static function msgOk($message, $handled = false) {
        self::_add($message, MessageType::SUCCESS);
    }
    public static function msgWarn($message, $handled = false) {
        self::_add($message, MessageType::WARNING);
    }

    public static function msgHtml() {

        $core_messages = get_settings_errors(); // messages added by WP

        $delete_transient = false;
        $key = Transient::getUserKey(Configuration::MESSAGE_CODE);
        if ( !empty( $key ) ) {
            $messages = Transient::get($key);
            if ( empty($messages) || !is_array($messages) ) {
                $messages = [];
            } else {
                $delete_transient = true;
            }
        } else {
            $messages = [];
        }

        foreach ( $core_messages as $core_msg ) {
            $messages[] = [
                'type'    => $core_msg['type'],
                'message' => $core_msg['message'],
            ];
        }

        if ( empty($messages) ) {
            return;
        }

        foreach ( $messages as $index => $message ) {
            $id = 'setting-error-'. Configuration::MESSAGE_CODE . '-' . $index;
            $type = $message['type'] ?? MessageType::INFO;
            $text = $message['message'] ?? '';
            echo  '<div id="' . esc_attr($id) . '" class="notice notice-' . esc_attr($type) . ' settings-error inline is-dismissible">';
            echo '<p>' . wp_kses( $text, sosidee_allowed_html() ) . '</p>';
            echo '</div>';
        }

        if ( $delete_transient ) {
            Transient::del($key);
        }
    }

    private static $plugin_row_messages = [];

    public static function _pluginAdd($message, $type, $basename = '') {
        if ( empty($basename) ) {
            $basename = self::plugin()->basename;
        }

        if ( ! isset( self::$plugin_row_messages[ $basename ] ) ) {
            self::$plugin_row_messages[ $basename ] = [];
            if ( !has_action("after_plugin_row_{$basename}", [__CLASS__, 'displayPluginRowMessages']) ) {
                add_action( "after_plugin_row_{$basename}", [ __CLASS__, 'displayPluginRowMessages' ], 10, 3 );
            }
        }

        self::$plugin_row_messages[ $basename ][] = [
            'message' => self::_prepareMessage( $message, true ),
            'type'    => $type,
        ];

    }

    public static function plugWarn($message, $basename = '') {
        self::_pluginAdd($message, MessageType::WARNING, $basename);
    }
    public static function plugErr($message, $basename = '') {
        self::_pluginAdd($message, MessageType::ERROR, $basename);
    }
    public static function plugInfo($message, $basename = '') {
        self::_pluginAdd($message, MessageType::INFO, $basename);
    }
    public static function plugOk($message, $basename = '') {
        self::_pluginAdd($message, MessageType::SUCCESS, $basename);
    }

    public static function displayPluginRowMessages( $plugin_file, $plugin_data, $status ) {
        if ( ! isset( self::$plugin_row_messages[ $plugin_file ] ) || empty( self::$plugin_row_messages[ $plugin_file ] ) ) {
            return;
        }

        $messages_to_display = self::$plugin_row_messages[ $plugin_file ];

        foreach ( $messages_to_display as $message ) {
            $text = $message['message'] ?? '';
            if ( ! empty( trim($text) ) ) {
                $notice_type = $message['type'] ?? MessageType::WARNING;
                // eliminata la classe 'update-message' dal div per rimuovere l'icona
                ?>
                <tr class="plugin-update-tr active sos-plugin-row-message">
                    <td colspan="4" class="plugin-update colspanchange">
                        <div class="notice inline notice-<?php echo esc_attr( $notice_type ); ?> notice-alt">
                            <p>
                                <?php echo wp_kses( $text, sosidee_allowed_html() ); ?>
                            </p>
                        </div>
                    </td>
                </tr>
                <?php
            }
        }

        self::$plugin_row_messages[ $plugin_file ] = [];
    }

    private static $page_notices = [];

    public static function _pageAdd( $screen_id, $message, $type ) {
        if ( ! has_action( 'admin_notices', [ __CLASS__, 'displayPageNotices' ] ) ) {
            add_action( 'admin_notices', [ __CLASS__, 'displayPageNotices' ] );
        }

        if ( ! isset( self::$page_notices[ $screen_id ] ) ) {
            self::$page_notices[ $screen_id ] = [];
        }

        self::$page_notices[ $screen_id ][] = [
            'message' => self::_prepareMessage( $message, true ),
            'type'    => $type,
        ];
    }

    public static function pageWarn($message, $screen_id = 'plugins') {
        self::_pageAdd($screen_id, $message, MessageType::WARNING);
    }
    public static function pageErr($message, $screen_id = 'plugins') {
        self::_pageAdd($screen_id, $message, MessageType::ERROR);
    }
    public static function pageInfo($message, $screen_id = 'plugins') {
        self::_pageAdd($screen_id, $message, MessageType::INFO);
    }
    public static function pageOk($message, $screen_id = 'plugins') {
        self::_pageAdd($screen_id, $message, MessageType::SUCCESS);
    }

    public static function displayPageNotices() {
        $current_screen = get_current_screen();

        if ( ! $current_screen ) {
            return;
        }
        $screen_id = $current_screen->id;

        if ( isset( self::$page_notices[ $screen_id ] ) && ! empty( self::$page_notices[ $screen_id ] ) ) {
            foreach ( self::$page_notices[ $screen_id ] as $notice ) {
                $notice_type = $notice['type'];
                $text = $notice['message'];
                ?>
                <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible">
                    <p><?php echo wp_kses( $text, sosidee_allowed_html() ); ?></p>
                </div>
                <?php
            }
            unset( self::$page_notices[ $screen_id ] );
        }
    }

}