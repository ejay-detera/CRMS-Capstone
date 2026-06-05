<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ConflictException;
use App\Models\VendorContractAssociation;
use App\Payloads\AttachContractPayload;

final readonly class AttachContractToVendor
{
    public function handle(AttachContractPayload $payload): VendorContractAssociation
    {
        $exists = VendorContractAssociation::where([
            'vendor_type' => $payload->vendorType,
            'vendor_id'   => $payload->vendorId,
            'contract_id' => $payload->contractId,
        ])->exists();

        if ($exists) {
            throw new ConflictException('Contract already linked to this vendor.');
        }

        return VendorContractAssociation::create([
            'vendor_type' => $payload->vendorType,
            'vendor_id'   => $payload->vendorId,
            'contract_id' => $payload->contractId,
            'attached_by' => $payload->attachedBy,
        ]);
    }
}
