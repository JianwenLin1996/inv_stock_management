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

    
    /**
        * @OA\GET(
        * path="/api/items",
        * summary="Get item list",
        * description="Get item list",
        * operationId="itemIndex",
        * tags={"item"},
        * security={ {"bearerAuth": {} } },
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Transactions returned successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
    public function index(Request $request)
    {
        $items = Item::get();
        return ResponseHelper::success('Item returned successfully.', data:[
            'items'=> $items,
        ]); 
    }

    
    /**
        * @OA\Post(
        * path="/api/items",
        * summary="Create new item",
        * description="Create new item by providing name, description",
        * operationId="itemCreate",
        * tags={"item"},
        * security={ {"bearerAuth": {} } },
        * @OA\RequestBody(
        *    required=true,
        *    description="Pass item information",
        *    @OA\JsonContent(
        *       type="object",
        *       required={"name","description"},
        *       @OA\Property(property="name", type="string", example="Water Bottle"),
        *       @OA\Property(property="description", type="string", example="BPA free water bottle")
        *    ),
        * ),
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Item created successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=422,
        *    description="Invalid item value",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Validation failed."),
        *       @OA\Property(property="status", type="boolean", example="false")
        *        )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
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

    
    /**
        * @OA\GET(
        * path="/api/items/{id}",
        * summary="Get specific item",
        * description="Get item by providing item id.",
        * operationId="itemGet",
        * tags={"item"},
        * security={ {"bearerAuth": {} } },
        *   @OA\Parameter(
        *     name="id",
        *     in="path",
        *     @OA\Schema(
        *      type="string",
        *     ),
        *     required=true,
        *     description="Item id",
        *   ),
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Item returned successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
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
    
    
    /**
        * @OA\Post(
        * path="/api/items/{id}",
        * summary="Update item",
        * description="Update item by providing name and description.",
        * operationId="itemUpdate",
        * tags={"item"},
        * security={ {"bearerAuth": {} } },
        *   @OA\Parameter(
        *     name="id",
        *     in="path",
        *     @OA\Schema(
        *      type="string",
        *     ),
        *     required=true,
        *     description="Item id",
        *   ),
        * @OA\RequestBody(
        *    required=true,
        *    description="Pass item information",
        *    @OA\JsonContent(
        *       type="object",
        *       required={"name","description"},
        *       @OA\Property(property="name", type="string", example="Water bottle"),
        *       @OA\Property(property="description", type="string", example="Premium water bottle. 500ml.")
        *    ),
        * ),
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Item updated successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=422,
        *    description="Invalid item value",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Validation failed."),
        *       @OA\Property(property="status", type="boolean", example="false")
        *        )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
    public function update(ItemRequest $request,  $id)
    {
        try {            
            // Check if item exists
            $item = Item::find($id);
            if ($item === null) {
                return ResponseHelper::notFound('Item not found.');              
            }
            
            $item->name = $request['name'];
            if ($request->has('description')) {
                $item->description = $request['description'];
            }
            
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

    
    /**
        * @OA\DELETE(
        * path="/api/items/{id}",
        * summary="Delete specific item",
        * description="Delete item by providing item id.",
        * operationId="itemDelete",
        * tags={"item"},
        * security={ {"bearerAuth": {} } },
        *   @OA\Parameter(
        *     name="id",
        *     in="path",
        *     @OA\Schema(
        *      type="string",
        *     ),
        *     required=true,
        *     description="Item id",
        *   ),
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Item deleted successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
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
    
    
    /**
        * @OA\GET(
        * path="/api/items/{id}/cost",
        * summary="Get cost",
        * description="Get cost for specific item.",
        * operationId="itemShowCost",
        * tags={"item"},
        * security={ {"bearerAuth": {} } },
        *   @OA\Parameter(
        *     name="id",
        *     in="path",
        *     @OA\Schema(
        *      type="string",
        *     ),
        *     required=true,
        *     description="Item id",
        *   ),
        * @OA\Response(
        *    response=200,
        *    description="Success",
        *     @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Current item cost returned successfully."),
        *       @OA\Property(property="status", type="boolean", example="true"),
        *       @OA\Property(property="data", type="object", example={}),
        *     )
        * ),
        * @OA\Response(
        *    response=401,
        *    description="Unauthorized",
        *    @OA\JsonContent(
        *       @OA\Property(property="message", type="string", example="Unauthorized.")
        *        )
        *     )
        * )
    */
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
