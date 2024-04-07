<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\ItemRequest;
use App\Models\Item;

use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $items = Item::get();
        return ResponseHelper::success('Item returned successfully.', data:[
            'items'=> $items,
        ]); 
    }

    public function store(ItemRequest $request)
    {   
        try {  
            $item = new Item();
            $item->name = $request['name'];
            $item->description = $request['description'];
            
            if($item->save()) {
                return ResponseHelper::success('Item created successfully.', data:[
                    'item'=> $item,
                ]); 
            }
            else
                return ResponseHelper::error('Item fail to be created.');   
        }  catch (\Exception $e) {
            return ResponseHelper::error('Error in creating item.', statusCode:500);
        }
    }

    public function show(Request $request, $id)
    {
        try {            
            // Check if item exists
            $item = Item::find($id);
            if ($item === null) {
                return ResponseHelper::notFound('Item not found.');              
            }
            return ResponseHelper::success('Item returned successfully.', data:[
                'item'=> $item,
            ]); 

        }  catch (\Exception $e) {
            return ResponseHelper::error("Item failed to be shown.", statusCode:500);
        }
    }
    
    public function update(ItemRequest $request,  $id)
    {
        try {            
            // Check if item exists
            $item = Item::find($id);
            if ($item === null) {
                return ResponseHelper::notFound('Item not found.');              
            }
            
            $item->name = $request['name'];
            $item->description = $request['description'];
            
            if (! $item->save()) {
                return ResponseHelper::error("Item failed to be updated.", statusCode:500);
            }

            return ResponseHelper::success('Item updated successfully.', data:[
                'item'=> $item,
            ]); 

        }  catch (\Exception $e) {
            return ResponseHelper::error("Item failed to be updated.", statusCode:500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {  
            // Check if item exists
            $item = Item::find($id);
            if ($item === null) {
                return ResponseHelper::notFound('Item not found.');              
            }

            if (! $item->delete()) {
                return ResponseHelper::error("Item failed to be deleted.", statusCode:500);
            }

            return ResponseHelper::success('Item deleted successfully.'); 

        }  catch (\Exception $e) {
            return ResponseHelper::error("Item failed to be deleted.", statusCode:500);
        }
    }
    
    public function showCost(Request $request, $id)
    {
        // Check if transaction exists
        $item = Item::find($id);
        if ($item === null) {
            return ResponseHelper::notFound('Item not found.');              
        }
        
        $logs = $item->itemLogs;
        if($logs->count() == 0) {
            return ResponseHelper::notFound('Item has no stock yet.');              
        }
        
        $latestLog = $item->itemLogs()->orderBy('logged_at', 'desc')
        ->orderBy('created_at', 'desc')
        ->first();

        return ResponseHelper::success('Current item cost returned successfully.', data:[
            'cost'=> $latestLog->cost_per_item,
        ]); 
        
    }
}
