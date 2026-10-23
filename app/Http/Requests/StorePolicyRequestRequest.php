<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePolicyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_request_id' => ['required', 'string', 'max:100'],
            'agent_id' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:trip_start'],
            'destination' => ['required', 'string', 'in:DOMESTIC,INTERNATIONAL,domestic,international'],
            'trip_start' => ['required', 'date_format:Y-m-d'],
            'trip_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:trip_start'],
            'plan_code' => ['required', 'string', 'in:BASIC,PLUS,PREMIUM,basic,plus,premium'],
            'sum_insured' => ['required', 'integer', 'in:100000,300000,500000'],
            'scenario' => ['nullable', 'string', 'in:SUCCESS,TEMPORARY_FAILURE,TIMEOUT_AFTER_ISSUE,DELAYED_CALLBACK,DUPLICATE_CALLBACK'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_request_id.required' => 'The client_request_id field is required.',
            'agent_id.required' => 'The agent_id field is required.',
            'trip_end.after_or_equal' => 'Trip end date must be greater than or equal to trip start date.',
            'plan_code.in' => 'Supported plans are BASIC, PLUS, and PREMIUM.',
            'sum_insured.in' => 'Supported coverage amounts are 100,000, 300,000, and 500,000.',
            'destination.in' => 'Destination must be DOMESTIC or INTERNATIONAL.',
            'date_of_birth.before' => 'Date of birth must be prior to the trip start date.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed for policy intake request.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
