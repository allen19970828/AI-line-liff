<?php

namespace App\Http\Controllers\Liff;

use App\Http\Controllers\Controller;

class SurveyController extends Controller
{
    public function show($id)
    {
        // Simple View Controller: Just pass ID and let frontend fetch details
        // Or fetch here for SEO/SSR? For LIFF, CSR is fine.
        return view('liff.survey', [
            'liffId' => config('services.line.liff_id'),
            'surveyId' => $id,
        ]);
    }
}
