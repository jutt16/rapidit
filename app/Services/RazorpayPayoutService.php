<?php

namespace App\Services;

use App\Models\BankingDetail;
use App\Models\Withdrawal;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class RazorpayPayoutService
{
    protected Client $http;
    protected string $baseUrl = 'https://api.razorpay.com/v1';
    protected string $keyId;
    protected string $keySecret;
    protected ?string $accountNumber;

    public function __construct()
    {
        $this->keyId = (string) config('services.razorpay.key_id');
        $this->keySecret = (string) config('services.razorpay.key_secret');
        $this->accountNumber = config('services.razorpay.account_number');
        $this->http = new Client([
            'base_uri' => $this->baseUrl . '/',
            'auth' => [$this->keyId, $this->keySecret],
            'timeout' => 30,
        ]);
    }

    public function ensureContactAndFundAccount(BankingDetail $bankingDetail): array
    {
        $contactId = $bankingDetail->razorpay_contact_id;
        $fundAccountId = $bankingDetail->razorpay_fund_account_id;

        if (!$contactId) {
            $contactId = $this->createContact($bankingDetail);
            $bankingDetail->razorpay_contact_id = $contactId;
            $bankingDetail->save();
        }

        if (!$fundAccountId) {
            $fundAccountId = $this->createFundAccount($contactId, $bankingDetail);
            $bankingDetail->razorpay_fund_account_id = $fundAccountId;
            $bankingDetail->save();
        }

        return [$contactId, $fundAccountId];
    }

    protected function createContact(BankingDetail $bankingDetail): string
    {
        $name = $bankingDetail->account_holder_name ?: ($bankingDetail->user?->name ?? 'User '.$bankingDetail->user_id);
        $email = $bankingDetail->user?->profile?->email ?? null;
        $phone = $bankingDetail->user?->phone ?? null;

        $payload = [
            'name' => $name,
            'email' => $email,
            'contact' => $phone,
            'type' => 'employee',
            'reference_id' => 'user_'.$bankingDetail->user_id,
        ];

        $res = $this->http->post('contacts', ['json' => $payload]);
        $data = json_decode((string) $res->getBody(), true);
        return $data['id'];
    }

    protected function createFundAccount(string $contactId, BankingDetail $bankingDetail): string
    {
        $payload = [
            'contact_id' => $contactId,
            'account_type' => 'bank_account',
            'bank_account' => [
                'name' => $bankingDetail->account_holder_name,
                'ifsc' => $bankingDetail->ifsc,
                'account_number' => $bankingDetail->account_number,
            ],
        ];

        $res = $this->http->post('fund_accounts', ['json' => $payload]);
        $data = json_decode((string) $res->getBody(), true);
        return $data['id'];
    }

    public function createPayout(Withdrawal $withdrawal, BankingDetail $bankingDetail, string $mode = 'IMPS'): array
    {
        if (!$this->accountNumber) {
            throw new \RuntimeException('RazorpayX account number not configured');
        }

        [$contactId, $fundAccountId] = $this->ensureContactAndFundAccount($bankingDetail);

        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'account_number' => $this->accountNumber,
            'fund_account_id' => $fundAccountId,
            'amount' => (int) round($withdrawal->amount * 100),
            'currency' => $withdrawal->currency ?: 'INR',
            'mode' => $mode,
            'purpose' => 'payout',
            'queue_if_low_balance' => true,
            'reference_id' => (string) $withdrawal->id,
            'narration' => 'Withdrawal '.$withdrawal->id,
            'notes' => [
                'withdrawal_id' => (string) $withdrawal->id,
                'user_id' => (string) $withdrawal->user_id,
            ],
        ];

        $res = $this->http->post('payouts', [
            'json' => $payload,
            'headers' => ['X-Idempotency-Key' => $idempotencyKey],
        ]);

        return json_decode((string) $res->getBody(), true);
    }

    public function fetchPayoutStatus(string $payoutId): array
    {
        $res = $this->http->get("payouts/{$payoutId}");
        return json_decode((string) $res->getBody(), true);
    }
}


