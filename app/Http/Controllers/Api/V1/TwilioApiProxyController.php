<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TwilioInvoiceRequest;
use App\Services\TwilioInvoiceService;

class TwilioApiProxyController extends Controller
{
    public function __invoke(TwilioInvoiceRequest $request)
    {
        // We can can inject an interface that will resolve the right service, instead of instantiating it ourselves
        // but for simple apps (where we don't need to swap implementation at run time) this will work just fine
        $result = (new TwilioInvoiceService($request->only(['account_number', 'start_date', 'end_date', 'calls_inbound_fee', 'sms_inbound_longcode_fee'])))->getUsage();
        return response()->json(['data' => $result->getData()], $result->getStatusCode());
    }
}
