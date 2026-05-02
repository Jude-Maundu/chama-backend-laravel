<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\AuditLog;
use App\Models\ComplianceChecklist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SecurityController extends Controller
{
    /**
     * Audit Log Viewer (Feature 30)
     */
    public function getAuditLogs(Chama $chama)
    {
        $logs = AuditLog::where('chama_id', $chama->id) // Assuming AuditLog has chama_id
                       ->orWhere(function($query) use ($chama) {
                           $query->where('table_name', 'chamas')->where('record_id', $chama->id);
                       })
                       ->with('user')
                       ->orderByDesc('created_at')
                       ->paginate(50);
        
        return response()->json(['success' => true, 'data' => $logs]);
    }

    /**
     * Compliance Checklist (Feature 31)
     */
    public function getComplianceChecklist(Chama $chama)
    {
        $checklist = ComplianceChecklist::where('chama_id', $chama->id)->get();
        
        if ($checklist->isEmpty()) {
            // Seed default items
            $this->seedDefaultCompliance($chama);
            $checklist = ComplianceChecklist::where('chama_id', $chama->id)->get();
        }

        return response()->json(['success' => true, 'data' => $checklist]);
    }

    public function updateComplianceItem(Request $request, ComplianceChecklist $item)
    {
        $request->validate(['is_completed' => 'required|boolean']);
        
        $item->update([
            'is_completed' => $request->is_completed,
            'completed_at' => $request->is_completed ? now() : null
        ]);

        return response()->json(['success' => true, 'data' => $item]);
    }

    private function seedDefaultCompliance(Chama $chama)
    {
        $defaults = [
            ['task' => 'Annual General Meeting', 'description' => 'Hold an AGM once a year'],
            ['task' => 'Tax Filing', 'description' => 'File annual returns with relevant tax authority'],
            ['task' => 'Membership Audit', 'description' => 'Verify all member details and documentation'],
            ['task' => 'Financial Audit', 'description' => 'Conduct external or internal financial audit'],
        ];

        foreach ($defaults as $item) {
            ComplianceChecklist::create(array_merge($item, ['chama_id' => $chama->id]));
        }
    }

    /**
     * GDPR Tools (Feature 33)
     */
    public function exportPersonalData()
    {
        $user = Auth::user()->load(['profile', 'contributions', 'loans', 'transactions']);
        $data = json_encode($user, JSON_PRETTY_PRINT);
        
        $filename = 'personal_data_' . $user->id . '.json';
        Storage::disk('local')->put('temp/' . $filename, $data);

        return response()->download(storage_path('app/temp/' . $filename))->deleteFileAfterSend(true);
    }

    public function requestAccountDeletion()
    {
        // Simple flag for now, actual deletion would be handled by admin or background job
        Auth::user()->update(['status' => 'pending_deletion']);
        return response()->json(['success' => true, 'message' => 'Account deletion request submitted']);
    }

    /**
     * Fraud Detection (Feature 34)
     */
    public function getFraudAlerts(Chama $chama)
    {
        // Mock fraud detection logic
        $alerts = [
            [
                'type' => 'duplicate_payment',
                'severity' => 'medium',
                'description' => 'Two identical contributions found for User X within 5 minutes.',
                'detected_at' => now()->subHours(2)
            ],
            [
                'type' => 'unusual_withdrawal',
                'severity' => 'high',
                'description' => 'Large petty cash withdrawal without attached receipt.',
                'detected_at' => now()->subDays(1)
            ]
        ];

        return response()->json(['success' => true, 'data' => $alerts]);
    }
}
