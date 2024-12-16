<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    public function register(Request $request)
    {

        try {
            // Generate a hashed WordPress password
            $hashedPassword = password_hash($request->input('password'), PASSWORD_BCRYPT);

            // Insert user into wp_users table
            $userId = DB::table('wp_users')->insertGetId([
                'user_login' => $request->input('email'),
                'user_pass' => $hashedPassword,
                'user_nicename' => strtolower($request->input('fname') . '-' . $request->input('lname')),
                'user_email' => $request->input('email'),
                'user_registered' => Carbon::now(),
                'display_name' => $request->input('fname') . ' ' . $request->input('lname'),
                'user_status' => 1,
            ]);

            // Insert user metadata into wp_usermeta table
            DB::table('wp_usermeta')->insert([
                [
                    'user_id' => $userId,
                    'meta_key' => 'first_name',
                    'meta_value' => $request->input('fname')
                ],
                [
                    'user_id' => $userId,
                    'meta_key' => 'last_name',
                    'meta_value' => $request->input('lname')
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user_id' => $userId
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            // Validate request inputs
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            // Get user by email
            $user = DB::table('wp_users')->where('user_email', $request->input('email'))->first();

            // Check if user exists
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            // Verify the password
            if (!password_verify($request->input('password'), $user->user_pass)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            // Generate an access token (simple token for now, use JWT in production)
            $token = base64_encode(bin2hex(random_bytes(40)));

            // Optionally, store the token in the database (if token-based auth is required)
            DB::table('wp_usermeta')->insert([
                'user_id' => $user->ID,
                'meta_key' => 'auth_token',
                'meta_value' => $token
            ]);
            // Retrieve the updated user meta details from wp_usermeta
            $userMeta = DB::table('wp_usermeta')
                ->where('user_id', $user->ID)
                ->pluck('meta_value', 'meta_key');

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'meta' => $userMeta
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log in',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Handle the generation and sending of an OTP.
     */
    public function getOtp(Request $request)
    {
        // Step 1: Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|string', // 'contact' can be phone number or email
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Step 2: Generate the OTP
        $otp = rand(100000, 999999); // Generate a 6-digit OTP

        // Step 3: Store the OTP securely with an expiration time
        $expiresAt = Carbon::now()->addMinutes(5); // Set expiration time to 5 minutes
        Cache::put('otp_' . $request->contact, $otp, $expiresAt);

        // Step 4: Send the OTP
        // Example for sending via email
        // Mail::to($request->contact)->send(new OtpMail($otp));

        // Example for sending via SMS (using Twilio)
        /*
        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        $twilio->messages->create($request->contact, [
            'from' => env('TWILIO_FROM'),
            'body' => "Your OTP is: $otp"
        ]);
        */

        // Step 5: Return a response
        return response()->json(['message' => 'OTP sent successfully!'], 200);
    }



    /**
     * Handle forgot password functionality.
     */
    public function forgotPassword(Request $request)
    {
        // Step 1: Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $email = $request->input('email');

        // Step 2: Check if email exists in wp_users
        $user = DB::table('wp_users')->where('user_email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'Email not found'], 404);
        }

        // Step 3: Generate a unique token
        $token = Str::random(60);

        // Step 4: Store the token (consider using a password_resets table)
        // DB::table('password_resets')->insert([
        //     'email' => $email,
        //     'token' => $token,
        //     'created_at' => Carbon::now(),
        // ]);

        // Step 5: Send reset email
        $resetLink = url('/api/auth/reset-password?token=' . $token . '&email=' . urlencode($email));

        // Mail::send('emails.reset', ['link' => $resetLink], function ($message) use ($email) {
        //     $message->to($email);
        //     $message->subject('Password Reset Request');
        // });

        // Step 6: Return response
        return response()->json(['message' => 'Reset email sent successfully'], 200);
    }


    /**
     * Handle the password reset process.
     */
    public function resetPassword(Request $request)
    {
        // Step 1: Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string',
            'new_password' => 'required|string|min:8', // Ensure minimum length for security
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $email = $request->input('email');
        $otp = $request->input('otp');
        $newPassword = $request->input('new_password');

        // Step 2: Verify the OTP
        // Assuming OTPs are stored in cache with a key like 'otp_<email>'
        if (Cache::get('otp_' . $email) !== $otp) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }

        // Step 3: Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Step 4: Update the password in wp_users table
        try {
            DB::table('wp_users')->where('user_email', $email)->update([
                'user_pass' => $hashedPassword,
                'user_registered' => Carbon::now(), // Optional: Update registration time if needed
            ]);

            // Optionally, you can remove the OTP from cache after successful reset
            Cache::forget('otp_' . $email);

            // Step 5: Return a response
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

/**
     * Logout the user according to WordPress in this Lumen app.
     */
    public function logout(Request $request)
    {
        try {
            // Validate that the token is provided in the request
            $this->validate($request, [
                'token' => 'required'
            ]);

            // Find the user using the provided token
            $userMeta = DB::table('wp_usermeta')
                ->where('meta_key', 'auth_token')
                ->where('meta_value', $request->input('token'))
                ->first();

            if (!$userMeta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ], 401);
            }

            // Delete the token to log the user out
            DB::table('wp_usermeta')
                ->where('meta_key', 'auth_token')
                ->where('meta_value', $request->input('token'))
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'User successfully logged out'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log out',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
