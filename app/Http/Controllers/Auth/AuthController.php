<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Traits\FormatResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentService;
use App\Models\User;
use App\Models\Role;
use Stripe\Stripe;
use Stripe\Customer;

class AuthController extends Controller
{
    use FormatResponseTrait;
    protected $paymentService;
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $user = User::create([
                'name'       => $request->name,
                'email'      => $request->email,
                'role_id'    => 2,
                'password'   => Hash::make($request->password),
            ]);
            $token = JWTAuth::fromUser($user);
            return $this->successResponse('User registered successfully', [
                'user'  => $user,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return $this->errorResponse('Registration failed', 403);
        }
    }

  
     public function login(Request $request)
    {
        $data = $request->only(['email', 'password']);
        
        $validator = Validator::make($data, [ 
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            if (!$token = JWTAuth::attempt($data)) {
                return $this->errorResponse('Invalid credentials', 403);
            }
            $user = Auth::user();
            try {
                $this->paymentService->getOrCreateStripeCustomer($user);
                Log::info("Stripe Customer sync successful for user during login.");
            } catch (Exception $e) {
                Log::warning('Stripe Customer sync failed during login: ' . $e->getMessage());
            }
            $user->token = $token;
            
            if ($user) {
                $user->load('role');
            }

            return $this->successResponse('User logged in successfully', $user);
            
        } catch (JWTException $exception) {
            Log::error('JWT Login Error: ' . $exception->getMessage());
            return $this->errorResponse('Could not create token', 403);
        } catch (Exception $e) {
             Log::error('Authentication process failed: ' . $e->getMessage());
             return $this->errorResponse('Authentication failed', 500);
        }
    }

    public function getProfile()
    {
        try {
                $user = Auth::user();
                if (!$user) {
                    return $this->errorResponse('User not found', 404);
                }
                $user->load([
                    'submissions' => function ($query) {
                        $query->with(['form', 'payment'])->orderBy('created_at', 'desc');
                    },
                ]);
                return $this->successResponse('User profile fetched successfully', $user);
        } catch (JWTException $e) {
            Log::error("JWT Exception in getProfile: " . $e->getMessage());
            return $this->errorResponse('Token invalid or expired', 403);
        } catch (Exception $e) {
            Log::error("General Exception in getProfile: " . $e->getMessage());
            return $this->errorResponse('An unexpected error occurred.', 500);
        }
    }
    

    
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->successResponse('Logout successful');
        } catch (JWTException $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return $this->errorResponse('Logout failed', 403);
        }
    }
}
