<?php
/**
 * Outgoing emails.
 */

namespace Modules\Crm\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CustomFields\Entities\CustomField;

class CustomerCustomerField extends Model
{
    protected $table = 'customer_customer_field';
    
    public $timestamps = false;

    protected $fillable = [
    	'customer_id', 'customer_field_id', 'value'
    ];

    /**
     * Get customer.
     */
    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }
}
