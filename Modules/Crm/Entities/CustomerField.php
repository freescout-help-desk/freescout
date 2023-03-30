<?php

namespace Modules\Crm\Entities;

use App\Customer;
use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class CustomerField extends Model
{
    use Rememberable;

    // This is obligatory.
    public $rememberCacheDriver = 'array';

    public $timestamps = false;

    const NAME_PREFIX       = 'cf_';
    const SHORT_LINK_LENGTH = 20;

	const TYPE_DROPDOWN   = 1;
	const TYPE_SINGLE_LINE = 2;
	const TYPE_MULTI_LINE = 3;
	const TYPE_NUMBER     = 4;
    const TYPE_DATE       = 5;
	const TYPE_LINK       = 6;
	
	public static $types = [
		self::TYPE_DROPDOWN   => 'Dropdown',
		self::TYPE_SINGLE_LINE => 'Single Line',
		self::TYPE_MULTI_LINE => 'Multi Line',
		self::TYPE_NUMBER     => 'Number',
        self::TYPE_DATE       => 'Date',
		self::TYPE_LINK       => 'Link',
	];

    protected $fillable = [
    	'name', 'type', 'required', 'options'
    ];

	protected $attributes = [
        'type' => self::TYPE_DROPDOWN,
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * To make types traanslatable.
     */
    public static function getTypes()
    {
    	return [
			1 => __('Dropdown'),
			2 => __('Single Line'),
			3 => __('Multi Line'),
			4 => __('Number'),
			5 => __('Date'),
    	];
    }

    public function setSortOrderLast()
    {
    	$this->sort_order = (int)CustomerField::max('sort_order')+1;
    }

    public static function getCustomerFields($cache = false)
    {
    	$query = CustomerField::orderby('sort_order');
        if ($cache) {
            $query->rememberForever();
        }
        return $query->get();
    }

    public static function getCustomerFieldsWithValues($customer_id)
    {
        // https://github.com/freescout-helpdesk/freescout/issues/2435
        if (!$customer_id) {
            $customer_id = -1;
        }
        return CustomerField::select(['customer_fields.*', 'customer_customer_field.value', \DB::raw($customer_id.' as customer_id')])
            ->orderby('customer_fields.sort_order')
            ->leftJoin('customer_customer_field', function ($join) use ($customer_id) {
                $join->on('customer_customer_field.customer_field_id', '=', 'customer_fields.id')
                    ->where('customer_customer_field.customer_id', '=', $customer_id);
            })
            ->get();
    }

    public static function getValue($customer_id, $customer_field_id)
    {
        $field = CustomerCustomerField::where('customer_id', $customer_id)
            ->where('customer_field_id', $customer_field_id)
            ->first();

        if ($field) {
            return $field->value;
        } else {
            return '';
        }
    }

    public static function setValue($customer_id, $customer_field_id, $value)
    {
        try {
            $field = CustomerCustomerField::firstOrNew([
                'customer_id' => $customer_id,
                'customer_field_id' => $customer_field_id,
            ]);

            $field->customer_id = $customer_id;
            $field->customer_field_id = $customer_field_id;
            $field->value = $value;
            $field->save();

            \Eventy::action('crm.customer_field.value_updated', $field, $customer_id);
        } catch (\Exception $e) {
            
        }
    }

    public function getNameEncoded()
    {
        return self::NAME_PREFIX.$this->id;
    }

    public static function decodeName($field_name)
    {
        return preg_replace("/^".self::NAME_PREFIX."/", '', $field_name);
    }

    public static function sanitizeValue($value, $field)
    {
        if ($field->type == CustomerField::TYPE_DROPDOWN) {
            if (!is_numeric($value) && array_search($value, $field->options)) {
                $value = array_search($value, $field->options);
            }
        } elseif ($field->type == CustomerField::TYPE_DATE) {
            if (!preg_match("/^\d\d\d\d\-\d\d\-\d\d$/", $value)) {
                $value = date("Y-m-d", strtotime($value));
            }
        } elseif ($field->type == CustomerField::TYPE_NUMBER) {
            if ($value) {
                $value = (int)$value;
            }
        } /*elseif ($field->type == CustomerField::TYPE_LINK) {
            if ($value) {
                $value = filter_var($value, FILTER_SANITIZE_URL);
            }
        }*/

        return $value;
    }

    public function getAsText()
    {
        if ($this->type == self::TYPE_DROPDOWN) {
            return $this->options[$this->value] ?? $this->value;
        } elseif ($this->type == self::TYPE_LINK) {
            return $this->getLink();
        } else {
            return $this->value;
        }
    }

    public function getLink($customer_fields_with_values = [])
    {
        if ($this->type == self::TYPE_LINK 
            && !empty($this->options['link_url'])
            && !empty($this->customer_id)
        ) {
            // Replace all vars.
            // If some placeholder is empty return ''.
            preg_match_all("#\{%([^%]+)%\}#", $this->options['link_url'], $m);
            // if (empty($m[1])) {
            //     return '';
            // }
            $placeholders = $m[1] ?? [];
            $customer = Customer::find($this->customer_id);
            if (!$customer) {
                return '';
            }
            $data = [
                'id' => $customer->id,
                'email' => $customer->getMainEmail(),
                'phone' => $customer->getMainPhoneValue(),
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'company' => $customer->company,
                'job_title' => $customer->job_title,
                'website' => $customer->getMainWebsite(),
                'address' => $customer->address,
                'city' => $customer->city,
                'state' => $customer->state,
                'zip' => $customer->zip,
                'country' => $customer->country,
            ];

            $need_cfs = false;
            foreach ($placeholders as $placeholder) {
                if (self::decodeName($placeholder) != $placeholder) {
                   $need_cfs = true;
                   break;
                }
            }
            if ($need_cfs) {
                if (!$customer_fields_with_values) {
                    $customer_fields_with_values = self::getCustomerFieldsWithValues($this->customer_id);
                }
                foreach ($customer_fields_with_values as $custom_field) {
                    // Skip self and link fields (to avoid loops).
                    if ($custom_field->id == $this->id || $custom_field->type == self::TYPE_LINK) {
                        continue;
                    }
                    $data[self::NAME_PREFIX.$custom_field->id] = $custom_field->getAsText();
                }
            }
            $link_url = $this->options['link_url'];
            foreach ($placeholders as $placeholder) {
                if (!isset($data[$placeholder]) || $data[$placeholder] == '') {
                    return '';
                } else {
                    $link_url = str_replace('{%'.$placeholder.'%}', $data[$placeholder], $link_url);
                }
            }
            return $link_url;
        } else {
            return '';
        }
    }

    public static function shortenLink($url)
    {
        if (mb_strlen($url) > self::SHORT_LINK_LENGTH) {
            $url = mb_substr($url, 0, self::SHORT_LINK_LENGTH).'…';
        }
        return $url;
    }
}