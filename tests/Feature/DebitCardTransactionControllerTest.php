<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        $this->debitCard->debitCardTransactions()->saveMany(DebitCardTransaction::factory()->count(2)->make());
        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);
        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJson([
            ['id' => $this->debitCard->debitCardTransactions->first()->id],
            ['id' => $this->debitCard->debitCardTransactions->last()->id],
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $otherUserDebitCard->debitCardTransactions()->saveMany(DebitCardTransaction::factory()->count(2)->make());
        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $otherUserDebitCard->id);
        $response->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100,
            'currency_code' => DebitCardTransaction::CURRENCY_SGD,
        ]);
        $response->assertStatus(201);
        $response->assertJson([
            'id' => $this->debitCard->debitCardTransactions->last()->id,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $otherUserDebitCard->id,
            'amount' => 100,
            'currency_code' => DebitCardTransaction::CURRENCY_SGD,
        ]);
        $response->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $this->debitCard->debitCardTransactions()->saveMany(DebitCardTransaction::factory()->count(2)->make());
        $response = $this->getJson('/api/debit-card-transactions/' . $this->debitCard->debitCardTransactions->first()->id);
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $this->debitCard->debitCardTransactions->first()->id,
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        $otherUserDebitCard->debitCardTransactions()->saveMany(DebitCardTransaction::factory()->count(2)->make());
        $response = $this->getJson('/api/debit-card-transactions/' . $otherUserDebitCard->debitCardTransactions->first()->id);
        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
