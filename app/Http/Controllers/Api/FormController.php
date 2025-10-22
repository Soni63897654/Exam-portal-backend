<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;
use App\Traits\FormatResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;

class FormController extends Controller
{
    use FormatResponseTrait;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }
            $perPage = $request->get('per_page', 10);
            if ($user->role_id === 1) {
                $forms = Form::orderBy('created_at', 'desc')->paginate($perPage);
            } else {
                $forms = Form::where('status', 'published')
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
            }
            return $this->successResponse('Forms retrieved successfully.', $forms);

        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }
            $formId = $request->form_id;
            $form = Form::find($formId);
            if (!$form) {
                return $this->errorResponse('Form not found.', 404);
            }
            $isAuthorized = ($user->role_id === 1 || $form->status === 'published' || $form->user_id === $user->id);
            if (!$isAuthorized) {
                return $this->errorResponse('You are not authorized to view this form.', 403);
            }
            return $this->successResponse('Form details retrieved successfully.', $form);
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong: ' . $e->getMessage(), 403);
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }
        if ($request->user()->role_id !== 1) {
            return $this->errorResponse('Unauthorized action.', 403);
        }
        $request->validate([
            'title'         => 'required|string',
            'structure'     => 'required|array', 
            'fee_amount'    => 'required|numeric|min:0',
            'description'   => 'nullable|string',
            'status'        => 'in:draft,published,archived',
        ]);
        $form = Form::create([
            'title'       => $request->title,
            'structure'   => $request->structure,
            'fee_amount'  => $request->fee_amount,
            'description' => $request->description,
            'status'      => $request->status ?? 'draft',
        ]);
        return $this->successResponse('Form created successfully.', $form, 201);
    }


    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }
        if ($user->role_id !== 1) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }
        $validated = $request->validate([
            'id'         => 'required|exists:forms,id',
            'title'      => 'sometimes|required|string|max:255',
            'structure'  => 'sometimes|required|array',
            'fee_amount' => 'sometimes|required|numeric|min:0',
            'status'     => 'sometimes|required|in:draft,published,archived',
            'description'=> 'sometimes|required|string',
        ]);
        $form = Form::find($request->id);
        if (!$form) {
            return $this->errorResponse('Form not found.', 404);
        }
        $form->update($validated);
        return $this->successResponse('Form updated successfully.', $form);
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
            'id' => 'required|exists:forms,id',
        ]);
        try {
            $form = Form::find($request->id);
            if (!$form) {
                return $this->errorResponse('Form not found.', 404);
            }
            $form->delete();
            return $this->successResponse('Form deleted successfully.', $form);
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

}
