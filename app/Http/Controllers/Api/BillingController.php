<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BillingController extends Controller
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

    public function SyncPayment() {
        //payments
        $response = $this->tryCatch('Payment Header', [$this, 'bulkUploadPaymentHeader']);
        if ($response->getStatusCode() === 422) {
            return $response;
        }
        $response = $this->tryCatch('Payment Details', [$this, 'bulkUploadPaymentDetails']);
        if ($response->getStatusCode() === 422) {
            return $response;
        }
        $response = $this->tryCatch('Payment Header Others', [$this, 'bulkUploadPaymentHeaderOthers']);
        if ($response->getStatusCode() === 422) {
            return $response;
        }
        $response = $this->tryCatch('Payment Details Others', [$this, 'bulkUploadPaymentDetailsOthers']);
        if ($response->getStatusCode() === 422) {
            return $response;
        }
        //Payments Validation
        $response = $this->tryCatch('Payment Header Validation', [$this, 'validatePaymentHeader']);
        if ($response->getStatusCode() === 422) {
            return $response;
        }
        $response = $this->tryCatch('Payment Details Validation', [$this, 'validatePaymentDetails']);
        if ($response->getStatusCode() === 422) {
            return $response;
        }
        $response = $this->tryCatch('Payment Header Others Validation', [$this, 'validatePaymentHeaderOthers']);
        if ($response->getStatusCode() === 422) {
            return $response;
        }
        $response = $this->tryCatch('Payment Details Others Validation', [$this, 'validatePaymentDetailsOthers']);
        if ($response->getStatusCode() === 422) {
            return $response;
        }

        //reading 
        // $response = $this->tryCatch('Reading Header', [$this, 'bulkUploadReadingHeader']);
        // if ($response->getStatusCode() === 422) {
        //     return $response;
        // }
        // $response = $this->tryCatch('Reading Details', [$this, 'bulkUploadReadingHeader']);
        // if ($response->getStatusCode() === 422) {
        //     return $response;
        // }

        $response = [
            'error' => false,
            'table' => 'Payment Header, Payment Detail, Payment Header Others, Payment Detail Others, Reading Header, Reading Details',
            'message' => 'Payment Data Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    private function tryCatch($table, callable $callback) {
        try {
            $response = call_user_func($callback);
        } catch (\Exception $e) {
            $response = [
                'error' => true,
                'table' => $table,
                'message' => 'There was an error inserting bulk data.',
                'catch' => $e
            ];
            return response()->json($response, 422);
        }
        if ($response && $response->getStatusCode() === 422) {
            return $response;
        }
        return response()->json(['error' => false], 200);
    }

    public function bulkUploadPaymentHeader() {
        $header = DB::table('txn_PaymentHeader')
                    ->where('PostStatus', 'Unposted')
                    ->limit(500)
                    ->orderBy('PaymentDate', 'Desc')
                    ->get();

        if ($header === false) {
            $response = [
                'error' => true,
                'table' => 'Payment Header',
                'message' => 'Error inserting data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Header',
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
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/BulkInsertPaymentHeader',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode == 422) {
            return response()->json([
                'error' => true,
                'table' => 'Payment Header',
                'message' => 'Error Inserting Data.'
            ], 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Payment header',
                'message' => 'Error Inserting Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        if(!is_int($result) && is_array($result)) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Payment Header',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }
        
        if($header->count() > $result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Payment Header',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        foreach ($header as $row) {
            DB::table('txn_PaymentHeader')
              ->where('ID', $row->ID)
              ->where('ORNumber', $row->ORNumber)
              ->where('CustomerID', $row->CustomerID)
              ->where('PaymentDate', $row->PaymentDate)
              ->update([
                  'PostStatus' => 'Posted',
            ]);
        }

        $response = [
            'error' => false,
            'table' => 'Payment Header',
            'message' => 'Payment Header Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function bulkUploadPaymentDetails() {
        $header = DB::table('txn_PaymentDetails')
                    ->where('PostStatus', 'Unposted')
                    ->limit(500)
                    ->orderBy('PaymentDate', 'Desc')
                    ->get();

        if ($header === false) {
            $response = [
                'error' => true,
                'table' => 'Payment Details',
                'message' => 'Error inserting data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Details',
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
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/BulkInsertPaymentDetails',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);
    
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode === 422) {
            return response()->json([
                'error' => true,
            ], 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Payment Details',
                'message' => 'Error Inserting Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        if(!$result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Payment Details',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() > $result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Payment Details',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        foreach ($header as $row) {
            DB::table('txn_PaymentDetails')
              ->where('ID', $row->ID)
              ->where('ORNumber', $row->ORNumber)
              ->where('CustomerID', $row->CustomerID)
              ->where('PaymentDate', $row->PaymentDate)
              ->update([
                  'PostStatus' => 'Posted',
              ]);
        }

        $response = [
            'error' => false,
            'table' => 'Payment Details',
            'message' => 'Payment Details Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function bulkUploadPaymentHeaderOthers() {
        $header = DB::table('txn_PaymentHeaderOthers')
                    ->where('PostStatus', 'Unposted')
                    ->limit(500)
                    ->orderBy('PaymentDate', 'Desc')
                    ->get();

        if ($header === false) {
            $response = [
                'error' => true,
                'table' => 'Payment Header Others',
                'message' => 'Error inserting data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Header Others',
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
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/BulkInsertPaymentHeaderOthers',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);
    
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode === 422) {
            return response()->json([
                'error' => true,
            ], 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Payment Header Others',
                'message' => 'Error Inserting Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        if(!$result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Payment Header Others',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() > $result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Payment Header Others',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        foreach ($header as $row) {
            DB::table('txn_PaymentHeaderOthers')
              ->where('ID', $row->ID)
              ->where('ORNumber', $row->ORNumber)
              ->where('CustomerID', $row->CustomerID)
              ->where('PaymentDate', $row->PaymentDate)
              ->update([
                  'PostStatus' => 'Posted',
              ]);
        }

        $response = [
            'error' => false,
            'table' => 'Payment Others',
            'message' => 'Payment Details Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function bulkUploadPaymentDetailsOthers() {
        $header = DB::table('txn_PaymentDetailsOthers')
                    ->where('PostStatus', 'Unposted')
                    ->limit(500)
                    ->orderBy('ID', 'Desc')
                    ->get();

        if ($header === false) {
            $response = [
                'error' => true,
                'table' => 'Payment Details Others',
                'message' => 'Error inserting data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Details Others',
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
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/BulkInsertPaymentDetailsOthers',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);
    
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode === 422) {
            return response()->json([
                'error' => true,
            ], 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Payment Details Others',
                'message' => 'Error Inserting Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        if(!$result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Payment Details Others',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        if($header->count() > $result) {
            $response = [
                'result' => $result,
                'error' => true,
                'table' => 'Payment Details Others',
                'message' => 'Error Inserting Data.'
            ];
            return response()->json($response, 422);
        }

        foreach ($header as $row) {
            DB::table('txn_PaymentDetailsOthers')
              ->where('ID', $row->ID)
              ->where('ORNumber', $row->ORNumber)
              ->where('CustomerID', $row->CustomerID)
              ->where('Particular', $row->Particular)
              ->update([
                  'PostStatus' => 'Posted',
              ]);
        }

        $response = [
            'error' => false,
            'table' => 'Payment Details Others',
            'message' => 'Payment Details Others Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function validatePaymentHeader() {
        $header = DB::table('txn_PaymentHeader')
                ->selectRaw('COALESCE(SUM(ToPay), 0) AS total_amount, COUNT(*) as total_count')
                ->where('PostStatus', '=', 'Posted')
                ->where('PaymentDate', '=', $this->today) 
                ->first();

        if($header->total_count == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Header',
                'message' => 'No records found.'
            ];
            return response()->json($response, 200);
        }

        $data = [
            'TotalCount' => $header->total_count,
            'TotalAmount' => $header->total_amount,
            'Date' => $this->today, 
            'BranchID' => $this->branchID,
            'Branch' => $this->branch
        ];

        $ch = curl_init();
    
        $options = array(
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/validatePaymentHeader',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($statusCode === 422) {
            DB::table('txn_PaymentHeader')
              ->where('PaymentDate', $this->today)
              ->update([
                'PostStatus' => 'Unposted'
            ]);

            $response = [
                'error' => true,
                'table' => 'Payment Header',
                'message' => 'Records will be reconstructed on the next sync.'
            ];
            return response()->json($response, 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Payment Header',
                'message' => 'Error Inserting Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        $response = [
            'error' => false,
            'table' => 'Payment Header',
            'message' => 'Records is up to date.'
        ];
        return response()->json($response, 200);
    }

    public function validatePaymentDetails() {
        $header = DB::table('txn_PaymentDetails')
                ->selectRaw('SUM(Amount) as total_amount, COUNT(*) as total_count')
                ->where('PostStatus', '=', 'Posted')
                ->where('PaymentDate', '=', $this->today)
                ->first();

        if($header->total_count == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Details',
                'message' => 'No records found.'
            ];
            return response()->json($response, 200);
        }

        $data = [
            'TotalCount' => $header->total_count,
            'TotalAmount' => $header->total_amount,
            'Date' => $this->today,
            'BranchID' => $this->branchID,
            'Branch' => $this->branch
        ];

        $ch = curl_init();
    
        $options = array(
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/validatePaymentDetails',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($statusCode === 422) {
            DB::table('txn_PaymentDetails')
              ->where('PaymentDate', $this->today)
              ->update([
                'PostStatus' => 'Unposted'
            ]);

            $response = [
                'error' => true,
                'table' => 'Payment Details',
                'message' => 'Records will be reconstructed on the next sync.'
            ];
            return response()->json($response, 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Payment Details',
                'message' => 'Error Inserting Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        $response = [
            'error' => false,
            'table' => 'Payment Details',
            'message' => 'Records is up to date.'
        ];
        return response()->json($response, 200);
    }

    public function validatePaymentHeaderOthers() {
        $header = DB::table('txn_PaymentHeaderOthers')
                ->selectRaw('COUNT(*) as total_count')
                ->where('PostStatus', '=', 'Posted')
                ->where('PaymentDate', '=', $this->today)
                ->first();

        if($header->total_count == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Header Others',
                'message' => 'No records found.'
            ];
            return response()->json($response, 200);
        }

        $data = [
            'TotalCount' => $header->total_count,
            'Date' => $this->today,
            'BranchID' => $this->branchID,
            'Branch' => $this->branch
        ];

        $ch = curl_init();
    
        $options = array(
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/validatePaymentHeaderOthers',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($statusCode === 422) {
            DB::table('txn_PaymentHeaderOthers')
              ->where('PaymentDate', $this->today)
              ->update([
                'PostStatus' => 'Unposted'
            ]);

            $response = [
                'error' => true,
                'table' => 'Payment Header Others',
                'message' => 'Records will be reconstructed on the next sync.'
            ];
            return response()->json($response, 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Payment Header Others',
                'message' => 'Error Inserting Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        $response = [
            'error' => false,
            'table' => 'Payment Header Others',
            'message' => 'Records is up to date.'
        ];
        return response()->json($response, 200);
    }

    public function validatePaymentDetailsOthers() {
        $header = DB::table('txn_PaymentDetailsOthers')
                ->selectRaw('COALESCE(SUM(Amount), 0) as total_amount, COUNT(*) as total_count')
                ->where('PostStatus', '=', 'Posted')
                ->first();

        if($header->total_count == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Details Others',
                'message' => 'No records found.'
            ];
            return response()->json($response, 200);
        }

        $data = [
            'TotalCount' => $header->total_count,
            'TotalAmount' => $header->total_amount,
            'BranchID' => $this->branchID,
            'Branch' => $this->branch
        ];

        $ch = curl_init();
    
        $options = array(
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/validatePaymentDetailsOthers',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1
        );
    
        curl_setopt_array($ch, $options);
    
        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if($statusCode === 422) {
            DB::table('txn_PaymentDetailsOthers')
              ->update([
                'PostStatus' => 'Unposted'
            ]);

            $response = [
                'error' => true,
                'table' => 'Payment Details Others',
                'message' => 'Records will be reconstructed on the next sync.'
            ];
            return response()->json($response, 422);
        }

        if(curl_errno($ch)) {
            $response = [
                'error' => true,
                'table' => 'Payment Details Others',
                'message' => 'Error Inserting Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }

        curl_close($ch);

        $response = [
            'error' => false,
            'table' => 'Payment Details Others',
            'message' => 'Records is up to date.'
        ];
        return response()->json($response, 200);
    }

    public function rebuildPaymentsWater(Request $request) {
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
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/rebuildWaterPayments',
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
                'table' => 'Rebuild Water Payments',
                'message' => 'Error Rebuilding Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        DB::table('txn_PaymentHeader')
            ->whereBetween('PaymentDate', [$input['dateFrom'], $input['dateTo']])
            ->update([
                'PostStatus' => 'Unposted'
            ]);
        DB::table('txn_PaymentDetails')
            ->whereBetween('PaymentDate', [$input['dateFrom'], $input['dateTo']])
            ->update([
                'PostStatus' => 'Unposted'
            ]);

        $response = [
            'error' => false,
            'message' => 'Record successfully rebuild.'
        ];
        return response()->json($response, 200);
    }

    public function rebuildPaymentsOthers(Request $request) {
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
            CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/rebuildOthersPayments',
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
                'table' => 'Rebuild Payment Others',
                'message' => 'Error Rebuilding Data - Please check the back-end code.'
            ];
            return response()->json($response, 422);
        }
    
        curl_close($ch);

        DB::table('txn_PaymentHeaderOthers')
            ->whereBetween('PaymentDate', [$input['dateFrom'], $input['dateTo']])
            ->update([
                'PostStatus' => 'Unposted'
            ]);
        DB::table('txn_PaymentDetailsOthers')
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