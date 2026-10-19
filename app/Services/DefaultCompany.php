<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DefaultCompany
{
    public function id(): ?int
    {
        $companyId = DB::table('companies')
            ->where('status', 'active')
            ->orderBy('company_id')
            ->value('company_id');

        return $companyId === null ? null : (int) $companyId;
    }

    public function requireId(): int
    {
        $companyId = $this->id();
        if ($companyId === null) {
            throw ValidationException::withMessages([
                'company_id' => 'Create an active company before adding users, devices, or shops.',
            ]);
        }

        return $companyId;
    }
}
