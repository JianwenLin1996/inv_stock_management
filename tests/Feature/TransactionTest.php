<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Item;
use App\Models\ItemLog;
use App\Models\Transaction;

class TransactionTest extends TestCase
{
    // php artisan test --filter TransactionTest
    use RefreshDatabase;

    private $transaction;

    private $user;

    private $item;

    public function setup(): void
    {
        parent::setup();

        $this->user = User::factory()->make();

        $this->item = Item::factory()->make();

        $this->transaction = Transaction::factory()->make();
    }

    public function create_item(): int
    {    
        $response = $this->actingAs($this->user)->postJson('/api/items', $this->item->toArray()); 
        
        return $response->json('data.item.id');
    }

    public function assertItemLogs(array $values, int $itemId): void
    {
        $itemLogs = ItemLog::where('item_id', $itemId)
        ->orderBy('logged_at', 'asc')
        ->orderBy('created_at', 'asc')
        ->get();
        // dump($itemLogs);

        foreach($itemLogs as $index => $log) 
        {
            $this->assertSame($values[$index]->total_stock, $log->total_stock);
            $this->assertSame(round($values[$index]->total_value, 2), round($log->total_value, 2));
            $this->assertSame(round($values[$index]->cost_per_item, 2), round($log->cost_per_item, 2));
        }
    }

    // TEST CREATE TRANSACTION //

