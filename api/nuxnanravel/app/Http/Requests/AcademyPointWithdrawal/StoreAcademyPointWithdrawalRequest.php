<?php

namespace App\Http\Requests\AcademyPointWithdrawal;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademyPointWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academy = $this->route('academy');

        return $this->user() && ($this->user()->id === $academy->user_id || $academy->isAdmin($this->user()));
    }

    public function rules(): array
    {
        return ['amount' => 'required|integer|min:24000|max:10000000', 'purpose' => 'nullable|string|max:500'];
    }
}
