<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReadingController extends Controller
{
    protected $_key, $branchID, $branch, $dateTime, $today;

    public function __construct()
    {
        $this->_key = env('VITE_API_KEY');
        $this->branchID = env('VITE_APP_BrachID');
        $this->branch = env('VITE_APP_Branch');
        $this->dateTime = now();
        $this->today = now()->format('Y-m-d');
    }

    public function bulkUploadReadingHeader() {
        $header = DB::table('txn_ReadingHeader')
                    ->where('PostStatus', '<>', 'Posted')
                    ->orWhereNull('PostStatus')
                    ->limit(500)
                    ->orderBy('BillingDate', 'Desc')
                    ->get();

        if ($header === false) {
            $response = [
                'error' => true,
                'table' => 'Reading Header',
                'message' => 'Error inserting data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Reading Header',
                'message' => 'All records is up to date.'
            ];
            return response()->json($response, 200);
        }

        $data = [
            'data' => json_encode($header),
            'TotalCount' => $header->count(),
            'firstID' => $header->first()->ID,
            'lastID' => $header->last()->ID,
            'BranchID' => $this->branchID,
            'Branch' => $this->branch,
            'dataTime' => $this->dateTime
        ];

        $ch = curl_init();
    
        $options = array(
            CURLOPT_URL => 'http://190.92.244.187/api/ReadingApi/BulkInsertReadingHeader',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode < 200 || $statusCode >= 300) {
            return response()->json([
                'error' => true,
                'table' => 'Reading Header',
                'message' => 'Error Inserting Data.',
                'result' => $result
            ], 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Reading header',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        if(!is_int($result) && is_array($result)) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Reading header',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }
        
        if($header->count() > $result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Reading header',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        foreach ($header as $row) {
            DB::table('txn_ReadingHeader')
              ->where('ID', $row->ID)
              ->where('UserCode', $row->UserCode)
              ->where('Period', $row->Period)
              ->where('ReadingDate', $row->ReadingDate)
              ->update([
                  'PostStatus' => 'Posted',
            ]);
        }

        $response = [
            'error' => false,
            'table' => 'Reading header',
            'message' => 'Reading header Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function bulkUploadReadingDetails() {
        $header = DB::table('txn_ReadingDetails')
                    ->where('PostStatus', '<>', 'Posted')
                    ->orWhereNull('PostStatus')
                    ->limit(500)
                    ->orderBy('Billdate', 'Desc')
                    ->get();

        if ($header === false) {
            $response = [
                'error' => true,
                'table' => 'Reading Details',
                'message' => 'Error Getting data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Reading Details',
                'message' => 'All records is up to date.'
            ];
            return response()->json($response, 200);
        }

        $data = [
            'data' => json_encode($header),
            'TotalCount' => $header->count(),
            'firstID' => $header->first()->ID,
            'lastID' => $header->last()->ID,
            'BranchID' => $this->branchID,
            'Branch' => $this->branch,
            'dataTime' => $this->dateTime
        ];

        $ch = curl_init();
    
        $options = array(
            CURLOPT_URL => 'http://190.92.244.187/api/ReadingApi/BulkInsertReadingDetails',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode < 200 || $statusCode >= 300) {
            return response()->json([
                'error' => true,
                'table' => 'Reading Details',
                'message' => 'Error Inserting Data.',
                'result' => $result
            ], 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Reading Details',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        if(!is_int($result) && is_array($result)) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Reading Details',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }
        
        if($header->count() > $result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Reading Details',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        foreach ($header as $row) {
            DB::table('txn_ReadingDetails')
              ->where('ID', $row->ID)
              ->where('Period', $row->Period)
              ->where('CustomerID', $row->CustomerID)
              ->where('Zone', $row->Zone)
              ->update([
                  'PostStatus' => 'Posted',
            ]);
        }

        $response = [
            'error' => false,
            'table' => 'Reading Details',
            'message' => 'Reading Details Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function rebuildReading(Request $request) {
        $input = $request->only(
            'masterkey', 'dateFrom', 'dateTo'
        );

        $validator = Validator::make($input, [
            'masterkey' => 'required',
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = [
            'dateFrom' => $input['dateFrom'],
            'dateTo' => $input['dateTo'],
            'masterkey' => $input['masterkey'],
            'BranchID' => $this->branchID,
            'Branch' => $this->branch
        ];

        $ch = curl_init();
    
        $options = array(
            CURLOPT_URL => 'http://190.92.244.187/api/ReadingApi/rebuildReading',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($statusCode === 422) {
            $data = json_decode($result);
            if($data->type == 'InvalidKey') {
                $response = [
                    "masterkey" => [$data->message]
                ];
            }else{
                $response = [
                    "date" => [$data->message]
                ];
            }
            return response()->json($response, 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Rebuild Reading',
                'message' => 'Error Rebuilding Data'
            ];
            return response()->json($response, 422);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return response()->json([
                'error' => true,
                'table' => 'Rebuild Reading',
                'message' => 'Error Rebuilding Data',
                'result' => $result
            ], 422);
        }
    
        curl_close($ch);

        DB::table('txn_ReadingHeader')
            ->whereBetween('ReadingDate', [$input['dateFrom'], $input['dateTo']])
            ->update([
                'PostStatus' => 'Unposted'
            ]);
        DB::table('txn_ReadingDetails')
            ->whereBetween('ReadingDate', [$input['dateFrom'], $input['dateTo']])
            ->update([
                'PostStatus' => 'Unposted'
            ]);

        $response = [
            'error' => false,
            'message' => 'Record successfully rebuild.'
        ];
        return response()->json($response, 200);
    }
}
