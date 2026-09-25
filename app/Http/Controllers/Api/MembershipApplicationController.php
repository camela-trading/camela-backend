<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipApplicationRequest;
use App\Mail\MembershipAdminMail;
use App\Mail\MembershipConfirmationMail;
use App\Models\MembershipApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MembershipApplicationController extends Controller
{
    private function getApplicationTypeLabel(string $type): string
    {
        return match ($type) {
            'member' => 'Member',
            'distribution-partner' => 'Distribution Partner',
            'importer' => 'Official Importer',
            default => 'Membership',
        };
    }

    public function store(MembershipApplicationRequest $request)
    {
        $applicationType = $request->string('application_type')->toString();

        $application = MembershipApplication::create([
            'application_type' => $applicationType,
            'full_name' => $request->string('full_name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->string('phone')->toString(),
            'health_goals' => $request->string('health_goals')->toString(),
            'status' => 'Pending',
        ]);

        $mailErrors = [];

        try {
            Log::info('Sending membership confirmation', [
                'email' => $application->email,
                'application_id' => $application->id,
                'application_type' => $applicationType,
            ]);
            Mail::to($application->email)->send(
                new MembershipConfirmationMail($application->full_name, $this->getApplicationTypeLabel($applicationType))
            );
            Log::info('Customer confirmation email sent', [
                'email' => $application->email,
                'application_id' => $application->id,
                'application_type' => $applicationType,
            ]);
        } catch (\Throwable $e) {
            Log::error('Membership confirmation email failed', [
                'email' => $application->email,
                'application_id' => $application->id,
                'application_type' => $applicationType,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $mailErrors[] = $e;
        }

        $adminEmail = config('mail.application_to');
        if ($adminEmail) {
            try {
                Log::info('Sending membership admin notification', [
                    'email' => $adminEmail,
                    'application_id' => $application->id,
                    'application_type' => $applicationType,
                ]);
                Mail::to($adminEmail)->send(new MembershipAdminMail([
                    'id' => $application->id,
                    'application_type' => $applicationType,
                    'application_type_label' => $this->getApplicationTypeLabel($applicationType),
                    'full_name' => $application->full_name,
                    'email' => $application->email,
                    'phone' => $application->phone,
                    'health_goals' => $application->health_goals,
                    'submitted_at' => Carbon::now()->toDateTimeString(),
                ]));
                Log::info('Admin notification email sent', [
                    'email' => $adminEmail,
                    'application_id' => $application->id,
                    'application_type' => $applicationType,
                ]);
            } catch (\Throwable $e) {
                Log::error('Membership admin notification failed', [
                    'email' => $adminEmail,
                    'application_id' => $application->id,
                    'application_type' => $applicationType,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $mailErrors[] = $e;
            }
        }

        if (! empty($mailErrors)) {
            throw $mailErrors[0];
        }

        return response()->json([
            'success' => true,
            'message' => 'Membership application submitted successfully.',
            'data' => [
                'id' => $application->id,
                'status' => $application->status,
            ],
        ]);
    }
}
