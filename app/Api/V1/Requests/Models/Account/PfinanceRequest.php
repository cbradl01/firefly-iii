<?php

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests\Models\Account;

use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class PfinanceRequest.
 */
class PfinanceRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    /**
     * Get all data from the request.
     */
    public function getAll(): array
    {
        return [
            'account_id' => $this->convertString('account_id'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|string',
        ];
    }
}
