<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;


class AuthTest extends TestCase
{
    // php artisan test --filter AuthTest

    use RefreshDatabase;

    private $data = [
        'name' => 'xyz16',
        'email' => 'xyz16@gmail.com',
        'password' => 'test1234',
    ];
    
    private $wrongData = [
        'name' => 'xyz16',
        'email' => 'xyz16@gmail.com',
        'password' => 'test12345',
    ];

    private $missingData = [
        'email' => 'missing@gmail.com',
        'password' => 'test1234',
    ]; 

    private $invalidData = [
        'name' => 'xyz', // invalid email format
        'email' => 'xyz',
        'password' => 'test1234',
    ]; 

    private $user;

    public function setup(): void
    {
        parent::setup();

        $this->user = User::factory()->make();
    }

    public function test_can_signup(): void
    {    
        $response = $this->postJson('/api/signup', $this->data);
        
        $response->assertStatus(200);        
    }

    public function test_cannot_signup_with_same_email(): void
    {    
        $this->test_can_signup();

        $response = $this->postJson('/api/signup', $this->data);

        $response->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'Validation failed',
        ]);        
    }    

    public function test_cannot_signup_with_missing_name(): void
    {   
        $response = $this->postJson('/api/signup', $this->missingData);
    
        $response->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'Validation failed',
        ]);        
    }

    public function test_cannot_signup_with_invalid_email(): void
    {   
        $response = $this->postJson('/api/signup', $this->invalidData);
    
        $response->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'Validation failed',
        ]);        
    }

    public function test_can_login(): void
    {    
        $this->test_can_signup();

        $response = $this->postJson('/api/login', $this->data);  

        $response->assertStatus(200);   
    }

    public function test_cannot_login_with_wrong_password(): void
    {    
        $this->test_can_signup();

        $response = $this->postJson('/api/login', $this->wrongData);  

        $response->assertStatus(403);   
    }
    
    public function test_can_logout(): void
    {   
        $this->test_can_login();

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);       
    }

    public function test_me_only_when_login(): void
    {    
        $response = $this->getJson('/api/me');
        
        $response->assertStatus(401); 

        $response = $this->actingAs($this->user)->getJson('/api/me');
        
        $response->assertStatus(200); 
    }
    
    public function test_can_refresh(): void
    {   
        $this->test_can_login();

        $response = $this->postJson('/api/refresh');

        dump($response);

        $response->assertStatus(200);       
    }
    
}


