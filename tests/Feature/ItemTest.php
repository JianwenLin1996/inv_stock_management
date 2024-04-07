<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Item;

class ItemTest extends TestCase
{
    // php artisan test --filter ItemTest
    use RefreshDatabase;

    private $user;

    private $item;

    public function setup(): void
    {
        parent::setup();

        $this->user = User::factory()->make();

        $this->item = Item::factory()->make();
    }

    // TEST CREATE ITEM //

    public function test_can_create_item(): int
    {    
        $response = $this->actingAs($this->user)->postJson('/api/items', $this->item->toArray()); 
        
        $response->assertStatus(200); 
        
        return $response->json('data.item.id');
    }

    public function test_can_create_item_without_login(): void
    {    
        $response = $this->postJson('/api/items', $this->item->toArray()); 
        
        $response->assertStatus(401); 
    }

    public function test_can_create_item_with_empty_description(): void
    {    
        $this->item->description = "";

        $response = $this->actingAs($this->user)->postJson('/api/items', $this->item->toArray()); 

        $response->assertStatus(200);        
    }
    

    public function test_cannot_create_item_with_empty_name(): void
    {    
        $this->item->name = "";

        $response = $this->actingAs($this->user)->postJson('/api/items', $this->item->toArray());   

        $response->assertStatus(422);        
    }

    // TEST READ ITEM //

    public function test_can_read_items(): void
    {    
        $response = $this->actingAs($this->user)->getJson('/api/items');    

        $response->assertStatus(200);    
    }

    public function test_cannot_read_items_without_login(): void
    {    
        $response = $this->getJson('/api/items');    

        $response->assertStatus(401);     
    }

    // TEST SHOW ITEM //

    public function test_can_show_item(): void
    {    
        $id = $this->test_can_create_item(); 
        
        $response = $this->getJson('/api/items/'.$id);    
        
        $response->assertStatus(200);         
    }

    public function test_cannot_show_item_without_login(): void
    {    
        $response = $this->getJson('/api/items/1');    
        
        $response->assertStatus(401);         
    }
    
    public function test_cannot_show_not_found_item(): void
    {    
        $response = $this->actingAs($this->user)->getJson('/api/items/9999');    
        
        $response->assertStatus(404);         
    }

    // TEST UPDATE ITEM //

    public function test_can_update_item(): void
    {    
        $id = $this->test_can_create_item(); 
        
        $this->item->name = "updated_name";

        $response = $this->postJson('/api/items/'.$id, $this->item->toArray());    
        
        $response->assertStatus(200); 
        
        $this->assertSame( $this->item->name,  $response->json('data.item.name'));
    }

    public function test_cannot_update_item_without_login(): void
    {            
        $response = $this->postJson('/api/items/1', $this->item->toArray());    
        
        $response->assertStatus(401); 
    }

    public function test_cannot_update_item_with_empty_name(): void
    {            
        $id = $this->test_can_create_item(); 
        
        $this->item->name = "";

        $response = $this->postJson('/api/items/'.$id, $this->item->toArray());    
        
        $response->assertStatus(422);  
    }

    public function test_can_update_item_with_empty_description(): void
    {            
        $id = $this->test_can_create_item(); 
        
        $this->item->description = "";

        $response = $this->postJson('/api/items/'.$id, $this->item->toArray());    
        
        $response->assertStatus(200);  
    }

    // TEST DELETE ITEM //

    public function test_can_delete_item(): void
    {            
        $id = $this->test_can_create_item(); 

        $response = $this->json('delete', '/api/items/'.$id);    
        
        $response->assertStatus(200);  
    }

    public function test_cannot_delete_item_without_login(): void
    {            
        $response = $this->json('delete', '/api/items/1');    
        
        $response->assertStatus(401);  
    }

    public function test_cannot_delete_not_found_item(): void
    {     
        $response = $this->actingAs($this->user)->json('delete', '/api/items/9999');    
        
        $response->assertStatus(404);  
    }

}