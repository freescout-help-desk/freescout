<?php
/*
* File: Structure.php
* Category: -
* Author: M.Goldenbaum
* Created: 17.09.20 20:38
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;


use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;

/**
 * Class Structure
 *
 * @package Webklex\PHPIMAP
 */
class Structure {

    /**
     * Raw structure
     *
     * @var string $raw
     */
    public $raw = "";

    /**
     * @var Header $header
     */
    private $header = null;

    /**
     * Message type (if multipart or not)
     *
     * @var int $type
     */
    public $type = IMAP::MESSAGE_TYPE_TEXT;

    /**
     * All available parts
     *
     * @var Part[] $parts
     */
    public $parts = [];

    /**
     * Config holder
     *
     * @var array $config
     */
    protected $config = [];

    /**
     * Structure constructor.
     * @param $raw_structure
     * @param Header $header
     *
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    public function __construct($raw_structure, Header $header) {
        $this->raw = $raw_structure;
        $this->header = $header;
        $this->config = ClientManager::get('options');
        $this->parse();
    }

    /**
     * Parse the given raw structure
     *
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    protected function parse(){
        $this->findContentType();
        $this->parts = $this->find_parts();
    }

    /**
     * Determine the message content type
     */
    public function findContentType(){
        $content_type = $this->header->get("content_type");
        $content_type = (is_array($content_type)) ? implode(' ', $content_type) : $content_type;
        if($content_type && stripos($content_type, 'multipart') === 0) {
            $this->type = IMAP::MESSAGE_TYPE_MULTIPART;
        }else{
            $this->type = IMAP::MESSAGE_TYPE_TEXT;
        }
    }

    /**
     * Find all available headers and return the left over body segment
     * @var string $context
     * @var integer $part_number
     *
     * @return Part[]
     * @throws InvalidMessageDateException
     */
    private function parsePart(string $context, int $part_number = 0): array {
        $body = $context;
        while (($pos = strpos($body, "\r\n")) > 0) {
            $body = substr($body, $pos + 2);
        }
        $headers = substr($context, 0, strlen($body) * -1);
        $body = substr($body, 0, -2);

        $headers = new Header($headers);
        if (($boundary = $headers->getBoundary()) !== null) {
            //return $this->detectParts($boundary, $body, $part_number);
            $parts = $this->detectParts($boundary, $body, $part_number);

            if (count($parts) > 1) {
                return $parts;
            }
        }
        return [new Part($body, $headers, $part_number)];
    }

    /**
     * @param string $boundary
     * @param string $context
     * @param int $part_number
     *
     * @return array
     * @throws InvalidMessageDateException
     */
    private function detectParts(string $boundary, string $context, int $part_number = 0): array {
        // Below we get rid of exlode() as it consumes extra memory.
        // https://github.com/freescout-help-desk/freescout/issues/3956#issuecomment-2284592925
        //$base_parts = explode( $boundary, $context);
        $final_parts = [];
        //foreach($base_parts as $ctx) {
        
        $boundary_len = strlen($boundary);
        $last_pos = 0;
        $positions = [];
        while (($last_pos = strpos($context, $boundary, $last_pos)) !== false) {
            $positions[] = $last_pos;
            $last_pos = $last_pos + $boundary_len;
        }
        if (!count($positions) || $positions[0] != 0) {
            array_unshift($positions, 0);
        }
        
        foreach ($positions as $pos_i => $pos) {
            if ($pos == 0) {
                // First.
                if (isset($positions[$pos_i+1])) {
                    $ctx = substr($context, 0+2, $positions[$pos_i+1]-2);
                } else {
                    // First and last.
                    $ctx = substr($context, 0+2);
                }
            } elseif ($pos_i == count($positions)-1) {
                // Last.
                $ctx = substr($context, $pos+$boundary_len+2);
            } else {
                $ctx = substr($context, $pos+$boundary_len+2, $positions[$pos_i+1]-$pos-$boundary_len-2);
            }

            //$ctx = substr($ctx, 2);

            if ($ctx !== "--" && $ctx != "" && $ctx != "\r\n") {
                $parts = $this->parsePart($ctx, $part_number);
                foreach ($parts as $part) {
                    $final_parts[] = $part;
                    $part_number = $part->part_number;
                }
                $part_number++;
            }
        }

        return $final_parts;
    }

    /**
     * Find all available parts
     *
     * @return array
     * @throws MessageContentFetchingException
     * @throws InvalidMessageDateException
     */
    public function find_parts(): array {
        if($this->type === IMAP::MESSAGE_TYPE_MULTIPART) {
            if (($boundary = $this->header->getBoundary()) === null)  {
                throw new MessageContentFetchingException("no content found", 0);
            }

            return $this->detectParts($boundary, $this->raw);
        }

        return [new Part($this->raw, $this->header)];
    }

    /**
     * Try to find a boundary if possible
     *
     * @return string|null
     * @Depricated since version 2.4.4
     */
    public function getBoundary(){
        return $this->header->getBoundary();
    }
}
