<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Http\Request;
use App\Traits\FormatResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;

class SubmissionController extends Controller
{
    use FormatResponseTrait;
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }
            if ($user->role_id === 1) {
                $submissions = Submission::with('user', 'form', 'payment')->orderBy('created_at', 'desc')->get();
            } else {
                $submissions = Submission::with('form', 'payment')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            return $this->successResponse('Submissions retrieved successfully.', $submissions);

        } catch (\Exception $e) {
            Log::error("Submission Index Error: " . $e->getMessage());
            return $this->errorResponse('An error occurred while retrieving submissions.', 500);
        }
    }

    public function store(Request $request) 
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }
        $request->validate([
            'form_id' => 'required|integer|exists:forms,id', 
            'form_data' => 'required|array',
        ]);
        $form = Form::find($request->form_id);
        if (!$form || $form->status !== 'published') {
            return $this->errorResponse('This form is currently not available for submission.', 403);
        }
        if (Submission::where('form_id', $form->id)->where('user_id', $user->id)->exists()) {
            return $this->errorResponse('You have already submitted this form.', 409);
        }

        try {
            $submission = Submission::create([
                'user_id' => $user->id,
                'form_id' => $form->id,
                'form_data' => $request->form_data, 
                'status' => 'pending', 
            ]);
            $nextStepUrl = url("/api/payments/initiate/{$submission->id}");
            return $this->successResponse(
                'Form data saved. Proceed to payment.',
                [
                    'submission_id' => $submission->id,
                    'form_title' => $form->title,
                    'fee_amount' => $form->fee_amount,
                    'next_step_url' => $nextStepUrl 
                ],
                201 
            );
        } catch (\Exception $e) {
            Log::error("Submission creation failed: " . $e->getMessage(), ['exception' => $e]);
            return $this->errorResponse('An error occurred during form submission. Please try again.', 500);
        }
    }


    public function show(Request $request, Submission $submission)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }
        try {
            $isAuthorized = ($user->role_id === 1 || $submission->user_id === $user->id);

            if (!$isAuthorized) {
                return $this->errorResponse('You are not authorized to view this submission.', 403);
            }
            $submission->load('user', 'form', 'payment');
            
            return $this->successResponse('Submission details retrieved successfully.', $submission);

        } catch (\Exception $e) {
            Log::error("Submission Show Error: " . $e->getMessage());
            return $this->errorResponse('Something went wrong.', 403);
        }
    }
  
    public function updateStatus(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }
        if ($user->role_id !== 1) {
            return $this->errorResponse('Unauthorized action.', 403);
        }
        $request->validate([
            'submission_id' => 'required|integer|exists:form_submissions,id',
            'status'        => 'required|in:pending,approved,rejected,complete',
            'admin_notes'   => 'nullable|string'
        ]);
        try {
            $submission = Submission::findOrFail($request->submission_id);
            $submission->update([
                'status'        => $request->status,
                'admin_notes'   => $request->admin_notes,
                'processed_by'  => $user->id,
            ]);
            return $this->successResponse('Submission status updated successfully.', $submission);

        } catch (\Exception $e) {
            Log::error("Submission Status Update Error: " . $e->getMessage());
            return $this->errorResponse('Failed to update submission status.', 403);
        }
    }

   
    public function destroy(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }
        if ($user->role_id !== 1) {
            return $this->errorResponse('Unauthorized action.', 403);
        }
        $request->validate([
            'submission_id' => 'required|integer|exists:form_submissions,id',
            'user_id'       => 'required|integer|exists:users,id',
        ]);
        try {
            $submission = Submission::where('id', $request->submission_id)
                        ->where('user_id', $request->user_id)->first();
            if (!$submission) {
                return $this->errorResponse('Submission not found for this user.', 404);
            }
            $paymentDeleted = Payment::where('submission_id', $submission->id)->delete();
            $submission->delete();

            return $this->successResponse('Submission and related payment deleted successfully.', [
                'submission_deleted' => true,
                'payment_deleted' => $paymentDeleted > 0
            ], 200);

        } catch (\Exception $e) {
            Log::error("Submission Delete Error: " . $e->getMessage());
            return $this->errorResponse('Something went wrong during deletion.', 403);
        }
    }

    public function upload(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $request->validate([
            'file'    => 'required|file|mimes:pdf,jpg,png',
            'form_id' => 'required|exists:forms,id',
        ]);
        $file = $request->file('file');
        $path = $file->store('uploads/users', 'public');
        $submission = Submission::firstOrNew([
            'user_id' => $user->id,
            'form_id' => $request->form_id,
        ]);
        if (!empty($submission->document_path) && \Storage::disk('public')->exists($submission->document_path)) {
            \Storage::disk('public')->delete($submission->document_path);
        }
        $submission->document_path = $path;
        $submission->save();
        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'submission' => $submission,
        ]);
    }



}
