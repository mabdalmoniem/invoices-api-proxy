<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TwilioInvoiceRequest;

class TwilioApiProxyController extends Controller
{
    public function __invoke(TwilioInvoiceRequest $request)
    {
        // 
    }
}
