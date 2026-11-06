<?php
namespace SOSIDEE_DYNAMIC_QRCODE\SOS\WP;
defined( 'SOSIDEE_DYNAMIC_QRCODE' ) or die( 'you were not supposed to be here' );

class ActionResult
{
    use TMessage;

        public $error;
        private $messages;

    public function __construct( $error = false, $text = '', $type = MessageType::INFO ) {
        $this->error = $error;
        $this->messages = [];
        if ( !empty($text) ) {
            $this->addMessage( $text, $type );
        }
    }

    public function hasMessages() {
        return count($this->messages) > 0;
    }

    public function getMessages() {
        return $this->messages;
    }

    public function sendMessages() {
        foreach ( $this->messages as $message ) {
            $text = $message->text;
            switch ( $message->type ) {
                case MessageType::SUCCESS:
                    self::msgOk( $text);
                    break;
                case MessageType::INFO:
                    self::msgInfo( $text );
                    break;
                case MessageType::WARNING:
                    self::msgWarn( $text );
                    break;
                case MessageType::ERROR:
                    self::msgErr( $text );
                    break;
            }
        }
    }

    private function addMessage( $text, $type ) {
        $message = (object) [
            'text' => $text,
            'type' => $type,
        ];
        $this->messages[] = $message;
        return $this;
    }

    public function addSuccess( $text ) {
        return $this->addMessage( $text, MessageType::SUCCESS );
    }
    public function addInfo( $text ) {
        return $this->addMessage( $text, MessageType::INFO );
    }
    public function addWarning( $text ) {
        return $this->addMessage( $text, MessageType::WARNING );
    }
    public function addError( $text ) {
        return $this->addMessage( $text, MessageType::ERROR );
    }

    public static function success( $text ): self {
        return new static(false, $text,MessageType::SUCCESS );
    }

    public static function info( $text ): self {
        return new static(false, $text,MessageType::INFO );
    }

    public static function warning( $text ): self {
        return new static(false, $text,MessageType::WARNING );
    }

    public static function error( $text ): self {
        return new static(true, $text,MessageType::ERROR );
    }

    public function append( $ar ) {
        if ( $ar instanceof ActionResult && $ar->hasMessages() ) {
            foreach ($ar->getMessages() as $message ) {
                $this->addMessage( $message->text, $message->type );
            }
        }
    }

}