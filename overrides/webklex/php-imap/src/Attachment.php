<?php
/*
* File:     Attachment.php
* Category: -
* Author:   M. Goldenbaum
* Created:  16.03.18 19:37
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

use Illuminate\Support\Str;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;
use Webklex\PHPIMAP\Support\Masks\AttachmentMask;

/**
 * Class Attachment
 *
 * @package Webklex\PHPIMAP
 *
 * @property integer part_number
 * @property integer size
 * @property string content
 * @property string type
 * @property string content_type
 * @property string id
 * @property string name
 * @property string disposition
 * @property string img_src
 *
 * @method integer getPartNumber()
 * @method integer setPartNumber(integer $part_number)
 * @method string  getContent()
 * @method string  setContent(string $content)
 * @method string  getType()
 * @method string  setType(string $type)
 * @method string  getContentType()
 * @method string  setContentType(string $content_type)
 * @method string  getId()
 * @method string  setId(string $id)
 * @method string  getSize()
 * @method string  setSize(integer $size)
 * @method string  getName()
 * @method string  getDisposition()
 * @method string  setDisposition(string $disposition)
 * @method string  setImgSrc(string $img_src)
 */
class Attachment {

    /**
     * @var Message $oMessage
     */
    protected $oMessage;

    /**
     * Used config
     *
     * @var array $config
     */
    protected $config = [];

    /** @var Part $part */
    protected $part;

    /**
     * Attribute holder
     *
     * @var array $attributes
     */
    protected $attributes = [
        'content' => null,
        'hash' => null,
        'type' => null,
        'part_number' => 0,
        'content_type' => null,
        'id' => null,
        'name' => null,
        'filename' => null,
        'description'  => null,
        'disposition' => null,
        'img_src' => null,
        'size' => null,
    ];

    /**
     * Default mask
     *
     * @var string $mask
     */
    protected $mask = AttachmentMask::class;

    /**
     * Attachment constructor.
     * @param Message   $oMessage
     * @param Part      $part
     */
    public function __construct(Message $oMessage, Part $part) {
        $this->config = ClientManager::get('options');

        $this->oMessage = $oMessage;
        $this->part = $part;
        $this->part_number = $part->part_number;

        $default_mask = $this->oMessage->getClient()->getDefaultAttachmentMask();
        if($default_mask != null) {
            $this->mask = $default_mask;
        }

        $this->findType();
        $this->fetch();
    }

    /**
     * Call dynamic attribute setter and getter methods
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws MethodNotFoundException
     */
    public function __call(string $method, array $arguments) {
        if(strtolower(substr($method, 0, 3)) === 'get') {
            $name = Str::snake(substr($method, 3));

            if(isset($this->attributes[$name])) {
                return $this->attributes[$name];
            }

            return null;
        }elseif (strtolower(substr($method, 0, 3)) === 'set') {
            $name = Str::snake(substr($method, 3));

            $this->attributes[$name] = array_pop($arguments);

            return $this->attributes[$name];
        }

        throw new MethodNotFoundException("Method ".self::class.'::'.$method.'() is not supported');
    }

