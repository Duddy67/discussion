<?php

namespace App\Http\Requests\Discussion;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        $rules = [
            'subject' => 'required',
            'description' => 'required',
            'category_id' => 'required',
            'discussion_date' => 'required',
            'platform' => 'required',
            'discussion_link' => 'required',
            'max_attendees' => 'required',
            //'image' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048', 'dimensions:min_width=100,min_height=100,max_width=1000,max_height=1000'],
            'status' => 'required',
        ];

        if (auth()->user()->canAccessAdmin()) {
            $rules['access_level'] = 'required';
            $rules['owned_by'] = 'required';
        }

        return $rules;
    }
}
