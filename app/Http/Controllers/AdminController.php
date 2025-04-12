<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Services\AuditService;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Notifications\UserApprovalNotification;

class AdminController extends Controller {
    /**
     * Get all users pending approval
     */
    public function getPendingUsers(Request $request)
    {
        // Validate admin access
        $user = Auth::user();
        if ($user->role !== 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Get users with pending status
        $pendingUsers = User::where('status', 'pending')->get();
        return response()->json(['users' => $pendingUsers], 200);
    }

    
    
   /**
     * Approve user
     */
    public function approveUser(Request $request, $id) {
        // Validate admin access
        $user = Auth::user();
        if ($user->role !== 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Find and validate user
        $userToApprove = User::findOrFail($id);
        
        // Check if user is already approved
        if ($userToApprove->status === 'approved') {
            return response()->json(['message' => 'User is already approved'], 200);
        }
        
        // Store old status for audit log
        $oldStatus = $userToApprove->status;
        
        // Update user status
        $userToApprove->status = 'approved';
        $userToApprove->save();
        
        // Log the action
        AuditService::logUserStatusChange($id, $oldStatus, 'approved');
        
        // Send notification via Laravel's notification system
        $userToApprove->notify(new UserApprovalNotification('approved'));
        
        // Send Firebase notification manually if needed
        if ($userToApprove->firebase_token) {
            $firebaseService = app(FirebaseService::class);
            $firebaseService->sendNotification(
                $userToApprove->firebase_token,
                'Account Approved',
                'Your account has been approved! You can now access all features.',
                ['status' => 'approved']
            );
        }
        
        return response()->json(['message' => 'User approved successfully'], 200);
    }
    /**
     * Reject user
     */
    public function rejectUser(Request $request, $id) {
        // Validate admin access and request
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        if ($user->role !== 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Find and validate user
        $userToReject = User::findOrFail($id);
        
        // Store old status for audit log
        $oldStatus = $userToReject->status;
        
        // Update user status
        $userToReject->status = 'rejected';
        $userToReject->save();
        
        // Log the action with reason if provided
        $oldValues = ['status' => $oldStatus];
        $newValues = [
            'status' => 'rejected',
            'reason' => $request->input('reason', 'No reason provided')
        ];
        
        AuditService::log('user_rejected', 'User', $id, $oldValues, $newValues);
        
        // Send notification
        $userToReject->notify(new UserApprovalNotification('rejected'));
        
        return response()->json(['message' => 'User rejected successfully'], 200);
    }

    /**
     * Request additional verification
     */
    public function requestVerification(Request $request, $id) {
        // Validate admin access and request
        $validator = Validator::make($request->all(), [
            'verification_type' => 'required|string|in:id,address,business,other',
            'message' => 'required|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        if ($user->role !== 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Find and validate user
        $userToVerify = User::findOrFail($id);
        
        // Store old status for audit log
        $oldStatus = $userToVerify->status;
        
        // Update user status
        $userToVerify->status = 'verification_requested';
        $userToVerify->save();
        
        // Log the action with verification details
        $oldValues = ['status' => $oldStatus];
        $newValues = [
            'status' => 'verification_requested',
            'verification_type' => $request->verification_type,
            'message' => $request->message
        ];
        
        AuditService::log('verification_requested', 'User', $id, $oldValues, $newValues);
        
        // Send notification
        $userToVerify->notify(new UserApprovalNotification('verification_requested'));
        
        return response()->json([
            'message' => 'Verification requested from user',
            'verification_type' => $request->verification_type
        ], 200);
    }
    
    /**
     * Get audit logs for user approval actions
     */
    public function getAuditLogs(Request $request)
    {
        // Validate admin access
        $user = Auth::user();
        if ($user->role !== 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Get filters from request
        $userId = $request->input('user_id');
        $action = $request->input('action');
        $from = $request->input('from');
        $to = $request->input('to');
        
        // Build query
        $query = \App\Models\AuditLog::query();
        
        if ($userId) {
            $query->where('target_id', $userId);
        }
        
        if ($action) {
            $query->where('action', $action);
        }
        
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        
        // Get results with pagination
        $logs = $query->with(['user:id,firstname,lastname,email', 'target:id,firstname,lastname,email'])
                     ->orderBy('created_at', 'desc')
                     ->paginate(15);
        
        return response()->json($logs, 200);
    }
}