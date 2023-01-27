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
        $hResponse = $this->uploadPaymentHeader();
        if($hResponse->getData()->error == true) {
            return $hResponse;
        }

        $dResponse = $this->uploadPaymentDetails();
        if($dResponse->getData()->error == true) {
            return $dResponse;
        }

        $response = [
            'error' => false,
            'table' => 'Payment Header, Payment Details',
            'message' => 'Payment Inserted Successfully.'
        ];
        return response()->json($response, 200);

        $this->uploadPaymentHeaderOthers();
        $this->uploadPaymentDetailsOthers();
    }

    public function uploadPaymentHeader() {
        $header = DB::table('txn_PaymentHeader')
                    ->where('PostStatus', 'Unposted')
                    ->where('PaymentDate', $this->today)
                    ->limit(200)->get();

        foreach($header as $h) {
            $data = array(
                '_key' => $this->_key,
                'CustomerID' => $h->CustomerID,
                'AccountNo' => $h->AccountNo,
                'NoOfMonthPaid' => $h->NoOfMonthPaid,
                'PaymentDate' => $h->PaymentDate,
                'ORNumber' => $h->ORNumber,
                'Pamana' => $h->Pamana,
                'Due' => $h->Due,
                'DiscountEWT' => $h->DiscountEWT,
                'ToPay' => $h->ToPay,
                'Tendered' => $h->Tendered,
                'Change' => $h->Change,
                'Category' => $h->Category,
                'Bank' => $h->Bank,
                'CheckNumber' => $h->CheckNumber,
                'SystemDate' => $h->SystemDate,
                'eUser' => $h->eUser,
                'WithReceiptNo' => $h->WithReceiptNo,
                'WithReceiptAmount' => $h->WithReceiptAmount,
                'PreviousDate' => $h->PreviousDate,
                'PresentDate' => $h->PresentDate,
                'DueDate' => $h->DueDate,
                'Prev' => $h->Prev,
                'Pres' => $h->Pres,
                'Usage' => $h->Usage,
                'Amount' => $h->Amount,
                'AgeingID' => $h->AgeingID,
                'Status' => $h->Status,
                'BranchID' => $this->branchID,
                'Branch' => $this->branch,
                'PostStatus' => 'Posted',
                'DateTimeUploaded' => $this->dateTime
            );
    
            $ch = curl_init();
    
            $options = array(
                CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/InsertPaymentHeader',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => 1
            );
    
            curl_setopt_array($ch, $options);
    
            $result = curl_exec($ch);

            curl_close($ch);
    
            if($result !== '1') {
                $response = [
                    'result' => $result,
                    'error' => true,
                    'table' => 'Payment Header',
                    'message' => 'Error Inserting Data.',
                    'account' => $h
                ];
                return response()->json($response, 422);
            }

            $okay = DB::table('txn_PaymentHeader')->where('ID', $h->ID)->update(['PostStatus' => 'Posted']);
            if(!$okay) {
                $response = [
                    'result' => $okay,
                    'error' => true,
                    'table' => 'Payment Header',
                    'message' => 'Error Updating Client Data.',
                    'account' => $h
                ];
                return response()->json($response, 422);
            }
        }

        $response = [
            'error' => false,
            'table' => 'Payment Header',
            'message' => 'Payment Header Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function uploadPaymentDetails() {
        $details = DB::table('txn_PaymentDetails')
        ->where('PostStatus', 'Unposted')
        ->where('PaymentDate', $this->today)
        ->limit(200)->get();

        foreach($details as $d) {
            $data = array(
                '_key' => $this->_key,
                'PaymentDate' => $d->PaymentDate,
                'ORNumber' => $d->ORNumber,
                'Pamana' => $d->Pamana,
                'CustomerID' => $d->CustomerID,
                'BillDate' => $d->BillDate,
                'Month' => $d->Month,
                'YearCovered' => $d->YearCovered,
                'Description' => $d->Description,
                'Amount' => $d->Amount,
                'ReferenceNo' => $d->ReferenceNo == '' ? '0' : $d->ReferenceNo,
                'eUser' => $d->eUser,
                'Choose' => $d->Choose,
                'txnCode' => $d->txnCode,
                'Zone' => $d->Zone,
                'Discount' => $d->Discount,
                'EWT2' => $d->EWT2,
                'EWT5' => $d->EWT5,
                'Advance' => 0,
                'AgeingID' => $d->AgeingID,
                'Status' => $d->Status,
                'BranchID' => $this->branchID,
                'Branch' => $this->branch,
                'PostStatus' => 'Posted'
            );
    
            $ch = curl_init();
    
            $options = array(
                CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/InsertPaymentDetails',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => 1
            );
    
            curl_setopt_array($ch, $options);
    
            $result = curl_exec($ch);
    
            curl_close($ch);

            if($result !== '1') {
                $response = [
                    'result' => $result,
                    'error' => true,
                    'table' => 'Payment Details',
                    'message' => 'Error Inserting Data.',
                    'account' => $d
                ];
                return response()->json($response, 422);
            }

            $okay = DB::table('txn_PaymentDetails')->where('id', $d->ID)->update(['PostStatus' => 'Posted']);
            if(!$okay) {
                $response = [
                    'result' => $okay,
                    'error' => true,
                    'table' => 'Payment Details',
                    'message' => 'Error Updating Client Data.',
                    'account' => $d
                ];
                return response()->json($response, 422);
            }
        }

        $response = [
            'error' => false,
            'table' => 'Payment Details',
            'message' => 'Payment Details Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function uploadPaymentHeaderOthers() {
        $headerOther = DB::table('txn_PaymentHeaderOthers')
                    ->where('PostStatus', 'Unposted')
                    ->where('PaymentDate', $this->today)
                    ->limit(100)->get();

        foreach($headerOther as $h) {
            $data = array(
                '_key' => $this->_key,
                'CustomerID' => $h->CustomerID,
                'AccountNo' => $h->AccountNo,
                'AccountName' => $h->AccountName,
                'ORNumber' => $h->ORNumber,
                'Pamana' => $h->Pamana,
                'PaymentDate' => $h->PaymentDate,
                'Printed' => $h->Printed,
                'eUser' => $h->eUser,
                'Status' => $h->Status,
                'ORtype' => $h->ORtype,
                'BranchID' => $this->branchID,
                'Branch' => $this->branch,
                'PostStatus' => 'Posted',
                'DateTimeUploaded' => $this->dateTime
            );

    
            $ch = curl_init();
    
            $options = array(
                CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/InsertPaymentHeader',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => 1
            );
    
            curl_setopt_array($ch, $options);
    
            $result = curl_exec($ch);

            curl_close($ch);
    
            if($result !== '1') {
                $response = [
                    'result' => $result,
                    'error' => true,
                    'table' => 'Payment Header',
                    'message' => 'Error Inserting Data.',
                    'account' => $h
                ];
                return response()->json($response, 422);
            }

            $okay = DB::table('txn_PaymentHeader')->where('ID', $h->ID)->update(['PostStatus' => 'Posted']);
            if(!$okay) {
                $response = [
                    'result' => $okay,
                    'error' => true,
                    'table' => 'Payment Header',
                    'message' => 'Error Updating Client Data.',
                    'account' => $h
                ];
                return response()->json($response, 422);
            }
        }

        $response = [
            'error' => false,
            'table' => 'Payment Header',
            'message' => 'Payment Header Inserted Successfully.'
        ];
        return response()->json($response, 200);
    }

    public function uploadPaymentDetailsOthers() {
        $detailsother = DB::table('txn_PaymentDetailsOthers')->where('PostStatus', '!=', 'PostStatus')->get();

        foreach($detailsother as $do) {

            $data = array(
                '_key' => $this->_key,
                'CustomerID' => $do->CustomerID,
                'ORNumber' => $do->ORNumber,
                'Particular' => $do->Particular,
                'Quantity' => $do->Quantity,
                'UnitAmount' => $do->UnitAmount,
                'Amount' => $do->Amount,
                'EWT' => $do->EWT,
                'BranchID' => $this->branchID,
                'Branch' => $this->branch,
                'PostStatus' => 'Posted'
            );
    
            $ch = curl_init();
    
            $options = array(
                CURLOPT_URL => 'http://190.92.244.187/api/BillingApi/InsertPaymentDetailsOthers',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => 1
            );
    
            curl_setopt_array($ch, $options);
    
            $result = curl_exec($ch);
    
            curl_close($ch);

            DB::table('txn_PaymentDetailsOthers')->where('id', $do->id)->update(['PostStatus' => 'Posted']);
        }
    }
}
