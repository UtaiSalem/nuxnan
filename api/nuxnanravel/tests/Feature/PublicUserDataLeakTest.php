<?php

namespace Tests\Feature;

use App\Http\Resources\Earn\DonateResource;
use App\Http\Resources\PublicLeaderboardUserResource;
use App\Http\Resources\UserResource;
use App\Models\Donate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Tests\TestCase;

class PublicUserDataLeakTest extends TestCase
{
    use RefreshDatabase;

    private const PRIVATE_USER_KEYS = ['email', 'phone', 'phone_number', 'personal_code', 'pp', 'points',
        'wallet', 'locked_balance', 'suggester_code', 'freeze_reason', 'google_id', 'facebook_id',
        'two_factor_confirmed_at', 'deletion_reason'];

    protected $ceo;

    protected $recipient;

    protected $donor;

    protected $donate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ceo = User::factory()->create(['id' => 1, 'phone_number' => '0800000001', 'wallet' => 999]);
        $this->recipient = User::factory()->create(['pp' => 999999, 'wallet' => 1234.50, 'phone_number' => '0811111111']);
        $this->donor = User::factory()->create(['phone_number' => '0822222222', 'wallet' => 50]);

        $this->donate = Donate::create([
            'donor_id' => $this->donor->id,
            'user_id' => $this->donor->id,
            'amounts' => 100,
            'slip' => 'slips/test.jpg',
            'transfer_date' => now()->toDateString(),
            'status' => 1,
            'remaining_points' => 50,
            'transaction_id' => 'TX-SECRET-1',
            'donor_address' => '123 Secret Road',
            'payment_method' => 'bank',
            'notes' => 'internal note',
        ]);
    }

    private function assertNoPrivateKeys(?array $user, string $where): void
    {
        $this->assertIsArray($user, "{$where}: expected a user object");

        foreach (self::PRIVATE_USER_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $user, "{$where} leaks private key [{$key}]");
        }
    }

    private function assertMissingKeys(?array $data, array $keys, string $where): void
    {
        $this->assertIsArray($data, "{$where}: expected an object");

        foreach ($keys as $key) {
            $this->assertArrayNotHasKey($key, $data, "{$where} leaks key [{$key}]");
        }
    }

    public function test_welcome_recipients_and_ceo_have_no_private_columns(): void
    {
        $res = $this->getJson('/api/welcome');

        $res->assertStatus(200);

        $recipients = $res->json('donateRecipients');
        $this->assertNotEmpty($recipients);

        foreach ($recipients as $i => $recipient) {
            $this->assertMissingKeys($recipient, ['email', 'phone', 'phone_number', 'wallet', 'locked_balance'], "donateRecipients.{$i}");
        }

        foreach (['username', 'avatar', 'personal_code', 'points'] as $key) {
            $this->assertArrayHasKey($key, $recipients[0], "donateRecipients.0 is missing [{$key}] used by the home page");
        }

        $this->assertMissingKeys($res->json('ceo'), ['email', 'phone', 'phone_number', 'wallet', 'personal_code', 'points'], 'ceo');
    }

    public function test_welcome_donations_hide_donor_private_data(): void
    {
        $res = $this->getJson('/api/welcome');

        $res->assertStatus(200);

        $donor = $res->json('donates.0.donor');
        $this->assertMissingKeys($donor, ['email', 'phone', 'phone_number', 'wallet'], 'donates.0.donor');
        foreach (['personal_code', 'reference_code', 'username'] as $key) {
            $this->assertArrayHasKey($key, $donor, "donates.0.donor is missing [{$key}]");
        }

        $this->assertMissingKeys(
            $res->json('donates.0'),
            ['donor_address', 'transaction_id', 'payment_method', 'notes', 'review_note', 'approved_by'],
            'donates.0'
        );
    }

    public function test_referral_landing_has_no_private_columns(): void
    {
        $res = $this->getJson("/api/register/{$this->recipient->reference_code}");

        $res->assertStatus(200);

        $suggester = $res->json('suggester');
        $this->assertMissingKeys($suggester, ['email', 'phone', 'phone_number', 'wallet', 'personal_code', 'points'], 'register.suggester');
        $this->assertArrayHasKey('name', $suggester);
        $this->assertArrayHasKey('reference_code', $suggester);
    }

    public function test_suggester_check_has_no_private_columns(): void
    {
        $res = $this->getJson("/api/suggester/check/{$this->recipient->personal_code}");

        $res->assertStatus(200);

        $this->assertMissingKeys($res->json('suggester'), ['email', 'phone', 'phone_number', 'wallet'], 'suggester.check.suggester');
    }

    public function test_signed_in_donation_views_keep_full_donor_details(): void
    {
        $donate = $this->donate->load('donor');

        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->recipient);

        $data = (new DonateResource($donate))->toArray($request);

        $this->assertInstanceOf(UserResource::class, $data['donor']);
        $this->assertSame('TX-SECRET-1', $data['transaction_id']);

        $guestRequest = Request::create('/');

        $guestData = (new DonateResource($donate))->toArray($guestRequest);

        $this->assertInstanceOf(PublicLeaderboardUserResource::class, $guestData['donor']);
        $this->assertInstanceOf(MissingValue::class, $guestData['transaction_id']);
    }
}
