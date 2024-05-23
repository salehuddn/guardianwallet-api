<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MerchantController extends Controller
{
    public function listOfMerchants ()
    {
        $merchants = Merchant::with(['type', 'wallet'])->get();
        $merchants = $merchants->map(function ($merchant) {
            return [
                'id' => $merchant->id,
                'name' => $merchant->name,
                'registration_no' => $merchant->registration_no,
                'type' => $merchant->type->name,
                'wallet' => $merchant->wallet->balance
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'merchants' => $merchants
        ], 200);
    }

    public function showQr (Request $request)
    {
        if (!$request->merchant_id) {
            return response()->json([
                'code' => '400',
                'message' => 'merchant_id is required'
            ], 400);
        }

        $merchant = Merchant::find($request->merchant_id);
        $qrContent = $merchant->id . '-' . $merchant->type->name;
        $qrCode = QrCode::size(300)->generate($qrContent);

        return $qrCode;
    }

    // public function showQr(Request $request)
    // {
    //     if (!$request->merchant_id) {
    //         return response()->json([
    //             'code' => '400',
    //             'message' => 'merchant_id is required'
    //         ], 400);
    //     }

    //     $merchant = Merchant::find($request->merchant_id);

    //     if (!$merchant) {
    //         return response()->json([
    //             'code' => '404',
    //             'message' => 'Merchant not found'
    //         ], 404);
    //     }

    //     $qrContent = $merchant->id . '-' . $merchant->type->name;
    //     $fileName = 'qr_codes/' . $merchant->id . '.png';

    //     if (!Storage::exists($fileName)) {
    //         $qrCode = QrCode::format('png')
    //             ->size(300)
    //             ->generate($qrContent);
    //         Storage::put($fileName, $qrCode);
    //     }

    //     $path = storage_path('app/' . $fileName);

    //     return response()->file($path);
    // }
}
