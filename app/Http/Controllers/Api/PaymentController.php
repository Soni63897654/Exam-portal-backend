<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PaymentService;
use App\Models\Submission;
use App\Traits\FormatResponseTrait;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect; 

class PaymentController extends Controller
{
	use FormatResponseTrait;

	protected PaymentService $paymentService;

	public function __construct(PaymentService $paymentService)
	{
		$this->paymentService = $paymentService;
	}

	public function initiate(Request $request, $submissionId)
	{
		$submission = Submission::find($submissionId);

		if (!$submission) {
			return $this->errorResponse("Submission with ID {$submissionId} not found.", 404);
		}

		$user = Auth::user();
		if (!$user || $submission->user_id !== $user->id) {
			return $this->errorResponse('Unauthorized access to this submission.', 403);
		}
		if ($submission->payment_status === 'paid') {
			return $this->errorResponse('Payment for this submission has already been completed.', 400);
		}

		try {
			$paymentUrl = $this->paymentService->initiatePayment($submission);

			return $this->successResponse('Payment initiated. Redirecting to gateway.', [
				'payment_url' => $paymentUrl
			]);

		} catch (Exception $e) {
			Log::error("Payment initiation failed for submission {$submission->id}: " . $e->getMessage());
			return $this->errorResponse("Payment initiation failed: " . $e->getMessage(), 500);
		}
	}

	
	public function success(Request $request, $submissionId)
	{
		$submission = Submission::with('payment', 'form')->find($submissionId); 
		if (!$submission) {

			return Redirect::to(config('app.url') . '/payment/error')->with('error', "Submission not found.");
		}
		$sessionId = $request->query('session_id');
		if (!$sessionId) {
			return Redirect::to(config('app.url') . '/payment/error')->with('error', 'Missing session ID from payment gateway.');
		}

		try {
			
			// The service verifies payment, updates DB, generates the PDF receipt URL, and returns the URL.
			$pdfUrl = $this->paymentService->handleSuccess($submission, $sessionId);

			// Correct Action: Return the success view (which includes the receipt button)
			// View path is corrected to 'payments.success'
			return view('payments.success', [ 
				'submission' => $submission,
				'receiptUrl' => $pdfUrl
			]);

		} catch (Exception $e) {
			Log::error("Payment success callback failed for submission {$submission->id} and session {$sessionId}: " . $e->getMessage());
			// Redirect to a user-friendly error page
			return Redirect::to(config('app.url') . '/payment/error')->with('error', 'Payment verification failed. Please try again.');
		}
	}


	public function cancel($submissionId)
	{
		$submission = Submission::find($submissionId);

		if (!$submission) {
			return $this->errorResponse("Submission with ID {$submissionId} not found.", 404);
		}
		
		// Redirect to a specific route/page after cancellation
		return Redirect::to(config('app.url') . '/submission/' . $submissionId)->with('warning', 'Payment canceled by user.');
	}

	public function downloadReceipt($submissionId)
	{
		$submission = Submission::find($submissionId);

		if (!$submission) {
			return $this->errorResponse("Submission with ID {$submissionId} not found.", 404);
		}

		$user = Auth::user();
		
		if (!$user || $submission->user_id !== $user->id) {
			return $this->errorResponse('Unauthorized to download this receipt.', 403);
		}
		$submission->load('payment'); 
		$payment = $submission->payment;
		if (!$payment || !$payment->receipt_path) {
			 return $this->errorResponse('Receipt file not found. Payment might be incomplete.', 404);
		}
		$filePath = str_replace(asset('storage') . '/', '', $payment->receipt_path);
		
		if (!Storage::disk('public')->exists($filePath)) {
			 return $this->errorResponse('Receipt file not found in storage.', 404);
		}

		try {
			return Storage::disk('public')->download($filePath, 'receipt_' . $submission->id . '.pdf');
		} catch (Exception $e) {
			Log::error("Failed to download receipt for submission {$submission->id}: " . $e->getMessage());
			return $this->errorResponse('Failed to download receipt.', 500);
		}
	}
}
