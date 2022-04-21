<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TwilioInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_date' => ['date', 'date_format:Y-m-d', 'before_or_equal:start_date'],
            'end_date' => ['date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'calls_inbound_fee' => ['numeric', 'gte:0'],
            'sms_inbound_longcode_fee' => ['numeric', 'gte:0'],
        ];
    }
}
