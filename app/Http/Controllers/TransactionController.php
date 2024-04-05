<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\TransactionRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Models\ItemLog;
use App\Models\Transaction;

use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{    
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    protected function processItemLogs($insertedLog, $transaction, $id=0) {
        if ($insertedLog === null) {
            // Create zero ItemLog if $insertedLog is null, usually happens when removing first transaction of the item
            $prevLog = new ItemLog ();
            $prevLog->id = 0;
            $prevLog->total_stock = 0;
            $prevLog->total_value = 0;
            $prevLog->cost_per_item = 0;
        }else {
            $prevLog = $insertedLog;
        }
        // Obtain those log for transaction after prevLog and transaction at the same time as prevLog and id larger than prevLog id (means inserted after)
        if ($prevLog->id == 0) {
            $futureItemLogs = ItemLog::where('item_id', $transaction->item_id)
            ->where('id', '!=', $id)
            ->orderBy('logged_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
        } else {
            $futureItemLogs = ItemLog::where('item_id', $transaction->item_id)
            ->where(function ($query) use ($transaction, $prevLog) { 
                $query->where('logged_at', '>', $prevLog->logged_at)
                      ->orWhere(function ($query) use ($transaction, $prevLog) {
                          $query->where('logged_at', '=', $transaction->transaction_at)
                                ->where('id', '>', $prevLog->id);
                      });
            })
            ->where('id', '!=', $id)
            ->orderBy('logged_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
        }
        
        $updateLogs = [];
    
        foreach ($futureItemLogs as $futureLog) {
            if ($futureLog->transaction->is_purchase) {
                // Process for purchase
                $futureLog->total_stock = $prevLog->total_stock + $futureLog->transaction->item_count;
                $futureLog->total_value = $prevLog->total_value + $futureLog->transaction->total_item_price;
                $futureLog->cost_per_item = $futureLog->total_value / $futureLog->total_stock;
            } else {
                // Process for sales
                $futureLog->total_stock = $prevLog->total_stock - $futureLog->transaction->item_count;
                $futureLog->total_value = $prevLog->total_value - ($prevLog->cost_per_item * $futureLog->transaction->item_count);
                $futureLog->cost_per_item = $futureLog->total_value / $futureLog->total_stock;
            }
            // If either total_stock or total_value is below zero, it means data not aligned, thus straight return false
            if (($futureLog->total_stock < 0) || ($futureLog->total_value < 0)) {
                return False;
            }
    
            $updateLogs[] = [
                'id' => $futureLog->id,
                'total_stock' => $futureLog->total_stock,
                'total_value' => $futureLog->total_value,
                'cost_per_item' => $futureLog->cost_per_item
            ];
    
            $prevLog = $futureLog;
        }
    
        try {
            // Batch update reduceS the repeated data transfer from backend to DB
            ItemLog::batchUpdate($updateLogs, 'id');
            return True;
        } catch (\Exception $e) {
            return False;
        }
    }
    
    public function index(Request $request)
    {
        $query = Transaction::query();
        
        if ($request->has('is_purchase') && $request->input('is_purchase')) {
            $query->where('is_purchase', 1);
        } else {
            $query->where('is_purchase', 0);
        }

        if($request->has('item')) {
            $item = $request->input('item');
            $itemList = explode(",", $item);
            $query->whereIn('item_id', $itemList);
        }

        $paginator = $query->paginate(10);

        try {
            $transformedTransactions = $paginator->map(function($transaction) {
                $transaction->itemLog;
                return $transaction;
            });
            $responseData = [
                'data' => $transformedTransactions,
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ];
            return ResponseHelper::success('Transactions returned successfully.', data:$responseData);
        } catch (\Exception $e) {
            return ResponseHelper::error('Error in showing transactions.', statusCode:500);
        }
    }

    public function store(TransactionRequest $request)
    {
        try {  
                // INSERT TRANSACTION //
                $transaction = new Transaction();
                $transaction->item_id = $request['item_id'];
                $transaction->is_purchase = $request['is_purchase'];
                $transaction->item_count = $request['item_count'];
                $transaction->total_item_price = $request['total_item_price'];
                $transaction->transaction_at = Carbon::parse($request['transaction_at'], env('TIMEZONE'));               
                            
                // Check if latest item log allow sales to happen
                $nearestLog = ItemLog::where('item_id', $transaction->item_id)
                ->where('logged_at', '<=', $transaction->transaction_at)
                ->orderBy('logged_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
                if (($nearestLog !== null) && ($nearestLog->total_stock < $transaction->item_count) && (!$transaction->is_purchase)) {
                    return ResponseHelper::error('Stock insufficient.');   
                } elseif (($nearestLog === null) && (!$transaction->is_purchase)) { 
                    // If first log wants to be udpated to sales, return error
                    return ResponseHelper::error('Please purchase stock first.');   
                }
                
                if(! $transaction->save()) {
                    return ResponseHelper::error('Transaction fail to be created.');   
                }

                // INSERT ITEMLOG //
                $itemLog = new ItemLog();
                $itemLog->item_id = $transaction->item_id;
                $itemLog->transaction_id = $transaction->id;
                $itemLog->logged_at = $transaction->transaction_at;

                // Generate total_stock, total_value, cost_per_item log using available latest item log
                if ($nearestLog !== null) {
                    if ($transaction->is_purchase) {
                        // Process for purchase
                        $itemLog->total_stock = $nearestLog->total_stock + $transaction->item_count;
                        $itemLog->total_value = $nearestLog->total_value + $transaction->total_item_price;
                        $itemLog->cost_per_item = $itemLog->total_value / $itemLog->total_stock;
                    }else {
                        // Process for sales
                        $itemLog->total_stock = $nearestLog->total_stock - $transaction->item_count;
                        $itemLog->total_value = $nearestLog->total_value - ($nearestLog->cost_per_item * $transaction->item_count);
                        $itemLog->cost_per_item = $nearestLog->cost_per_item;
                    }
                }else {
                    $itemLog->total_stock = $transaction->item_count;
                    $itemLog->total_value = $transaction->total_item_price;
                    $itemLog->cost_per_item = $itemLog->total_value / $itemLog->total_stock;               
                }
                
                if(! $itemLog->save()) {
                    $transaction->delete();
                    return ResponseHelper::error('Transaction fail to be created.');   
                }

                // UPDATE FUTURE ITEMLOG //
                if (! $this->processItemLogs($itemLog, $transaction)) {
                    return ResponseHelper::error("Transaction failed to be created. Invalid value.", statusCode:500);
                }

                return ResponseHelper::success('Transaction created successfully.', data:[
                    'transaction'=> $transaction,
                ]); 
            }  catch (\Exception $e) {
            return ResponseHelper::error("Transaction failed to be created.", statusCode:500);
        }
    }

    public function show(Request $request, $id)
    {
        try {            
            // Check if transaction exists
            $transaction = Transaction::find($id);
            if ($transaction === null) {
                return ResponseHelper::notFound('Transaction not found.');              
            }
            
            $transaction->itemLog;
            return ResponseHelper::success('Transaction returned successfully.', data:[
                'transaction'=> $transaction,
            ]); 

        }  catch (\Exception $e) {
            return ResponseHelper::error("Transaction failed to be shown.", statusCode:500);
        }
    }

    public function update(TransactionUpdateRequest $request, $id)
    {
        /**
        * Updating imte_id and transaction_at requires special treatment, thus not available ATM
        * User should opt to delete then create a new transaction in new transaction_at date to achieve the same result
        **/
        try {  
            // Check if transaction exists
            $transaction = Transaction::find($id);
            if ($transaction === null) {
                return ResponseHelper::notFound('Transaction not found.');              
            }

            // Update transaction first
            $transaction->is_purchase = $request['is_purchase'];
            $transaction->item_count = $request['item_count'];
            $transaction->total_item_price = $request['total_item_price'];
                
            // Find ItemLog, take those before transaction_at, and for those having same transaction_at, only take the smaller id
            $log = $transaction->itemLog;            
            $nearestLog = ItemLog::where('item_id', $transaction->item_id)
            ->where(function ($query) use ($transaction, $log) {
                $query->where('logged_at', '<', $log->logged_at)
                    ->orWhere(function ($query) use ($transaction, $log) {
                        $query->where('logged_at', '=', $log->logged_at)
                                ->where('id', '<', $log->id);
                    });
            })
            ->orderBy('logged_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

            // If first log wants to be udpated to sales, return error
            if (($nearestLog === null) && (!$transaction->is_purchase)) {
                return ResponseHelper::error('Please purchase stock first.');   
            }

            if ($nearestLog !== null) {
                if ($transaction->is_purchase) {
                    $log->total_stock = $nearestLog->total_stock + $transaction->item_count;
                    $log->total_value = $nearestLog->total_value + $transaction->total_item_price;
                    $log->cost_per_item = $log->total_value / $log->total_stock;
                } else {
                    // Process for sales
                    $log->total_stock = $nearestLog->total_stock - $transaction->item_count;
                    $log->total_value = $nearestLog->total_value - ($nearestLog->cost_per_item * $transaction->item_count);
                    $log->cost_per_item = $log->total_value / $log->total_stock;
                }
            } else {
                $log->total_stock = $transaction->item_count;
                $log->total_value = $transaction->total_item_price;
                $log->cost_per_item = $log->total_value / $log->total_stock;     
            }

            // Process for purchase
            if (($log->total_stock < 0) || ($log->total_value < 0)) {
                return ResponseHelper::error("Transaction failed to be updated. Invalid value.", statusCode:500);
            }
            
            // Check if there is any unaligned data in future item log
            if (! $this->processItemLogs($log, $transaction, $log->id)) {
                return ResponseHelper::error("Transaction failed to be updated. Invalid value.", statusCode:500);
            }

            $transaction->save();
            $log->save();


            return ResponseHelper::success('Transaction updated successfully.', data:[
                'transaction'=> $transaction,
            ]); 

            
        }  catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), statusCode:500);
            // return ResponseHelper::error("Transaction failed to be updated.", statusCode:500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {  
            // Check if transaction exists
            $transaction = Transaction::find($id);
            if ($transaction === null) {
                return ResponseHelper::notFound('Transaction not found.');              
            }

            // Find ItemLog, take those before transaction_at, and for those having same transaction_at, only take the smaller id
            $log = $transaction->itemLog;
            $nearestLog = ItemLog::where('item_id', $transaction->item_id)
            ->where(function ($query) use ($transaction, $log) {
                $query->where('logged_at', '<', $log->logged_at)
                      ->orWhere(function ($query) use ($transaction, $log) {
                          $query->where('logged_at', '=', $log->logged_at)
                                ->where('id', '<', $log->id);
                      });
            })
            ->orderBy('logged_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
            
            if (! $this->processItemLogs($nearestLog, $transaction, $log->id)) {
                return ResponseHelper::error("Transaction failed to be deleted. Invalid value.", statusCode:500);
            }

            $transaction->delete();
            $log->delete();


            return ResponseHelper::success('Transaction deleted successfully.'); 
        }  catch (\Exception $e) {
            // return ResponseHelper::error($e->getMessage(), statusCode:500);
            return ResponseHelper::error("Transaction failed to be deleted.", statusCode:500);
        }
    }
}
