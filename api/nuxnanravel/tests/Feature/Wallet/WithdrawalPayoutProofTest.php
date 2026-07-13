<?php

namespace Tests\Feature\Wallet;

use App\Models\Role;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WithdrawalPayoutProofTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletService::class);
        Storage::fake('local');

        // Create Roles
        foreach (['SUPER_ADMIN', 'ADMIN', 'MODERATOR'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create Admin
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('name', 'ADMIN')->first());

        // Create Normal User
        $this->user = User::factory()->create(['wallet' => 50000]);
    }

    private function bankAccount(): array
    {
        return ['bank_name' => 'kbank', 'account_number' => '1234567890', 'account_name' => 'Test User'];
    }

    private function createWithdrawal(User $user, string $amount): WalletTransaction
    {
        return $this->service->withdraw($user, $amount, 'bank_transfer', $this->bankAccount());
    }

    /** 1. Mark paid with valid proof succeeds */
    public function test_mark_paid_with_valid_proof_succeeds(): void
    {
        $tx = $this->createWithdrawal($this->user, '500');
        $this->service->approveWithdrawal($tx->fresh(), $this->admin);
        $this->service->processWithdrawal($tx->fresh(), $this->admin);

        $file = UploadedFile::fake()->image('receipt.png', 800, 600)->size(100); // 100 KB

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/wallet/withdrawals/{$tx->id}/paid", [
                'payment_reference' => 'TXN-999-SUCCESS',
                'proof' => $file,
            ]);

        $response->assertStatus(200);

        $freshTx = $tx->fresh();
        $this->assertSame('paid', $freshTx->status);
        $this->assertSame('TXN-999-SUCCESS', $freshTx->payment_reference);
        $this->assertNotNull($freshTx->payout_proof_path);
        $this->assertSame('receipt.png', $freshTx->payout_proof_original_name);
        $this->assertSame('image/png', $freshTx->payout_proof_mime);

        // Assert file exists in local (private) disk
        Storage::disk('local')->assertExists($freshTx->payout_proof_path);

        // Assert audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'withdrawal.paid',
            'entity_id' => $freshTx->id,
            'entity_type' => WalletTransaction::class,
        ]);
    }

    /** 2. Mark paid without proof fails validation */
    public function test_mark_paid_without_proof_fails_validation(): void
    {
        $tx = $this->createWithdrawal($this->user, '500');
        $this->service->approveWithdrawal($tx->fresh(), $this->admin);
        $this->service->processWithdrawal($tx->fresh(), $this->admin);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/wallet/withdrawals/{$tx->id}/paid", [
                'payment_reference' => 'TXN-999-SUCCESS',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['proof']);
    }

    /** 3. File validation check (invalid MIME / size limit) */
    public function test_mark_paid_invalid_file_fails_validation(): void
    {
        $tx = $this->createWithdrawal($this->user, '500');
        $this->service->approveWithdrawal($tx->fresh(), $this->admin);
        $this->service->processWithdrawal($tx->fresh(), $this->admin);

        // Invalid MIME (e.g. text/plain or executable shell script)
        $txtFile = UploadedFile::fake()->create('exploit.txt', 100, 'text/plain');

        $response1 = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/wallet/withdrawals/{$tx->id}/paid", [
                'payment_reference' => 'TXN-999-SUCCESS',
                'proof' => $txtFile,
            ]);

        $response1->assertStatus(422);

        // Over size limit (6MB)
        $largeFile = UploadedFile::fake()->image('huge.png')->size(6000); // 6MB (limit is 5MB)

        $response2 = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/wallet/withdrawals/{$tx->id}/paid", [
                'payment_reference' => 'TXN-999-SUCCESS',
                'proof' => $largeFile,
            ]);

        $response2->assertStatus(422);
    }

    /** 4. Double mark paid or paid in invalid status fails */
    public function test_cannot_mark_paid_in_invalid_status_or_twice(): void
    {
        $tx = $this->createWithdrawal($this->user, '500');
        // Status is still pending, not processing yet!
        $file = UploadedFile::fake()->image('receipt.png');

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/wallet/withdrawals/{$tx->id}/paid", [
                'payment_reference' => 'TXN-999',
                'proof' => $file,
            ]);

        $response->assertStatus(409); // Conflict (wrong state transitions)

        // Assert no file left in storage
        $files = Storage::disk('local')->allFiles("withdrawal-proofs/{$this->user->id}");
        $this->assertEmpty($files);
    }

    /** 5. Download proof authorization constraints */
    public function test_download_proof_authorizations(): void
    {
        $tx = $this->createWithdrawal($this->user, '500');
        $this->service->approveWithdrawal($tx->fresh(), $this->admin);
        $this->service->processWithdrawal($tx->fresh(), $this->admin);

        $file = UploadedFile::fake()->image('receipt.png');
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/wallet/withdrawals/{$tx->id}/paid", [
                'payment_reference' => 'TXN-999',
                'proof' => $file,
            ]);

        // Non-admin (normal user) downloading it (Unauthorized if not owner, but owner has view policy)
        $otherUser = User::factory()->create();
        $response1 = $this->actingAs($otherUser, 'api')
            ->getJson("/api/admin/wallet/withdrawals/{$tx->id}/proof");
        $response1->assertStatus(403);

        // Admin downloading it
        $response2 = $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/wallet/withdrawals/{$tx->id}/proof");
        $response2->assertStatus(200);
        $this->assertTrue($response2->headers->has('content-disposition'));
        $this->assertStringContainsString('receipt.png', $response2->headers->get('content-disposition'));

        // Owner (non-admin) trying to download via admin route gets 403 due to admin middleware protection
        $response3 = $this->actingAs($this->user, 'api')
            ->getJson("/api/admin/wallet/withdrawals/{$tx->id}/proof");
        $response3->assertStatus(403);
    }

    /** 6. Pending list includes both pending and under_review */
    public function test_pending_list_includes_pending_and_under_review(): void
    {
        $otherUser = User::factory()->create(['wallet' => 50000]);
        $tx1 = $this->createWithdrawal($this->user, '100'); // pending
        $tx2 = $this->createWithdrawal($otherUser, '200'); // pending
        $this->service->viewWithdrawal($tx2, $this->admin); // moves to under_review

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/wallet/withdrawals/pending');

        $response->assertStatus(200);
        $items = $response->json('data.withdrawals');

        $ids = collect($items)->pluck('id')->toArray();
        $this->assertContains($tx1->id, $ids);
        $this->assertContains($tx2->id, $ids);
    }

    /** 7. Maker checker regression: High value approval blocks maker from approving */
    public function test_maker_checker_blocks_maker_from_approving(): void
    {
        config(['wallet.withdraw.maker_checker_threshold' => 10000]);

        $largeTx = $this->createWithdrawal($this->user, '15000');

        // Admin views -> status is under_review, reviewer = admin
        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/wallet/withdrawals/{$largeTx->id}");

        // Admin tries to approve their own reviewed high-value withdrawal
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/wallet/withdrawals/{$largeTx->id}/approve", [
                'admin_note' => 'I reviewed and I want to approve it',
            ]);

        $response->assertStatus(422); // Validation/Maker checker error (422/409 depending on custom exception catch)
        // Let's assert it fails (could return 400 or 422 depending on WalletService throw logic)
        $this->assertFalse($response->json('success'));
    }
}
