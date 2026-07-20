<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://meilisearch:7700'),
        'key' => env('MEILISEARCH_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | high_risk_approval_gate_enabled: US-023's mandatory approval gate (block
    | "Approved" for High/Critical AI Risk Assessment results until a
    | rationale'd decision is recorded). Defaults to OFF — the AI Risk
    | Assessment is currently advisory only: it surfaces a flag/warning on
    | the contract but never prevents approval. All of the gate's supporting
    | code (HighRiskApprovalGateService, ContractApproval model/migration,
    | ContractApprovalController, the SLA escalation command) is left in
    | place, inert behind this flag, so it can be re-enabled later by simply
    | flipping HIGH_RISK_APPROVAL_GATE_ENABLED=true without further code changes.
    |
    */

    'features' => [
        'high_risk_approval_gate_enabled' => env('HIGH_RISK_APPROVAL_GATE_ENABLED', false),
    ],

];
