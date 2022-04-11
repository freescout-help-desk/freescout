<?php namespace Frlnc\Slack\Contracts\Http;

interface Response {

    /**
     * Gets the body of the response.
     *
     * @return string
     */
    public function getBody();

    /**
     * Gets the headers of the response.
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Gets the status code of the response.
     *
     * @return integer
     */
    public function getStatusCode();

}
