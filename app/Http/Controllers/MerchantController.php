<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MerchantController extends Controller
{
    public function showQr ()
    {
        return QrCode::generate(
            'Hello, World!',
        );
    }
}
