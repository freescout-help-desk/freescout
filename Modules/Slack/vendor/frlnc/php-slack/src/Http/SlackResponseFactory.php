<?php namespace Frlnc\Slack\Http;

class SlackResponseFactory implements \Frlnc\Slack\Contracts\Http\ResponseFactory {

    /**
     * {@inheritdoc}
     */
    public function build($body, array $headers, $statusCode)
    {
        return new SlackResponse($body, $headers, $statusCode);
    }

}
