<?php namespace Frlnc\Slack\Contracts\Http;

interface Interactor {

    /**
     * Send a get request to a URL.
     *
     * @param  string $url
     * @param  array  $parameters
     * @param  array  $headers
     * @return \Frlnc\Slack\Contracts\Http\Response
     */
    public function get($url, array $parameters = [], array $headers = []);

    /**
     * Send a post request to a URL.
     *
     * @param  string $url
     * @param  array  $urlParameters
     * @param  array  $postParameters
     * @param  array  $headers
     * @return \Frlnc\Slack\Contracts\Http\Response
     */
    public function post($url, array $urlParameters = [], array $postParameters = [], array $headers = []);

    /**
     * Sets the response factory to use.
     *
     * @param  \Frlnc\Slack\Contracts\Http\ResponseFactory $factory
     * @return void
     */
    public function setResponseFactory(ResponseFactory $factory);

}