    /**
     * Magic setter
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function __set($name, $value) {
        $this->attributes[$name] = $value;

        return $this->attributes[$name];
    }

    /**
     * magic getter
     * @param $name
     *
     * @return mixed|null
     */
    public function __get($name) {
        if(isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * Determine the structure type
     */
    protected function findType() {
        switch ($this->part->type) {
            case IMAP::ATTACHMENT_TYPE_MESSAGE:
                $this->type = 'message';
                break;
            case IMAP::ATTACHMENT_TYPE_APPLICATION:
                $this->type = 'application';
                break;
            case IMAP::ATTACHMENT_TYPE_AUDIO:
                $this->type = 'audio';
                break;
            case IMAP::ATTACHMENT_TYPE_IMAGE:
                $this->type = 'image';
                break;
            case IMAP::ATTACHMENT_TYPE_VIDEO:
                $this->type = 'video';
                break;
            case IMAP::ATTACHMENT_TYPE_MODEL:
                $this->type = 'model';
                break;
            case IMAP::ATTACHMENT_TYPE_TEXT:
                $this->type = 'text';
                break;
            case IMAP::ATTACHMENT_TYPE_MULTIPART:
                $this->type = 'multipart';
                break;
            default:
                $this->type = 'other';
                break;
        }
    }

    /**
     * Fetch the given attachment
     */
    protected function fetch() {

        $content = $this->part->content;

        $this->content_type = $this->part->content_type;
        $this->content = $this->oMessage->decodeString($content, $this->part->encoding);

        // Create a hash of the raw part - this can be used to identify the attachment in the message context. However,
        // it is not guaranteed to be unique and collisions are possible.
        // Some additional online resources:
        // - https://en.wikipedia.org/wiki/Hash_collision
        // - https://www.php.net/manual/en/function.hash.php
        // - https://php.watch/articles/php-hash-benchmark
        // Benchmark speeds:
        // -xxh3    ~15.19(GB/s) (requires php-xxhash extension or >= php8.1)
        // -crc32c  ~14.12(GB/s)
        // -sha256  ~0.25(GB/s)
        // xxh3 would be nice to use, because of its extra speed and 32 instead of 8 bytes, but it is not compatible with
        // php < 8.1. crc32c is the next fastest and is compatible with php >= 5.1. sha256 is the slowest, but is compatible
        // with php >= 5.1 and is the most likely to be unique. crc32c is the best compromise between speed and uniqueness.
        // Unique enough for our purposes, but not so slow that it could be a bottleneck.
        //$this->hash = hash("crc32c", $this->part->getHeader()->raw."\r\n\r\n".$this->part->content);
        // https://github.com/freescout-helpdesk/freescout/issues/3991

        if (($id = $this->part->id) !== null) {
            $this->id = str_replace(['<', '>'], '', $id);
        }else{
            $this->id = $this->getHash();
        }

        $this->size = $this->part->bytes;
        $this->disposition = $this->part->disposition;

        if (($filename = $this->part->filename) !== null) {
            $this->filename = $this->decodeName($filename);
        }

        // if (($description = $this->part->description) !== null) {
        //     $this->description = $this->part->getHeader()->decode($description);
        // }

        if (($name = $this->part->name) !== null) {
            $this->name = $this->decodeName($name);
        }

        // if (($filename = $this->part->filename) !== null) {
        //     $this->setName($filename);
        // } elseif (($name = $this->part->name) !== null) {
        //     $this->setName($name);
        // }else {
        //     $this->setName("undefined");
        // }

        // if (IMAP::ATTACHMENT_TYPE_MESSAGE == $this->part->type) {
        //     if ($this->part->ifdescription) {
        //         $this->setName($this->part->description);
        //     } else {
        //         $this->setName($this->part->subtype);
        //     }
        // }
        
        if (IMAP::ATTACHMENT_TYPE_MESSAGE == $this->part->type) {
            if ($this->part->ifdescription) {
                if (!$this->name) {
                    $this->name = $this->decodeName($this->part->description);
                }
            } else if (!$this->name) {
                $this->name = $this->decodeName($this->part->subtype);
            }
        }
        $this->attributes = array_merge($this->part->getHeader()->getAttributes(), $this->attributes);

        if (!$this->filename) {
            $this->filename = $this->getHash();
        }

        if (!$this->name && $this->filename != "") {
            $this->name = $this->filename;
        }
    }

    public function getHash()
    {
        if (!$this->hash) {
            $this->hash = substr(md5($this->part->getHeader()->raw."\r\n\r\n".$this->part->content), 0, 8);
        }

        return $this->hash;
    }

    /**
     * Save the attachment content to your filesystem
     * @param string $path
     * @param string|null $filename
     *
     * @return boolean
     */
    public function save(string $path, $filename = null): bool {
        //$filename = $filename ?: $this->getName();
        $filename = $filename ? $this->decodeName($filename) : $this->filename;
        
        // sanitize $name
        // order of '..' is important
        // https://github.com/freescout-helpdesk/freescout/issues/3592
        // https://github.com/Webklex/php-imap/issues/461
        $filename = str_replace(['\\', '../', '/..', '/', chr(0), ':'], '', $filename ?? '');

        return file_put_contents($path.DIRECTORY_SEPARATOR.$filename, $this->getContent()) !== false;
    }

    /**
     * Set the attachment name and try to decode it
     * @param $name
     */
    public function setName($name) {
        $this->name = $this->decodeName($name);
    }

    public function decodeName($name) {
        // $decoder = $this->config['decoder']['attachment'];
        // if ($name !== null) {
        //     if($decoder === 'utf-8' && extension_loaded('imap')) {
        //         $this->name = \imap_utf8($name);
        //     }else{
        //         $this->name = mb_decode_mimeheader($name);
        //     }
        // }
        // https://github.com/freescout-helpdesk/freescout/issues/3089
        if ($name !== null) {
            // RFC6266 and RFC8187
            // UTF-8''%E3%80...
            // utf-8'en'%C2%A3%20rates
            preg_match("#([^']+)'([^']{2})?'(.+)#", $name, $m);
            $name_charset = '';
            if (!empty($m[1]) && !empty($m[3])) {
                $name = $m[3];
                $name_charset = $m[1];
            }

            // $decoder = $this->config['decoder']['message'];
            // if ($decoder === 'utf-8' && extension_loaded('imap')) {
            //     $name = \imap_utf8($name);
            // }

            //if (preg_match('/=\?([^?]+)\?(Q|B)\?(.+)\?=/i', $name, $matches)) {
            $name = \MailHelper::decodeSubject($name);
            //}

            // check if $name is url encoded
            if (preg_match('/%[0-9A-F]{2}/i', $name)) {
                $name = urldecode($name);
            }

            // sanitize $name
            // order of '..' is important
            $name = str_replace(['\\', '../', '/..', '/', chr(0), ':'], '', $name);
        }
        return $name;
    }

    /**
     * Get the attachment mime type
     *
     * @return string|null
     */
    public function getMimeType(){
        return (new \finfo())->buffer($this->getContent(), FILEINFO_MIME_TYPE);
    }

    /**
     * Try to guess the attachment file extension
     *
     * @return string|null
     */
    public function getExtension(){
        $extension = null;
        $guesser = "\Symfony\Component\Mime\MimeTypes";
        if (class_exists($guesser) !== false) {
            /** @var Symfony\Component\Mime\MimeTypes $guesser */
            $extensions = $guesser::getDefault()->getExtensions($this->getMimeType());
            $extension = $extensions[0] ?? null;
        }
        if ($extension === null) {
            $deprecated_guesser = "\Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser";
            if (class_exists($deprecated_guesser) !== false) {
                /** @var \Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser $deprecated_guesser */
                $extension = $deprecated_guesser::getInstance()->guess($this->getMimeType());
            }
        }
        if ($extension === null) {
            $parts = explode(".", $this->filename);
            $extension = count($parts) > 1 ? end($parts) : null;
        }
        if ($extension === null) {
            $parts = explode(".", $this->name);
            $extension = count($parts) > 1 ? end($parts) : null;
        }
        return $extension;
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getAttributes(): array {
        return $this->attributes;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message {
        return $this->oMessage;
    }

    /**
     * Set the default mask
     * @param $mask
     *
     * @return $this
     */
    public function setMask($mask): Attachment {
        if(class_exists($mask)){
            $this->mask = $mask;
        }

        return $this;
    }

    /**
     * Get the used default mask
     *
     * @return string
     */
    public function getMask(): string {
        return $this->mask;
    }

    /**
     * Get a masked instance by providing a mask name
     * @param string|null $mask
     *
     * @return mixed
     * @throws MaskNotFoundException
     */
    public function mask($mask = null){
        $mask = $mask !== null ? $mask : $this->mask;
        if(class_exists($mask)){
            return new $mask($this);
        }

        throw new MaskNotFoundException("Unknown mask provided: ".$mask);
    }
}