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

namespace Webklex\IMAP;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

/**
 * Class Attachment
 *
 * @package Webklex\IMAP
 */
class Attachment {

    /** @var Message $oMessage */
    protected $oMessage;

    /** @var object $structure */
    protected $structure;

    /** @var int $part_number */
    protected $part_number = 1;

    /** @var null|string $content */
    public $content = null;

    /** @var null|string $type */
    public $type = null;

    /** @var null|string $content_type */
    public $content_type = null;

    /** @var null|string $id */
    public $id = null;

    /** @var null|string $name */
    public $name = null;

    /** @var null|string $disposition */
    public $disposition = null;

    /** @var null|string $img_src */
    public $img_src = null;

    /**
     * Attachment const
     *
     * @const integer   TYPE_TEXT
     * @const integer   TYPE_MULTIPART
     * @const integer   TYPE_MESSAGE
     * @const integer   TYPE_APPLICATION
     * @const integer   TYPE_AUDIO
     * @const integer   TYPE_IMAGE
     * @const integer   TYPE_VIDEO
     * @const integer   TYPE_MODEL
     * @const integer   TYPE_OTHER
     */
    const TYPE_TEXT = 0;
    const TYPE_MULTIPART = 1;
    const TYPE_MESSAGE = 2;
    const TYPE_APPLICATION = 3;
    const TYPE_AUDIO = 4;
    const TYPE_IMAGE = 5;
    const TYPE_VIDEO = 6;
    const TYPE_MODEL = 7;
    const TYPE_OTHER = 8;

    /**
     * Attachment constructor.
     *
     * @param Message   $oMessage
     * @param object    $structure
     * @param integer   $part_number
     *
     * @throws Exceptions\ConnectionFailedException
     */
    public function __construct(Message $oMessage, $structure, $part_number = 1) {
        $this->oMessage = $oMessage;
        $this->structure = $structure;
        $this->part_number = ($part_number) ? $part_number : $this->part_number;

        $this->findType();
        $this->fetch();
    }

    /**
     * Determine the structure type
     */
    protected function findType() {
        switch ($this->structure->type) {
            case self::TYPE_MESSAGE:
                $this->type = 'message';
                break;
            case self::TYPE_APPLICATION:
                $this->type = 'application';
                break;
            case self::TYPE_AUDIO:
                $this->type = 'audio';
                break;
            case self::TYPE_IMAGE:
                $this->type = 'image';
                break;
            case self::TYPE_VIDEO:
                $this->type = 'video';
                break;
            case self::TYPE_MODEL:
                $this->type = 'model';
                break;
            case self::TYPE_TEXT:
                $this->type = 'text';
                break;
            case self::TYPE_MULTIPART:
                $this->type = 'multipart';
                break;
            default:
                $this->type = 'other';
                break;
        }
    }

    /**
     * Fetch the given attachment
     *
     * @throws Exceptions\ConnectionFailedException
     */
    protected function fetch() {

        $content = imap_fetchbody($this->oMessage->getClient()->getConnection(), $this->oMessage->getUid(), $this->part_number, $this->oMessage->getFetchOptions() | FT_UID);

        $this->content_type = $this->type.'/'.strtolower($this->structure->subtype);
        $this->content = $this->oMessage->decodeString($content, $this->structure->encoding);

        if (property_exists($this->structure, 'id')) {
            $this->id = str_replace(['<', '>'], '', $this->structure->id);
        }

        if (property_exists($this->structure, 'dparameters')) {
            foreach ($this->structure->dparameters as $parameter) {
                if (strtolower($parameter->attribute) == "filename") {
                    $this->setName($parameter->value);
                    $this->disposition = property_exists($this->structure, 'disposition') ? $this->structure->disposition : null;
                    break;
                }
            }
        }

        if (self::TYPE_MESSAGE == $this->structure->type) {
            if ($this->structure->ifdescription) {
                $this->setName($this->structure->description);
            } else {
                $this->setName($this->structure->subtype);
            }
        }

        if (!$this->name && property_exists($this->structure, 'parameters')) {
            foreach ($this->structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == "name") {
                    $this->setName($parameter->value);
                    $this->disposition = property_exists($this->structure, 'disposition') ? $this->structure->disposition : null;
                    break;
                }
            }
        }

        if ($this->type == 'image') {
            $this->img_src = 'data:'.$this->content_type.';base64,'.base64_encode($this->content);
        }
    }

    /**
     * Save the attachment content to your filesystem
     *
     * @param string|null $path
     * @param string|null $filename
     *
     * @return boolean
     */
    public function save($path = null, $filename = null) {
        $path = $path ?: storage_path();
        $filename = $filename ?: $this->getName();

        $path = substr($path, -1) == DIRECTORY_SEPARATOR ? $path : $path.DIRECTORY_SEPARATOR;

        return File::put($path.$filename, $this->getContent()) !== false;
    }

    /**
     * @return null|string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @return null|string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function getContentType() {
        return $this->content_type;
    }

    /**
     * @return null|string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param $name
     */
    public function setName($name) {
        $this->name = $this->oMessage->decodeString($this->oMessage->convertEncoding($name, $this->oMessage->getEncoding($name)), 'UTF-7');
    }

    /**
     * @return null|string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getDisposition() {
        return $this->disposition;
    }

    /**
     * @return null|string
     */
    public function getImgSrc() {
        return $this->img_src;
    }

    /**
     * @return string|null
     */
    public function getMimeType(){
        return (new \finfo())->buffer($this->getContent(), FILEINFO_MIME_TYPE);
    }

    /**
     * @return string|null
     */
    public function getExtension(){
        return ExtensionGuesser::getInstance()->guess($this->getMimeType());
    }
}