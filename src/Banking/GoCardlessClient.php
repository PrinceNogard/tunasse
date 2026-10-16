<?php
declare(strict_types=1);

namespace Tunasse\Banking;

/**
 * Client Open Banking officiel pour GoCardless Bank Account Data (ex-Nordigen).
 * Permet la synchronisation automatique des comptes LCL et Revolut en conformité DSP2.
 */
class GoCardlessClient
{
    private const BASE_URL = 'https://bankaccountdata.gocardless.com/api/v2';
    
    // Identifiants officiels des institutions bancaires pour la France
    public const INSTITUTION_LCL = 'LCL_FR_CL42';
    public const INSTITUTION_REVOLUT = 'REVOLUT_REVOFR22';
    public const INSTITUTION_REVOLUT_EU = 'REVOLUT_REVOGB2L';

    private ?string $secretId;
    private ?string $secretKey;
    private ?string $cachedToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct(?string $secretId = null, ?string $secretKey = null)
    {
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretId) && !empty($this->secretKey);
    }

    /**
     * Obtient un JWT Access Token auprès de GoCardless.
     */
    public function authenticate(): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        if ($this->cachedToken !== null && time() < ($this->tokenExpiresAt - 60)) {
            return $this->cachedToken;
        }

        $response = $this->request('POST', '/token/new/', [
            'secret_id' => $this->secretId,
            'secret_key' => $this->secretKey,
        ], false);

        if (!empty($response['access'])) {
            $this->cachedToken = (string)$response['access'];
            $this->tokenExpiresAt = time() + (int)($response['access_expires'] ?? 86400);
            return $this->cachedToken;
        }

        return null;
    }

    /**
     * Initialise une demande de consentement bancaire (Requisition).
     * Retourne le lien officiel de redirection vers LCL ou Revolut.
     */
    public function createRequisition(string $institutionId, string $redirectUrl, string $reference): array
    {
        if (!$this->isConfigured()) {
            // Mode simulation si les clés API ne sont pas encore configurées
            return [
                'id' => 'sim_req_' . bin2hex(random_bytes(8)),
                'status' => 'CR',
                'link' => $redirectUrl . (str_contains($redirectUrl, '?') ? '&' : '?') . 'ref=' . urlencode($reference) . '&simulated=1',
                'institution_id' => $institutionId,
                'simulated' => true
            ];
        }

        $token = $this->authenticate();
        if (!$token) {
            throw new \RuntimeException("Impossible d'authentifier l'API GoCardless.");
        }

        $payload = [
            'redirect' => $redirectUrl,
            'institution_id' => $institutionId,
            'reference' => $reference,
            'user_language' => 'FR',
        ];

        return $this->request('POST', '/requisitions/', $payload, true);
    }

    /**
     * Récupère les informations d'une requisition (dont la liste des ID de comptes liés).
     */
    public function getRequisition(string $requisitionId): array
    {
        if (!$this->isConfigured() || str_starts_with($requisitionId, 'sim_req_')) {
            return [
                'id' => $requisitionId,
                'status' => 'LN',
                'accounts' => ['sim_acc_lcl_01', 'sim_acc_revolut_02'],
                'simulated' => true
            ];
        }

        return $this->request('GET', '/requisitions/' . urlencode($requisitionId) . '/', null, true);
    }

    /**
     * Récupère le solde en temps réel d'un compte bancaire.
     */
    public function getAccountBalances(string $accountId): array
    {
        if (!$this->isConfigured() || str_starts_with($accountId, 'sim_acc_')) {
            $isLcl = str_contains($accountId, 'lcl');
            return [
                'balances' => [
                    [
                        'balanceAmount' => [
                            'amount' => $isLcl ? '1450.20' : '620.40',
                            'currency' => 'EUR',
                        ],
                        'balanceType' => 'closingBooked',
                    ]
                ],
                'simulated' => true
            ];
        }

        return $this->request('GET', '/accounts/' . urlencode($accountId) . '/balances/', null, true);
    }

    /**
     * Récupère les transactions récentes d'un compte bancaire.
     */
    public function getAccountTransactions(string $accountId): array
    {
        if (!$this->isConfigured() || str_starts_with($accountId, 'sim_acc_')) {
            return [
                'transactions' => [
                    'booked' => [],
                    'pending' => [],
                ],
                'simulated' => true
            ];
        }

        return $this->request('GET', '/accounts/' . urlencode($accountId) . '/transactions/', null, true);
    }

    /**
     * Synchronise tous les comptes connectés dans l'état applicatif.
     */
    public function syncAll(array &$state): array
    {
        $banking = $state['banking'] ?? [
            'provider' => 'gocardless',
            'secret_id' => '',
            'secret_key' => '',
            'accounts' => [],
            'requisitions' => [],
        ];

        $updatedAccounts = [];
        $accounts = $banking['accounts'] ?? [];

        foreach ($accounts as $acc) {
            $accId = $acc['id'] ?? '';
            if (empty($accId)) {
                continue;
            }

            try {
                $balData = $this->getAccountBalances($accId);
                $balances = $balData['balances'] ?? [];
                $liveAmount = null;

                if (!empty($balances[0]['balanceAmount']['amount'])) {
                    $liveAmount = (float)$balances[0]['balanceAmount']['amount'];
                }

                $acc['last_balance'] = $liveAmount ?? ($acc['last_balance'] ?? 0.0);
                $acc['last_sync'] = date('Y-m-d H:i:s');
                $acc['status'] = 'connected';
                $updatedAccounts[] = $acc;
            } catch (\Throwable $e) {
                $acc['status'] = 'error';
                $acc['last_error'] = $e->getMessage();
                $updatedAccounts[] = $acc;
            }
        }

        $banking['accounts'] = $updatedAccounts;
        $banking['last_global_sync'] = date('Y-m-d H:i:s');
        $state['banking'] = $banking;

        return [
            'success' => true,
            'synced_accounts' => count($updatedAccounts),
            'last_sync' => $banking['last_global_sync'],
            'accounts' => $updatedAccounts,
        ];
    }

    /**
     * Envoie une requête HTTP à l'API GoCardless.
     */
    private function request(string $method, string $endpoint, ?array $payload = null, bool $auth = true): array
    {
        $url = self::BASE_URL . $endpoint;
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($auth) {
            $token = $this->authenticate();
            if ($token) {
                $headers[] = 'Authorization: Bearer ' . $token;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException("Erreur réseau GoCardless: " . $err);
        }

        $data = json_decode((string)$raw, true) ?? [];
        if ($code >= 400) {
            $msg = $data['detail'] ?? $data['summary'] ?? "Erreur HTTP $code";
            throw new \RuntimeException("Erreur API GoCardless ($code): " . $msg);
        }

        return $data;
    }
}
