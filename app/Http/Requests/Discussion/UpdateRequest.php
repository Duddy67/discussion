<?php

namespace App\Http\Requests\Discussion;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\AccessLevel;


class UpdateRequest extends FormRequest
{
    use AccessLevel;

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
            'title' => 'required',
            //'image' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048', 'dimensions:min_width=100,min_height=100,max_width=1000,max_height=1000'],
            'description' => 'required',
            'category' => 'required',
            'discussion_date' => 'required',
            'discussion_link' => 'required',
            'max_attendees' => 'required',
        ];

        if ($this->discussion->canChangeAccessLevel()) {
            $rules['access_level'] = 'required';
        }

        if ($this->discussion->canChangeStatus()) {
            $rules['status'] = 'required';
        }

        if ($this->discussion->canChangeAttachments()) {
            $rules['owned_by'] = 'required';
        }

        return $rules;
    }
}
