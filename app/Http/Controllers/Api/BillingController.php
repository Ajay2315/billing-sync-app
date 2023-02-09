<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $hResponse = $this->bulkUploadPaymentHeader();
        if($hResponse->getData()->error == true) {
            return $hResponse;
        }

        $dResponse = $this->bulkUploadPaymentDetails();
        if($dResponse->getData()->error == true) {
            return $dResponse;
        }

        return;

        $hoResponse = $this->bulkUploadPaymentHeaderOthers();
        if($hoResponse->getData()->error == true) {
            return $hoResponse;
        }

        $doResponse = $this->bulkUploadPaymentDetailsOthers();
        if($doResponse->getData()->error == true) {
            return $doResponse;
        }

        $response = [
            'error' => false,
            'table' => 'Payment Header, Payment Detail, Payment Header Others, Payment Detail Others',
            'message' => 'Payment Data Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function bulkUploadPaymentHeader() {
        $header = DB::table('txn_PaymentHeader')
                    ->where('PostStatus', 'Unposted')
                    ->limit(2000)
                    ->orderBy('ID', 'Desc')
                    ->get();

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Header',
                'message' => 'All records is up to date.'
            ];
            return response()->json($response, 422);
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
    
        curl_close($ch);

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
              ->where('ID', '=', $row->ID)
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
                    ->limit(2000)
                    ->orderBy('ID', 'Desc')
                    ->get();

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Details',
                'message' => 'All records is up to date.'
            ];
            return response()->json($response, 422);
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
    
        curl_close($ch);

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
              ->where('ID', '=', $row->ID)
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
                    ->limit(2000)
                    ->orderBy('ID', 'Desc')
                    ->get();

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Header Others',
                'message' => 'All records is up to date.'
            ];
            return response()->json($response, 422);
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
    
        curl_close($ch);

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
              ->where('ID', '=', $row->ID)
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
                    ->limit(2000)
                    ->orderBy('ID', 'Desc')
                    ->get();

        if($header->count() == 0) {
            $response = [
                'error' => false,
                'table' => 'Payment Details Others',
                'message' => 'All records is up to date.'
            ];
            return response()->json($response, 422);
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
    
        curl_close($ch);

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
              ->where('ID', '=', $row->ID)
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
}
