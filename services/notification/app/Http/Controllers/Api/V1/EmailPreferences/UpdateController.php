<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\EmailPreferences;

use App\Actions\EmailPreferences\UpdateEmailPreference;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmailPreferences\UpdateEmailPreferenceRequest;
use App\Http\Resources\EmailPreferenceResource;

final class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        UpdateEmailPreferenceRequest $request,
        UpdateEmailPreference $action
    ): EmailPreferenceResource {
        $userId = (int) $request->input('auth_id');

        $preference = $action->handle($userId, $request->toPayload());

        return new EmailPreferenceResource($preference);
    }
}
