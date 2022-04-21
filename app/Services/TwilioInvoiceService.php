<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TwilioInvoiceService
{
    private array $data;
    private int $statusCode;
    const TRACKED_CATEGORIES = [
        'calls-inbound',
        'sms-inbound-longcode'
    ];

    public function __construct(public array $queryParams)
    {
        $this->data = [];
        $this->statusCode = 400;
    }

    public function getUsage(): self
    {
        try {
            $response = Http::withToken($this->getToken())->get($this->getUrl(), $this->queryParams);
            $this->statusCode = $response->status();
            if ($response->status() == 200 && !empty($response->json())) {
                $this->tryToExtractOutput($response->json())->addTrattaPriceKey();
            }
        } catch (\Throwable $t) {
            dd('t', $t->getMessage());
        }

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    private function getUrl(): string
    {
        return config('apiproxy.urls.twilio', 'https://sandbox.tratta.io/api/twilio-test-run');
    }

    private function getToken(): string
    {
        return config('apiproxy.tokens.twilio', 'Bearer tratta-test-run');
    }

    private function tryToExtractOutput($data = []): self
    {
        if (array_key_exists('usage_records', $data)) {
            $this->data = collect($data['usage_records'])->filter(fn ($r) => !empty($r['category']) && in_array($r['category'], self::TRACKED_CATEGORIES))->values()->all();
        }

        return $this;
    }

    private function addTrattaPriceKey(): self
    {
        if (!empty($this->data)) {
            $this->data = collect($this->data)->map(function ($r) {
                $r['tratta_price'] = 0;
                if ($r["category"] == "calls-inbound" && !empty($this->queryParams["calls_inbound_fee"])) {
                    $r['tratta_price'] = $this->queryParams["calls_inbound_fee"] * $r["usage"];
                } elseif ($r["category"] == "sms-inbound-longcode" && !empty($this->queryParams["sms_inbound_longcode_fee"])) {
                    $r['tratta_price'] = $this->queryParams["sms_inbound_longcode_fee"] * $r["usage"];
                }
                return $r;
            })->values()->all();
        }

        return $this;
    }
}
