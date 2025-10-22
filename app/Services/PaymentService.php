<?php
namespace App\Services;
use App\Models\Submission;
use App\Models\Payment;
use App\Models\UserMeta; 
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer; 
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User; 
use Stripe\Exception\ApiErrorException; 

class PaymentService
{
    protected string $currency = 'INR';

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->currency = config('app.currency', 'INR'); 
    }

    
    public function generatePdfReceipt(Submission $submission): string
    {
        $submission->load(['payment', 'user', 'form']);
        $pdf = Pdf::loadView('pdf.receipt', ['submission' => $submission]);
        $fileName = 'receipts/receipt_' . $submission->id . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());
        return asset('storage/' . $fileName); 
    }

   
    public function getOrCreateStripeCustomer(User $user): string
    {
        $meta = UserMeta::where('user_id', $user->id)
                             ->where('meta_key', 'stripe_customer_id')
                             ->first();
        if ($meta) {
            return $meta->meta_value;
        }
        
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name'  => $user->name ?? $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);
            
            UserMeta::create([
                'user_id' => $user->id,
                'meta_key' => 'stripe_customer_id',
                'meta_value' => $customer->id,
            ]);

            Log::info("New Stripe Customer created by PaymentService for user ID: {$user->id}");
            return $customer->id;

        } catch (ApiErrorException $e) {
            Log::error('Stripe Customer Creation failed during payment initiation for user ' . $user->id . ': ' . $e->getMessage());
            throw new Exception("Could not create Stripe customer for payment: " . $e->getMessage());
        }
    }

    public function initiatePayment(Submission $submission): string
    {
        try {
            $user = $submission->user;
            $stripeCustomerId = $this->getOrCreateStripeCustomer($user);
            $unitAmount = (int)($submission->form->fee_amount * 100);
            $orderId    = $submission->id;
            $payment = Payment::where('submission_id', $submission->id)
                             ->whereIn('status', ['pending', 'initiated'])
                             ->first();
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'customer' => $stripeCustomerId, 
                'line_items' => [[
                    'price_data' => [
                        'currency'     => $this->currency,
                        'product_data' => ['name' => $submission->form->title],
                        'unit_amount'  => $unitAmount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'billing_address_collection' => 'required',
                'success_url' => config('app.url') . "/api/payments/success/{$submission->id}?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url'   => config('app.url') . "/api/payments/cancel/{$submission->id}",
                'metadata'     => [
                    'submission_id' => $submission->id,
                    'user_id'       => $submission->user_id,
                    'order_id'      => $orderId, 
                ],
            ]);
            if ($payment) {
                $payment->update([
                    'provider_payment_id' => $session->id,
                    'status'              => 'pending',
                ]);
            } else {
                Payment::create([
                    'submission_id'       => $submission->id,
                    'user_id'             => $submission->user_id,
                    'amount'              => $submission->form->fee_amount,
                    'currency'            => $this->currency,
                    'order_id'            => $orderId,           
                    'provider_payment_id' => $session->id,          
                    'status'              => 'pending',
                ]);
            }
            $submission->update([
                'payment_status' => 'initiated', 
            ]);

            return $session->url; 

        } catch (Exception $e) {
            Log::error('Stripe payment initiation failed: ' . $e->getMessage());
            throw new Exception("Stripe payment initiation failed: " . $e->getMessage());
        }
    }

    
    public function handleSuccess(Submission $submission, string $sessionId): string
    {
        try {
            $session = StripeSession::retrieve($sessionId);
            if ($session->payment_status !== 'paid' || !$session->payment_intent) {
                throw new Exception("Payment not completed or payment intent missing.");
            }
            $paidPayment = Payment::where('submission_id', $submission->id)
                ->where('provider_payment_id', $sessionId)
                ->where('status', 'paid')
                ->first();
            
            if ($paidPayment) {
                Log::info("Payment for submission {$submission->id} and session {$sessionId} already paid. Returning existing receipt.");
                return $paidPayment->receipt_path;
            }
            
            $payment = Payment::where('submission_id', $submission->id)
                ->where('provider_payment_id', $sessionId)
                ->whereIn('status', ['pending', 'initiated'])
                ->first();

            if (!$payment) {
                Log::warning("Payment completion without matching pending record found for submission {$submission->id} and session {$sessionId}.");
                throw new Exception("No matching pending payment found for this submission.");
            }
            $payment->update([
                'status'           => 'paid', 
                'transaction_id'   => $session->payment_intent, 
                'payment_method'   => $session->payment_method_types[0] ?? 'card',
                'gateway_response' => json_encode($session->toArray()),
            ]);
            $submission->update([
                'payment_status'   => 'success', 
                'payment_id'       => $payment->id, 
                'transaction_id'   => $session->payment_intent,
            ]);
            $pdfUrl = $this->generatePdfReceipt($submission);
            $payment->update([
                'receipt_path'     => $pdfUrl,
            ]);
            return $pdfUrl;

        } catch (Exception $e) {
            Log::error('Payment verification failed for session ' . $sessionId . ': ' . $e->getMessage());
            throw new Exception("Payment verification failed: " . $e->getMessage());
        }
    }
}
