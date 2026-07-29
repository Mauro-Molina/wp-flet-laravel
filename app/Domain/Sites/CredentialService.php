<?php

namespace App\Domain\Sites;

use App\Domain\Hmac\HmacService;
use App\Models\Site;
use App\Models\SiteCredential;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CredentialService
{
    public function __construct(
        private readonly HmacService $hmac,
    ) {}

    /**
     * @return array{credential: SiteCredential, plain_secret: string}
     */
    public function issue(Site $site): array
    {
        $plain = $this->hmac->generateSecret();

        $credential = SiteCredential::query()->create([
            'tenant_id' => $site->tenant_id,
            'site_id' => $site->id,
            'secret_encrypted' => Crypt::encryptString($plain),
            'secret_hash' => $this->hmac->hashSecret($plain),
            'version' => 1,
            'is_active' => true,
        ]);

        return ['credential' => $credential, 'plain_secret' => $plain];
    }

    /**
     * @return array{credential: SiteCredential, plain_secret: string}
     */
    public function rotate(Site $site): array
    {
        return DB::transaction(function () use ($site) {
            SiteCredential::query()
                ->where('site_id', $site->id)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->update([
                    'is_active' => false,
                    'revoked_at' => now(),
                ]);

            $nextVersion = (int) SiteCredential::query()
                ->where('site_id', $site->id)
                ->max('version') + 1;

            $plain = $this->hmac->generateSecret();

            $credential = SiteCredential::query()->create([
                'tenant_id' => $site->tenant_id,
                'site_id' => $site->id,
                'secret_encrypted' => Crypt::encryptString($plain),
                'secret_hash' => $this->hmac->hashSecret($plain),
                'version' => $nextVersion,
                'is_active' => true,
                'rotated_at' => now(),
            ]);

            return ['credential' => $credential, 'plain_secret' => $plain];
        });
    }

    public function revokeAll(Site $site): void
    {
        SiteCredential::query()
            ->where('site_id', $site->id)
            ->whereNull('revoked_at')
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);
    }
}
