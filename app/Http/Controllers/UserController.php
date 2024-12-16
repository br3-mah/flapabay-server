<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        // Placeholder for the index method logic
    }

    public function test()
    {
        try {
            // Query to fetch all users from the wp_users table
            $users = DB::table('wp_users')->select(
                'ID',
                'user_login',
                'user_email',
                'user_registered',
                'display_name'
            )->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($user_id){
        try {
            $id = $user_id;
            // Find the user by ID from the wp_users table
            $user = DB::table('wp_users')->where('ID', $id)->first();

            // Check if user exists
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Optionally, get the user's metadata (like first_name, last_name, etc.)
            $userMeta = DB::table('wp_usermeta')
                ->where('user_id', $id)
                ->pluck('meta_value', 'meta_key');

            // Merge user data with metadata (optional)
            $userData = [
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_nicename' => $user->user_nicename,
                'user_email' => $user->user_email,
                'user_registered' => $user->user_registered,
                'display_name' => $user->display_name,
                'first_name' => $userMeta['first_name'] ?? null,
                'last_name' => $userMeta['last_name'] ?? null,
            ];

            return response()->json([
                'success' => true,
                'user' => $userData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user details and return the updated user data.
     */
    public function update(Request $request, $user_id)
    {
        try {
            // Validate request data
            $validatedData = Validator::make($request->all(), [
                'email' => 'nullable|email',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'about_me' => 'nullable|string',
                'i_live_in' => 'nullable|string',
                'paypal_email' => 'nullable|email',
                'phone' => 'required|string|max:15',
                'i_speak' => 'nullable|string',
                'website' => 'nullable|url',
                'skype_link' => 'nullable|url',
                'facebook_url' => 'nullable|url',
                'twitter_url' => 'nullable|url',
                'linkedin_url' => 'nullable|url',
                'pinterest_url' => 'nullable|url',
                'youtube_url' => 'nullable|url',
            ]);

            if ($validatedData->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validatedData->errors()
                ], 422);
            }

            // Extract validated data
            $data = $validatedData->validated();

            // Prepare data for `wp_users` table
            $userData = [];

            if (!empty($data['email'])) {
                $userData['user_email'] = $data['email'];
            }

            if (!empty($data['first_name']) && !empty($data['last_name'])) {
                $userData['user_nicename'] = strtolower($data['first_name'] . '-' . $data['last_name']);
                $userData['display_name'] = $data['first_name'] . ' ' . $data['last_name'];
            }

            // If user data exists, update the wp_users table
            if (!empty($userData)) {
                DB::table('wp_users')->where('ID', $user_id)->update($userData);
            }

            // Prepare data for `wp_usermeta` table
            $userMeta = [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'about_me' => $data['about_me'] ?? null,
                'i_live_in' => $data['i_live_in'] ?? null,
                'paypal_email' => $data['paypal_email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'i_speak' => $data['i_speak'] ?? null,
                'website' => $data['website'] ?? null,
                'skype_link' => $data['skype_link'] ?? null,
                'facebook_url' => $data['facebook_url'] ?? null,
                'twitter_url' => $data['twitter_url'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'pinterest_url' => $data['pinterest_url'] ?? null,
                'youtube_url' => $data['youtube_url'] ?? null,
            ];

            // Remove null values from the user meta data
            $userMeta = array_filter($userMeta, function ($value) {
                return !is_null($value);
            });

            // Insert or update the user meta data in `wp_usermeta`
            foreach ($userMeta as $metaKey => $metaValue) {
                // Check if the meta_key already exists for the user
                $exists = DB::table('wp_usermeta')
                    ->where('user_id', $user_id)
                    ->where('meta_key', $metaKey)
                    ->exists();

                if ($exists) {
                    // Update the existing meta key
                    DB::table('wp_usermeta')
                        ->where('user_id', $user_id)
                        ->where('meta_key', $metaKey)
                        ->update(['meta_value' => $metaValue]);
                } else {
                    // Insert new meta key
                    DB::table('wp_usermeta')->insert([
                        'user_id' => $user_id,
                        'meta_key' => $metaKey,
                        'meta_value' => $metaValue
                    ]);
                }
            }

            // Retrieve the updated user details from wp_users
            $user = DB::table('wp_users')->where('ID', $user_id)->first();

            // Retrieve the updated user meta details from wp_usermeta
            $userMeta = DB::table('wp_usermeta')
                ->where('user_id', $user_id)
                ->pluck('meta_value', 'meta_key');

            $userDetails = [
                'ID' => $user->ID,
                'user_email' => $user->user_email,
                'user_nicename' => $user->user_nicename,
                'display_name' => $user->display_name,
                'meta' => $userMeta
            ];

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $userDetails
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function updateProfilePicture(Request $request, $user_id)
    {
        try {
            // Step 1: Validate the incoming request
            $validatedData = Validator::make($request->all(), [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
            ]);

            // Check if validation fails
            if ($validatedData->fails()) {
                return response()->json(['error' => $validatedData->errors()], 400);
            }

            // Step 2: Retrieve the uploaded file
            $file = $request->file('profile_picture');

            // Step 3: Generate a unique filename
            $fileName = uniqid('profile_', true) . '.' . $file->getClientOriginalExtension();

            // Step 4: Store the file using the 'public' disk and profile-pictures directory
            $filePath = $file->storeAs('profile-pictures', $fileName, 'public');

            // Step 5: Remove 'public/' from the path to use it as a URL
            $publicPath = str_replace('public/', '', $filePath);

            // Step 6: Update or Insert custom_picture in the usermeta table
            $metaKey = 'custom_picture';
            $metaKey2 = 'small_custom_picture';

            // Check if the custom_picture meta_key exists
            $existingMeta = DB::table('wp_usermeta')
                ->where('user_id', $user_id)
                ->where('meta_key', $metaKey)
                ->first();

            if ($existingMeta) {
                // Update existing custom_picture
                DB::table('wp_usermeta')
                    ->where('user_id', $user_id)
                    ->where('meta_key', $metaKey)
                    ->update(['meta_value' => $publicPath]);
                DB::table('wp_usermeta')
                    ->where('user_id', $user_id)
                    ->where('meta_key', $metaKey2)
                    ->update(['meta_value' => $publicPath]);
            } else {
                // Insert new custom_picture meta_key
                DB::table('wp_usermeta')->insert([
                    'user_id' => $user_id,
                    'meta_key' => $metaKey,
                    'meta_value' => $publicPath
                ]);
                DB::table('wp_usermeta')->insert([
                    'user_id' => $user_id,
                    'meta_key' => $metaKey2,
                    'meta_value' => $publicPath
                ]);
            }

            // Step 7: Return the uploaded file's path in the response
            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'file_path' => asset('storage/' . $publicPath), // URL to the image
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}