    public function test_can_create_purchase_with_expected_item_logs(): int
    {
        $itemId = $this->create_item();
        
        $this->transaction->item_id = $itemId;

        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 20,
                'total_value' => 400.00,
                'cost_per_item' => 20.00
            ]
        ], $itemId);

        return $response->json('data.transaction.id');
    }
    
    public function test_cannot_create_purchase_without_login(): void
    {
        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(401); 
    }
    
    public function test_cannot_create_first_sales(): void
    {
        $itemId = $this->create_item();

        $this->transaction->item_id = $itemId;
        
        $this->transaction->is_purchase = 0;

        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(422); 
    } 

    public function test_can_create_second_purchase_with_expected_item_logs(): array
    {
        $prevTransactionIds = $this->test_can_create_purchase_with_expected_item_logs();
        
        $this->transaction->item_count = 20;
        $this->transaction->total_item_price = 800;
        $this->transaction->transaction_at = '2024-04-06 12:00:00';

        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 20,
                'total_value' => 400.00,
                'cost_per_item' => 20.00
            ],
            (object)[
                'total_stock' => 40,
                'total_value' => 1200.00,
                'cost_per_item' => 30.00
            ]
        ], $this->transaction->item_id);

        return [$prevTransactionIds, $response->json('data.transaction.id')];
    }  

    public function test_can_create_second_sales_with_expected_item_logs(): array
    {
        $prevTransactionIds = $this->test_can_create_purchase_with_expected_item_logs();
        
        $this->transaction->is_purchase = 0;
        $this->transaction->item_count = 10;
        $this->transaction->total_item_price = 800;
        $this->transaction->transaction_at = '2024-04-06 12:00:00';

        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 20,
                'total_value' => 400.00,
                'cost_per_item' => 20.00
            ],
            (object)[
                'total_stock' => 10,
                'total_value' => 200.00,
                'cost_per_item' => 20.00
            ]
        ], $this->transaction->item_id);

        return [$prevTransactionIds, $response->json('data.transaction.id')];
    } 
    
    public function test_cannot_create_second_sales_with_invalid_item_count(): void
    {
        $this->test_can_create_purchase_with_expected_item_logs();
        
        $this->transaction->is_purchase = 0;
        $this->transaction->item_count = 100;
        $this->transaction->total_item_price = 800;
        $this->transaction->transaction_at = '2024-04-06 12:00:00';

        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(422); 
    } 
    
    public function test_can_create_earlier_purchase_with_expected_item_logs(): void
    {
        $this->test_can_create_purchase_with_expected_item_logs();
        
        $this->transaction->item_count = 20;
        $this->transaction->total_item_price = 800;
        $this->transaction->transaction_at = '2024-04-02 12:00:00';

        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 20,
                'total_value' => 800.00,
                'cost_per_item' => 40.00
            ],
            (object)[
                'total_stock' => 40,
                'total_value' => 1200.00,
                'cost_per_item' => 30.00
            ]
        ], $this->transaction->item_id);
    } 
    
    public function test_can_create_earlier_sales_with_expected_item_logs(): array
    {
        $prevTransactionIds = $this->test_can_create_second_purchase_with_expected_item_logs();
        
        $this->transaction->is_purchase = 0;
        $this->transaction->item_count = 10;
        $this->transaction->total_item_price = 800;
        $this->transaction->transaction_at = '2024-04-05 12:00:00';

        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 20,
                'total_value' => 400.00,
                'cost_per_item' => 20.00
            ],
            (object)[
                'total_stock' => 10,
                'total_value' => 200.00,
                'cost_per_item' => 20.00
            ],
            (object)[
                'total_stock' => 30,
                'total_value' => 1000.00,
                'cost_per_item' => 33.33
            ]
        ], $this->transaction->item_id);

        array_splice($prevTransactionIds, 1, 0, $response->json('data.transaction.id'));

        return $prevTransactionIds;        
    } 
    
    public function test_cannot_create_first_earlier_sales(): void
    {
        $this->test_can_create_purchase_with_expected_item_logs();
        
        $this->transaction->is_purchase = 0;
        $this->transaction->item_count = 10;
        $this->transaction->total_item_price = 800;
        $this->transaction->transaction_at = '2024-04-03 12:00:00';

        $response = $this->postJson('/api/transactions', $this->transaction->toArray()); 
        
        $response->assertStatus(422); 
    } 

    // TEST UPDATE TRANSACTION //  

    public function test_can_update_purchase_with_expected_item_logs(): void
    {
        $prevTransactionIds = $this->test_can_create_second_purchase_with_expected_item_logs();
        
        $this->transaction->item_count = 30;
        $this->transaction->total_item_price = 700;

        $response = $this->postJson('/api/transactions/' . $prevTransactionIds[0], $this->transaction->toArray()); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 30,
                'total_value' => 700.00,
                'cost_per_item' => 23.33
            ],
            (object)[
                'total_stock' => 50,
                'total_value' => 1500.00,
                'cost_per_item' => 30.00
            ]
        ], $this->transaction->item_id);
    } 

    public function test_can_update_sales_with_expected_item_logs(): void
    {
        $prevTransactionIds = $this->test_can_create_earlier_sales_with_expected_item_logs();
        
        $this->transaction->is_purchase = 0;
        $this->transaction->item_count = 15;
        $this->transaction->total_item_price = 700;

        $response = $this->postJson('/api/transactions/' . $prevTransactionIds[1], $this->transaction->toArray()); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 20,
                'total_value' => 400.00,
                'cost_per_item' => 20.00
            ],
            (object)[
                'total_stock' => 5,
                'total_value' => 100.00,
                'cost_per_item' => 20.00
            ],
            (object)[
                'total_stock' => 25,
                'total_value' => 900.00,
                'cost_per_item' => 36.00
            ]
        ], $this->transaction->item_id);
    } 
    
    public function test_cannot_update_purchase_without_login(): void
    {
        $response = $this->postJson('/api/transactions/1', $this->transaction->toArray()); 
        
        $response->assertStatus(401); 
    }

    public function test_cannot_update_purchase_with_invalid_item_count(): void
    {
        $prevTransactionIds = $this->test_can_create_earlier_sales_with_expected_item_logs();
        
        $this->transaction->item_count = 5;
        $this->transaction->total_item_price = 50;

        $response = $this->postJson('/api/transactions/' . $prevTransactionIds[0], $this->transaction->toArray()); 
        
        $response->assertStatus(422); 
    } 

    public function test_cannot_update_sales_with_invalid_item_count(): void
    {
        $prevTransactionIds = $this->test_can_create_earlier_sales_with_expected_item_logs();
        
        $this->transaction->is_purchase = 0;
        $this->transaction->item_count = 30;
        $this->transaction->total_item_price = 700;

        $response = $this->postJson('/api/transactions/' . $prevTransactionIds[1], $this->transaction->toArray()); 
        
        $response->assertStatus(422); 
    } 

    // TEST DELETE TRANSACTION //  

    public function test_can_delete_purchase_with_expected_item_logs(): void
    {
        $prevTransactionIds = $this->test_can_create_second_purchase_with_expected_item_logs();

        $response = $this->json('delete', '/api/transactions/' . $prevTransactionIds[0]); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 20,
                'total_value' => 800.00,
                'cost_per_item' => 40.00
            ]
        ], $this->transaction->item_id);
    } 

    public function test_can_delete_sales_with_expected_item_logs(): void
    {
        $prevTransactionIds = $this->test_can_create_earlier_sales_with_expected_item_logs();

        $response = $this->json('delete', '/api/transactions/' . $prevTransactionIds[1]); 
        
        $response->assertStatus(200); 

        $this->assertItemLogs( [
            (object)[
                'total_stock' => 20,
                'total_value' => 400.00,
                'cost_per_item' => 20.00
            ],
            (object)[
                'total_stock' => 40,
                'total_value' => 1200.00,
                'cost_per_item' => 30.00
            ]
        ], $this->transaction->item_id);
    } 
    
    public function test_cannot_delete_purchase_without_login(): void
    {
        $response = $this->json('delete', '/api/transactions/1'); 
        
        $response->assertStatus(401); 
    }

    public function test_cannot_delete_first_purchase_before_sales(): void
    {
        $prevTransactionIds = $this->test_can_create_earlier_sales_with_expected_item_logs();

        $response = $this->json('delete', '/api/transactions/' . $prevTransactionIds[0]); 
        
        $response->assertStatus(422); 
    } 

    // TEST READ TRANSACTION //

    public function test_can_read_transactions(): void
    {         
        $prevTransactionIds = $this->test_can_create_purchase_with_expected_item_logs(); 
        
        $response = $this->getJson('/api/transactions?page=1&is_purchase=1&item='.$this->transaction->item_id);  

        $response->assertStatus(200);

        $response->assertSee('cost_per_item');
    }

    public function test_cannot_read_transactions_without_login(): void
    {    
        $response = $this->getJson('/api/transactions');    

        $response->assertStatus(401);     
    }

    // TEST SHOW TRANSACTION //

    public function test_can_show_transaction(): void
    {    
        $prevTransactionIds = $this->test_can_create_purchase_with_expected_item_logs(); 
        
        $response = $this->getJson('/api/transactions/'.$prevTransactionIds);    
        
        $response->assertStatus(200);   
    }

    public function test_cannot_show_transaction_without_login(): void
    {    
        $response = $this->getJson('/api/transactions/1');    
        
        $response->assertStatus(401);         
    }
    
    public function test_cannot_show_not_found_transaction(): void
    {    
        $response = $this->actingAs($this->user)->getJson('/api/transactions/9999');    
        
        $response->assertStatus(404);         
    }
}
