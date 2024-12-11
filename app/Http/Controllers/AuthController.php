<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->ID,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'first_name' => DB::table('wp_usermeta')->where('user_id', $user->ID)->where('meta_key', 'first_name')->value('meta_value'),
                    'last_name' => DB::table('wp_usermeta')->where('user_id', $user->ID)->where('meta_key', 'last_name')->value('meta_value'),
                ]
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

    }

    /**
     * Handle the password reset process.
     */
    public function resetPassword(Request $request)
    {

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
