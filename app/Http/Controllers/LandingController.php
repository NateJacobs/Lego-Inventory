<?php

namespace App\Http\Controllers;

use App\Services\CollectionSummary;
use Illuminate\Contracts\View\View;

/**
 * The one page a signed-out visitor sees. Everything else lives behind the
 * panel at /collection.
 */
class LandingController extends Controller
{
    public function __invoke(CollectionSummary $summary): View
    {
        return view('landing', ['figures' => $summary->figures()]);
    }
}
