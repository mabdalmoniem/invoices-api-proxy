<?php

namespace Tests\Feature;

use Tests\TestCase;

class TwilioApiProxyTest extends TestCase
{

    /**
     * @test 
     */
    public function it_rejects_wrong_date_formats()
    {
        $response = $this->getJson(route('twilio.invoice', ['start_date' => '20/01/01', 'end_date' => '20/01/01']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
        $response->assertJsonFragment(['The start date does not match the format Y-m-d.']);
        $response->assertJsonFragment(['The end date does not match the format Y-m-d.']);
    }

    /**
     * @test 
     */
    public function it_rejects_end_date_if_it_is_before_start_date()
    {
        $response = $this->getJson(route('twilio.invoice', ['start_date' => '2020-01-01', 'end_date' => '2019-01-01']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
        $response->assertJsonFragment(['The end date must be a date after or equal to start date.']);
    }

    /**
     * @test 
     */
    public function it_rejects_additional_fess_if_it_less_than_zero()
    {
        $response = $this->getJson(route('twilio.invoice', ['calls_inbound_fee' => '-1', 'sms_inbound_longcode_fee' => '-1']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['calls_inbound_fee', 'sms_inbound_longcode_fee']);
        $response->assertJsonFragment(['The calls inbound fee must be greater than or equal to 0.']);
        $response->assertJsonFragment(['The sms inbound longcode fee must be greater than or equal to 0.']);
    }

    /**
     * @test 
     */
    public function it_returns_empty_result_with_wrong_account_number()
    {
        $response = $this->getJson(route('twilio.invoice', ['account_number' => 'not_an_actual_account_number']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['data' => []]);
    }

    /**
     * @test 
     */
    public function it_returns_result_with_valid_account_number()
    {
        $response = $this->getJson(route('twilio.invoice', ['account_number' => 'AC977a3102fe6a45dd7dd2b70895049f7f']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                [
                    "account_sid",
                    "category",
                    "tratta_price",
                ],
                [
                    "account_sid",
                    "category",
                    "tratta_price",
                ],
            ]
        ]);

        $decodedResponse = $response->decodeResponseJson();
        $inbound_calls = collect($decodedResponse['data'])->first(fn ($item) => $item['category'] == 'calls-inbound');
        $inbound_sms = collect($decodedResponse['data'])->first(fn ($item) => $item['category'] == 'sms-inbound-longcode');

        $this->assertNotNull($inbound_calls);
        $this->assertNotNull($inbound_sms);
    }

    /**
     * @test 
     */
    public function it_returns_zero_for_tratta_price_when_no_fees_are_supplied()
    {
        $response = $this->getJson(route('twilio.invoice', ['account_number' => 'AC977a3102fe6a45dd7dd2b70895049f7f']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['tratta_price' => 0]);
    }

    /**
     * @test 
     */
    public function it_can_calculate_tratta_price_when_fees_are_supplied()
    {
        $response = $this->getJson(route('twilio.invoice', ['account_number' => 'AC977a3102fe6a45dd7dd2b70895049f7f', 'calls_inbound_fee' => 0.1, 'sms_inbound_longcode_fee' => 0.2]));

        $decodedResponse = $response->decodeResponseJson();
        $inbound_calls = collect($decodedResponse['data'])->first(fn ($item) => $item['category'] == 'calls-inbound');
        $inbound_sms = collect($decodedResponse['data'])->first(fn ($item) => $item['category'] == 'sms-inbound-longcode');

        $response->assertStatus(200);
        $this->assertEquals((0.1 * $inbound_calls['usage']), $inbound_calls['tratta_price']);
        $this->assertEquals((0.2 * $inbound_sms['usage']), $inbound_sms['tratta_price']);
    }
}
