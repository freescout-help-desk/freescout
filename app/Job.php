<?php

namespace App;

use App\Thread;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
	const UPDATED_AT = null;

	public $payload_decoded = null;

    /**
     * Automatically converted into Carbon dates.
     */
    protected $dates = ['created_at', 'available_at', 'reserved_at'];

    public function getPayloadDecoded()
    {
    	if ($this->payload_decoded !== null) {
    		return $this->payload_decoded;
    	}

    	$this->payload_decoded = json_decode($this->payload, true);

    	return $this->payload_decoded;
    }

    public function getCommand()
    {
    	return self::getPayloadCommand($this->getPayloadDecoded());
    }

    public function getCommandLastThread()
    {
	    $command = $this->getCommand();
        if ($command && !empty($command->threads)) {
            return Thread::getLastThread($command->threads);
        }

        return null;
    }

    public static function getPayloadCommand($payload)
    {
    	if (empty($payload['data']) || empty($payload['data']['command'])) {
    		return null;
    	}
        try {
            // If some record has been deleted from DB, there will be an error:
            // No query results for model [App\Conversation].
            return unserialize($payload['data']['command']);
        } catch (\Exception $e) {
            return null;
        }
    }
}
