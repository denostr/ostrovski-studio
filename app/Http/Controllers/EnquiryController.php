<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnquiryRequest;
use App\Mail\EnquiryReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class EnquiryController extends Controller
{
    /**
     * An enquiry from the booking modal (AJAX, JSON). Nothing is stored —
     * the form's only job is to email the enquiry to the site owner.
     *
     * A mailer outage must not look like a success to the visitor, so the
     * failure is logged and reported back as a 500 — the frontend shows
     * its generic "try again" message.
     */
    public function __invoke(EnquiryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $enquiry = [
            'service' => $data['service'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'message' => $data['message'],
        ];

        try {
            Mail::to(config('ostrovski.email'))->send(new EnquiryReceived($enquiry));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }
}
